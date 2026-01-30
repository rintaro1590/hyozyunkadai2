package jp.ac.kanto_pc.ishikawa.test2;

import android.content.Context;
import android.content.Intent;
import android.os.Handler;
import android.util.Log;
import android.widget.TextView;
import org.json.JSONObject;
import java.io.BufferedReader;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;

class MyNetThread extends Thread {
    private final Handler handler;
    private final TextView viewStatus;
    private final String name, pass, targetUrl;
    private final int user_id, kamei_id;
    private final Context context;

    public MyNetThread(Context context, Handler handler, TextView viewStatus, String name, String pass, int user_id, int kamei_id, String url) {
        this.context = context;
        this.handler = handler;
        this.viewStatus = viewStatus;
        this.name = name;
        this.pass = pass;
        this.user_id = user_id;
        this.kamei_id = kamei_id;
        this.targetUrl = url;
    }

    @Override
    public void run() {
        String result = "";
        HttpURLConnection conn = null;
        try {
            JSONObject postData = new JSONObject();
            // PHP側のswitch文を通過させるための識別子
            postData.put("type", "Android");

            if (name != null && name.equals("Android")) {
                // 【ログイン時】IDとパスワードのみ送信
                postData.put("user_id", user_id);
                postData.put("password", pass);
            } else {
                // 【新規登録時】全項目を送信
                postData.put("user_id", user_id);
                postData.put("kamei_id", kamei_id);
                postData.put("name", name);
                postData.put("password", pass);
            }

            Log.d("PHP_DEBUG", "送信データ: " + postData.toString());

            URL url = new URL(targetUrl);
            conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setRequestProperty("Content-Type", "application/json; charset=utf-8");
            conn.setDoOutput(true);
            conn.setConnectTimeout(5000);

            try (OutputStream os = conn.getOutputStream()) {
                os.write(postData.toString().getBytes(StandardCharsets.UTF_8));
                os.flush();
            }

            int status = conn.getResponseCode();
            InputStream is = (status < 400) ? conn.getInputStream() : conn.getErrorStream();
            if (is != null) {
                BufferedReader br = new BufferedReader(new InputStreamReader(is, StandardCharsets.UTF_8));
                StringBuilder sb = new StringBuilder();
                String line;
                while ((line = br.readLine()) != null) sb.append(line);
                result = sb.toString().trim();
            }
        } catch (Exception e) {
            result = "JAVA_ERROR: " + e.getMessage();
        } finally {
            if (conn != null) conn.disconnect();
        }

        final String finalResult = result;
        handler.post(() -> handleResponse(finalResult));
    }

    private void handleResponse(String response) {
        try {
            JSONObject json = new JSONObject(response);
            Log.d("PHP_RESULT", "受信データ: " + response);

            // 1. ログイン成否の確認
            if (json.optBoolean("login")) {

                // 2. 先生判定の強化
                // boolean型のtrueだけでなく、文字列の"t"や"true"も許容する
                boolean isTeacher = false;
                if (json.has("teacher")) {
                    Object tObj = json.get("teacher");
                    if (tObj instanceof Boolean) {
                        isTeacher = (Boolean) tObj;
                    } else if (tObj instanceof String) {
                        String tStr = (String) tObj;
                        isTeacher = tStr.equalsIgnoreCase("t") || tStr.equalsIgnoreCase("true");
                    }
                }

                Intent intent;
                if (isTeacher) {
                    // 先生：QR生成画面へ
                    intent = new Intent(context, qrActivity.class);
                    if (json.has("subjects")) {
                        intent.putExtra("SUBJECTS_JSON", json.getJSONArray("subjects").toString());
                    }
                } else {
                    // 学生：QRスキャン画面へ
                    intent = new Intent(context, ScannerActivity.class);
                }

                // ユーザーIDを引き継ぎ
                intent.putExtra("USER_ID", json.getInt("user_id"));
                intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
                context.startActivity(intent);

            } else {
                viewStatus.setText("ログイン失敗：IDまたはパスワードが違います");
            }
        } catch (Exception e) {
            viewStatus.setText("解析失敗: " + response);
            Log.e("DEBUG", "JSONエラー", e);
        }
    }
}