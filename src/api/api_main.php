<?php

header('Content-Type: application/json; charset=utf-8');
require_once 'db_config.php';
require_once 'arduino_logic.php';
require_once 'android_logic.php';
require_once 'in_logic.php';

$dbconn = getDbConnection(); // db_config.phpから読み込み
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!$data || !isset($data['type'])) {
    echo json_encode(["status" => "error", "message" => "Invalid Request"]);
    exit;
}

$response = [];
try {
    switch ($data['type']) {
        case 'Arduino':
            $response = handleArduinoRequest($dbconn, $data);
            break;
        case 'Android':
            $response = handleAndroidRequest($dbconn, $data);
            break;
        case 'In':
            $response = handleInRequest($dbconn, $data);
            break;
        default:
            $response = ["status" => "error", "message" => "Unknown type"];
    }
} catch (Exception $e) {
    $response = ["status" => "error", "message" => "Exception: " . $e->getMessage()];
}

pg_close($dbconn);
echo json_encode($response);
exit;
