<?php
header('Content-Type: application/json; charset=utf-8');

// 1. 接続文字列（ホスト、ポート、DB名、ユーザー、パスワード）
$conn_str = "host=localhost port=5432 dbname=group1 user=postgres password=postgres";
$dbconn = pg_connect($conn_str);

if (!$dbconn) {
    echo json_encode(["status" => "error", "message" => "DB接続失敗"]);
    echo "接続エラー詳細: " . pg_last_error();
    exit;
}

// 2. 受信データの取得
$json_raw = file_get_contents('php://input');
// デバッグ用：届いた生データをファイルに保存してみる
file_put_contents(__DIR__ . '/debug.txt', "Time: " . date('Y-m-d H:i:s') . "\nData: " . $json_raw);
$data = json_decode($json_raw, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "JSONが空です!!!"]);
    exit;
}

$response = [];

// --- 3. 分岐処理 (単純化バージョン) ---

// JSONの中に 'level' が含まれているかだけをチェックする
if ($data['type'] === 'Arduino') {
    // Arduinoからの振動データ処理
    // データを取り出す（datetimeがなければ現在のサーバー時間を入れる）
    $datetime = isset($data['datetime']) ? $data['datetime'] : date('Y-m-d H:i:s');
    $level    = $data['level'];

    // SQL文: quake_recordテーブルにデータを挿入
    $sql = "INSERT INTO quake_record (datetime, level) VALUES ($1, $2)";
    $result = pg_query_params($dbconn, $sql, array($datetime, $level));

    if ($result) {
        echo json_encode(["status" => "success", "message" => "振動データを保存しました"]);
    } else {
        echo json_encode(["status" => "error", "message" => "SQL失敗: " . pg_last_error($dbconn)]);
    }

    // 処理が終わったら終了
    pg_close($dbconn);
    exit;
}

// もし上のif文に当てはまらない（levelがない）場合の予備
echo json_encode(["status" => "error", "message" => "levelデータが見つかりません"]);