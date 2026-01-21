<?php
function handleInRequest($dbconn, $data) {
    if ($data['data'] === 'kamei') {
        $result = pg_query($dbconn, "SELECT * from kamei");

        $kamei = [];
        if ($result){
            $rows = pg_fetch_all($result);
            if ($rows){
                foreach ($rows as $row) {
                    $kamei[] = [
                        'kamei_id' => $row['kamei_id'],
                        'kamei_mei' => $row['kamei_mei']
                    ];
                }
            }
        }
        return ["status" => true,"kamei" => $kamei];
    }

    if ($data['data'] === 'user_id' && isset($data['user_id_min'])) {
        $user_id_min = $data['user_id_min'];
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
        return ["status" => true, "numbers" => $numbers];
    }

    if ($data['data'] === 'user_name' && isset($data['user_id'])) {
        $user_id = $data['user_id'];
        $sql = "SELECT name FROM account WHERE user_id = $1";
        $result = pg_query_params($dbconn, $sql, [$user_id]);
            
        $reponse = [];
        if ($result) {
            $row = pg_fetch_assoc($result);
            if ($row) {
                $reponse = ["status" => true, "username" => $row['name']];
            } else {
                $reponse = ["status" => false];
            }
        } else {
            $reponse = ["status" => false];
        }
        return $reponse;
    }
    return ["status" => false];
}
?>