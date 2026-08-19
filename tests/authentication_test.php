<?php
$passed=0;$failed=0;
function auth_check(bool $value,string $name):void{global $passed,$failed;if($value){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$u=file_get_contents(__DIR__.'/../base/User.class.php');
$a=file_get_contents(__DIR__.'/../base/AdminAuth.class.php');
$api=file_get_contents(__DIR__.'/../api/auth.php');
$test=file_get_contents(__DIR__.'/../scripts/backend/create_test_user.php');
$admin=file_get_contents(__DIR__.'/../scripts/backend/create_default_admin.php');
$provisioner=file_get_contents(__DIR__.'/../scripts/backend/create_admin.php');
$index=file_get_contents(__DIR__.'/../index.php');
auth_check(strpos($u,'strlen($password) < 8')!==false,'player password minimum is enforced');
auth_check(strpos($u,'filter_var($email, FILTER_VALIDATE_EMAIL)')!==false,'player email validation is enforced');
auth_check(strpos($u,'INSERT INTO')!==false&&strpos($u,'users')!==false,'registration creates player identity');
auth_check(strpos($u,'userdata')!==false,'registration creates player game state');
auth_check(strpos($u,'$_SESSION[\'userid\']')!==false,'player login establishes user session');
auth_check(strpos($a,'password_verify')!==false,'administrator passwords use password_verify');
auth_check(strpos($a,"['moderator'=>1,'operator'=>2,'superadmin'=>3]")!==false,'administrator role hierarchy is defined');
auth_check(strpos($api,"if (\$_SERVER['REQUEST_METHOD'] !== 'POST')")!==false,'authentication API requires POST');
auth_check(strpos($api,'session_regenerate_id(true)')!==false,'player API login regenerates the session identifier');
auth_check(strpos($api,'Credentials were not accepted')!==false,'authentication errors are generic');
auth_check(strpos($test,'APP_ENV')!==false&&strpos($test,'ALLOW_TEST_USER')!==false,'test-user command has environment safety guard');
auth_check(strpos($admin,'SGW_ADMIN_PASSWORD')!==false&&strpos($admin,'create_admin.php')!==false,'default admin wrapper requires environment password');
auth_check(strpos($provisioner,'INSERT INTO users')!==false&&strpos($provisioner,'legacyPassword')!==false,'administrator provisioning synchronizes the shared player account');
auth_check(strpos($a,'$_SESSION[\'userid\']')!==false&&strpos($a,'admin_users')!==false,'admin control plane recognizes authenticated shared player sessions');
auth_check(strpos($index,'Administrator Console')===false&&strpos($index,'Civilization Login')!==false,'public title page exposes only the unified login entry');
if($failed){fwrite(STDERR,"$failed authentication checks failed; $passed passed.\n");exit(1);}echo "All $passed authentication checks passed.\n";
?>
