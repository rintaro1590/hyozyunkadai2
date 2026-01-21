<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_config.php';
require_once 'arduino_logic.php';
require_once 'android_logic.php';
require_once 'in_logic.php';

$dbconn = getDbConnection(); // db_config.phpから読み込み
$data = json_decode(file_get_contents('php://input'), true);
// デバッグ用：届いた生データをファイルに保存してみる
file_put_contents(__DIR__ . '/debug_receive.txt', "Time: " . date('Y-m-d H:i:s') . "\nData: " . file_get_contents('php://input'));

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

// --- 追加部分：レスポンスJSONをテキストファイルに保存 ---
$response_json = json_encode($response, JSON_UNESCAPED_UNICODE);
$log_file = 'debug_send.txt';
$timestamp = date('Y-m-d H:i:s');

// 読みやすいように [日時] [種類] データを1行にまとめる
$log_entry = "[$timestamp] TYPE: {$data['type']} | RES: $response_json" . PHP_EOL;
file_put_contents($log_file, $log_entry, FILE_APPEND);

pg_close($dbconn);
echo json_encode($response);
exit;