<?php
// Config File
session_cache_expire(180);
session_start();

function cfg_env($key, $default) {
	$value = getenv($key);
	return ($value === false || $value === '') ? $default : $value;
}

// General Information
$subs['{TITLE}'] = cfg_env('SGW_TITLE', "Universe Civilization: Empire At Wars");					# Name of site(header)
$subs['{SUBTITLE}'] = cfg_env('SGW_SUBTITLE', "The place to get your war on!");	# Second header(subhead)
$subs['{ADMIN_EMAIL}'] = cfg_env('SGW_ADMIN_EMAIL', "test.com");			# Person to email if something goes wrong
$subs['{HEAD_STUFF}'] = "";								# Stuff to put in <head>(left blank intentionally)

// Database Information
$conf['db_server'] = cfg_env('SGW_DB_HOST', "localhost");					# IP or hostname of DB server(usually localhost)
$conf['db_name']  = cfg_env('SGW_DB_NAME', "sgw");					# Name of DB within the server
$conf['db_username']  = cfg_env('SGW_DB_USER', "sgw");							# Username for DB
$conf['db_password']  = cfg_env('SGW_DB_PASS', "sgwpass");					# Password for DB
$conf['db_prefix'] = "";							# Prefix for DB tables
// Set Error Reporting
//error_reporting(E_ALL | E_STRICT);

define("PATH", dirname(__FILE__));
define("SCRIPT_PATH",PATH."/base/");
define("TEMPLATES_PATH",PATH."/templates/");
define("DEBUG",false);

include(SCRIPT_PATH."Chive.class.php");
include(SCRIPT_PATH."User.class.php");
include(SCRIPT_PATH."Debug.class.php");
include(SCRIPT_PATH."functions.php");
include(SCRIPT_PATH."Game.class.php");

// Optional developer/machine specific overrides.
$localConfig = PATH . "/config.local.php";
if (is_file($localConfig)) {
	include($localConfig);
}
?>
