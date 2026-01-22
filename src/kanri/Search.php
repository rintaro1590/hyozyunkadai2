<html>
<head>
    <link rel="stylesheet" type="text/css" href="Search.css">
    <meta http-equiv="Content-Type" content="width=device-width,initial-scale=1.0">
    <title>グループ1</title>
</head>
<body>
    <div class='sita-container'>
        <div class='tabs'>
            <div class='tab'><a href='Home.php'>ホーム</a></div>
            <div class='tab2'><a href='Detail.php'>詳細</a></div>
            <div class='tab3'>出席</div>
            <div class='tab-right'></div>
        </div>
        <div class="screen-container">
            <div class="form-box">
                <div class="input-group">
                    <label>科名</label>
                    <select id="department" onchange="updateNumbers()">
                        <option value="">読み込み中...</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>学年</label>
                    <select id="grade" onchange="updateNumbers()">
                        <option value="">選択してください</option>
                        <option value="1">1年</option>
                        <option value="2">2年</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>番号</label>
                    <select id="number" onchange="searchStudent()"> <option value="">科名と学年を選択してください</option>
                    </select>
                </div>

                <div class="name-display" id="name-result">名前を表示</div>

                <div class="button-area">
                    <button type="button" class="search-btn" onclick="searchStudent()">検索</button>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html><html>