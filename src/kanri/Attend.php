<?php
// 1. DB接続設定
require_once './api/db_config.php';
$dbconn = getDbConnection();

if (!$dbconn) {
    exit('データベース接続失敗。');
}

// 2. データの受け取り
$user_name = isset($_POST['user_name']) ? $_POST['user_name'] : '名前未取得';
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$selected_date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
$day_end_val = $selected_date . ' 23:59:59';

// 3. SQLの実行 (出席記録と滞在時間の算出)
$sql = "
WITH all_student_classes AS (
    SELECT DISTINCT
        pm.period_id,
        s.subject_mei,
        s.subject_id
    FROM period_master pm
    JOIN stu_record r ON 
        (r.checkout IS NOT NULL AND CAST(r.checkin AS TIME) < pm.end_time AND CAST(r.checkout AS TIME) > pm.start_time)
        OR
        (r.checkout IS NULL AND CAST(r.checkin AS TIME) < pm.end_time AND CAST(r.checkin AS TIME) >= pm.start_time - INTERVAL '90 minutes' AND CAST(r.checkin AS TIME) < pm.end_time)
    JOIN subject s ON r.subject_id = s.subject_id
    WHERE CAST(r.checkin AS DATE) = $1
      AND LEFT(CAST(r.user_id AS TEXT), 2) = LEFT(CAST($3 AS TEXT), 2)
      AND s.subject_id >= (CAST(SUBSTR(CAST($3 AS TEXT), 3, 1) AS INTEGER) * 100)
      AND s.subject_id < ((CAST(SUBSTR(CAST($3 AS TEXT), 3, 1) AS INTEGER) + 1) * 100)
),
user_personal_record AS (
    SELECT
        pm.period_id,
        r.checkin,
        r.checkout,
        r.status as original_status,
        /* 出席時間の計算ロジック修正 */
        GREATEST(
            0,
            EXTRACT(EPOCH FROM (
                /* 終了時刻の判定：早退(3)なら実時刻、それ以外(1,2)なら授業枠の終了時刻(end_time) */
                CASE 
                    WHEN r.status = 3 THEN LEAST(pm.end_time, CAST(COALESCE(r.checkout, CURRENT_TIMESTAMP) AS TIME))
                    ELSE pm.end_time
                END 
                - 
                /* 開始時刻の判定：遅刻(2)なら実時刻、それ以外(1,3)なら授業枠の開始時刻(start_time) */
                CASE 
                    WHEN r.status = 2 THEN GREATEST(pm.start_time, CAST(r.checkin AS TIME))
                    ELSE pm.start_time
                END
            )) / 60
        ) as stay_minutes_in_period,
        CASE 
            WHEN r.status = 3 AND pm.period_id = MAX(pm.period_id) OVER(PARTITION BY r.user_id, r.checkin) THEN 3
            WHEN r.status != 3 AND pm.period_id = MIN(pm.period_id) OVER(PARTITION BY r.user_id, r.checkin) THEN r.status
            ELSE 1 
        END as status,
        CASE WHEN r.checkout IS NULL THEN 1 ELSE 0 END as is_current_stay,
        EXTRACT(EPOCH FROM (CAST(r.checkin AS TIME) - pm.start_time)) / 60 as lateness_min,
        EXTRACT(EPOCH FROM (pm.end_time - CAST(r.checkout AS TIME))) / 60 as early_leave_min,
        MIN(pm.period_id) OVER(PARTITION BY r.user_id, r.checkin) as first_p,
        MAX(pm.period_id) OVER(PARTITION BY r.user_id, r.checkin) as last_p
    FROM period_master pm
    JOIN stu_record r ON 
        CAST(r.checkin AS TIME) < pm.end_time 
        AND CAST(COALESCE(r.checkout, $2) AS TIME) > pm.start_time
    WHERE CAST(r.checkin AS DATE) = $1
      AND r.user_id = CAST($3 AS INTEGER)
)
SELECT
    pm.period_id,
    COALESCE(asc_table.subject_mei, '-') as subject_mei,
    CASE
        WHEN upr.period_id = upr.first_p THEN SUBSTR(CAST(CAST(upr.checkin AS TIME) AS text), 1, 5)
        ELSE '-'
    END as start_time_disp,
    CASE
        WHEN upr.period_id = upr.last_p THEN 
            CASE 
                WHEN upr.checkout IS NULL THEN '在室中'
                ELSE SUBSTR(CAST(CAST(upr.checkout AS TIME) AS text), 1, 5)
            END
        ELSE '-'
    END as end_time_disp,
    upr.status,
    upr.is_current_stay,
    upr.lateness_min,
    upr.early_leave_min,
    upr.stay_minutes_in_period
FROM period_master pm
LEFT JOIN all_student_classes asc_table ON pm.period_id = asc_table.period_id
LEFT JOIN user_personal_record upr ON pm.period_id = upr.period_id
WHERE pm.period_id BETWEEN 1 AND 4
ORDER BY pm.period_id;
";

$params = [$selected_date, $day_end_val, $user_id];
$result = pg_query_params($dbconn, $sql, $params);
$results = pg_fetch_all($result) ?: [];

