<?php
header('Content-Type: application/json; charset=utf-8');

// 1. データベース接続
$conn_str = "host=localhost port=5432 dbname=group1 user=postgres password=postgres";
$dbconn = pg_connect($conn_str);
if (!$dbconn) {
    echo json_encode(["status" => "error", "message" => "DB接続失敗"]);
    exit;
}

// 2. 受信データの取得
$json_raw = file_get_contents('php://input');
// デバッグ用：届いた生データをファイルに保存
file_put_contents(__DIR__ . '/debug.txt', "Time: " . date('Y-m-d H:i:s') . "\nData: " . $json_raw);
$data = json_decode($json_raw, true);
if (!$data) {
    echo json_encode(["status" => "error", "message" => "JSONが空です!!!"]);
    exit;
}

// 3. 分岐処理
$response = [];
if ($data['type'] === 'Arduino') {

    if (isset($data['datetime'],$data['room_num'],$data['temp'],$data['humid'],$data['press'],$data['light'])){
        // SQL文: env_recordテーブルにデータを挿入
        $sql = "INSERT INTO env_record (datetime, room_num, temperature, humidity, pressure) VALUES ($1, $2, $3, $4, $5)";
        $result = pg_query_params($dbconn, $sql, array($data['datetime'],$data['room_num'],$data['temp'],$data['humid'],$data['press']));
        
        $sql = "UPDATE room_state SET lit = $1 WHERE room_num = $2";
        $result = pg_query_params($dbconn, $sql, array($data['light'],$data['room_num']));

    } elseif (isset($data['datetime'], $data['room_num'], $data['level'])){
        $sql = "INSERT INTO quake_record (datetime, room_num, level) VALUES ($1, $2, $3)";
        $result = pg_query_params($dbconn, $sql, array($data['datetime'],$data['room_num'],$data['level']));
    } else {
        $response['response'] = false;
        $response['message'] = "incorrect data(Arduino)";
    }

} elseif ($data['type'] === 'Android') {

    if (isset($data['user_id'],$data['kamei_id'],$data['name'],$data['password'])){ // 新規作成
        $sql = "SELECT user_id FROM account WHERE user_id = $1";
        $result = pg_query_params($dbconn, $sql, array($data['user_id']));
        if (pg_num_rows($result) > 0) {
            $response['submit'] = false;
        } else {
            $sql = "INSERT INTO account (user_id, kamei_id, name, password) VALUES ($1, $2, $3, $4)";
            $result = pg_query_params($dbconn, $sql, array($data['user_id'],$data['kamei_id'],$data['name'],$data['password']));
            $response['submit'] = true;
        }
    } elseif (isset($data['user_id'],$data['password'])){ //ログイン
        $sql = "SELECT user_id, password FROM account WHERE user_id = $1 AND password = $2";
        $result = pg_query_params($dbconn, $sql, array($data['user_id'],$data['password']));
        $user = pg_fetch_assoc($result);
        if (pg_num_rows($result) > 0) {       
            $response['login'] = true;
            $response['info'] = $user;
        } else {
            $response['login'] = false;
        }
    } elseif (isset($data['user_id'],$data['subject_id'],$data['time'],$data['status'],$data['room_num'])){
        /* 定数定義 */
        $attend = 1; //出席
        $late = 2; //遅刻
        $leave_early = 3; //早退
        $leave = 4; //退席
        if ($data['status'] >= $leave_early){
            if ($data['status'] == $leave_early){
                $sql = "UPDATE stu_record SET checkout = $1, status = $4
                    WHERE user_id = $2 and subject_id = $3 and checkout is null";
                $result = pg_query_params($dbconn, $sql, array($data['time'],$data['user_id'],$data['subject_id'],$data['status']));
            } elseif ($data == $leave) {
                $sql = "UPDATE stu_record SET checkout = $1 
                    WHERE user_id = $2 and subject_id = $3 and checkout is null";
                $result = pg_query_params($dbconn, $sql, array($data['time'],$data['user_id'],$data['subject_id']));
            }
            if (pg_affected_rows($result)) {
                $response['response'] = true;
            } else {
                $response['response'] = false;
            }
        } elseif ($data['status'] >= $attend) { // Androidのバックキーでcheckoutもできるかも?
            $sql = "INSERT INTO stu_record (user_id, subject_id, checkin, status)
                    SELECT $1, $2, $3, $4
                    WHERE NOT EXISTS (
                        SELECT 1 FROM stu_record 
                        WHERE user_id = $1 AND checkout IS NULL
                    )";
            $result = pg_query_params($dbconn, $sql, array($data['user_id'], $data['subject_id'], $data['time'], $data['status']));
            if (pg_affected_rows($result)) {
                $response['response'] = true;
            } else {
                $response['response'] = false;
            }
        }
    }

} else {
    $response['response'] = false;
    $response['message'] = "incorrect data(all)";
}

echo json_encode($response);