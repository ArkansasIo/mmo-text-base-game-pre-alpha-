<?php
include_once("config.php");
$s = new Game();
if (!empty($_GET['burst']))
{
 echo $s->Bursted($_GET['burst']);
}

?>