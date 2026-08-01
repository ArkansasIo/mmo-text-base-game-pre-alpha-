<?php
include_once("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }
$s->updatePower($_SESSION['userid']);

$actID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($actID > 0) {
    if (!$s->getActID($actID)) {
        echo "<p>Sorry, that action report does not exist or you are not authorized to view it.</p>";
    }
} else {
    echo "<p>No action ID specified.</p>";
}
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>