package jp.ac.kanto_pc.ishikawa.test2;

import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;

public class MainActivity extends AppCompatActivity {

    private final Handler handler = new Handler(Looper.getMainLooper());
    private EditText editID;
    private EditText editPassword;
    private Button btnSend;
    private Button btnNew;
    private TextView textError;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        // XML要素の取得
        editID = findViewById(R.id.editID);
        editPassword = findViewById(R.id.editPassword);
        btnSend = findViewById(R.id.btnSend);
        btnNew = findViewById(R.id.btnNew);
        textError = findViewById(R.id.textError);

        // --- 新規作成画面へ遷移 ---
        btnNew.setOnClickListener(v -> {
            Intent intent = new Intent(MainActivity.this, RegisterActivity.class);
            startActivity(intent);
        });

        // --- ログイン（送信）ボタンの処理 ---
        btnSend.setOnClickListener(v -> {
            String userIdStr = editID.getText().toString();
            String password = editPassword.getText().toString();

            if (!userIdStr.isEmpty() && !password.isEmpty()) {
                try {
                    int userId = Integer.parseInt(userIdStr);

                    // 通信スレッドの開始
                    // nameに "Android" を渡すことで、PHP側のログインロジックへ誘導します
                    MyNetThread thread = new MyNetThread(
                            MainActivity.this,
                            handler,
                            textError,        // 通信結果を表示するTextView
                            "Android",        // ログイン時は name を "Android" 固定にする
                            password,
                            userId,
                            0,                // kamei_id は不要なので 0
                            "http://10.100.56.161/api/api_main.php" // サーバーのURL
                    );
                    thread.start();

                } catch (NumberFormatException e) {
                    textError.setText("IDは数字で入力してください");
                }
            } else {
                textError.setText("IDとパスワードを入力してください");
            }
        });
    }
}