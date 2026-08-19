<?php
include_once("config.php");
$s = new Game();
$loginRequired = true;
$publicStatus = $s->connected() ? 'COMMAND NETWORK ONLINE' : 'BACKEND LINK STANDBY';
$publicStatusDetail = $s->connected() ? 'Authentication and universe services responding' : 'Database link requires operator attention';
$loginSetting = $s->query("SELECT setting_value FROM app_settings WHERE setting_key='game_login_required' LIMIT 1");
if ($loginSetting && ($settingRow = $loginSetting->fetch_assoc())) {
    $loginRequired = (string)$settingRow['setting_value'] !== '0';
}
if (!$loginRequired && $s->connected()) {
    $demoQuery = $s->query("SELECT u.uid,u.uname,u.password,u.alevel,ud.rid,ud.progress FROM users u LEFT JOIN userdata ud ON ud.uid=u.uid WHERE u.uid=1 LIMIT 1");
    $demo = $demoQuery ? $demoQuery->fetch_assoc() : null;
    if ($demo) {
        $_SESSION['username']=$demo['uname']; $_SESSION['password']=$demo['password']; $_SESSION['access']=(int)$demo['alevel'];
        $_SESSION['userid']=(int)$demo['uid']; $_SESSION['raceID']=(int)($demo['rid'] ?? 1); $_SESSION['progress']=(int)($demo['progress'] ?? 0);
        $s->loggedIn = true; $s->userName=$demo['uname']; $s->password=$demo['password']; $s->access=(int)$demo['alevel'];
        $s->userid=(int)$demo['uid']; $s->raceID=(int)($demo['rid'] ?? 1); $s->progress=(int)($demo['progress'] ?? 0);
    }
}

if (isset($_GET['logout']) && $_GET['logout']) { User::logOut();} 
if (isset($_POST['submit']) && $_POST['submit'] == "Login")
{
        $s = new User($_POST['user'], $_POST['pass']);
}

