package jp.ac.kanto_pc.ishikawa.test2;

import android.content.Intent; // これを追加！
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Spinner;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;

public class RegisterActivity extends AppCompatActivity {

    private final Handler handler = new Handler(Looper.getMainLooper());

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register);

        // --- 部品の取得 ---
        Spinner spinnerYear = findViewById(R.id.spinnerYear);
        Spinner spinnerDept = findViewById(R.id.spinnerDept);
        Spinner spinnerNumber = findViewById(R.id.spinnerNumber);
        EditText editName = findViewById(R.id.editName);
        EditText editNewPassword = findViewById(R.id.editNewPassword);
        Button btnRegister = findViewById(R.id.btnRegister);
        TextView textResultId = findViewById(R.id.textResultId);
        TextView textResultPw = findViewById(R.id.textResultPw);

        // --- プルダウンの設定 ---
        setupSpinner(spinnerYear, new String[]{"2024", "2025", "2026"});
        setupSpinner(spinnerDept, new String[]{"生産電子情報システム技術科", "建築科", "電子情報技術科"});
        setupSpinner(spinnerNumber, new String[]{"1", "2", "3", "4", "5", "10"});

        // --- 登録ボタンの処理 ---
        btnRegister.setOnClickListener(v -> {
            // 1. 各入力を取得
            String name = editName.getText().toString().trim();
            String pw = editNewPassword.getText().toString().trim();

            // 2. 入力チェック（最初にやる！）
            if (name.isEmpty() || pw.isEmpty()) {
                textResultId.setText("名前とパスワードを入力してください");
                return;
            }

            // 3. 計算ロジック
            String yearStr = spinnerYear.getSelectedItem().toString();
            String dept = spinnerDept.getSelectedItem().toString();
            String numStr = spinnerNumber.getSelectedItem().toString();
            int num = Integer.parseInt(numStr);
            int year = Integer.parseInt(yearStr);
            int kamei_id = 0;

            switch(year){
                case 2024: year = 24; break;
                case 2025: year = 25; break;
                case 2026: year = 26; break;
            }
            switch(dept){
                case "生産電子情報システム技術科": kamei_id = 7; break;
                case "電子情報技術科": kamei_id = 3; break;
                case "建築科": kamei_id = 4; break;
            }
            int user_id = (year * 1000) + (kamei_id * 100) + num;

            // 4. ResultActivityへの移動 (Intent)
            Intent intent = new Intent(RegisterActivity.this, ResultActivity.class);
            intent.putExtra("REGISTERED_USER_ID", user_id); // 名前を ResultActivity と合わせる
            intent.putExtra("REGISTERED_name", name);
            intent.putExtra("REGISTERED_pw", pw);
            intent.putExtra("REGISTERED_KAMEI_ID", kamei_id);
            startActivity(intent);
        });
    }

    private void setupSpinner(Spinner spinner, String[] items) {
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_item, items);
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        spinner.setAdapter(adapter);
    }
}