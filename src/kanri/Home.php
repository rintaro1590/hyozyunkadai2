<html>
<head>
    <link rel="stylesheet" type="text/css" href="Home.css">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>標準課題2</title>
</head>
<body>
<?php
require_once '../api/db_config.php';

$dbconn = getDbConnection(); 

echo "<div class='sita-container'>";
    echo "<div class='tabs'>";
        echo "<div class='tab'>ホーム</div>";
        echo "<div class='tab2'><a href='Detail.php'>詳細</a></div>";
        echo "<div class='tab3'><a href='Choice.php'>出席</a></div>";
        echo "<div class='tab-right'></div>";
    echo "</div>";
    echo "<div class='home'>";
        echo "<div class='leftbox'>";
            echo "<div class='room'>";
                //5階
                echo "<div class='five'>";
                    //職員室
                    echo "<div class='teacher'></div>";
                    //0-504
                    echo "<div class='fivefour'>";
                        echo "<div class='zero'>0-504</div>";
                        echo "<img src='syasin/人.png' class='fhito'>";
                        echo "<img src='syasin/ondokei1.png' class='f_ondo'>";
                    echo "</div>";
                    //階段1
                    echo "<div class='stairs'></div>";
                    //0-502
                    echo "<div class='fivetwo'>";
                        echo "<div class='zero'>0-502</div>";
                        echo "<img src='syasin/人.png' class='thito'>";
                        echo "<img src='syasin/ondokei2.png' class='t_ondo'>";
                    echo "</div>";
                    //階段2
                    echo "<div class='stairs2'></div>";
                    //0-506
                    echo "<div class='xbox'>";
                        echo "<div class='fivesix'>";
                            echo "<div class='szero'>0-506</div>";
                            echo "<img src='syasin/人.png' class='shito'>";
                            echo "<img src='syasin/ondokei3.png' class='s_ondo'>";
                        echo"</div>";
                    echo "</div>";
                echo "</div>";
            echo "</div>";


            //気温等
            echo "<div class='temp'>";
                if(!$dbconn){
                    echo "接続エラーが発生しました。";
                }else{
                    //データを取得するクエリ
                    $query = "SELECT room_num,temperature,humidity,pressure FROM env_record ORDER BY room_num ASC LIMIT 1";
                    $result = pg_query($dbconn, $query);
                    if(!$result){
                        echo "クエリの実行に失敗しました。";
                    }else{
                        //取得した行をループで回して表示
                        while($row = pg_fetch_assoc($result)){
                            echo htmlspecialchars($row['room_num']) . "  ";
                            echo "気温" . htmlspecialchars($row['temperature']) . "℃ ";
                            echo "湿度" . htmlspecialchars($row['humidity']) . "% ";
                            echo "気圧" . htmlspecialchars($row['pressure']) . "hPa<br>";
                        }
                    }
                    //接続を閉じる
                    pg_close($dbconn);
                }
            echo "</div>";
        echo "</div>";
        echo "<div class='rightbox'>";
        echo "</div>";
    echo "</div>";
echo "</div>";
?>
</body>

</html>