<html>
<head>
    <link rel="stylesheet" type="text/css" href="Detail.css">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>標準課題2</title>
</head>
<body>
<?php
//データベース接続
require_once '../api/db_config.php';
$dbconn = getDbConnection();
/*
//初期値設定
$row502 = null;
$row504 = null;
$row506 = null;
$row502_quake = null;
$row504_quake = null;
$row506_quake = null;
*/
//今日の日付取得
$selected_date = date('Y-m-d');

/*if($dbconn){
    //0-502のデータ取得-------------------------------------------------
    $query1 = "SELECT room_num,temperature,humidity,pressure FROM env_record WHERE room_num = '0-502' ORDER BY datetime DESC LIMIT 1";
    $result1 = pg_query($dbconn, $query1);
    if($result1) $row502 = pg_fetch_assoc($result1);
    //地震のデータ取得
    $query_quake1 = "SELECT * FROM quake_record WHERE room_num = '0-502' ORDER BY datetime DESC LIMIT 1";
    $result_quake1 = pg_query($dbconn, $query_quake1);
    if($result_quake1) $row502_quake = pg_fetch_assoc($result_quake1);

    //0-504のデータ取得--------------------------------------------------
    $query2 = "SELECT room_num,temperature,humidity,pressure FROM env_record WHERE room_num = '0-504' ORDER BY datetime DESC LIMIT 1";
    $result2 = pg_query($dbconn, $query2);
    if($result2) $row504 = pg_fetch_assoc($result2);
    //地震のデータ取得
    $query_quake2 = "SELECT * FROM quake_record WHERE room_num = '0-504' ORDER BY datetime DESC LIMIT 1";
    $result_quake2 = pg_query($dbconn, $query_quake2);
    if($result_quake2) $row504_quake = pg_fetch_assoc($result_quake2);

    //0-506のデータ取得---------------------------------------------------
    $query3 = "SELECT room_num,temperature,humidity,pressure FROM env_record WHERE room_num = '0-506' ORDER BY datetime DESC LIMIT 1";
    $result3 = pg_query($dbconn, $query3);
    if($result3) $row506 = pg_fetch_assoc($result3);
    //地震のデータ取得
    $query_quake3 = "SELECT * FROM quake_record WHERE room_num = '0-506' ORDER BY datetime DESC LIMIT 1";
    $result_quake3 = pg_query($dbconn, $query_quake3);
    if($result_quake3) $row506_quake = pg_fetch_assoc($result_quake3);
}*/


echo "<div class='sita-container'>";
    echo "<div class='tabs'>";
        echo "<div class='tab'><a href='Home.php'>ホーム</a></div>";
        echo "<div class='tab2'>詳細</div>";
        echo "<div class='tab3'><a href='Search.php'>出席</a></div>";
        echo "<div class='tab-right'></div>";
    echo "</div>";
    echo "<div class='detail'>";
        //画面上
        echo "<div class='topbox'>";
            //部屋選択リスト
            echo "<div class='dropdown-container'>";
                echo "<button id='dropdownBtn' class='dropdown-btn'>0-502</button>";
                echo "<ul id='dropdownList' class='dropdown-list'>";
                    echo "<li data-value='1'>0-502</li>";
                    echo "<li data-value='2'>0-504</li>";
                    echo "<li data-value='3'>0-506</li>";
                echo "</ul>";
            echo "</div>";
            //日付選択リスト
            echo "<input type='date' id='date-picker' value='" . htmlspecialchars($selected_date) . "' onchange='updateChartsByInput()'>";
        echo "</div>";

        //画面下
        echo "<div class='bottombox'>";
            //温度box
            echo "<div class='tempbox'>";
                //グラフ
                echo "<div class='gurafu'>";
                    echo "<canvas id='tempChart'></canvas>";
                echo "</div>";
            echo "</div>";
            //湿度box
            echo "<div class='humibox'>";
                //グラフ
                echo "<div class='gurafu'>";
                    echo "<canvas id='humiChart'></canvas>";
                echo "</div>";
            echo "</div>";
            //気圧box
            echo "<div class='presbox'>";
                //グラフ
                echo "<div class='gurafu'>";
                    echo "<canvas id='presChart'></canvas>";
                echo "</div>";
            echo "</div>";
            //地震box
            echo "<div class='earqbox'>";
                //地震
                echo "<div class='e_title'>地震</div>";
                echo "<div id='quake-list' class='earsquake'></div>";
            echo "</div>";
        echo "</div>";
    echo "</div>";
