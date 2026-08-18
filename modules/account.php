<?php
include_once('../config.php');
$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$game = new Game();
if (!$game->loggedIn) {
    header('Location: ../index.php');
    exit;
}

$db = $game->db_link;
$uid = (int)($_SESSION['userid'] ?? 0);

function acct_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function acct_one(mysqli $db, string $sql): ?array
{
    $query = $db->query($sql);
    return $query ? $query->fetch_assoc() : null;
}

function acct_has_column(mysqli $db, string $column): bool
{
    $safeColumn = $db->real_escape_string($column);
    $query = $db->query("SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='player_account_settings' AND column_name='$safeColumn' LIMIT 1");
    return $query instanceof mysqli_result && $query->num_rows > 0;
}

function acct_install(mysqli $db, int $uid): void
{
    $db->query("CREATE TABLE IF NOT EXISTS player_account_settings (
        uid INT NOT NULL,
        theme VARCHAR(32) NOT NULL DEFAULT 'industrial-blue',
        density ENUM('compact','standard','expanded') NOT NULL DEFAULT 'standard',
        timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
        landing_page VARCHAR(32) NOT NULL DEFAULT 'overview',
        sound_enabled TINYINT(1) NOT NULL DEFAULT 1,
        ambient_music TINYINT(1) NOT NULL DEFAULT 1,
        reduce_motion TINYINT(1) NOT NULL DEFAULT 0,
        notify_messages TINYINT(1) NOT NULL DEFAULT 1,
        notify_battles TINYINT(1) NOT NULL DEFAULT 1,
        notify_guild TINYINT(1) NOT NULL DEFAULT 1,
        notify_events TINYINT(1) NOT NULL DEFAULT 1,
        notify_trade TINYINT(1) NOT NULL DEFAULT 1,
        notify_raids TINYINT(1) NOT NULL DEFAULT 1,
        show_online_status TINYINT(1) NOT NULL DEFAULT 1,
        profile_visibility ENUM('public','guild','private') NOT NULL DEFAULT 'public',
        session_timeout_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 240,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(uid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS player_security_events (
        event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        uid INT NOT NULL,
        event_type ENUM('login','logout','password_change','profile_update','preference_update','failed_login') NOT NULL,
        ip_address VARCHAR(64) NOT NULL DEFAULT '',
        details VARCHAR(255) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(event_id), KEY idx_player_security_uid(uid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $compatColumns = [
        'landing_page' => "VARCHAR(32) NOT NULL DEFAULT 'overview'",
        'sound_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'ambient_music' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'reduce_motion' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'notify_guild' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'notify_events' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'notify_trade' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'notify_raids' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'profile_visibility' => "ENUM('public','guild','private') NOT NULL DEFAULT 'public'",
        'session_timeout_minutes' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 240'
    ];
    foreach ($compatColumns as $column => $definition) {
        if (!acct_has_column($db, $column)) {
            $db->query("ALTER TABLE player_account_settings ADD COLUMN `$column` $definition");
        }
    }
    $db->query("INSERT IGNORE INTO player_account_settings (uid) VALUES ($uid)");
}

function acct_event(mysqli $db, int $uid, string $type, string $details = ''): void
{
    $ip = $db->real_escape_string((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    $safeDetails = $db->real_escape_string($details);
    $db->query("INSERT INTO player_security_events (uid,event_type,ip_address,details) VALUES ($uid,'$type','$ip','$safeDetails')");
}

acct_install($db, $uid);
$message = '';
$error = '';
$action = (string)($_GET['id'] ?? 'mainDisplay');
$csrf = $_SESSION['account_csrf'] ?? bin2hex(random_bytes(24));
$_SESSION['account_csrf'] = $csrf;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $posted)) {
        $error = 'Security validation failed. Refresh the account console and try again.';
    } else {
        $operation = (string)($_POST['account_action'] ?? '');
        if ($operation === 'profile') {
            $email = trim((string)($_POST['email'] ?? ''));
            $homeWorld = trim((string)($_POST['hpname'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $homeWorld === '' || strlen($homeWorld) > 80) {
                $error = 'Enter a valid email address and a home-world name up to 80 characters.';
            } else {
                $stmt = $db->prepare('UPDATE users SET email=? WHERE uid=? LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('si', $email, $uid);
                    $stmt->execute();
                }
                $stmt = $db->prepare('UPDATE userdata SET link=? WHERE uid=? LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('si', $homeWorld, $uid);
                    $stmt->execute();
                }
                acct_event($db, $uid, 'profile_update', 'Player profile updated');
                $message = 'Player profile saved.';
            }
        } elseif ($operation === 'preferences') {
            $themes = ['industrial-blue', 'cyan-command', 'midnight'];
            $densities = ['compact', 'standard', 'expanded'];
            $landingPages = ['overview', 'resourcehq', 'fleet', 'guild', 'notifications'];
            $theme = in_array($_POST['theme'] ?? '', $themes, true) ? $_POST['theme'] : 'industrial-blue';
            $density = in_array($_POST['density'] ?? '', $densities, true) ? $_POST['density'] : 'standard';
            $landingPage = in_array($_POST['landing_page'] ?? '', $landingPages, true) ? $_POST['landing_page'] : 'overview';
            $timezone = trim((string)($_POST['timezone'] ?? 'UTC'));
            if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
                $timezone = 'UTC';
            }
            $sound = isset($_POST['sound_enabled']) ? 1 : 0;
            $music = isset($_POST['ambient_music']) ? 1 : 0;
            $motion = isset($_POST['reduce_motion']) ? 1 : 0;
            $messages = isset($_POST['notify_messages']) ? 1 : 0;
            $battles = isset($_POST['notify_battles']) ? 1 : 0;
            $guild = isset($_POST['notify_guild']) ? 1 : 0;
            $events = isset($_POST['notify_events']) ? 1 : 0;
            $trade = isset($_POST['notify_trade']) ? 1 : 0;
            $raids = isset($_POST['notify_raids']) ? 1 : 0;
            $stmt = $db->prepare('INSERT INTO player_account_settings (uid,theme,density,timezone,landing_page,sound_enabled,ambient_music,reduce_motion,notify_messages,notify_battles,notify_guild,notify_events,notify_trade,notify_raids) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE theme=VALUES(theme),density=VALUES(density),timezone=VALUES(timezone),landing_page=VALUES(landing_page),sound_enabled=VALUES(sound_enabled),ambient_music=VALUES(ambient_music),reduce_motion=VALUES(reduce_motion),notify_messages=VALUES(notify_messages),notify_battles=VALUES(notify_battles),notify_guild=VALUES(notify_guild),notify_events=VALUES(notify_events),notify_trade=VALUES(notify_trade),notify_raids=VALUES(notify_raids)');
            if ($stmt) {
                $stmt->bind_param('issssiiiiiiii', $uid, $theme, $density, $timezone, $landingPage, $sound, $music, $motion, $messages, $battles, $guild, $events, $trade, $raids);
                $stmt->execute();
            }
            acct_event($db, $uid, 'preference_update', 'Interface and notification preferences updated');
            $message = 'Interface and notification preferences saved.';
        } elseif ($operation === 'privacy') {
            $visibilityOptions = ['public', 'guild', 'private'];
            $visibility = in_array($_POST['profile_visibility'] ?? '', $visibilityOptions, true) ? $_POST['profile_visibility'] : 'public';
            $online = isset($_POST['show_online_status']) ? 1 : 0;
            $timeout = (int)($_POST['session_timeout_minutes'] ?? 240);
            if (!in_array($timeout, [30, 60, 120, 240, 480], true)) {
                $timeout = 240;
            }
            $stmt = $db->prepare('UPDATE player_account_settings SET show_online_status=?,profile_visibility=?,session_timeout_minutes=? WHERE uid=?');
            if ($stmt) {
                $stmt->bind_param('isii', $online, $visibility, $timeout, $uid);
                $stmt->execute();
            }
            acct_event($db, $uid, 'preference_update', 'Privacy and session controls updated');
            $message = 'Privacy and session settings saved.';
        } elseif ($operation === 'password') {
            $old = (string)($_POST['old_password'] ?? '');
            $new = (string)($_POST['new_password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');
            $user = acct_one($db, "SELECT password FROM users WHERE uid=$uid LIMIT 1");
            $legacy = (new User())->salt($old);
            if (!$user || !hash_equals((string)$user['password'], $legacy)) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($new) < 8) {
                $error = 'New passwords must contain at least 8 characters.';
            } elseif ($new !== $confirm) {
                $error = 'New password confirmation does not match.';
            } else {
                $hash = (new User())->salt($new);
                $stmt = $db->prepare('UPDATE users SET password=? WHERE uid=? LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('si', $hash, $uid);
                    $stmt->execute();
                }
                $_SESSION['password'] = $hash;
                acct_event($db, $uid, 'password_change', 'Player password changed');
                $message = 'Password changed successfully.';
            }
        }
    }
}

if ($action === 'logout') {
    acct_event($db, $uid, 'logout', 'Player signed out');
    User::logOut();
    header('Location: ../index.php');
    exit;
}

$profile = acct_one($db, "SELECT u.uname,u.email,ud.link FROM users u LEFT JOIN userdata ud ON ud.uid=u.uid WHERE u.uid=$uid LIMIT 1") ?? [];
$defaults = [
    'theme' => 'industrial-blue', 'density' => 'standard', 'timezone' => 'UTC', 'landing_page' => 'overview',
    'sound_enabled' => 1, 'ambient_music' => 1, 'reduce_motion' => 0, 'notify_messages' => 1, 'notify_battles' => 1,
    'notify_guild' => 1, 'notify_events' => 1, 'notify_trade' => 1, 'notify_raids' => 1, 'show_online_status' => 1,
    'profile_visibility' => 'public', 'session_timeout_minutes' => 240
];
$prefs = array_merge($defaults, acct_one($db, "SELECT * FROM player_account_settings WHERE uid=$uid LIMIT 1") ?? []);
$events = $db->query("SELECT event_type,details,created_at FROM player_security_events WHERE uid=$uid ORDER BY event_id DESC LIMIT 12");
?>
<div class="page-hub account-console">
  <div class="page-hub-head"><span class="rts-kicker">UNIVERSE CIVILIZATION // PLAYER ACCOUNT</span><h3>Player Account and Settings</h3><p>Manage your profile, command interface, alert network, privacy, sessions, and password security.</p></div>
  <?php if ($message): ?><div class="card rts-alert success"><strong><?= acct_h($message) ?></strong></div><?php endif; ?>
  <?php if ($error): ?><div class="card rts-alert danger"><strong><?= acct_h($error) ?></strong></div><?php endif; ?>
  <div class="rts-unit-grid">
    <section class="card"><h4>Profile Settings</h4><form class="admin-form-grid" method="post" action="/modules/account.php"><input type="hidden" name="csrf" value="<?= acct_h($csrf) ?>"><input type="hidden" name="account_action" value="profile"><label>Username<input value="<?= acct_h($profile['uname'] ?? '') ?>" disabled></label><label>Email<input type="email" name="email" value="<?= acct_h($profile['email'] ?? '') ?>" required></label><label>Home-world name<input name="hpname" value="<?= acct_h($profile['link'] ?? '') ?>" maxlength="80" required></label><button class="rts-primary">Save Profile</button></form></section>
    <section class="card"><h4>Interface Settings</h4><form class="admin-form-grid" method="post" action="/modules/account.php"><input type="hidden" name="csrf" value="<?= acct_h($csrf) ?>"><input type="hidden" name="account_action" value="preferences"><input type="hidden" name="notify_messages" value="<?= !empty($prefs['notify_messages']) ? '1' : '' ?>"><input type="hidden" name="notify_battles" value="<?= !empty($prefs['notify_battles']) ? '1' : '' ?>"><input type="hidden" name="notify_guild" value="<?= !empty($prefs['notify_guild']) ? '1' : '' ?>"><input type="hidden" name="notify_events" value="<?= !empty($prefs['notify_events']) ? '1' : '' ?>"><input type="hidden" name="notify_trade" value="<?= !empty($prefs['notify_trade']) ? '1' : '' ?>"><input type="hidden" name="notify_raids" value="<?= !empty($prefs['notify_raids']) ? '1' : '' ?>"><label>Theme<select name="theme"><option value="industrial-blue" <?= $prefs['theme'] === 'industrial-blue' ? 'selected' : '' ?>>Industrial Blue</option><option value="cyan-command" <?= $prefs['theme'] === 'cyan-command' ? 'selected' : '' ?>>Cyan Command</option><option value="midnight" <?= $prefs['theme'] === 'midnight' ? 'selected' : '' ?>>Midnight Operations</option></select></label><label>Interface density<select name="density"><option value="compact" <?= $prefs['density'] === 'compact' ? 'selected' : '' ?>>Compact</option><option value="standard" <?= $prefs['density'] === 'standard' ? 'selected' : '' ?>>Standard</option><option value="expanded" <?= $prefs['density'] === 'expanded' ? 'selected' : '' ?>>Expanded</option></select></label><label>Timezone<select name="timezone"><?php foreach (['UTC','America/New_York','America/Los_Angeles','Europe/London','Europe/Berlin','Asia/Tokyo','Australia/Sydney'] as $zone): ?><option value="<?= acct_h($zone) ?>" <?= $prefs['timezone'] === $zone ? 'selected' : '' ?>><?= acct_h($zone) ?></option><?php endforeach; ?></select></label><label>Default command view<select name="landing_page"><?php foreach (['overview'=>'Overview','resourcehq'=>'Resource HQ','fleet'=>'Fleet Command','guild'=>'Guild Command','notifications'=>'Alert Network'] as $value => $label): ?><option value="<?= acct_h($value) ?>" <?= $prefs['landing_page'] === $value ? 'selected' : '' ?>><?= acct_h($label) ?></option><?php endforeach; ?></select></label><label><input type="checkbox" name="sound_enabled" <?= !empty($prefs['sound_enabled']) ? 'checked' : '' ?>> Interface sounds</label><label><input type="checkbox" name="ambient_music" <?= !empty($prefs['ambient_music']) ? 'checked' : '' ?>> Ambient space music</label><label><input type="checkbox" name="reduce_motion" <?= !empty($prefs['reduce_motion']) ? 'checked' : '' ?>> Reduce motion effects</label><button class="rts-primary">Save Interface Settings</button></form></section>
    <section class="card"><h4>Alert Network</h4><form class="admin-form-grid" method="post" action="/modules/account.php"><input type="hidden" name="csrf" value="<?= acct_h($csrf) ?>"><input type="hidden" name="account_action" value="preferences"><input type="hidden" name="theme" value="<?= acct_h($prefs['theme']) ?>"><input type="hidden" name="density" value="<?= acct_h($prefs['density']) ?>"><input type="hidden" name="timezone" value="<?= acct_h($prefs['timezone']) ?>"><input type="hidden" name="landing_page" value="<?= acct_h($prefs['landing_page']) ?>"><input type="hidden" name="sound_enabled" value="<?= !empty($prefs['sound_enabled']) ? '1' : '' ?>"><input type="hidden" name="ambient_music" value="<?= !empty($prefs['ambient_music']) ? '1' : '' ?>"><input type="hidden" name="reduce_motion" value="<?= !empty($prefs['reduce_motion']) ? '1' : '' ?>"><label><input type="checkbox" name="notify_messages" <?= !empty($prefs['notify_messages']) ? 'checked' : '' ?>> Private messages</label><label><input type="checkbox" name="notify_battles" <?= !empty($prefs['notify_battles']) ? 'checked' : '' ?>> Battle reports</label><label><input type="checkbox" name="notify_guild" <?= !empty($prefs['notify_guild']) ? 'checked' : '' ?>> Guild communications</label><label><input type="checkbox" name="notify_events" <?= !empty($prefs['notify_events']) ? 'checked' : '' ?>> Celestial events</label><label><input type="checkbox" name="notify_trade" <?= !empty($prefs['notify_trade']) ? 'checked' : '' ?>> Market and trade updates</label><label><input type="checkbox" name="notify_raids" <?= !empty($prefs['notify_raids']) ? 'checked' : '' ?>> Incoming raids and invasions</label><button class="rts-primary">Save Alert Settings</button></form></section>
    <section class="card"><h4>Privacy and Sessions</h4><form class="admin-form-grid" method="post" action="/modules/account.php"><input type="hidden" name="csrf" value="<?= acct_h($csrf) ?>"><input type="hidden" name="account_action" value="privacy"><label>Profile visibility<select name="profile_visibility"><option value="public" <?= $prefs['profile_visibility'] === 'public' ? 'selected' : '' ?>>Public commanders</option><option value="guild" <?= $prefs['profile_visibility'] === 'guild' ? 'selected' : '' ?>>Guild members only</option><option value="private" <?= $prefs['profile_visibility'] === 'private' ? 'selected' : '' ?>>Private</option></select></label><label>Session timeout<select name="session_timeout_minutes"><?php foreach ([30,60,120,240,480] as $minutes): ?><option value="<?= $minutes ?>" <?= (int)$prefs['session_timeout_minutes'] === $minutes ? 'selected' : '' ?>><?= $minutes < 60 ? $minutes . ' minutes' : ($minutes / 60) . ' hours' ?></option><?php endforeach; ?></select></label><label><input type="checkbox" name="show_online_status" <?= !empty($prefs['show_online_status']) ? 'checked' : '' ?>> Show online status to other commanders</label><button class="rts-primary">Save Privacy Settings</button></form></section>
  </div>
  <section class="card"><h4>Password Security</h4><form class="admin-form-grid" method="post" action="/modules/account.php"><input type="hidden" name="csrf" value="<?= acct_h($csrf) ?>"><input type="hidden" name="account_action" value="password"><label>Current password<input type="password" name="old_password" required></label><label>New password<input type="password" name="new_password" minlength="8" required></label><label>Confirm new password<input type="password" name="confirm_password" minlength="8" required></label><button>Change Password</button></form><p>Use a unique password. Password changes are recorded in the security event history.</p></section>
  <section class="card"><h4>Security Events</h4><table class="mini-table" width="100%"><tr><th>Event</th><th>Details</th><th>Time</th></tr><?php if ($events): while ($event = $events->fetch_assoc()): ?><tr><td><?= acct_h($event['event_type']) ?></td><td><?= acct_h($event['details']) ?></td><td><?= acct_h($event['created_at']) ?></td></tr><?php endwhile; endif; ?></table><p><a class="rts-primary" href="javascript:void(0)" onclick="sendData('account','get','logout');return false">Sign out of player account</a></p></section>
</div>
<?php $pagegen->stop(); print('page generation time: ' . $pagegen->gen()); ?>
