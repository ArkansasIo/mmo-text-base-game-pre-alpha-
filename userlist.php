<?php
include("config.php");

header('Content-Type: application/json; charset=UTF-8');

$s = new Game();
$term = trim((string)($_GET['val'] ?? ''));
$result = [];

if ($term !== '' && $s->db_link instanceof mysqli) {
    $query = "SELECT users.uname, userdata.uid, race.r_name AS race, rank.overall AS rank
              FROM users
              INNER JOIN userdata ON userdata.uid = users.uid
              INNER JOIN race ON race.rid = userdata.rid
              INNER JOIN rank ON rank.uid = userdata.uid
              WHERE users.uname LIKE ?
              ORDER BY rank.overall ASC
              LIMIT 15";
    $stmt = $s->db_link->prepare($query);
    if ($stmt) {
        $searchVal = $term . '%';
        $stmt->bind_param('s', $searchVal);
        if ($stmt->execute()) {
            $rows = $stmt->get_result();
            if ($rows) {
                while ($data = $rows->fetch_assoc()) {
                    $result[] = [
                        (string)$data['uname'],
                        (string)$data['race'],
                        (string)$data['rank'],
                        (int)$data['uid']
                    ];
                }
            }
        }
        $stmt->close();
    }
}

echo json_encode(['result' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
