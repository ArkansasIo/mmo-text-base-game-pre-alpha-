<?php
include_once("config.php");
$s = new Game();
$loginRequired = true;
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
        <span>Graphics and systems by <a href="https://github.com/ArkansasIo" target="_blank" rel="noopener noreferrer">github.com/ArkansasIo</a></span>
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