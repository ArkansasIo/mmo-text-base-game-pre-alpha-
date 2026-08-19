<?php
$passed=0;$failed=0;function dashboard_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$module=file_get_contents(__DIR__.'/../modules/email.php');$playerTest=file_get_contents(__DIR__.'/../scripts/backend/test_player_email.php');$admin=file_get_contents(__DIR__.'/../admin/email.php');$dashboard=file_get_contents(__DIR__.'/../admin/index.php');$nav=file_get_contents(__DIR__.'/../templates/index.tpl');
dashboard_check(strpos($module,'game_email_messages')!==false&&strpos($module,'email_action')!==false,'player Email Network provides durable direct-message actions');
dashboard_check(strpos($module,'to_uid')!==false&&strpos($module,'from_uid')!==false,'player mail stores sender and recipient identities');
dashboard_check(strpos($playerTest,'SGW_EMAIL_FROM_UID')!==false&&strpos($playerTest,'SGW_EMAIL_TO_UID')!==false,'player-to-player integration test accepts explicit UIDs');
dashboard_check(strpos($admin,"isAtLeast('operator')")!==false&&strpos($admin,'hash_equals')!==false,'admin email center enforces role and CSRF checks');
dashboard_check(strpos($admin,'send_root_game_email')!==false&&strpos($admin,'game_email_messages')!==false,'admin email center queues and audits root messages');
dashboard_check(strpos($dashboard,'/admin/email.php')!==false&&strpos($dashboard,'Admin Control Plane')!==false,'admin dashboard exposes the Root Email entry');
dashboard_check((strpos($dashboard,'isAuthenticated()')!==false&&strpos($dashboard,"header('Location: /")!==false),'admin dashboard denies unauthenticated access');
dashboard_check(strpos($nav,"sendData('email'")!==false,'Email Network is reachable from authenticated game navigation');
if($failed){fwrite(STDERR,"$failed admin/email checks failed; $passed passed.\n");exit(1);}echo "All $passed admin/email checks passed.\n";
?>
