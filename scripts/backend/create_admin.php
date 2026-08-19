<?php
require_once __DIR__ . '/../../config.php';
$username = trim((string)(getenv('SGW_ADMIN_USERNAME') ?: 'admin'));
$email = trim((string)(getenv('SGW_ADMIN_EMAIL') ?: 'admin@localhost'));
$password = (string)(getenv('SGW_ADMIN_PASSWORD') ?: '');
$role = (string)(getenv('SGW_ADMIN_ROLE') ?: 'superadmin');
if ($username === '' || strlen($password) < 12 || !in_array($role, ['superadmin','operator','moderator'], true)) {
    fwrite(STDERR, "Usage: SGW_ADMIN_USERNAME=admin SGW_ADMIN_EMAIL=admin@example.com SGW_ADMIN_PASSWORD='12+ chars' SGW_ADMIN_ROLE=superadmin php scripts/backend/create_admin.php\n");
    exit(1);
}
$db = new mysqli($conf['db_server'], $conf['db_username'], $conf['db_password'], $conf['db_name']);
if ($db->connect_error) { fwrite(STDERR, "Database connection failed.\n"); exit(1); }
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO admin_users (username,email,password_hash,role) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE email=VALUES(email), password_hash=VALUES(password_hash), role=VALUES(role), is_active=1");
$stmt->bind_param('ssss', $username, $email, $hash, $role);
if (!$stmt->execute()) { fwrite(STDERR, "Administrator provisioning failed.\n"); exit(1); }
$legacyPassword = md5(crypt($password, '.u55ybcbC,ufzQu2'));
$shared = $db->prepare("UPDATE users SET email=?, password=?, alevel=1 WHERE uname=?");
if (!$shared) { fwrite(STDERR, "Shared account preparation failed.\n"); exit(1); }
$shared->bind_param('sss', $email, $legacyPassword, $username);
if (!$shared->execute()) { fwrite(STDERR, "Shared player account synchronization failed.\n"); exit(1); }
if ($shared->affected_rows === 0) {
    $insertShared = $db->prepare("INSERT INTO users (uname,email,allyid,lastLogin,arank,ip,password,alevel) VALUES (?,?,0,0,0,0,?,1)");
    if (!$insertShared) { fwrite(STDERR, "Shared account insert preparation failed.\n"); exit(1); }
    $insertShared->bind_param('sss', $username, $email, $legacyPassword);
    if (!$insertShared->execute()) { fwrite(STDERR, "Shared player account creation failed.\n"); exit(1); }
}
echo "Administrator '$username' provisioned with role '$role' and unified player login.\n";
?>
