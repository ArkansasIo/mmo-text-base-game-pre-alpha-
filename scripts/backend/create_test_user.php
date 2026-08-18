<?php
/**
 * Provision a disposable local player account using the normal registration path.
 * Refuses to run unless APP_ENV is local/development or ALLOW_TEST_USER=1.
 */
if (!in_array(strtolower((string)(getenv('APP_ENV') ?: 'local')), ['local','development'], true) && getenv('ALLOW_TEST_USER') !== '1') {
    fwrite(STDERR, "Refusing to create a test user outside a local/development environment. Set APP_ENV=local or ALLOW_TEST_USER=1 explicitly.\n"); exit(1);
}
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../base/User.class.php';
$username = (string)(getenv('SGW_TEST_USERNAME') ?: 'tessssssst');
$email = (string)(getenv('SGW_TEST_EMAIL') ?: 'tessssssst@example.local');
$password = (string)(getenv('SGW_TEST_PASSWORD') ?: 'TestPassword!123');
$home = (string)(getenv('SGW_TEST_HOMEWORLD') ?: 'Test World');
$race = (int)(getenv('SGW_TEST_RACE') ?: 1);
if (strlen($password) < 8 || !filter_var($email, FILTER_VALIDATE_EMAIL)) { fwrite(STDERR, "Invalid test-user password or email.\n"); exit(1); }
$u = new User();
if (!$u->addUser($username, $password, 1, $email, $race, $home, '0')) { fwrite(STDERR, "Test-user creation failed; the username or email may already exist.\n"); exit(1); }
echo "Local test user provisioned.\n";
echo "Username: {$username}\nEmail: {$email}\nHome world: {$home}\nPassword: supplied through SGW_TEST_PASSWORD or the documented local default\n";
