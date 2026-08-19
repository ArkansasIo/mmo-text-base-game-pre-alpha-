<?php
require_once __DIR__.'/../base/GameEmailPolicy.class.php';
$passed=0;$failed=0;function mail_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$admin=file_get_contents(__DIR__.'/../admin/email.php');$module=file_get_contents(__DIR__.'/../modules/email.php');$status=file_get_contents(__DIR__.'/../modules/mail_status.php');$js=file_get_contents(__DIR__.'/../js/mail-alerts.js');$header=file_get_contents(__DIR__.'/../templates/header.tpl');$migration=file_get_contents(__DIR__.'/../database/sql/47_mail_broadcast_attachments.sql');$runner=file_get_contents(__DIR__.'/../scripts/backend/db_migrate.sh');
mail_check(GameEmailPolicy::validAttachment('currency','metal','',100),'currency attachment policy accepts whitelisted resource');
mail_check(GameEmailPolicy::validAttachment('item','','blueprint.alpha',2),'item attachment policy accepts safe asset key');
mail_check(!GameEmailPolicy::validAttachment('currency','password','',1),'attachment policy rejects unknown currency');
mail_check(!GameEmailPolicy::validAttachment('equipment','','unsafe key',1),'attachment policy rejects unsafe asset key');
mail_check(strpos($admin,"admin_email_action']??''")!==false&&strpos($admin,"'broadcast'")!==false,'admin email center exposes global broadcast action');
mail_check(strpos($admin,'game_mail_broadcasts')!==false&&strpos($admin,'recipient_count')!==false,'broadcasts persist recipient counts');
mail_check(strpos($admin,'game_email_attachments')!==false&&strpos($admin,'NotificationPolicy::push')!==false,'broadcasts attach rewards and notify every recipient');
mail_check(strpos($module,"email_action']??''")!==false&&strpos($module,'claim')!==false&&strpos($module,'player_mail_assets')!==false,'player mail supports transactional attachment claims');
mail_check(strpos($status,'unread')!==false&&strpos($status,'latest')!==false,'mail status endpoint returns unread count and latest mail');
mail_check(strpos($js,'mail-alert-badge')!==false&&strpos($js,'setInterval(poll,20000)')!==false&&strpos($js,'mail-alert-popup')!==false,'shell polls and displays live mail alerts');
mail_check(strpos($header,'mail-alerts.js')!==false&&strpos($header,'mail-alert-badge')!==false,'authenticated shell loads mail alerts');
mail_check(strpos($migration,'game_mail_broadcasts')!==false&&strpos($migration,'player_mail_assets')!==false&&strpos($runner,'47_mail_broadcast_attachments.sql')!==false,'broadcast attachment migration is registered');
if($failed){fwrite(STDERR,"$failed mail expansion checks failed; $passed passed.\n");exit(1);}echo "All $passed mail expansion checks passed.\n";
?>
