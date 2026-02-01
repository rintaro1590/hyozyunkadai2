<?php

/**
 * 初期設定・データ取得（In）リクエストを振り分けるメインエントリポイント
 */
function handleInRequest($dbconn, $data) {
    // 1. 学校（kamei）一覧取得
    if (isKameiListRequest($data)) {
        return fetchKameiList($dbconn);
    }
    
    // 2. ユーザーIDの重複・使用状況チェック
    if (isUserIdCheckRequest($data)) {
        return fetchUserIdStatus($dbconn, $data);
    }
    
    // 3. ユーザー名取得
    if (isUserNameRequest($data)) {
        return fetchUserName($dbconn, $data);
    }

    // エラー時も response キーを使用
    return ["response" => false, "message" => "incorrect In data"];
}

/* --- 判定関数群 --- */

function isKameiListRequest($data) {
    return isset($data['data']) && $data['data'] === 'kamei';
}

function isUserIdCheckRequest($data) {
    return isset($data['data'], $data['user_id_min']) && $data['data'] === 'user_nums';
}

function isUserNameRequest($data) {
    return isset($data['data'], $data['user_id']) && $data['data'] === 'user_name';
}

/* --- 処理関数群 --- */

/** 学校一覧を取得 */
function fetchKameiList($dbconn) {
    $result = pg_query($dbconn, "SELECT kamei_id, kamei_mei FROM kamei");
    $kamei = [];
    
    if ($result) {
        $rows = pg_fetch_all($result);
        if ($rows) {
            foreach ($rows as $row) {
                $kamei[] = [
                    'kamei_id' => $row['kamei_id'],
                    'kamei_mei' => $row['kamei_mei']
                ];
            }
        }
    }
    // status を response に変更
    return ["response" => true, "kamei" => $kamei];
}

/** 指定範囲のユーザーID使用状況を取得 */
function fetchUserIdStatus($dbconn, $data) {
    $user_id_min = $data['user_id_min'];
    $user_id_max = $user_id_min + 100;
    
    $sql = "SELECT user_id FROM account WHERE user_id > $1 AND user_id < $2 ORDER BY user_id";
    $result = pg_query_params($dbconn, $sql, [$user_id_min, $user_id_max]);
        
    $numbers = [];
    if ($result) {
        $rows = pg_fetch_all($result);
        if ($rows) {
            foreach ($rows as $row) {
                $numbers[] = $row['user_id'] - $user_id_min;
            }
        }
    }
    // status を response に変更
    return ["response" => true, "numbers" => $numbers];
}

/** ユーザーIDから名前を取得 */
function fetchUserName($dbconn, $data) {
    $sql = "SELECT name FROM account WHERE user_id = $1";
    $result = pg_query_params($dbconn, $sql, [$data['user_id']]);
        
    if ($result) {
        $row = pg_fetch_assoc($result);
        if ($row) {
            // status を response に変更
            return ["response" => true, "username" => $row['name']];
        }
    }
    return ["response" => false];
}