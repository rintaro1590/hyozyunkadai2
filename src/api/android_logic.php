<?php

/**
 * Android端末からのリクエストを振り分けるメインエントリポイント
 */
function handleAndroidRequest($dbconn, $data) {
    if (isSignupRequest($data)) {
        return registerUser($dbconn, $data);
    }
    if (isLoginRequest($data)) {
        return authenticateUser($dbconn, $data);
    }
    if (isAttendanceRequest($data)) {
        return processAttendance($dbconn, $data);
    }
    return ["status" => "error", "message" => "incorrect Android data"];
}

function isSignupRequest($data) {
    return isset($data['user_id'], $data['kamei_id'], $data['name'], $data['password']);
}

function isLoginRequest($data) {
    return isset($data['user_id'], $data['password']) && !isset($data['name']);
}

function isAttendanceRequest($data) {
    return isset($data['user_id'], $data['subject_id'], $data['datetime'], $data['status'], $data['room']);
}

function registerUser($dbconn, $data) {
    $sqlCheck = "SELECT user_id FROM account WHERE user_id = $1";
    $check = pg_query_params($dbconn, $sqlCheck, [$data['user_id']]);
    if (pg_num_rows($check) > 0) {
        return ["submit" => false];
    }
    $sqlInsert = "INSERT INTO account (user_id, kamei_id, name, password) VALUES ($1, $2, $3, $4)";
    pg_query_params($dbconn, $sqlInsert, [$data['user_id'], $data['kamei_id'], $data['name'], $data['password']]);
    return ["submit" => true, "user_id" => $data['user_id'], "password" => $data['password']];
}

function authenticateUser($dbconn, $data) {
    $sql = "SELECT a.user_id, a.teacher, s.subject_id, s.subject_mei 
            FROM account a 
            LEFT JOIN subject s ON a.user_id = s.user_id
            WHERE a.user_id = $1 AND a.password = $2";
    $result = pg_query_params($dbconn, $sql, [$data['user_id'], $data['password']]);
    $rows = pg_fetch_all($result);
    if (!$rows) {
        return ["login" => false];
    }
    $subjects = [];
    foreach ($rows as $row) {
        if ($row['subject_id'] !== null) {
            $subjects[] = ["subject_id" => (int)$row['subject_id'], "subject_mei" => $row['subject_mei']];
        }
    }
    return [
        "login"    => true, 
        "user_id"  => (int)$rows[0]['user_id'], 
        "teacher"  => ($rows[0]['teacher'] === 't'),
        "subjects" => $subjects
    ];
}

function processAttendance($dbconn, $data) {
    $status = (int)$data['status'];
    $isCheckout = ($status >= 3);
    if ($isCheckout) {
        $result = handleCheckout($dbconn, $data, $status);
    } else {
        $result = handleCheckin($dbconn, $data, $status);
    }
    if ($result['response'] > 0) {
        updateRoomCount($dbconn, $data['room'], $isCheckout ? -1 : 1);
    }
    return $result;
}

function handleCheckout($dbconn, $data, $status) {
    // date_truncで秒を00に丸める
    $sql = "UPDATE stu_record SET checkout = date_trunc('minute', CAST($1 AS TIMESTAMP)) " . 
           ($status == 3 ? ", status = $5 " : "") . 
           "WHERE user_id = $2 AND subject_id = $3 AND room_num = $4 AND checkout IS NULL";
    $params = ($status == 3) 
        ? [$data['datetime'], $data['user_id'], $data['subject_id'], $data['room'], $status] 
        : [$data['datetime'], $data['user_id'], $data['subject_id'], $data['room']];
    $res = pg_query_params($dbconn, $sql, $params);
    return ["response" => (bool) pg_affected_rows($res)];
}

function handleCheckin($dbconn, $data, $status) {
    // date_truncで秒を00に丸める
    $sql = "INSERT INTO stu_record (user_id, subject_id, room_num, checkin, status)
            SELECT $1, $2, $3, date_trunc('minute', CAST($4 AS TIMESTAMP)), $5 WHERE NOT EXISTS (
                SELECT 1 FROM stu_record WHERE user_id = $1 AND checkout IS NULL
            )";
    $res = pg_query_params($dbconn, $sql, [
        $data['user_id'], $data['subject_id'], $data['room'], $data['datetime'], $status
    ]);
    return ["response" => (bool) pg_affected_rows($res)];
}

function updateRoomCount($dbconn, $room_num, $change) {
    if ($change > 0) {
        $sql = "UPDATE room_state SET human_cnt = human_cnt + 1 WHERE room_num = $1";
    } else {
        $sql = "UPDATE room_state SET human_cnt = human_cnt - 1 WHERE room_num = $1 AND human_cnt > 0";
    }
    return pg_query_params($dbconn, $sql, [$room_num]);
}