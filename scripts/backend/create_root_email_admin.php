<?php
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
require_once __DIR__ . '/../../config.php';
$username = trim((string)(getenv('SGW_ROOT_USERNAME') ?: 'root'));
$email = trim((string)(getenv('SGW_ROOT_EMAIL') ?: 'root@universecivilization.game'));
$password = (string)(getenv('SGW_ROOT_PASSWORD') ?: '');
if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 16) {
    fwrite(STDERR, "Set SGW_ROOT_USERNAME, SGW_ROOT_EMAIL, and SGW_ROOT_PASSWORD (16+ characters).\n"); exit(1);
}
$db = new mysqli($conf['db_server'], $conf['db_username'], $conf['db_password'], $conf['db_name']);
if ($db->connect_error) { fwrite(STDERR, "Database connection failed.\n"); exit(1); }
$hash = password_hash($password, PASSWORD_DEFAULT); $legacy = md5(crypt($password, '.u55ybcbC,ufzQu2'));
$admin = $db->prepare("INSERT INTO admin_users(username,email,password_hash,role,is_active) VALUES(?,?,?,'superadmin',1) ON DUPLICATE KEY UPDATE email=VALUES(email),password_hash=VALUES(password_hash),role='superadmin',is_active=1");
if (!$admin) { fwrite(STDERR, "Root admin preparation failed.\n"); exit(1); }
$admin->bind_param('sss',$username,$email,$hash); if (!$admin->execute()) { fwrite(STDERR, "Root admin creation failed.\n"); exit(1); }
$user = $db->prepare("INSERT INTO users(uname,email,allyid,lastLogin,arank,ip,password,alevel) VALUES(?,?,0,0,0,0,?,1) ON DUPLICATE KEY UPDATE email=VALUES(email),password=VALUES(password),alevel=1");
if (!$user) { fwrite(STDERR, "Root shared-user preparation failed.\n"); exit(1); }
$user->bind_param('sss',$username,$email,$legacy); if (!$user->execute()) { fwrite(STDERR, "Root shared-user creation failed.\n"); exit(1); }
// Older local databases may contain duplicate legacy rows for the root username.
// Synchronize every matching row so username and email login resolve consistently.
$sync = $db->prepare("UPDATE users SET email=?, password=?, alevel=1 WHERE uname=? OR email=?");
if (!$sync) { fwrite(STDERR, "Root shared-user synchronization failed.\n"); exit(1); }
$sync->bind_param('ssss', $email, $legacy, $username, $email);
if (!$sync->execute()) { fwrite(STDERR, "Root shared-user synchronization failed.\n"); exit(1); }
echo "Root administrator '$username' unified with $email.\n";
?>
