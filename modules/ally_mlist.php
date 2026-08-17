<?php
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || empty($_GET['time'])){ header("Location: ../index.php"); exit; }
$s->updatePower($_SESSION['userid']);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page <= 0) {
	$page = 1;
}

$allyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($allyId <= 0) {
	$base = $s->baseVars();
	$allyId = isset($base->allyid) ? (int)$base->allyid : 0;
}

$rankings = [];
$allyinfo = (object)['allyname' => 'No Alliance'];
if ($allyId > 0) {
	$rankings = $s->allyRankings($page, $allyId);
	$allyinfo = $s->getallyinfo($allyId);
}
?>
<table width="100%" border="0">
  <tr>
    <td>Name</td>
    <td>Rank</td>
    <td>Army Size </td>
    <td>Race</td>
    <td>Treasury</td>
  </tr>
<?php
for($x = 0; $x < count($rankings); $x++)
{
	if(isset($rankings[$x]['rank']) && $rankings[$x]['rank'] != 0){?>
    <tr>
  	  <td><a href='javascript:void(0)' onclick="sendData('user','get','<?= $rankings[$x]['uid']; ?>')"><?= $rankings[$x]['name']; ?></a>[<?= $allyinfo->allyname;?>]</a></td>
    	<td><?= $rankings[$x]['rank']; ?></td>
    	<td><?= $rankings[$x]['army']; ?></td>
    	<td><?= $rankings[$x]['race']; ?></td>
    	<td><?= $rankings[$x]['cash']; ?></td>
  		</tr>
	
<?php
}
}
?>
</table>
<?php
echo "Query Count: ".$s->queryCount."<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>