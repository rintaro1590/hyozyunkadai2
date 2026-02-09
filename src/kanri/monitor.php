<?php
// ヘッダーの設定（SSE用）
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$conn_str = "host=localhost dbname=group1 user=postgres password=postgres";
$dbconn = pg_connect($conn_str);

if (!$dbconn) {
    echo "data: {\"error\": \"DB接続失敗\"}\n\n";
    exit;
}

pg_query($dbconn, 'LISTEN new_row_event');

while (true) {
    $notify = pg_get_notify($dbconn);
    if ($notify) {
        $jsonData = $notify['payload'];
        // そのままフロントエンドに送信
        echo "data: {$jsonData}\n\n";
    }
    
    // 接続が切れていないか確認し、バッファを強制出力
    if (ob_get_level() > 0) ob_flush();
    flush();

    // CPU負荷軽減
    usleep(200000); 
}