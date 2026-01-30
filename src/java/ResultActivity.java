package jp.ac.kanto_pc.ishikawa.test2;

import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.widget.Button;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import org.json.JSONObject;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.Locale;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class ResultActivity extends AppCompatActivity {

    // 成功・失敗の状態を保存する変数（ボタンの挙動を分けるため）
    private boolean isSuccessStatus = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_result);

        TextView idDisplay = findViewById(R.id.textIdDisplay);
        TextView passDisplay = findViewById(R.id.textPassDisplay);
        Button btnBack = findViewById(R.id.btnBackToLogin);

        // 1. 前の画面(RegisterActivity)から届いたデータを受け取る
        Intent receivedIntent = getIntent();
        int user_id = receivedIntent.getIntExtra("REGISTERED_USER_ID", 0);
        int kamei_id = receivedIntent.getIntExtra("REGISTERED_KAMEI_ID", 0);
        String name = receivedIntent.getStringExtra("REGISTERED_name");
        String pass = receivedIntent.getStringExtra("REGISTERED_pw");

        ExecutorService executor = Executors.newSingleThreadExecutor();
        Handler handler = new Handler(Looper.getMainLooper());

        executor.execute(() -> {
            String rawResponse = "";
            try {
                URL url = new URL("http://10.100.56.161/api/api_main.php");
                HttpURLConnection con = (HttpURLConnection) url.openConnection();
                con.setRequestMethod("POST");
                con.setRequestProperty("Content-Type", "application/json; utf-8");
                con.setDoOutput(true);

                // 2. JSONを組み立てて送信
                String jsonInput = String.format(Locale.US,
                        "{\"type\":\"Android\", \"user_id\":%d, \"kamei_id\":%d, \"name\":\"%s\", \"password\":\"%s\"}",
                        user_id, kamei_id, name, pass);

                try (OutputStream os = con.getOutputStream()) {
                    os.write(jsonInput.getBytes("utf-8"));
                }

                // 3. サーバーからの返信を受け取る
                int status = con.getResponseCode();
                if (status == HttpURLConnection.HTTP_OK) {
                    BufferedReader reader = new BufferedReader(new InputStreamReader(con.getInputStream(), "UTF-8"));
                    StringBuilder sb = new StringBuilder();
                    String line;
                    while ((line = reader.readLine()) != null) sb.append(line);
                    rawResponse = sb.toString();
                    reader.close();
                } else {
                    rawResponse = "{\"submit\":false, \"message\":\"Server Error " + status + "\"}";
                }

            } catch (Exception e) {
                rawResponse = "{\"submit\":false, \"message\":\"Connection Failed\"}";
            }

            // --- 4. 解析してメッセージ作成 ---
            final String fRaw = rawResponse;
            String mainMessage;
            String subMessage;
            boolean success;

            try {
                JSONObject json = new JSONObject(fRaw);
                success = json.optBoolean("submit", false);
                isSuccessStatus = success; // クラス変数に保存

                if (success) {
                    String resId = json.optString("user_id", String.valueOf(user_id));
                    String resPw = json.optString("password", "（非表示）");
                    mainMessage = "登録が完了しました！";
                    subMessage = "あなたのユーザーIDは " + resId + " です。\n" +
                            "パスワード: " + resPw;
                } else {
                    String msg = json.optString("message", "すでに登録されているか、エラーが発生しました。");
                    mainMessage = "登録できませんでした";
                    subMessage = "理由: " + msg;
                }
            } catch (Exception e) {
                mainMessage = "解析エラー";
                subMessage = "受信データの形式が正しくありません。";
                success = false;
            }

            final String fMain = mainMessage;
            final String fSub = subMessage;
            final boolean fSuccess = success;

            handler.post(() -> {
                idDisplay.setText(fMain);
                passDisplay.setText(fSub);

                // 成功したかどうかでボタンの文字を変える
                if (fSuccess) {
                    btnBack.setText("メイン画面へ戻る");
                } else {
                    btnBack.setText("入力画面");
                }
            });
        });

        // 5. ボタンのクリック処理
        btnBack.setOnClickListener(v -> {
            if (isSuccessStatus) {
                // 【成功時】すべてクリアして MainActivity へ
                Intent backIntent = new Intent(ResultActivity.this, MainActivity.class);
                backIntent.setFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_NEW_TASK);
                startActivity(backIntent);
            } else {
                // 【失敗時】この画面を閉じるだけで、一個前の入力画面がそのまま出る
                finish();
            }
        });
    }
}