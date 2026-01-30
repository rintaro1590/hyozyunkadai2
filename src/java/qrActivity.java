package jp.ac.kanto_pc.ishikawa.test2;

import android.graphics.Bitmap;
import android.os.Bundle;
import android.util.Log;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.Spinner;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;

import com.google.zxing.BarcodeFormat;
import com.journeyapps.barcodescanner.BarcodeEncoder;

import org.json.JSONArray;
import org.json.JSONObject;
import java.util.ArrayList;

public class qrActivity extends AppCompatActivity {

    private ImageView ivQrCode;
    private Spinner spZyugyou, spKyositu, spSyusseki;
    private Button btnGenerate;
    private ArrayList<Integer> subjectIds = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_qr);

        ivQrCode = findViewById(R.id.ivQrCode);
        spZyugyou = findViewById(R.id.spinnerzyugyou);
        spKyositu = findViewById(R.id.spinnerkyositu);
        spSyusseki = findViewById(R.id.spinnersyusseki);
        btnGenerate = findViewById(R.id.btnGenerate);

        setupSpinners();

        btnGenerate.setOnClickListener(v -> generateQR());
    }

    private void setupSpinners() {
        try {
            String jsonStr = getIntent().getStringExtra("SUBJECTS_JSON");
            if (jsonStr != null) {
                JSONArray subjects = new JSONArray(jsonStr);
                ArrayList<String> subjectNames = new ArrayList<>();
                for (int i = 0; i < subjects.length(); i++) {
                    JSONObject obj = subjects.getJSONObject(i);
                    subjectNames.add(obj.getString("subject_mei"));
                    subjectIds.add(obj.getInt("subject_id"));
                }
                spZyugyou.setAdapter(new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, subjectNames));
            }
        } catch (Exception e) {
            Log.e("QR_ERROR", "科目解析失敗", e);
        }

        String[] rooms = {"0-502", "0-504", "0-506"};
        spKyositu.setAdapter(new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, rooms));

        // 1:出席, 2:遅刻, 3:早退, 4:退席
        String[] statuses = {"1:出席", "2:遅刻", "3:早退", "4:退席"};
        spSyusseki.setAdapter(new ArrayAdapter<>(this, android.R.layout.simple_spinner_dropdown_item, statuses));
    }

    private void generateQR() {
        try {
            int subjectId = subjectIds.get(spZyugyou.getSelectedItemPosition());
            String room = spKyositu.getSelectedItem().toString();
            int status = spSyusseki.getSelectedItemPosition() + 1;

            JSONObject qrContent = new JSONObject();
            qrContent.put("sid", subjectId);
            qrContent.put("room", room);
            qrContent.put("status", status);

            BarcodeEncoder barcodeEncoder = new BarcodeEncoder();
            Bitmap bitmap = barcodeEncoder.encodeBitmap(qrContent.toString(), BarcodeFormat.QR_CODE, 400, 400);
            ivQrCode.setImageBitmap(bitmap);

        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}