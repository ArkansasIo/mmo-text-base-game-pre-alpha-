<?php
$passed = 0;
$failed = 0;
function account_check(bool $value, string $name): void
{
    global $passed, $failed;
    if ($value) {
        $passed++;
        echo "PASS: $name\n";
    } else {
        $failed++;
        echo "FAIL: $name\n";
    }
}
$module = file_get_contents(__DIR__ . '/../modules/account.php');
$migration = file_get_contents(__DIR__ . '/../database/sql/19_player_accounts.sql');
$template = file_get_contents(__DIR__ . '/../templates/index.tpl');
account_check(strpos($module, 'if (!$game->loggedIn)') !== false, 'account settings require an authenticated player');
account_check(strpos($module, 'hash_equals($csrf, $posted)') !== false, 'account changes validate CSRF tokens');
account_check(strpos($module, 'in_array($_POST[\'theme\'] ?? \'\', $themes, true)') !== false, 'theme values use a server-side allowlist');
account_check(strpos($module, 'in_array($_POST[\'density\'] ?? \'\', $densities, true)') !== false, 'density values use a server-side allowlist');
account_check(strpos($module, "DateTimeZone::listIdentifiers()") !== false, 'timezone values are validated server-side');
account_check(strpos($module, 'landing_page') !== false && strpos($module, 'sound_enabled') !== false, 'interface and audio options are persisted');
account_check(strpos($module, 'notify_guild') !== false && strpos($module, 'notify_raids') !== false, 'guild and raid alert options are persisted');
account_check(strpos($module, 'profile_visibility') !== false && strpos($module, 'session_timeout_minutes') !== false, 'privacy and session controls are persisted');
account_check(strpos($module, 'password_change') !== false && strpos($module, 'acct_event') !== false, 'password changes are recorded in security history');
account_check(substr_count($module, 'action="/modules/account.php"') >= 5, 'all account settings forms use the root-relative endpoint');
account_check(strpos($module, 'name="email"') !== false && strpos($module, 'name="hpname"') !== false, 'profile email and home-world fields are present');
account_check(strpos($module, '$operation === \'profile\'') !== false, 'profile form has a server-side persistence action');
account_check(strpos($module, '$operation === \'preferences\'') !== false, 'interface and alert forms share the preferences persistence action');
account_check(strpos($migration, 'landing_page') !== false && strpos($migration, 'notify_raids') !== false, 'migration defines expanded account settings');
account_check(strpos($template, "sendData('account','get','mainDisplay')") !== false, 'main navigation exposes Account Settings');
if ($failed) {
    fwrite(STDERR, "$failed account-settings checks failed; $passed passed.\n");
    exit(1);
}
echo "All $passed account-settings checks passed.\n";
?>
