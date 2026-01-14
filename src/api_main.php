<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_config.php';
require_once 'arduino_logic.php';
require_once 'android_logic.php';

$dbconn = getDbConnection(); // db_config.phpから読み込み
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['type'])) {
    echo json_encode(["status" => "error", "message" => "Invalid Request"]);
    exit;
}

$response = [];
switch ($data['type']) {
    case 'Arduino':
        $response = handleArduinoRequest($dbconn, $data);
        break;
    case 'Android':
        $response = handleAndroidRequest($dbconn, $data);
        break;
    default:
        $response = ["status" => "error", "message" => "Unknown type"];
}

echo json_encode($response);