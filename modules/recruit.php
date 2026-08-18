<?php
include_once("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
$db = $s->db_link;

$db->query("CREATE TABLE IF NOT EXISTS `recruit_ips` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `recruitID` int(11) NOT NULL DEFAULT 0,
    `uid` int(11) NOT NULL,
    `ip` varchar(45) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uid_ip` (`uid`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1");

$recruitLink = $s->clean_sql($_GET['id'] ?? '', 0);
$msg = $_GET['strErr'] ?? '';
$recruiterName = '';
$error = false;

if ($recruitLink !== '') {
    $stmt = $db->prepare("SELECT u.uid, u.uname FROM users u
        INNER JOIN userdata ud ON ud.uid=u.uid
        WHERE ud.link=? LIMIT 1");
    $stmt->bind_param("s", $recruitLink);
    $stmt->execute();
    $recruiter = $stmt->get_result()->fetch_object();

    if (!$recruiter) {
        $error = true;
        $msg = 'That recruit link does not exist.';
    } else {
        $recruiterUID  = (int)$recruiter->uid;
        $recruiterName = $recruiter->uname;
        $clientIP      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $stmt = $db->prepare("SELECT id FROM recruit_ips WHERE uid=? AND ip=? LIMIT 1");
        $stmt->bind_param("is", $recruiterUID, $clientIP);
        $stmt->execute();
        $already = $stmt->get_result()->fetch_object();

        if ($already) {
            $error = true;
            $msg = 'Your IP has already used this recruit link.';
        } else {
            $db->begin_transaction();
            try {
                $stmt = $db->prepare("UPDATE units SET untrained=untrained+4 WHERE uid=? LIMIT 1");
                $stmt->bind_param("i", $recruiterUID);
                $stmt->execute();

                $stmt = $db->prepare("INSERT IGNORE INTO recruit_ips (recruitID, uid, ip) VALUES (0, ?, ?)");
                $stmt->bind_param("is", $recruiterUID, $clientIP);
                $stmt->execute();

                $db->commit();
            } catch (Throwable $e) {
                $db->rollback();
                $error = true;
                $msg = 'Could not process recruit link. Please try again.';
            }
        }
    }
}
?>
<html>
<head><title>Recruit - <?= htmlspecialchars($subs['{TITLE}'] ?? 'Universe Civilization: Empire At Wars') ?></title></head>
<body>
<center>
<table width="60%" cellspacing="1" cellpadding="8">
<tr><td>
  <?php if ($msg): ?>
    <p><strong><?= htmlspecialchars($msg) ?></strong></p>
  <?php endif; ?>

  <?php if ($error || $recruitLink === ''): ?>
    <strong>Welcome to <?= htmlspecialchars($subs['{TITLE}'] ?? 'Universe Civilization: Empire At Wars') ?>!</strong>
    <p>We are sorry, but your IP has already used this enlistment link, or the link is invalid.<br>
    Why not join and get others to click your own link?<br>
    <a href="../index.php">Join Now</a></p>
  <?php else: ?>
    <strong>Welcome to <?= htmlspecialchars($subs['{TITLE}'] ?? 'Universe Civilization: Empire At Wars') ?>!</strong>
    <p>By clicking this enlistment link you have recruited 4 troops into the armies of
    <strong><?= htmlspecialchars($recruiterName) ?></strong>.<br>
    Join up and build your own empire!<br>
    <a href="../index.php">Join Now</a></p>
  <?php endif; ?>
</td></tr>
</table>
</center>
</body>
</html>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
