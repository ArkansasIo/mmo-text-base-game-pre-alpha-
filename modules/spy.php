<?php
include_once("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }
$s->updatePower($_SESSION['userid']);

$db = $s->db_link;
$myUID = (int)$_SESSION['userid'];

$stmt = $db->prepare("SELECT power.mil_cov, power.mil_anti, userdata.actionTurns
    FROM power INNER JOIN userdata ON power.uid=userdata.uid
    WHERE power.uid=? LIMIT 1");
$stmt->bind_param("i", $myUID);
$stmt->execute();
$me = $stmt->get_result()->fetch_object();

$targetUID = (int)($_GET['id'] ?? 0);
$targetInfo = null;
if ($targetUID > 0 && $targetUID !== $myUID) {
    $stmt = $db->prepare("SELECT u.uname, p.mil_cov, p.mil_anti FROM users u
        INNER JOIN power p ON p.uid=u.uid WHERE u.uid=? LIMIT 1");
    $stmt->bind_param("i", $targetUID);
    $stmt->execute();
    $targetInfo = $stmt->get_result()->fetch_object();
}
?>
<h3>Covert Operations</h3>

<table width="100%"><tr><td valign="top" width="48%">
<strong>Your Covert Stats</strong><br>
Covert Power: <?= number_format($me->mil_cov ?? 0) ?><br>
Anti-Covert:  <?= number_format($me->mil_anti ?? 0) ?><br>
Action Turns: <?= number_format($me->actionTurns ?? 0) ?><br>
</td><td valign="top">
<?php if ($targetInfo): ?>
<strong>Target: <?= htmlspecialchars($targetInfo->uname) ?></strong><br>
Covert Power: <?= number_format($targetInfo->mil_cov) ?><br>
Anti-Covert:  <?= number_format($targetInfo->mil_anti) ?><br>
<?php endif; ?>
</td></tr></table>

<br>
<strong>Send a Spy Mission</strong><br>
<form method="get" action="action.php">
  <input type="hidden" name="atype" value="spy">
  <label>Target UID:<br>
    <input type="number" name="id" value="<?= $targetUID ?: '' ?>" min="1" required style="width:120px">
  </label><br><br>
  <label>Turns to use:<br>
    <input type="number" name="turns" value="1" min="1" max="<?= (int)($me->actionTurns ?? 0) ?>" style="width:80px">
  </label><br><br>
  <button type="submit">Launch Mission</button>
</form>

<br>
<strong>Recent Spy Operations</strong><br>
<?php
$stmt = $db->prepare(
    "SELECT al.actID, al.to_uid, u.uname AS target, al.time, al.success
     FROM actionlog al INNER JOIN users u ON u.uid=al.to_uid
     WHERE al.uid=? AND al.type='spy'
     ORDER BY al.actID DESC LIMIT 20");
$stmt->bind_param("i", $myUID);
$stmt->execute();
$spyLog = $stmt->get_result();
if ($spyLog->num_rows === 0) {
    echo "<p>No spy missions yet.</p>";
} else {
    echo "<table border='0' cellpadding='4'><tr><th>Target</th><th>Time</th><th>Result</th><th>Details</th></tr>";
    while ($row = $spyLog->fetch_object()) {
        $result = $row->success ? '<span style="color:#6f6">Success</span>' : '<span style="color:#f66">Detected</span>';
        echo "<tr><td>" . htmlspecialchars($row->target) . "</td><td>" . htmlspecialchars($row->time) . "</td><td>$result</td>"
            . "<td><a href='javascript:void(0)' onclick=\"sendData('actionLogs','get','" . $row->actID . "')\">View</a></td></tr>";
    }
    echo "</table>";
}

echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>