<html>
<head>
    <link rel="stylesheet" type="text/css" href="Home.css">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>標準課題2</title>
</head>
<body>
<?php
//データベース接続
require_once '../api/db_config.php';
$dbconn = getDbConnection();

//初期値設定
$row502 = null;
$row504 = null;
$row506 = null;
$row502_syou = null;
$row504_syou = null;
$row506_syou = null;

if($dbconn){
    //0-502のデータ取得-------------------------------------------------
    $query1 = "SELECT room_num,temperature,humidity,pressure FROM env_record WHERE room_num = '0-502' ORDER BY datetime DESC LIMIT 1";
    $result1 = pg_query($dbconn, $query1);
    if($result1) $row502 = pg_fetch_assoc($result1);
    //照明のデータベース
    $query1_syou = "SELECT * FROM room_state WHERE room_num = '0-502'";
    $result1_syou = pg_query($dbconn, $query1_syou);
    if($result1_syou) $row502_syou = pg_fetch_assoc($result1_syou);

    //0-504のデータ取得--------------------------------------------------
    $query2 = "SELECT room_num,temperature,humidity,pressure FROM env_record WHERE room_num = '0-504' ORDER BY datetime DESC LIMIT 1";
    $result2 = pg_query($dbconn, $query2);
    if($result2) $row504 = pg_fetch_assoc($result2);
    //照明のデータベース
    $query2_syou = "SELECT * FROM room_state WHERE room_num = '0-504'";
    $result2_syou = pg_query($dbconn, $query2_syou);
    if($result2_syou) $row504_syou = pg_fetch_assoc($result2_syou);

    //0-506のデータ取得---------------------------------------------------
    $query3 = "SELECT room_num,temperature,humidity,pressure FROM env_record WHERE room_num = '0-506' ORDER BY datetime DESC LIMIT 1";
    $result3 = pg_query($dbconn, $query3);
    if($result3) $row506 = pg_fetch_assoc($result3);
    //照明のデータベース
    $query3_syou = "SELECT * FROM room_state WHERE room_num = '0-506'";
    $result3_syou = pg_query($dbconn, $query3_syou);
    if($result3_syou) $row506_syou = pg_fetch_assoc($result3_syou);
}
//0-502-------------------------------------------------------------
$temperature_502 = "syasin/ondokei.png";
$bg_color_502 = "#fffafa";
if($row502){
    $temp1 = $row502['temperature'];//温度
    $hum1 = $row502['humidity'];//湿度
    
    //温度
    if($temp1 <= 20){
        $temperature_502 = "syasin/ondokei1.png";
    }elseif($temp1 <= 22){
        $temperature_502 = "syasin/ondokei2.png";
    }else{
        $temperature_502 = "syasin/ondokei3.png";
    }
    //湿度
    if($hum1 <= 12.9){
        $bg_color_502 = "#add8e6";
    }elseif($hum1 <= 15){
        $bg_color_502 = "#00bfff";
    }else{
        $bg_color_502 = "#1e90ff";
    }
}
//照明
$animate_502 = "";
$denki_502 = "#fffafa";
if($row502_syou){
    $lit_502 = $row502_syou['lit'];//照明
    $human_502 = $row502_syou['human_cnt'];//人カウント
    if($lit_502 >= 1 && $human_502 == 0){
        $animate_502 = " animate-denki3";
        $denki_502 = "";
    }elseif($lit_502 >= 1 && $human_502 >= 1){
        $animate_502 = "";
        $denki_502 = "yellow";
    }else{
        $animate_502 = "";
        $denki_502 = "black";
    }
}
//0-504---------------------------------------------------------------
$temperature_504 = "syasin/ondokei.png";
$bg_color_504 = "#fffafa";
if($row504){
    $temp2 = $row504['temperature'];//温度
    $hum2 = $row504['humidity'];//湿度
    
    //温度
    if($temp2 <= 23.5){
        $temperature_504 = "syasin/ondokei1.png";
    }elseif($temp2 <= 24.3){
        $temperature_504 = "syasin/ondokei2.png";
    }else{
        $temperature_504 = "syasin/ondokei3.png";
    }
    //湿度
    if($hum2 <= 12.9){
        $bg_color_504 = "#add8e6";
    } elseif($hum2 <= 15){
        $bg_color_504 = "#00bfff";
    } else {
        $bg_color_504 = "#1e90ff";
    }
}
//照明
$animate_504 = "";
$denki_504 = "#fffafa";
if($row504_syou){
    $lit_504 = $row504_syou['lit'];//照明
    $human_504 = $row504_syou['human_cnt'];//人カウント
    if($lit_504 >= 1 && $human_504 == 0){
        $animate_504 = " animate-denki3";
        $denki_504 = "";
    }elseif($lit_504 >= 1 && $human_504 >= 1){
        $animate_504 = "";
        $denki_504 = "yellow";
    }else{
        $animate_504 = "";
        $denki_504 = "black";
    }
}
//0-506---------------------------------------------------------------
$temperature_506 = "syasin/ondokei.png";
$bg_color_506 = "#fffafa";
if($row506){
    $temp3 = $row506['temperature'];//温度
    $hum3 = $row506['humidity'];//湿度
    
    //温度
    if($temp3 <= 23.5){
        $temperature_506 = "syasin/ondokei1.png";
    }elseif($temp3 <= 24.3){
        $temperature_506 = "syasin/ondokei2.png";
    }else{
        $temperature_506 = "syasin/ondokei3.png";
    }
    //湿度
    if($hum3 <= 5){
        $bg_color_506 = "#add8e6";
    } elseif($hum3 <= 10){
        $bg_color_506 = "#00bfff";
    } else {
        $bg_color_506 = "#1e90ff";
    }
}
//照明
$animate_506 = "";
$denki_506 = "#fffafa";
if($row506_syou){
    $lit_506 = $row506_syou['lit'];//照明
    $human_506 = $row506_syou['human_cnt'];//人カウント
    if($lit_506 >= 1 && $human_506 == 0){
        $animate_506 = " animate-denki3";
        $denki_506 = "";
    }elseif($lit_506 >= 1 && $human_506 >= 1){
        $animate_506 = "";
        $denki_506 = "yellow";
    }else{
        $animate_506 = "";
        $denki_506 = "black";
    }
}