if($loginRequired && (!$s->loggedIn || (isset($_GET['logout']) && $_GET['logout'])))
{

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
		<script type="text/javascript" src="js/main.js"></script>
		<script type="text/javascript" src="js/auto.js"></script>
		<script type="text/javascript" src="js/train.js"></script>
		<script type="text/javascript" src="js/images.js"></script>
		<script type="text/javascript" src="js/bbfix.js"></script>
		<script type="text/javascript" src="js/title-audio.js"></script>
    <title>Universe Civilization: Empire at Wars // Command Network</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<LINK REL=STYLESHEET TYPE='text/css' HREF='main.css' />
<link rel="icon" href="favicon.svg" type="image/svg+xml" />
</head>

<body background="images/stars.jpg" onLoad="mainUpdate('login','Login'); MM_preloadImages('images/galaxy1-2.jpg','images/galaxy2-2.jpg','images/galaxy3-2.jpg'); autoclear(); bb_init('divBody', false);">

<div id="divBody">

<div class="public-shell">
  <header class="public-top">
    <div class="public-brand">
      <span class="public-eyebrow">UNIVERSE CIVILIZATION // EMPIRE AT WARS</span>
      <h1>Universe Civilization: Empire at Wars</h1>
      <p>Build your civilization, command fleets, master interstellar gateways, and shape the fate of the galaxy.</p>
    </div>
    <div class="public-actions">
      <button type="button" class="public-btn audio-toggle" id="audio-toggle" aria-pressed="false">Audio On</button>
      <a class="public-btn" href="javascript:void(0)" onClick="mainUpdate('login','Login'); return false" onMouseOver="rollUpDate('Login'); return false" onMouseOut="autoclear(); return false">Civilization Login</a>
      <a class="public-btn secondary" href="javascript:void(0)" onClick="mainUpdate('register','Register To Play'); return false" onMouseOver="rollUpDate('Register To Play'); return false" onMouseOut="autoclear(); return false">Found Your Civilization</a>
      <a class="public-btn admin-btn" href="/admin/">Administrator Console</a>
    </div>
  </header>

  <section class="public-hero">
    <div class="public-hero-left">
      <a href="javascript:void(0)" onClick="mainUpdate('login','Login'); return false" onMouseOver="rollUpDate('Login'); return false" onMouseOut="autoclear(); return false">
        <img src="images/galaxy1.jpg" name="Image12" border="0" id="Image12" onMouseOver="MM_swapImage('Image12','','images/galaxy1-2.jpg',1)" onMouseOut="MM_swapImgRestore()" />
      </a>
    </div>
    <div class="public-hero-right">
      <a href="javascript:void(0)" onClick="mainUpdate('register','Register To Play'); return false" onMouseOver="rollUpDate('Register To Play'); return false" onMouseOut="autoclear(); return false">
        <img src="images/galaxy2.jpg" name="Image11" border="0" id="Image11" onMouseOver="MM_swapImage('Image11','','images/galaxy2-2.jpg',1)" onMouseOut="MM_swapImgRestore()" />
      </a>
      <a href="javascript:void(0)" onClick="mainUpdate('updates','Updates'); return false" onMouseOver="rollUpDate('Updates'); return false" onMouseOut="autoclear(); return false">
        <img src="images/galaxy3.JPG" name="Image13" border="0" id="Image13" onMouseOver="MM_swapImage('Image13','','images/galaxy3-2.jpg',1)" onMouseOut="MM_swapImgRestore()" />
      </a>
    </div>
  </section>

  <section class="title-briefing" id="briefing">
    <div class="briefing-head">
      <div>
        <span class="public-eyebrow">MISSION BRIEFING // PRE-ALPHA NETWORK</span>
        <h2>Build a civilization that survives the frontier.</h2>
        <p>Universe Civilization: Empire at Wars is a persistent browser MMO where every planet, fleet, corporation, market decision, and wormhole expedition contributes to a living strategic universe.</p>
      </div>
      <div class="network-status"><span class="status-dot"></span><strong><?= htmlspecialchars($publicStatus, ENT_QUOTES, 'UTF-8'); ?></strong><small><?= htmlspecialchars($publicStatusDetail, ENT_QUOTES, 'UTF-8'); ?></small></div>
    </div>
    <div class="briefing-actions">
      <a class="public-btn secondary" href="javascript:void(0)" onClick="mainUpdate('register','Register To Play'); return false">Create a civilization</a>
      <a class="public-btn" href="javascript:void(0)" onClick="mainUpdate('login','Login'); return false">Open command login</a>
      <a class="brief-link" href="#systems">Review game systems</a>
      <a class="brief-link" href="#roadmap">View pre-alpha status</a>
    </div>
  </section>

  <section class="title-system-grid" id="systems">
    <article class="title-system-card"><span class="system-index">01 // GENESIS</span><h3>Procedural universes</h3><p>Generate worlds, moons, biomes, governments, races, and starbases from a seeded universe model. Explore a galaxy where each frontier sector has its own strategic identity.</p><span class="system-tags">SEEDS · WORLDS · MOONS</span></article>
    <article class="title-system-card"><span class="system-index">02 // FLEET COMMAND</span><h3>90 ship blueprints</h3><p>Research a full A–Z blueprint catalog, fit high, medium, and low modules, manage CPU and power, then deploy fleets for defense, combat, trade, and exploration.</p><span class="system-tags">FITTING · SHIPYARDS · FLEETS</span></article>
    <article class="title-system-card"><span class="system-index">03 // ECONOMY</span><h3>Industrial production</h3><p>Produce and trade ten resources, including strategic materials and premium Dark Matter. Build a corporation-led economy through markets, research, and rare-item orders.</p><span class="system-tags">10 RESOURCES · MARKETS · RESEARCH</span></article>
    <article class="title-system-card"><span class="system-index">04 // DIPLOMACY</span><h3>Corporations and warfare</h3><p>Organize members, share research pools, coordinate fleet operations, form alliances, manage warfronts, raid territory, and defend the interests of your civilization.</p><span class="system-tags">ALLIANCES · WARFRONTS · ROLES</span></article>
    <article class="title-system-card"><span class="system-index">05 // DARK MATTER</span><h3>Wormhole expeditions</h3><p>Scan unstable signatures, launch timed probes, push deeper for exotic rewards, and decide when the rising collapse risk is no longer worth the potential gain.</p><span class="system-tags">SCANNING · RISK · EXOTICS</span></article>
    <article class="title-system-card"><span class="system-index">06 // OPERATIONS</span><h3>Persistent command loop</h3><p>Production ticks, PvP resolution, expedition settlement, notifications, achievements, and account security keep the command network active between sessions.</p><span class="system-tags">TICKS · ALERTS · PROGRESSION</span></article>
  </section>

  <section class="title-roadmap" id="roadmap">
    <div><span class="public-eyebrow">PRE-ALPHA OPERATIONS STATUS</span><h2>Choose your first strategic objective.</h2></div>
    <div class="roadmap-columns">
      <div><strong>FOUND</strong><p>Register an account, choose your race and government, name your homeworld, and establish your first production base.</p></div>
      <div><strong>EXPAND</strong><p>Research blueprints, fit ships, claim territory, build orbital infrastructure, and grow your population and resource network.</p></div>
      <div><strong>COMMAND</strong><p>Join a corporation, trade rare modules, coordinate missions, defend your alliance, and explore the unknown through Dark Matter.</p></div>
    </div>
  </section>

  <section class="public-content-grid">
    <aside class="public-panel public-news">
      <span class="public-eyebrow">COMMAND NETWORK ONLINE</span>
      <h3>Enter the frontier</h3>
      <div id="up2date"></div>
      <h4>Player access</h4>
      <div id="rollover">Secure login required</div>
    </aside>
    <main class="public-panel public-main">
	  <?php
	if (isset($_POST['submit']) && $_POST['submit']=="Register")
{
	$number = $_POST['number'];
	if(md5($number) != $_SESSION['image_value'])
	{
	echo 'Validation string not valid! Please try again!<br>';
	}
	else
	{
	$s->addUser($_POST['user'], $_POST['pass'], 1, $_POST['email'], $_POST['rid'], $_POST['hpname'], $_SERVER["REMOTE_ADDR"], (int)($_POST['government_id'] ?? 1));
	}
}
?>
      <div id="mainDisplay"></div>
      <div class="public-footnote">
        <span>Version 0.9 pre-alpha · Industrial Blue Command Network</span><span>Graphics and systems by <a href="https://github.com/ArkansasIo" target="_blank" rel="noopener noreferrer">github.com/ArkansasIo</a></span>
      </div>
    </main>
  </section>
</div>

</div>
</body>
</html>

<?php
}
else {

showPage();

}

?>