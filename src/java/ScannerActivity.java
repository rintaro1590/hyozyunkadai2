package jp.ac.kanto_pc.ishikawa.test2;

import android.os.Bundle;
import android.util.Log;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;

import com.journeyapps.barcodescanner.CompoundBarcodeView;

import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;

public class ScannerActivity extends AppCompatActivity {
    private CompoundBarcodeView barcodeView;
    private TextView tvScanDisplay;
    private int userId;
    private boolean isScanned = false; // 1回読み取ったかどうかのフラグ

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_scanner);

        userId = getIntent().getIntExtra("USER_ID", 0);
        barcodeView = findViewById(R.id.barcodeView);
        tvScanDisplay = findViewById(R.id.tvScanDisplay);
        Button btnBack = findViewById(R.id.btnBack);

        // キャンセルボタン（ログイン画面に戻る）
        btnBack.setOnClickListener(v -> finish());

        barcodeView.decodeContinuous(result -> {
            // すでに読み取り済みなら何もしない
            if (isScanned) return;

            if (result.getText() != null) {
                isScanned = true; // 読み取り済みにする
                barcodeView.pause(); // カメラを止める
                tvScanDisplay.setText("送信中...");
                sendAttendanceData(result.getText());
            }
        });
    }

    private void sendAttendanceData(String qrContent) {
        new Thread(() -> {
            try {
                JSONObject qrJson = new JSONObject(qrContent);
                int subjectId = qrJson.getInt("sid");
                String room = qrJson.getString("room");
                int status = qrJson.getInt("status");
                String datetime = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).format(new Date());

                JSONObject postData = new JSONObject();
                postData.put("type", "Android");
                postData.put("user_id", userId);
                postData.put("subject_id", subjectId);
                postData.put("datetime", datetime);
                postData.put("status", status);
                postData.put("room", room);

                URL url = new URL("http://10.100.56.161/api/api_main.php");
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json; charset=utf-8");
                conn.setDoOutput(true);

                try (OutputStream os = conn.getOutputStream()) {
                    byte[] input = postData.toString().getBytes(StandardCharsets.UTF_8);
                    os.write(input, 0, input.length);
                }

                int responseCode = conn.getResponseCode();
                StringBuilder response = new StringBuilder();
                try (BufferedReader br = new BufferedReader(new InputStreamReader(
                        responseCode == 200 ? conn.getInputStream() : conn.getErrorStream(), StandardCharsets.UTF_8))) {
                    String line;
                    while ((line = br.readLine()) != null) {
                        response.append(line);
                    }
                }

                runOnUiThread(() -> {
                    try {
                        JSONObject resJson = new JSONObject(response.toString());
                        boolean isSuccess = resJson.optBoolean("response", false);
                        if (responseCode == 200 && isSuccess) {
                            tvScanDisplay.setText("送信完了しました。\nキャンセルボタンで戻ってください。");
                            Toast.makeText(this, "記録しました", Toast.LENGTH_SHORT).show();
                        } else {
                            tvScanDisplay.setText("登録失敗（二重登録など）");
                        }
                    } catch (Exception e) {
                        tvScanDisplay.setText("エラーが発生しました");
                    }
                    // ★ 連続読み取りさせないため、ここでは barcodeView.resume() を呼びません
                });

            } catch (Exception e) {
                runOnUiThread(() -> tvScanDisplay.setText("QR読み取りエラー"));
            }
        }).start();
    }

    @Override
    protected void onResume() {
        super.onResume();
        // 1回読み取り済みなら、画面に戻ってきてもカメラを再開させない
        if (!isScanned) {
            barcodeView.resume();
        }
    }

    @Override
    protected void onPause() {
        super.onPause();
        barcodeView.pause();
    }
}