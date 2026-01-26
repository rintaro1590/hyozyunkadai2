<html>
<head>
    <link rel="stylesheet" type="text/css" href="Detail.css">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>標準課題2</title>
</head>
<body>
<?php
//今日の日付取得
$selected_date = date('Y-m-d');

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
                echo "<input type='date' id='date-picker' value='" . htmlspecialchars($selected_date) . "' onchange='postDateUpdate(this.value)'>";
            echo "</div>";

            //画面下
            echo "<div class='bottombox'></div>";
        echo "</div>";
    echo "</div>";
?>
</body>

<script>
//すべてのドロップダウンの箱を取得
const dropdowns = document.querySelectorAll('.dropdown-container');
//それぞれのドロップダウンに対して処理を行う
dropdowns.forEach(dropdown => {
    //今ループしている 'dropdown' の中から探す
    const button = dropdown.querySelector('.dropdown-btn');
    const list = dropdown.querySelector('.dropdown-list');
    const items = dropdown.querySelectorAll('.dropdown-list li');

    //ボタンを押した時の処理
    button.addEventListener('click', () => {
        //他の開いているメニューがあれば閉じる処理（オプション）
        //挙動をシンプルにするため、自分のリストの表示切替だけ行います
        list.classList.toggle('show');
    });
    //リストの項目を選んだ時の処理
    items.forEach(item => {
        item.addEventListener('click', (e) => {
            const text = e.target.textContent;
            button.textContent = text;
            list.classList.remove('show');
        });
    });
});
//外側をクリックしたら閉じる処理（全体共通）
document.addEventListener('click', (e) => {
    //クリックした要素がドロップダウンの中に無ければ
    if (!e.target.closest('.dropdown-container')) {
        //すべてのリストから show クラスを外す
        document.querySelectorAll('.dropdown-list').forEach(list => {
            list.classList.remove('show');
        });
    }
});
function postDateUpdate(selectedDate){
    document.getElementById('hidden-date').value = selectedDate;
    document.getElementById('refresh-form').submit();
}
</script>

</html>