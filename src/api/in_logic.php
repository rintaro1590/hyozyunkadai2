<?php
function handleInRequest($dbconn, $data) {
    $user_id_min = $data['user_id'] ?? '';

    if ($user_id_min) {
        $user_id_max = $user_id_min + 100;
        $sql = "SELECT user_id FROM account WHERE user_id > $1 and user_id < $2 ORDER BY user_id";
        $result = pg_query_params($dbconn, $sql, [$user_id_min, $user_id_max]);
        
        $numbers = [];
        if ($result) {
            $rows = pg_fetch_all($result);
            if ($rows) {
                foreach ($rows as $row) {
                    $numbers[] = $row['user_id'] - $user_id_min;
                }
            }
        }
        return ["status" => "success", "numbers" => $numbers];
    }

    if (isset($data['kamei'])){
        $result = pg_query_params($dbconn, "SELECT * from kamei");

        $kamei = [];
        if ($result){
            $rows = pg_fetch_all($result);
            if ($rows){
                foreach ($rows as $row) {
                    $kamei['kamei_id'] = $row['kamei_id'];
                    $kamei['kamei_mei'] = $row['kamei_mei'];
                }
            }
        }
        return ["status" => true,"kamei" => $kamei];
    }
    return ["status" => "error", "message" => "Parameter missing"];
}
?>