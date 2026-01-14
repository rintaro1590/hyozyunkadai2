<?php
function handleAndroidRequest($dbconn, $data) {
    // 1. 新規作成
    if (isset($data['user_id'], $data['kamei_id'], $data['name'], $data['password'])) {
        $check = pg_query_params($dbconn, "SELECT user_id FROM account WHERE user_id = $1", [$data['user_id']]);
        if (pg_num_rows($check) > 0) return ["submit" => false, "message" => "exists"];
        
        pg_query_params($dbconn, "INSERT INTO account (user_id, kamei_id, name, password) VALUES ($1, $2, $3, $4)", 
            [$data['user_id'], $data['kamei_id'], $data['name'], $data['password']]);
        return ["submit" => true];
    }

    // 2. ログイン
    if (isset($data['user_id'], $data['password'])) {
        $sql = "SELECT a.user_id,a.teacher,s.subject_id,s.subject_mei FROM account a LEFT JOIN subject s ON a.user_id = s.user_id
                WHERE a.user_id = $1 AND a.password = $2";
        $result = pg_query_params($dbconn, $sql,[$data['user_id'], $data['password']]);
        $rows = pg_fetch_all($result);
        if ($rows) {
            $subjects = [];
            foreach ($rows as $row) {
                if ($row['subject_id'] !== null) {
                    $subjects[] = [
                        "subject_id"  => (int)$row['subject_id'],
                        "subject_mei" => $row['subject_mei']
                    ];
                }
            }

            return ["login" => true, "user_id" => (int)$rows[0]['user_id'], 
                    "teacher" => ($rows[0]['teacher'] === 't'),
                    "subjects" => $subjects];
        } else {
            return ["login" => false];
        }
    }

    // 3. 出席管理 (出席・遅刻・早退・退席)
    if (isset($data['user_id'], $data['subject_id'], $data['time'], $data['status'], $data['room'])) {
        return processAttendance($dbconn, $data);
    }

    return ["status" => "error", "message" => "incorrect Android data"];
}

// 出席・退席の細かいサブロジック
function processAttendance($dbconn, $data) {
    $status = (int)$data['status'];
    
    // 退席系 (status 3:早退, 4:退席)
    if ($status >= 3) {
        $sql = "UPDATE stu_record SET checkout = $1 " . ($status == 3 ? ", status = $4 " : "") . 
               "WHERE user_id = $2 AND subject_id = $3 AND checkout IS NULL";
        $params = ($status == 3) ? [$data['time'], $data['user_id'], $data['subject_id'], $status] 
                                 : [$data['time'], $data['user_id'], $data['subject_id']];
        $res = pg_query_params($dbconn, $sql, $params);
        if($res){
            $sql = "UPDATE room_state SET human_cnt = human_cnt - 1 WHERE room_num = $1 AND human_cnt > 0";
            pg_query_params($dbconn, $sql, array($data['room']));
        }
        return ["response" => (bool)pg_affected_rows($res)];
    } 
    // 出席系 (status 1:出席, 2:遅刻)
    else {
        $sql = "INSERT INTO stu_record (user_id, subject_id, checkin, status)
                SELECT $1, $2, $3, $4 WHERE NOT EXISTS (
                    SELECT 1 FROM stu_record WHERE user_id = $1 AND checkout IS NULL
                )";
        $res = pg_query_params($dbconn, $sql, [$data['user_id'], $data['subject_id'], $data['time'], $status]);
        if($res){
            $sql = "UPDATE room_state SET human_cnt = human_cnt + 1 WHERE room_num = $1";
            pg_query_params($dbconn, $sql, array($data['room']));
        }
        return ["response" => (bool)pg_affected_rows($res)];
    }
}