echo "</div>";
?>
</body>

<!--グラフ読み込み-->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
//グラフのインスタンスを保持する変数をグローバルに宣言
let tempChart, humiChart, presChart;

//ドロップダウン関連の要素を取得
const dropdownBtn = document.getElementById('dropdownBtn');
const dropdownList = document.getElementById('dropdownList');
const items = dropdownList.querySelectorAll('li');
const datePicker = document.getElementById('date-picker');

//ドロップダウンの表示・非表示切り替え
dropdownBtn.addEventListener('click', () => {
    dropdownList.classList.toggle('show');
});
//リスト項目がクリックされた時の処理
items.forEach(item => {
    item.addEventListener('click', (e) => {
        const selectedRoom = e.target.textContent;
        dropdownBtn.textContent = selectedRoom;
        dropdownList.classList.remove('show');
        //現在のカレンダーの日付を取得して更新
        const currentDate = datePicker.value;
        updateCharts(selectedRoom, currentDate); 
    });
});
//外側をクリックしたらドロップダウンを閉じる
document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown-container')) {
        dropdownList.classList.remove('show');
    }
});
//カレンダーが変更された時
function updateChartsByInput() {
    const room = dropdownBtn.textContent;
    const date = datePicker.value;
    updateCharts(room, date);
}

//グラフ描画・更新メイン関数
async function updateCharts(roomNum, selectedDate) {
    try {
        //デバッグ用
        console.log(`Fetching data for: ${roomNum} on ${selectedDate}`);
        //URLに部屋番号と日付を含めてデータを取得
        const response = await fetch(`get_data.php?room=${encodeURIComponent(roomNum)}&date=${selectedDate}`);
        const jsonData = await response.json();
        //データが空の場合
        if (jsonData.length === 0) {
            console.warn("データが見つかりませんでした。");
        }

        /*const labels = jsonData.map(item => item.time);
        const temps = jsonData.map(item => item.temp);
        const humis = jsonData.map(item => item.humi);
        const press = jsonData.map(item => item.pres);*/
        const envData = jsonData.env;
        const labels = envData.map(item => item.time);
        const temps = envData.map(item => item.temp);
        const humis = envData.map(item => item.humi);
        const press = envData.map(item => item.pres);

        //温度グラフ
        if (tempChart) tempChart.destroy();
        const ctxTemp = document.getElementById('tempChart').getContext('2d');
        tempChart = new Chart(ctxTemp, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: `温度`,// (${roomNum}  ${selectedDate})
                    data: temps,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: { 
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: false }
                }
            }
        });
        //湿度グラフ
        if (humiChart) humiChart.destroy();
        const ctxHumi = document.getElementById('humiChart').getContext('2d');
        humiChart = new Chart(ctxHumi, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: `湿度`,
                    data: humis,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: { maintainAspectRatio: false }
        });
        //気圧グラフ
        if (presChart) presChart.destroy();
        const ctxPres = document.getElementById('presChart').getContext('2d');
        presChart = new Chart(ctxPres, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: `気圧`,
                    data: press,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: { maintainAspectRatio: false }
        });

        // --- 地震情報の表示更新 (新規追加) ---
        const quakeContainer = document.getElementById('quake-list');
        quakeContainer.innerHTML = ""; // 一旦クリア

        if (jsonData.quakes && jsonData.quakes.length > 0) {
            jsonData.quakes.forEach(q => {
                const row = document.createElement('div');
                row.style.padding = "5px 10px";
                row.style.borderBottom = "1px solid #ccc";
                // 指定のフォーマット: 日付 地震発生 強さ
                row.textContent = `${q.datetime}　回数:${q.level}`;
                quakeContainer.appendChild(row);
            });
        } else {
            quakeContainer.innerHTML = "<div style='padding:10px;'>この日の記録はありません。</div>";
        }

    } catch (error) {
        console.error('データの取得またはグラフの描画に失敗しました:', error);
    }
}

//初期表示（ページ読み込み時に実行）
window.onload = () => {
    const today = "<?php echo $selected_date; ?>";
    updateCharts('0-502', today);
};
//1000ミリ秒 × 60秒 × 60分 = 3,600,000ミリ秒 (1時間)
    setTimeout(function(){
        location.reload();
    }, 3600000);
</script>

</html>