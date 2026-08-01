<?php
include_once("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }

$atype = $_GET['atype'] ?? '';
$touid = (int)($_GET['id'] ?? 0);
$turns = max(1, (int)($_GET['turns'] ?? 15));

if (!in_array($atype, ['attack', 'raid', 'spy'])) {
    echo "Invalid action type.";
    exit;
}
if ($touid <= 0) {
    echo "Invalid target.";
    exit;
}
if ($touid === (int)$_SESSION['userid']) {
    echo "You cannot attack yourself.";
    exit;
}

if ($atype === 'attack' || $atype === 'raid') {
    $actID = $s->attack_raid($atype, $touid, $turns);
} else {
    $actID = $s->spy($touid, $turns);
}

if ($actID) {
    header("Location: actionLogs.php?id=" . $actID . "&time=" . microtime());
} else {
    echo "Action failed. Please check your turns and try again.";
}
exit;
?>