// 4. 成績一覧の計算ロジック（累積）
$sql_grades = "
WITH actual_stay AS (
    SELECT 
        r.subject_id,
        SUM(
            GREATEST(
                0,
                EXTRACT(EPOCH FROM (
                    CASE 
                        WHEN r.status = 3 THEN LEAST(pm.end_time, CAST(COALESCE(r.checkout, CURRENT_TIMESTAMP) AS TIME))
                        ELSE pm.end_time
                    END 
                    - 
                    CASE 
                        WHEN r.status = 2 THEN GREATEST(pm.start_time, CAST(r.checkin AS TIME))
                        ELSE pm.start_time
                    END
                )) / 60
            )
        ) as total_stay_minutes
    FROM stu_record r
    JOIN period_master pm ON 
        CAST(r.checkin AS TIME) < pm.end_time 
        AND CAST(COALESCE(r.checkout, r.checkin + interval '10 hours') AS TIME) > pm.start_time
    WHERE r.user_id = CAST($1 AS INTEGER)
    GROUP BY r.subject_id
)
SELECT 
    s.subject_mei,
    COALESCE(SUM(a.total_stay_minutes), 0) as total_minutes,
    CASE 
        WHEN EXTRACT(EPOCH FROM s.basetime) > 0 THEN
            LEAST(100, ROUND((COALESCE(SUM(a.total_stay_minutes), 0) / (EXTRACT(EPOCH FROM s.basetime) / 60)) * 100))
        ELSE 0 
    END as attendance_rate
FROM subject s
LEFT JOIN actual_stay a ON s.subject_id = a.subject_id
WHERE s.subject_id >= (CAST(SUBSTR(CAST($1 AS TEXT), 3, 1) AS INTEGER) * 100)
  AND s.subject_id < ((CAST(SUBSTR(CAST($1 AS TEXT), 3, 1) AS INTEGER) + 1) * 100)
GROUP BY s.subject_id, s.subject_mei, s.basetime
ORDER BY s.subject_id;
";

$res_grades = pg_query_params($dbconn, $sql_grades, [$user_id]);
$grades = pg_fetch_all($res_grades) ?: [];

function formatDiffTime($total_minutes)
{
    $total_minutes = max(0, (int)$total_minutes);
    if ($total_minutes === 0) return "";
    $hours = floor($total_minutes / 60);
    $minutes = $total_minutes % 60;
    return ($hours > 0 ? $hours . "時間" : "") . ($minutes > 0 ? $minutes . "分" : "");
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="attend.css">
    <title>出席詳細</title>
</head>
<body>
    <div class='sita-container'>
        <div class='tabs'>
            <div class='tab'><a href='home.php'>ホーム</a></div>
            <div class='tab2'><a href='detail.php'>詳細</a></div>
            <div class='tab3'>出席</div>
            <div class='tab-right'></div>
        </div>

        <div class="choice">
            <div class="content-wrapper">
                <h2 style="text-align: center; margin-bottom: 20px;"><?php echo htmlspecialchars($user_name); ?></h2>

                <h3 class="section-title">出席表</h3>
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th colspan="5" style="text-align: right; background: #fff; border-bottom: none; padding: 5px;">
                                <label style="font-size: 14px;">表示日：</label>
                                <input type="date" value="<?php echo htmlspecialchars($selected_date); ?>" onchange="postDateUpdate(this.value)">
                            </th>
                        </tr>
                        <tr>
                            <th>時限</th><th>授業名</th><th>入室時刻</th><th>退出時刻</th><th>状況</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['period_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['subject_mei']); ?></td>
                                <td><?php echo htmlspecialchars($row['start_time_disp']); ?></td>
                                <td><?php echo htmlspecialchars($row['end_time_disp']); ?></td>
                                <td>
                                    <?php
                                    if ($row['status'] !== null) {
                                        if ($row['status'] == 3) {
                                            echo formatDiffTime($row['early_leave_min']) . "早退";
                                        } elseif ($row['status'] == 2) {
                                            echo formatDiffTime($row['lateness_min']) . "遅刻";
                                        } elseif ($row['status'] == 1) {
                                            if ($row['is_current_stay'] == 1 && $row['start_time_disp'] === '-') {
                                                echo "-";
                                            } else {
                                                echo "出席";
                                            }
                                        }
                                    } else {
                                        echo ($row['subject_mei'] !== '-') ? '<span style="color:red;">欠席</span>' : '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="grade-section">
                    <h3 class="section-title">成績表</h3>
                    <div class="grade-table-wrapper">
                        <table class="grade-table">
                            <thead>
                                <tr>
                                    <th>授業名</th>
                                    <th>合計出席時間</th>
                                    <th>出席率(%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $g): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($g['subject_mei']); ?></td>
                                        <td><?php echo formatDiffTime($g['total_minutes']); ?></td>
                                        <td><?php echo (int)$g['attendance_rate']; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="button-area">
                    <button class="search-btn" onclick="location.href='search.php'">戻る</button>
                </div>
            </div>
        </div>
    </div>

    <form id="refresh-form" method="POST" action="attend.php" style="display:none;">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
        <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($user_name); ?>">
        <input type="hidden" name="date" id="hidden-date">
    </form>

    <script>
        function postDateUpdate(selectedDate) {
            document.getElementById('hidden-date').value = selectedDate;
            document.getElementById('refresh-form').submit();
        }
    </script>
</body>
</html>