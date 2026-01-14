import java.io.BufferedReader;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.Random;
import java.util.stream.Collectors;

public class DatabaseTester {

    private static final String TARGET_URL = "http://localhost/api/api_main.php";

    public static void main(String[] args) {
        Thread testThread = new Thread(() -> {
            Random random = new Random();
            DateTimeFormatter dtf = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");

            System.out.println("=== 通信テスト開始 ===");

            while (true) {
                try {
                    String jsonPayload = "";
                    int mode = random.nextInt(3);

                    switch (mode) {
                        case 0: // Arduino: 環境データ
                            jsonPayload = String.format(
                                "{\"type\":\"Arduino\",\"datetime\":\"%s\",\"room_num\":%d,\"temp\":%.1f,\"humid\":%.1f,\"press\":%.1f,\"light\":%b}",
                                dtf.format(LocalDateTime.now()), random.nextInt(5)+1, 25.5, 60.0, 1013.2, random.nextBoolean() ? 1 : 0
                            );
                            break;
                        case 1: // Arduino: 地震データ
                            jsonPayload = String.format(
                                "{\"type\":\"Arduino\",\"datetime\":\"%s\",\"room_num\":\"%s\",\"level\":%d}",
                                dtf.format(LocalDateTime.now()), Integer.valueOf(random.nextInt(10) + 1).toString(), random.nextInt(7)
                            );
                            break;
                        case 2: // Android: ログイン
                            jsonPayload = String.format(
                                "{\"type\":\"Android\",\"user_id\":1234,\"password\":\"password\"}"
                            );
                            break;
                    }

                    // 送信とレスポンスの取得
                    sendPostAndPrintResponse(jsonPayload);

                    Thread.sleep(1000); // 5秒間隔で送信

                } catch (Exception e) {
                    System.err.println("エラー: " + e.getMessage());
                }
            }
        });

        testThread.start();
    }

    private static void sendPostAndPrintResponse(String jsonInputString) {
        try {
            URL url = new URL(TARGET_URL);
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setRequestProperty("Content-Type", "application/json; utf-8");
            conn.setRequestProperty("Accept", "application/json");
            conn.setDoOutput(true);

            // 1. データの送信
            try (OutputStream os = conn.getOutputStream()) {
                byte[] input = jsonInputString.getBytes(StandardCharsets.UTF_8);
                os.write(input, 0, input.length);
            }

            // 2. レスポンスの受信
            int code = conn.getResponseCode();
            InputStream is = (code >= 200 && code < 300) ? conn.getInputStream() : conn.getErrorStream();
            
            String responseBody;
            try (BufferedReader br = new BufferedReader(new InputStreamReader(is, StandardCharsets.UTF_8))) {
                responseBody = br.lines().collect(Collectors.joining("\n"));
            }

            // 3. 結果の表示
            System.out.println("[送信データ]: " + jsonInputString);
            System.out.println("[HTTPステータス]: " + code);
            System.out.println("[受信レスポンス]: " + responseBody);
            System.out.println("----------------------------------------------");

        } catch (Exception e) {
            System.out.println("通信失敗: " + e.getMessage());
        }
    }
}