echo "<div class='sita-container'>";
    echo "<div class='tabs'>";
        echo "<div class='tab'>ホーム</div>";
        echo "<div class='tab2'><a href='Detail.php'>詳細</a></div>";
        echo "<div class='tab3'><a href='Search.php'>出席</a></div>";
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
                    echo "<div class='fivefour' style='background-color:{$bg_color_504};'>";
                        echo "<div class='zero'>0-504</div>";
                        echo "<img src='syasin/人.png' class='fhito'>";
                        echo "<img src='{$temperature_504}' class='f_ondo'>";
                        echo "<div class='denki1{$animate_504}' style='background-color:{$denki_504};'></div>";
                        echo "<div class='h_cnt1'>{$human_504}</div>";
                    echo "</div>";
                    //階段1
                    echo "<div class='stairs'></div>";
                    //0-502
                    echo "<div class='fivetwo' style='background-color:{$bg_color_502};'>";
                        echo "<div class='zero'>0-502</div>";
                        echo "<img src='syasin/人.png' class='thito'>";
                        echo "<img src='{$temperature_502}' class='t_ondo'>";
                        echo "<div class='denki2{$animate_502}' style='background-color:{$denki_502};'></div>";
                        echo "<div class='h_cnt2'>{$human_502}</div>";
                    echo "</div>";
                    //階段2
                    echo "<div class='stairs2'></div>";
                    //0-506
                    echo "<div class='xbox'>";
                        echo "<div class='fivesix' style='background-color:{$bg_color_506};'>";
                            echo "<div class='szero'>0-506</div>";
                            echo "<img src='syasin/人.png' class='shito'>";
                            echo "<img src='{$temperature_506}' class='s_ondo'>";
                            echo "<div class='denki3{$animate_506}' style='background-color:{$denki_506};'></div>";
                            echo "<div class='h_cnt3'>{$human_506}</div>";
                        echo"</div>";
                    echo "</div>";
                echo "</div>";
            echo "</div>";
            
            //気温等
            echo "<div class='temp'>";
                if(!$dbconn){
                    echo "接続エラーが発生しました。";
                }else{
                    //0-502の表示
                    if($row502){
                        echo htmlspecialchars($row502['room_num']) . "  ";
                        echo "気温" . htmlspecialchars($row502['temperature']) . "℃ ";
                        echo "湿度" . htmlspecialchars($row502['humidity']) . "% ";
                        echo "気圧" . number_format($row502['pressure'], 0) . "hPa<br>";
                    } else {
                        echo "0-502のデータ取得失敗<br>";
                    }
                    //0-504の表示
                    if($row504){
                        echo htmlspecialchars($row504['room_num']) . "  ";
                        echo "気温" . htmlspecialchars($row504['temperature']) . "℃ ";
                        echo "湿度" . htmlspecialchars($row504['humidity']) . "% ";
                        echo "気圧" . number_format($row504['pressure'], 0) . "hPa<br>";
                    } else {
                        echo "0-504のデータ取得失敗<br>";
                    }
                    //0-506の表示
                    if($row506){
                        echo htmlspecialchars($row506['room_num']) . "  ";
                        echo "気温" . htmlspecialchars($row506['temperature']) . "℃ ";
                        echo "湿度" . htmlspecialchars($row506['humidity']) . "% ";
                        echo "気圧" . number_format($row506['pressure'], 0) . "hPa<br>";
                    } else {
                        echo "0-506のデータ取得失敗<br>";
                    }
                    //接続を閉じる
                    pg_close($dbconn);
                }
            echo "</div>";
        echo "</div>";

        //画面右
        echo "<div class='rightbox'>";
            //温度
            echo "<div class='o_moji'>温度</div>";
            echo "<div class='o_wariai'>0℃　　 ~　　50℃</div>";
            echo "<div class='o_sihyou'>";
                echo "<img src='syasin/ondokei1.png' class='syasin1'>";
                echo "<img src='syasin/ondokei2.png' class='syasin2'>";
                echo "<img src='syasin/ondokei3.png' class='syasin3'>";
            echo "</div>";
            echo "<div class='o_setumei'>17℃　 23℃</div>";
            //湿度
            echo "<div class='s_moji'>湿度</div>";
            echo "<div class='s_wariai'>0%　　~　　100%</div>";
            echo "<div class='s_sihyou'></div>";
            echo "<div class='s_setumei'>30%　 60%</div>";
            //照明
            echo "<div class='sh_moji'>照明</div>";
            echo "<div class='sh_wariai'>点灯　 点滅 　消灯</div>";
            echo "<div class='sh_sihyou'>";
                echo "<div class='syoumei1'></div>";
                echo "<div class='syoumei2 animate-denki3'></div>";
                echo "<div class='syoumei3'></div>";
            echo "</div>";
            echo "<div class='sh_setumei'>点滅は,点灯&人がいない状態</div>";
        echo "</div>";
    echo "</div>";
echo "</div>";
?>
<script>
    //1000ミリ秒 × 60秒 × 60分 = 3,600,000ミリ秒 (1時間)
    setTimeout(function(){
        location.reload();
    }, 10000);
</script>
</body>
</html>