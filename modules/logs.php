<?php
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }
$s->updatePower($_SESSION['userid']);
?>
<center>
    <a href="javascript:void(0)" onclick="sendData('logs','get','id','attack');return false">Attack</a> | 
    <a href="javascript:void(0)" onclick="sendData('logs','get','id','raid');return false">Raid</a> | 
    <a href="javascript:void(0)" onclick="sendData('logs','get','id','spy');return false">Spy</a> | 
    <a href="javascript:void(0)" onclick="sendData('logs','get','id','sab');return false">Sabotage</a>
</center>
<?php
$atype = in_array($_GET['atype'] ?? '', ['attack','raid','spy','sab']) ? $_GET['atype'] : 'attack';
$s->actionLog($atype);

echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>