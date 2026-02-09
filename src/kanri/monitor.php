<?php
// 1. データベース接続
$conn_str = "host=localhost dbname=group1 user=postgres password=postgres";
$dbconn = pg_connect($conn_str);

if (!$dbconn) {
    die("DB接続エラー: " . pg_last_error());
}

// 2. PostgreSQLの通知チャンネル 'new_row_event' をリッスン
pg_query($dbconn, 'LISTEN new_row_event');

echo "--- 監視中: PostgreSQLから直接データを受け取ります ---\n";

while (true) {
    // 通知を取得
    $notify = pg_get_notify($dbconn);

    if ($notify) {
        // SQL側の row_to_json(NEW) で送られたデータを受け取る
        $jsonData = $notify['payload']; 
        $data = json_decode($jsonData, true); // 配列に変換

        echo "\n【新着通知】\n";
        echo "受信した生データ: " . $jsonData . "\n";
        
        // --- ここで受け取ったデータを使って好きな処理ができます ---
        // 例: 特定のカラムを表示する
        if (isset($data['datetime'])) {
            echo "Datetime: " . $data['datetime'] . " の行が追加されました。\n";
        }
        // -----------------------------------------------------
    }

    // CPUの負荷を抑えるために 0.1秒待機
    usleep(100000);
}