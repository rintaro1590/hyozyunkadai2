<?php
function handleArduinoRequest($dbconn, $data) {
    // 環境データ + 照明状態
    if (isset($data['datetime'], $data['room'], $data['temp'], $data['humid'], $data['press'], $data['light'])) {
        // room_state の更新
        $result = pg_query_params($dbconn, "UPDATE room_state SET lit = $1 WHERE room_num = $2", [$data['light'], $data['room']]);
        // env_record への挿入
        $res = pg_query_params($dbconn, 
            "INSERT INTO env_record (datetime, room_num, temperature, humidity, pressure) VALUES ($1, $2, $3, $4, $5)", 
            [$data['datetime'], $data['room'], $data['temp'], $data['humid'], $data['press']]
        );
        return ["status" => "success", "type" => "environment"];

    } 
    // 振動データ
    elseif (isset($data['datetime'], $data['room'], $data['quake'])) {
        pg_query_params($dbconn, 
            "INSERT INTO quake_record (datetime, room_num, level) VALUES ($1, $2, $3)", 
            [$data['datetime'], $data['room'], $data['quake']]
        );
        return ["status" => "success", "type" => "quake"];
    }

    return ["status" => "error", "message" => "incorrect Arduino data"];
}