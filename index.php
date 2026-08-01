<?php
include_once("config.php");
$s = new Game();
if (isset($_GET['logout']) && $_GET['logout']) { User::logOut();} 
if (isset($_POST['submit']) && $_POST['submit'] == "Login")
{
        $s = new User($_POST['user'], $_POST['pass']);
}

if(!$s->loggedIn || (isset($_GET['logout']) && $_GET['logout']))
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
		<link rel="meta" href="http://codenamelantea.com/labels.rdf" type="application/rdf+xml" title="ICRA labels" />
<meta http-equiv="pics-Label" content='(pics-1.1 "http://www.icra.org/pics/vocabularyv03/" l gen true for "http://codenamelantea.com" r (n 0 s 0 v 0 l 2 oa 0 ob 0 oc 0 od 0 oe 0 of 0 og 0 oh 0 c 3) gen true for "http://www.codenamelantea.com" r (n 0 s 0 v 0 l 2 oa 0 ob 0 oc 0 od 0 oe 0 of 0 og 0 oh 0 c 3))' />
    <title>Universe: Empires at War</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<LINK REL=STYLESHEET TYPE='text/css' HREF='main.css' />
</head>

<body background="images/stars.jpg" onLoad="mainUpdate('login','Login'); MM_preloadImages('images/galaxy1-2.jpg','images/galaxy2-2.jpg','images/galaxy3-2.jpg'); autoclear(); bb_init('divBody', false);">

<div id="divBody">

<div class="public-shell">
  <header class="public-top">
    <div class="public-brand">
      <h1>Universe: Empires at War</h1>
      <p>Turn-based Stargate strategy command network</p>
    </div>
    <div class="public-actions">
      <a class="public-btn" href="javascript:void(0)" onClick="mainUpdate('login','Login'); return false" onMouseOver="rollUpDate('Login'); return false" onMouseOut="autoclear(); return false">Pilot Login</a>
      <a class="public-btn secondary" href="javascript:void(0)" onClick="mainUpdate('register','Register To Play'); return false" onMouseOver="rollUpDate('Register To Play'); return false" onMouseOut="autoclear(); return false">Create Account</a>
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
      <h3>Command Feed</h3>
      <div id="up2date"></div>
      <h4>Status</h4>
      <div id="rollover">Login</div>
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
	$s->addUser($_POST['user'], $_POST['pass'], 1, $_POST['email'], $_POST['rid'], $_POST['hpname'],$_SERVER["REMOTE_ADDR"]);
	}
}
?>
      <div id="mainDisplay"></div>
      <div class="public-footnote">
        <span>Graphics by Fraehr</span>
        <a href="http://www.icra.org/sitelabel/" target="_blank"><img src="images/icra.gif" alt="ICRA" /></a>
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