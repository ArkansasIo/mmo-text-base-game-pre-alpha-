<?php
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || empty($_GET['time'])) {
    header("Location: ../index.php"); exit;
}
$s->updatePower($_SESSION['userid']);

$statusMessage = "";
$uid = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_SESSION['userid'];

if (isset($_GET['atype']) && $_GET['atype'] === 'set_commander') {
  $statusMessage = $s->setCommander($uid);
} elseif (isset($_GET['atype']) && $_GET['atype'] === 'clear_commander') {
  $statusMessage = $s->clearCommander();
} elseif (!empty($_POST) && isset($_GET['atype']) && $_GET['atype'] === 'support') {
  $supportType = isset($_POST['supportType']) ? (string)$_POST['supportType'] : '';
  $supportAmount = isset($_POST['supportAmount']) ? (int)$_POST['supportAmount'] : 0;
  $statusMessage = $s->sendSupport($uid, $supportType, $supportAmount);
}

$user = $s->getUserInfo($uid);
$planets = $s->getUserPlanets($uid);
$myTurns = $s->getActionTurnsByUid((int)$_SESSION['userid']);
$myBank = $s->bank();
$myPersonnel = $s->getPersonnel((int)$_SESSION['userid']);
$myUntrained = $myPersonnel ? (int)$myPersonnel->uuCount : 0;
?>
<?php if ($statusMessage !== "") { ?>
<div><strong><?= htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8'); ?></strong></div>
<?php } ?>
<table width="100%" border="0">
  <tr>
    <td width="56%"><table width="100%" border="0">
 	  <tr align="left" valign="top">
	    <td>User ID</td>
		<td><?= htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?></td>
	  </tr>
      <tr align="left" valign="top">
        <td width="30%">Name</td>
        <td width="70%"><?= htmlspecialchars($user->userName, ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
      <tr align="left" valign="top">
        <td>Commander</td>
        <td><?php if ($user->cmdrName == "None") { echo "None"; } else { ?> <a href="javascript:void(0)" onclick="sendData('user','get','<?= htmlspecialchars($user->cmdrID, ENT_QUOTES, 'UTF-8'); ?>'); return false"><?= htmlspecialchars($user->cmdrName, ENT_QUOTES, 'UTF-8'); } ?></a></td>
      </tr>
      <tr align="left" valign="top">
        <td>Race</td>
        <td><?= htmlspecialchars($user->race, ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
      <tr align="left" valign="top">
        <td>Rank</td>
        <td><?= htmlspecialchars($user->rank, ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
      <tr align="left" valign="top">
        <td>Army Size </td>
        <td><?= htmlspecialchars($user->armySize, ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
      <tr align="left" valign="top">
        <td>Treasury</td>
        <td><?= htmlspecialchars($user->onHand, ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
      <tr align="left" valign="top">
        <td>Relation</td>
        <td>
        <?php if ($uid !== (int)$_SESSION['userid']) { ?>
          <a href="javascript:void(0)" onclick="sendData('user','get','<?= htmlspecialchars((string)$uid, ENT_QUOTES, 'UTF-8'); ?>','set_commander'); return false;">Make This My Commander</a>
        <?php } else { ?>
          <a href="javascript:void(0)" onclick="sendData('user','get','<?= htmlspecialchars((string)$uid, ENT_QUOTES, 'UTF-8'); ?>','clear_commander'); return false;">Clear Commander</a>
        <?php } ?>
        </td>
      </tr>
    </table></td>
    <td width="44%" rowspan="5" align="center" valign="top"><table width="100%" border="0">
      <tr>
        <td colspan="3">Planets</td>
        </tr>
      <tr>
        <td>Name</td>
        <td>Size</td>
        <td>Bonus</td>
      </tr>
      <?php if (count($planets) > 0) { ?>
      <?php foreach ($planets as $planet) { ?>
      <tr>
        <td><?= htmlspecialchars($planet['name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?= htmlspecialchars($planet['size'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?= htmlspecialchars($planet['bonus'], ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
      <?php } ?>
      <?php } else { ?>
      <tr>
        <td colspan="3" align="center">No planets found.</td>
      </tr>
      <?php } ?>
    </table>
      <table width="100%" border="0">
        <tr>
          <td colspan="3">Officers</td>
        </tr>
        <tr>
          <td>Name</td>
          <td>Race</td>
          <td>Rank</td>
        </tr>
          <?php
  $offi = $s->getOfficers($uid);
  for($x = 0; $x < count($offi); $x++) {
  	echo "<tr><td><a href=\"javascript:void(0)\" onclick=\"sendData('user','get','".htmlspecialchars($offi[$x]["uid"], ENT_QUOTES, 'UTF-8')."'); return false\">".htmlspecialchars($offi[$x]["name"], ENT_QUOTES, 'UTF-8')."</a> </td><td>" .htmlspecialchars($offi[$x]["race"], ENT_QUOTES, 'UTF-8')."</td><td>".htmlspecialchars($offi[$x]["rank"], ENT_QUOTES, 'UTF-8')."</td></tr>";
  }
    echo "<tr> <td colspan='3'>Number of Officers: ".count($offi)."</td> </tr>";
  ?>
      </table></td>
  </tr>
  <tr>
    <td height="60"><table width="100%" border="0" align="center">
      <tr>
        <td colspan="3" align="center" valign="top"><strong>Actions</strong></td>
        </tr>
      <tr>
        <td align="center" valign="top"><a href="javascript:void(0)" onclick="sendData('sendmessage','get','<?= htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>'); return false;">Send Message </a></td>
        <td align="center" valign="top"><a href="javascript:void(0)" onclick="sendData('action','get','<?= htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>','spy'); return false;">Spy</a></td>
        <td align="center" valign="top">Sabotage</td>
      </tr>
      <tr>
        <td align="center" valign="top"><a href="javascript:void(0)" onclick="sendData('action','get','<?= htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>','attack'); return false;">Attack</a></td>
        <td align="center" valign="top"><a href="javascript:void(0)" onclick="sendData('action','get','<?= htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>','raid'); return false;">Raid</a></td>
        <td align="center" valign="top">&nbsp;</td>
      </tr>
      <tr>
        <td colspan="3" align="center" valign="top"><strong>Fleet Action</strong></td>
        </tr>
      <tr>
        <td align="center" valign="top">Attack</td>
        <td align="center" valign="top">Spy</td>
        <td align="center" valign="top">Sabotage</td>
      </tr>
      <tr>
        <td align="center" valign="top">Attack</td>
        <td align="center" valign="top">Raid</td>
        <td align="center" valign="top">Conquere Planet </td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td><table width="100%" border="0">
      <tr>
        <td colspan="3" align="center" valign="top"><strong>Relations</strong></td>
        </tr>
      <tr>
        <td align="center" valign="top">War</td>
        <td align="center" valign="top">Neutral</td>
        <td align="center" valign="top">Peace</td>
      </tr>
      <tr>
        <td rowspan="2" align="center" valign="top">&nbsp;</td>
        <td align="center" valign="top">Make This My Commander </td>
        <td rowspan="2" align="center" valign="top">&nbsp;</td>
      </tr>
      <tr>
        <td align="center" valign="top">(all relations are 24 hours minimum) </td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td><table width="100%" border="0">
      <tr>
        <td height="21" colspan="3" align="center" valign="top"><p><strong>Command Relations</strong> <br />
        (affects You and All your officers and all of their officers ...etc) </p>
          </td>
        </tr>
      <tr>
        <td align="center" valign="top">Total War </td>
        <td align="center" valign="top"> Neutral </td>
        <td align="center" valign="top">Total Peace</td>
      </tr>
      <tr>
        <td colspan="3" align="center" valign="middle">&nbsp;</td>
        </tr>
    </table></td>
  </tr>
  <tr>
    <td><table width="100%" border="0">
      <tr>
        <td align="center" valign="top"><strong>Supporter Status</strong></td>
      </tr>
      <tr>
        <td align="center" valign="top">Your available resources: <?= number_format((int)$myBank->onHand); ?> Naquadah, <?= number_format($myTurns); ?> Turns, <?= number_format($myUntrained); ?> Untrained Units</td>
      </tr>
      <tr>
        <td align="center" valign="top">
          <?php if ($uid !== (int)$_SESSION['userid']) { ?>
          <form action="javascript:void(0)">
            <input type="hidden" name="supportTarget" value="<?= htmlspecialchars((string)$uid, ENT_QUOTES, 'UTF-8'); ?>" />
            <label for="supportType"><strong>Send Resource</strong></label>
            <select name="supportType" id="supportType">
              <option value="naq">Naquadah</option>
              <option value="turns">Turns</option>
              <option value="units">Untrained Units</option>
            </select>
            <input type="text" name="supportAmount" id="supportAmount" value="0" size="10" />
            <input type="submit" name="supportSubmit" value="Send" onclick="this.disabled=true; this.value='Sending...'; sendData('user','post','<?= htmlspecialchars((string)$uid, ENT_QUOTES, 'UTF-8'); ?>','support');" />
          </form>
          <?php } else { ?>
          Open another player's profile to send support.
          <?php } ?>
          <br />
<h6>1% of resources transferred will be paid to the broker, such is the cost of giving people stuff. <br /><br />Note that the function is to GIVE - not lend. If you GIVE resources to someone, the game administration has NO ability to return them to you. Place your trust wisely, or risk learning one of the lessons the cosmos can teach the hard way.</h6></td>
      </tr>
    </table></td>
  </tr>
</table>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
