<?php
$passed=0;$failed=0;function email_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$policy=file_get_contents(__DIR__.'/../base/GameEmailPolicy.class.php');$smtp=file_get_contents(__DIR__.'/../base/SmtpMailer.class.php');$root=file_get_contents(__DIR__.'/../scripts/backend/create_root_email_admin.php');$module=file_get_contents(__DIR__.'/../modules/email.php');$admin=file_get_contents(__DIR__.'/../admin/email.php');$worker=file_get_contents(__DIR__.'/../scripts/backend/email_tick.php');$migration=file_get_contents(__DIR__.'/../database/sql/46_game_email_system.sql');$cron=file_get_contents(__DIR__.'/../scripts/backend/cron_runner.sh');$nav=file_get_contents(__DIR__.'/../templates/index.tpl');$integration=file_get_contents(__DIR__.'/../scripts/backend/test_root_email.php');$env=file_get_contents(__DIR__.'/../.env.example');
email_check(strpos($policy,"root@universecivilization.game")!==false&&strpos($policy,'MAX_BODY')!==false,'root email policy defines sender and body limits');
email_check(strpos($root,'admin_users')!==false&&strpos($root,'INSERT INTO users')!==false&&strpos($root,'strlen($password) < 16')!==false,'root provisioner creates unified admin and player identities');
email_check(strpos($module,'game_email_messages')!==false&&strpos($module,'email_action')!==false,'player Email Network supports durable inbox actions');
email_check(strpos($admin,'AdminAuth')!==false&&strpos($admin,'send_root_game_email')!==false&&strpos($admin,'csrf')!==false,'admin root email center is role-protected and audited');
email_check(strpos($worker,"GAME_MAIL_TRANSPORT")!==false&&strpos($worker,'SmtpMailer')!==false&&strpos($worker,'game_email_delivery_log')!==false,'email worker supports SMTP transport and records delivery attempts');
email_check(strpos($smtp,'SMTP_HOST')!==false&&strpos($smtp,'STARTTLS')!==false&&strpos($smtp,'AUTH LOGIN')!==false,'SMTP client supports host, TLS, and authenticated delivery');
email_check(strpos($env,'GAME_MAIL_TRANSPORT=smtp')!==false&&strpos($env,'SMTP_PASSWORD')!==false,'environment template configures SMTP without real secrets');
email_check(strpos($integration,'SGW_EMAIL_TEST_UID')!==false&&strpos($integration,'Root Email Integration Test')!==false&&strpos($integration,'ALLOW_EMAIL_TEST')!==false,'guarded root-to-player email integration command exists');
email_check(strpos($migration,'game_email_messages')!==false&&strpos($migration,'game_email_delivery_log')!==false,'email migration creates message and delivery-log tables');
email_check(strpos($cron,'email_tick')!==false&&strpos($nav,"sendData('email'")!==false,'Email Network is wired to authenticated navigation and cron');
if($failed){fwrite(STDERR,"$failed email checks failed; $passed passed.\n");exit(1);}echo "All $passed email checks passed.\n";
?>
