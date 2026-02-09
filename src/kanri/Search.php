<html>
<head>
    <link rel="stylesheet" type="text/css" href="search.css">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>学生検索</title>
</head>
<body>
    <div class='sita-container'>
        <div class='tabs'>
            <div class='tab'><a href='home.php'>ホーム</a></div>
            <div class='tab2'><a href='detail.php'>詳細</a></div>
            <div class='tab3' style="border-bottom:none;">出席</div>
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
                    <select id="number" onchange="showNameOnly()"> 
                        <option value="">科名と学年を選択してください</option>
                    </select>
                </div>

                <div class="name-display" id="name-result">名前を表示</div>

                <div class="button-area">
                    <button type="button" class="search-btn" onclick="searchAndTransition()">検索</button>
                </div>

                <form id="transition-form" action="attend.php" method="POST" style="display:none;">
                    <input type="hidden" name="user_id" id="post-user-id">
                    <input type="hidden" name="user_name" id="post-user-name">
                </form>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>