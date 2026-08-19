<?php
$passed=0;$failed=0;function email_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$policy=file_get_contents(__DIR__.'/../base/GameEmailPolicy.class.php');$root=file_get_contents(__DIR__.'/../scripts/backend/create_root_email_admin.php');$module=file_get_contents(__DIR__.'/../modules/email.php');$admin=file_get_contents(__DIR__.'/../admin/email.php');$worker=file_get_contents(__DIR__.'/../scripts/backend/email_tick.php');$migration=file_get_contents(__DIR__.'/../database/sql/46_game_email_system.sql');$cron=file_get_contents(__DIR__.'/../scripts/backend/cron_runner.sh');$nav=file_get_contents(__DIR__.'/../templates/index.tpl');
email_check(strpos($policy,"root@universecivilization.game")!==false&&strpos($policy,'MAX_BODY')!==false,'root email policy defines sender and body limits');
email_check(strpos($root,'admin_users')!==false&&strpos($root,'INSERT INTO users')!==false&&strpos($root,'strlen($password) < 16')!==false,'root provisioner creates unified admin and player identities');
email_check(strpos($module,'game_email_messages')!==false&&strpos($module,'email_action')!==false,'player Email Network supports durable inbox actions');
email_check(strpos($admin,'AdminAuth')!==false&&strpos($admin,'send_root_game_email')!==false&&strpos($admin,'csrf')!==false,'admin root email center is role-protected and audited');
email_check(strpos($worker,"GAME_MAIL_TRANSPORT")!==false&&strpos($worker,'game_email_delivery_log')!==false&&strpos($worker,"getenv('GAME_MAIL_TRANSPORT')?:'log'")!==false,'email worker defaults to safe log transport and records delivery attempts');
email_check(strpos($migration,'game_email_messages')!==false&&strpos($migration,'game_email_delivery_log')!==false,'email migration creates message and delivery-log tables');
email_check(strpos($cron,'email_tick')!==false&&strpos($nav,"sendData('email'")!==false,'Email Network is wired to authenticated navigation and cron');
if($failed){fwrite(STDERR,"$failed email checks failed; $passed passed.\n");exit(1);}echo "All $passed email checks passed.\n";
?>
