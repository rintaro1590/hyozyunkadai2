import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

public class QuakeDataSender {

    public static void main(String[] args) {
        // 1. サーバーのURL（環境に合わせて変更してください）
        String urlString = "http://localhost/api/database.php";
        
        // 2. 送信データの準備
        // 現在時刻を "YYYY-MM-DD HH:MM:SS" 形式で取得
        String now = LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));
        int quakeLevel = 3; // 振動レベルの例

        // JSON文字列の組み立て
        String jsonInputString = String.format(
            "{\"type\": \"Arduino\", \"datetime\": \"%s\", \"level\": %d}",
            now, quakeLevel
        );

        try {
            URL url = new URL(urlString);
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            
            // HTTP設定
            conn.setRequestMethod("POST");
            conn.setRequestProperty("Content-Type", "application/json; utf-8");
            conn.setRequestProperty("Accept", "application/json");
            conn.setDoOutput(true);

            // データの書き込み
            try (OutputStream os = conn.getOutputStream()) {
                byte[] input = jsonInputString.getBytes(StandardCharsets.UTF_8);
                os.write(input, 0, input.length);
            }

            // レスポンスコードの確認
            //int code = conn.getResponseCode();
            //System.out.println("Response Code: " + code);

            // (任意) サーバーからのレスポンスを読み取る処理をここに追加

        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}