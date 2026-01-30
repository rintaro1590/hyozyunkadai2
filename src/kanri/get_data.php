<?php
require_once '../api/db_config.php';
$dbconn = getDbConnection();

//部屋番号を取得(送られてこなければデフォルト 0-502)
$room_num = isset($_GET['room']) ? $_GET['room'] : '0-502';
//指定がなければ今日(2026-01-30)
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
//$data = [];
// 返却用のデータ構造を定義
$response = [
    "env" => [],
    "quakes" => []
];

if($dbconn){
    //その日の全データを表示
    $query_env = "SELECT to_char(datetime, 'HH24:MI') as time, temperature, humidity, pressure 
          FROM env_record 
          WHERE room_num = $1 AND datetime::date = $2 
          ORDER BY datetime ASC";
    
    $result_env = pg_query_params($dbconn, $query_env, array($room_num, $date));

    if($result_env){
        while ($row = pg_fetch_assoc($result_env)) {
            /*$data[] = [
                "time" => $row['time'],
                "temp" => (float)$row['temperature'],
                "humi" => (float)$row['humidity'],
                "pres" => (float)$row['pressure']
            ];*/
            $response["env"][] = [
                "time" => $row['time'],
                "temp" => (float)$row['temperature'],
                "humi" => (float)$row['humidity'],
                "pres" => (float)$row['pressure']
            ];
        }
    }
    //地震データの取得
    $query_quake = "SELECT to_char(datetime, 'YYYY-MM-DD HH24:MI') as datetime, level 
                    FROM quake_record 
                    WHERE room_num = $1 AND datetime::date = $2 
                    ORDER BY datetime ASC
                    LIMIT 3";
    $result_quake = pg_query_params($dbconn, $query_quake, array($room_num, $date));

    if($result_quake){
        while ($row = pg_fetch_assoc($result_quake)) {
            $response["quakes"][] = [
                "datetime" => $row['datetime'],
                "level" => $row['level']
            ];
        }
    }
}

//JSONとして出力
header('Content-Type: application/json');
//echo json_encode($data);
echo json_encode($response);
exit;