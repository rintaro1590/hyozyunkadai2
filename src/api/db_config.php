<?php
function getDbConnection() {
    $conn_str = "host=localhost port=5432 dbname=group1 user=postgres password=postgres";
    $dbconn = pg_connect($conn_str);
    if (!$dbconn) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["status" => "error", "message" => "DB接続失敗"]);
        exit;
    }
    return $dbconn;
}