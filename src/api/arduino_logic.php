<?php

/*
 * Arduinoからのリクエストを判定・振り分けるメインエントリポイント
 */
function handleArduinoRequest($dbconn, $data) {
    if (isEnvironmentRequest($data)) {
        return processEnvironmentData($dbconn, $data);
    }
    if (isQuakeRequest($data)) {
        return processQuakeData($dbconn, $data);
    }

    return ["status" => "error", "message" => "incorrect Arduino data"];
}

/** 判定関数：環境データ（温度・湿度・気圧・照明）か */
function isEnvironmentRequest($data) {
    return isset($data['datetime'], $data['room'], $data['temp'], $data['humid'], $data['press'], $data['light']);
}

/** 判定関数：振動（地震）データか */
function isQuakeRequest($data) {
    return isset($data['datetime'], $data['room'], $data['quake']);
}

/** 処理関数：環境データの保存 */
function processEnvironmentData($dbconn, $data) {
    // 1. room_state の照明状態を更新
    pg_query_params($dbconn, 
        "UPDATE room_state SET lit = $1 WHERE room_num = $2", 
        [$data['light'], $data['room']]
    );

    // 2. env_record への環境ログ挿入
    pg_query_params($dbconn, 
        "INSERT INTO env_record (datetime, room_num, temperature, humidity, pressure) VALUES ($1, $2, $3, $4, $5)", 
        [$data['datetime'], $data['room'], $data['temp'], $data['humid'], $data['press']]
    );

    return ["status" => "success", "type" => "environment"];
}

/** 処理関数：振動データの保存 */
function processQuakeData($dbconn, $data) {
    pg_query_params($dbconn, 
        "INSERT INTO quake_record (datetime, room_num, level) VALUES ($1, $2, $3)", 
        [$data['datetime'], $data['room'], $data['quake']]
    );

    return ["status" => "success", "type" => "quake"];
}