<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../base/AdminAuth.class.php';

$s = new Game();
$auth = new AdminAuth($s->db_link);
if (!isset($_SESSION['admin_csrf'])) $_SESSION['admin_csrf'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['admin_csrf'];
$message = '';
$error = '';

$s->query("CREATE TABLE IF NOT EXISTS admin_operation_jobs (
    job_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id INT NOT NULL,
    job_type VARCHAR(64) NOT NULL,
    target_uid INT NULL,
    status ENUM('queued','running','completed','failed','cancelled') NOT NULL DEFAULT 'queued',
    payload_json TEXT NOT NULL,
    result_json TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    PRIMARY KEY (job_id), KEY idx_admin_job_status (status), KEY idx_admin_job_type (job_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function setting(mysqli $db, string $key, string $fallback = ''): string {
    $stmt = $db->prepare('SELECT setting_value FROM app_settings WHERE setting_key=? LIMIT 1');
    if (!$stmt) return $fallback;
    $stmt->bind_param('s', $key); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (string)$row['setting_value'] : $fallback;
}
function saveSetting(mysqli $db, string $key, string $value): bool {
    $stmt = $db->prepare('INSERT INTO app_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    if (!$stmt) return false;
    $stmt->bind_param('ss', $key, $value); return $stmt->execute();
}
function validMultiplier(string $value, float $min, float $max): string {
    $number = max($min, min($max, (float)$value));
    return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
}
function playerExists(mysqli $db, int $uid): bool {
    $stmt = $db->prepare('SELECT uid FROM users WHERE uid=? LIMIT 1');
    if (!$stmt) return false;
    $stmt->bind_param('i', $uid); $stmt->execute(); return (bool)$stmt->get_result()->fetch_assoc();
}

if (isset($_GET['logout'])) { $auth->logout(); header('Location: /admin/'); exit; }
if (!$auth->isAuthenticated() && isset($_POST['admin_login'])) {
    if ($auth->login(trim((string)($_POST['username'] ?? '')), (string)($_POST['password'] ?? ''))) {
        $auth->audit('admin_login', 'admin_control_plane'); header('Location: /admin/'); exit;
    }
    $error = 'Administrator credentials were not accepted.';
}

if ($auth->isAuthenticated() && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['admin_login'])) {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $error = 'Security token expired. Reload the control plane and try again.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $operatorActions = ['settings','grant_funds','player_resources','player_access','queue_job','cleanup_sessions'];
        if (in_array($action, $operatorActions, true) && !$auth->isAtLeast('operator')) $error = 'Operator privileges are required for this action.';
        elseif ($action === 'settings') {
            $values = [
                'game_login_required' => ((string)($_POST['game_login_required'] ?? '1') === '0') ? '0' : '1',
                'game_paused' => ((string)($_POST['game_paused'] ?? '0') === '1') ? '1' : '0',
                'registration_enabled' => ((string)($_POST['registration_enabled'] ?? '1') === '0') ? '0' : '1',
                'combat_enabled' => ((string)($_POST['combat_enabled'] ?? '1') === '0') ? '0' : '1',
                'expedition_enabled' => ((string)($_POST['expedition_enabled'] ?? '1') === '0') ? '0' : '1',
                'maintenance_mode' => ((string)($_POST['maintenance_mode'] ?? '0') === '1') ? '1' : '0',
                'turn_interval_minutes' => (string)max(1, min(1440, (int)($_POST['turn_interval_minutes'] ?? 30))),
                'resource_production_multiplier' => validMultiplier((string)($_POST['resource_production_multiplier'] ?? '1'), 0, 10),
                'fleet_speed_multiplier' => validMultiplier((string)($_POST['fleet_speed_multiplier'] ?? '1'), 0.1, 10),
                'combat_damage_multiplier' => validMultiplier((string)($_POST['combat_damage_multiplier'] ?? '1'), 0, 10),
                'defense_repair_ratio' => validMultiplier((string)($_POST['defense_repair_ratio'] ?? '0.70'), 0, 1),
                'power_grid_multiplier' => validMultiplier((string)($_POST['power_grid_multiplier'] ?? '1'), 0, 10),
                'maintenance_message' => trim((string)($_POST['maintenance_message'] ?? '')),
            ];
            foreach ($values as $key => $value) saveSetting($s->db_link, $key, $value);
            $auth->audit('update_game_logic_settings', 'admin_control_plane', ['keys'=>array_keys($values)]);
            $message = 'Game logic controls updated with validated limits.';
        } elseif ($action === 'grant_funds') {
            $uid = max(0, (int)($_POST['uid'] ?? 0)); $amount = max(0, min(100000000000, (float)($_POST['amount'] ?? 0)));
            if ($uid > 0 && $amount > 0 && playerExists($s->db_link, $uid)) {
                $stmt = $s->db_link->prepare('UPDATE bank SET onHand=onHand+? WHERE uid=?');
                if ($stmt) { $stmt->bind_param('di', $amount, $uid); $stmt->execute(); }
                $auth->audit('grant_funds', 'economy_control', ['uid'=>$uid,'amount'=>$amount]); $message = 'Naquadah grant completed.';
            } else $error = 'Provide an existing player UID and a positive amount.';
        } elseif ($action === 'player_resources') {
            $uid = max(0, (int)($_POST['uid'] ?? 0));
            $metal=max(0,min(1000000000000,(int)($_POST['metal']??0))); $crystal=max(0,min(1000000000000,(int)($_POST['crystal']??0))); $deut=max(0,min(1000000000000,(int)($_POST['deuterium']??0))); $energy=max(0,min(1000000000000,(int)($_POST['energy']??0)));
            if ($uid > 0 && playerExists($s->db_link, $uid)) {
                $stmt=$s->db_link->prepare('INSERT INTO player_resources (uid,metal,crystal,deuterium,energy) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE metal=VALUES(metal),crystal=VALUES(crystal),deuterium=VALUES(deuterium),energy=VALUES(energy)');
                if ($stmt) { $stmt->bind_param('iiiii',$uid,$metal,$crystal,$deut,$energy); $stmt->execute(); }
                $auth->audit('set_player_resources','economy_control',['uid'=>$uid,'metal'=>$metal,'crystal'=>$crystal,'deuterium'=>$deut,'energy'=>$energy]); $message='Player resource reserve updated.';
            } else $error='Provide an existing player UID.';
        } elseif ($action === 'player_access') {
            $uid=max(0,(int)($_POST['uid']??0)); $level=max(0,min(1,(int)($_POST['alevel']??0)));
            if ($uid>0 && playerExists($s->db_link,$uid)) { $stmt=$s->db_link->prepare('UPDATE users SET alevel=? WHERE uid=?'); if($stmt){$stmt->bind_param('ii',$level,$uid);$stmt->execute();} $auth->audit('update_player_access','player_control',['uid'=>$uid,'alevel'=>$level]);$message='Player access updated.'; } else $error='Provide an existing player UID.';
        } elseif ($action === 'queue_job') {
            $jobType=(string)($_POST['job_type']??''); $target=max(0,(int)($_POST['target_uid']??0)); $allowed=['recalculate_power','refresh_economy_metrics','rebuild_universe_index','repair_combat_integrity'];
            if (in_array($jobType,$allowed,true)) { $payload=json_encode(['requested_by'=>$auth->admin['username'],'target_uid'=>$target],JSON_UNESCAPED_SLASHES);$stmt=$s->db_link->prepare('INSERT INTO admin_operation_jobs (admin_id,job_type,target_uid,payload_json) VALUES (?,?,?,?)');if($stmt){$aid=(int)$auth->admin['admin_id'];$stmt->bind_param('isis',$aid,$jobType,$target,$payload);$stmt->execute();}$auth->audit('queue_admin_job','operations',['job_type'=>$jobType,'target_uid'=>$target]);$message='Administrative job queued for server processing.';} else $error='Unsupported operation job.';
        } elseif ($action === 'cleanup_sessions') {
            $s->query('DELETE FROM admin_sessions WHERE expires_at <= NOW()'); $auth->audit('cleanup_admin_sessions','security'); $message='Expired administrator sessions removed.';
        } elseif ($action === 'toggle_admin' && $auth->isAtLeast('superadmin')) {
            $adminId=max(0,(int)($_POST['admin_id']??0)); $active=((int)($_POST['is_active']??0)===1)?1:0;
            if($adminId>0 && $adminId !== (int)$auth->admin['admin_id']){$stmt=$s->db_link->prepare('UPDATE admin_users SET is_active=? WHERE admin_id=?');if($stmt){$stmt->bind_param('ii',$active,$adminId);$stmt->execute();}$auth->audit('toggle_admin_account','security',['admin_id'=>$adminId,'is_active'=>$active]);$message='Administrator account status updated.';}else$error='You cannot disable your current administrator session.';
        } elseif ($action === 'create_admin' && $auth->isAtLeast('superadmin')) {
            $username=trim((string)($_POST['new_username']??''));$password=(string)($_POST['new_password']??'');$role=(string)($_POST['new_role']??'operator');$email=trim((string)($_POST['new_email']??''));
            if($username!==''&&strlen($password)>=12&&in_array($role,['superadmin','operator','moderator'],true)){$hash=password_hash($password,PASSWORD_DEFAULT);$stmt=$s->db_link->prepare('INSERT INTO admin_users (username,email,password_hash,role) VALUES (?,?,?,?)');if($stmt){$stmt->bind_param('ssss',$username,$email,$hash,$role);if($stmt->execute()){$auth->audit('create_admin','security',['username'=>$username,'role'=>$role]);$message='Administrator account created.';}else$error='Administrator username may already exist.';}}else$error='Use a unique username and a password of at least 12 characters.';
        } elseif ($action !== '') $error='This action is unavailable for your administrator role.';
    }
}

if (!$auth->isAuthenticated()): ?>
<!doctype html><html><head><meta charset="utf-8"><title>Stargate Wars // Admin Control</title><link rel="stylesheet" href="/main.css"><link rel="icon" href="/favicon.svg" type="image/svg+xml"></head><body class="admin-body"><main class="admin-login"><span class="admin-kicker">STARGATE WARS // SECURE SERVER CONSOLE</span><h1>Administrator Control Plane</h1><p>Server-side game operations, moderation, simulation controls, and audit access.</p><?php if($error): ?><div class="admin-alert danger"><?=h($error)?></div><?php endif; ?><form method="post"><label>Administrator username<input name="username" autocomplete="username" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button name="admin_login" value="1">Enter Control Plane</button></form><a href="/index.php">Return to player title page</a></main></body></html>
<?php exit; endif;

$settingKeys=['game_login_required','game_paused','turn_interval_minutes','registration_enabled','combat_enabled','expedition_enabled','maintenance_mode','resource_production_multiplier','fleet_speed_multiplier','combat_damage_multiplier','defense_repair_ratio','power_grid_multiplier','maintenance_message'];
$settings=[];foreach($settingKeys as $key)$settings[$key]=setting($s->db_link,$key,$key==='defense_repair_ratio'?'0.70':'1');
$stats=[];foreach([['Players','users'],['Admins','admin_users'],['Power Nodes','power_nodes'],['Combat Sites','combat_sites'],['Queued Jobs','admin_operation_jobs']] as $stat){$q=$s->query("SELECT COUNT(*) c FROM `{$stat[1]}`");$stats[$stat[1]]=$q?(int)$q->fetch_assoc()['c']:0;}
$players=$s->query('SELECT uid,uname,email,alevel,lastLogin FROM users ORDER BY uid DESC LIMIT 35');
$admins=$s->query('SELECT admin_id,username,email,role,is_active,last_login_at FROM admin_users ORDER BY admin_id DESC LIMIT 20');
$jobs=$s->query('SELECT job_id,job_type,target_uid,status,created_at FROM admin_operation_jobs ORDER BY job_id DESC LIMIT 20');
$audit=$s->query('SELECT id,uid,action_type,module_name,details_json,created_at FROM app_audit_log ORDER BY id DESC LIMIT 35');
?><!doctype html><html><head><meta charset="utf-8"><title>Stargate Wars // Admin Control Plane</title><link rel="stylesheet" href="/main.css"><link rel="icon" href="/favicon.svg" type="image/svg+xml"></head><body class="admin-body"><main class="admin-shell"><header class="admin-header"><div><span class="admin-kicker">STARGATE WARS // SERVER OPERATIONS</span><h1>Admin Control Plane</h1><p>Signed in as <b><?=h($auth->admin['username'])?></b> · <?=h($auth->admin['role'])?></p></div><div><a class="admin-logout" href="/admin/email.php">Root Email</a> <a class="admin-logout" href="/admin/?logout=1">Sign out</a></div></header><?php if($message): ?><div class="admin-alert success"><?=h($message)?></div><?php endif; ?><?php if($error): ?><div class="admin-alert danger"><?=h($error)?></div><?php endif; ?><section class="admin-stats"><?php foreach([['Players','users'],['Admins','admin_users'],['Power Nodes','power_nodes'],['Combat Sites','combat_sites'],['Queued Jobs','admin_operation_jobs']] as $stat): ?><div><span><?=h($stat[0])?></span><strong><?=number_format($stats[$stat[1]]??0)?></strong></div><?php endforeach; ?></section>
<div class="admin-grid"><section class="admin-card admin-wide"><h2>Game Logic and Simulation</h2><form method="post" class="admin-form-grid"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="settings"><label>Login required<select name="game_login_required"><option value="1" <?=$settings['game_login_required']==='1'?'selected':''?>>On</option><option value="0" <?=$settings['game_login_required']==='0'?'selected':''?>>Off</option></select></label><label>Game state<select name="game_paused"><option value="0" <?=$settings['game_paused']==='0'?'selected':''?>>Running</option><option value="1" <?=$settings['game_paused']==='1'?'selected':''?>>Paused</option></select></label><label>Registration<select name="registration_enabled"><option value="1" <?=$settings['registration_enabled']==='1'?'selected':''?>>Enabled</option><option value="0" <?=$settings['registration_enabled']==='0'?'selected':''?>>Disabled</option></select></label><label>Combat system<select name="combat_enabled"><option value="1" <?=$settings['combat_enabled']==='1'?'selected':''?>>Enabled</option><option value="0" <?=$settings['combat_enabled']==='0'?'selected':''?>>Disabled</option></select></label><label>Expeditions<select name="expedition_enabled"><option value="1" <?=$settings['expedition_enabled']==='1'?'selected':''?>>Enabled</option><option value="0" <?=$settings['expedition_enabled']==='0'?'selected':''?>>Disabled</option></select></label><label>Maintenance mode<select name="maintenance_mode"><option value="0" <?=$settings['maintenance_mode']==='0'?'selected':''?>>Off</option><option value="1" <?=$settings['maintenance_mode']==='1'?'selected':''?>>On</option></select></label><label>Turn interval<input type="number" name="turn_interval_minutes" min="1" max="1440" value="<?=h($settings['turn_interval_minutes'])?>"></label><label>Production multiplier<input type="number" name="resource_production_multiplier" step="0.1" min="0" max="10" value="<?=h($settings['resource_production_multiplier'])?>"></label><label>Fleet speed multiplier<input type="number" name="fleet_speed_multiplier" step="0.1" min="0.1" max="10" value="<?=h($settings['fleet_speed_multiplier'])?>"></label><label>Combat damage multiplier<input type="number" name="combat_damage_multiplier" step="0.1" min="0" max="10" value="<?=h($settings['combat_damage_multiplier'])?>"></label><label>Defense repair ratio<input type="number" name="defense_repair_ratio" step="0.05" min="0" max="1" value="<?=h($settings['defense_repair_ratio'])?>"></label><label>Power grid multiplier<input type="number" name="power_grid_multiplier" step="0.1" min="0" max="10" value="<?=h($settings['power_grid_multiplier'])?>"></label><label class="admin-full">Maintenance message<textarea name="maintenance_message" rows="2"><?=h($settings['maintenance_message'])?></textarea></label><button class="admin-primary">Save Validated Game Controls</button></form></section>
<section class="admin-card"><h2>Economy Controls</h2><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="grant_funds"><label>Player UID<input type="number" name="uid" min="1" required></label><label>Naquadah amount<input type="number" name="amount" min="1" max="100000000000" required></label><button>Grant Naquadah</button></form><hr><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="player_resources"><label>Reserve UID<input type="number" name="uid" min="1" required></label><label>Metal<input type="number" name="metal" min="0" required></label><label>Crystal<input type="number" name="crystal" min="0" required></label><label>Deuterium<input type="number" name="deuterium" min="0" required></label><label>Energy<input type="number" name="energy" min="0" required></label><button>Set Resource Reserve</button></form></section>
<section class="admin-card"><h2>Player Governance</h2><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="player_access"><label>Player UID<input type="number" name="uid" min="1" required></label><label>Access<select name="alevel"><option value="0">Player</option><option value="1">Legacy admin flag</option></select></label><button>Update Player Access</button></form><hr><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="cleanup_sessions"><button>Clean Expired Admin Sessions</button></form></section>
<section class="admin-card"><h2>Server Operations Queue</h2><p>Queue controlled maintenance jobs for the backend worker.</p><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="queue_job"><label>Operation<select name="job_type"><option value="recalculate_power">Recalculate Power Grid</option><option value="refresh_economy_metrics">Refresh Economy Metrics</option><option value="rebuild_universe_index">Rebuild Universe Index</option><option value="repair_combat_integrity">Repair Combat Integrity</option></select></label><label>Target UID (optional)<input type="number" name="target_uid" min="0" value="0"></label><button>Queue Operation</button></form></section>
<?php if($auth->isAtLeast('superadmin')): ?><section class="admin-card"><h2>Create Administrator</h2><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="create_admin"><label>Username<input name="new_username" required></label><label>Email<input type="email" name="new_email"></label><label>Role<select name="new_role"><option>operator</option><option>moderator</option><option>superadmin</option></select></label><label>Password (12+ chars)<input type="password" name="new_password" minlength="12" required></label><button>Create Admin</button></form></section><?php endif; ?></div>
<section class="admin-card"><h2>Player Directory</h2><div class="admin-table-wrap"><table><thead><tr><th>UID</th><th>Username</th><th>Email</th><th>Access</th><th>Last Login</th></tr></thead><tbody><?php while($p=$players->fetch_assoc()): ?><tr><td><?= (int)$p['uid']?></td><td><?=h($p['uname'])?></td><td><?=h($p['email'])?></td><td><?= (int)$p['alevel']?></td><td><?=h($p['lastLogin']??'')?></td></tr><?php endwhile; ?></tbody></table></div></section>
<section class="admin-card"><h2>Administrator Accounts</h2><div class="admin-table-wrap"><table><thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Active</th><th>Last Login</th><th>Action</th></tr></thead><tbody><?php while($a=$admins->fetch_assoc()): ?><tr><td><?= (int)$a['admin_id']?></td><td><?=h($a['username'])?></td><td><?=h($a['role'])?></td><td><?=((int)$a['is_active']===1)?'Yes':'No'?></td><td><?=h($a['last_login_at']??'')?></td><td><?php if($auth->isAtLeast('superadmin') && (int)$a['admin_id'] !== (int)$auth->admin['admin_id']): ?><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="toggle_admin"><input type="hidden" name="admin_id" value="<?= (int)$a['admin_id']?>"><input type="hidden" name="is_active" value="<?=((int)$a['is_active']===1)?0:1?>"><button><?=((int)$a['is_active']===1)?'Disable':'Enable'?></button></form><?php else: ?>Protected<?php endif; ?></td></tr><?php endwhile; ?></tbody></table></div></section>
<section class="admin-card"><h2>Queued Operations</h2><div class="admin-table-wrap"><table><thead><tr><th>ID</th><th>Operation</th><th>Target</th><th>Status</th><th>Created</th></tr></thead><tbody><?php while($j=$jobs->fetch_assoc()): ?><tr><td><?= (int)$j['job_id']?></td><td><?=h($j['job_type'])?></td><td><?= (int)($j['target_uid']??0)?></td><td><?=h($j['status'])?></td><td><?=h($j['created_at'])?></td></tr><?php endwhile; ?></tbody></table></div></section>
<section class="admin-card"><h2>Audit Trail</h2><div class="admin-table-wrap"><table><thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Module</th><th>Details</th></tr></thead><tbody><?php while($a=$audit->fetch_assoc()): ?><tr><td><?=h($a['created_at'])?></td><td><?= (int)$a['uid']?></td><td><?=h($a['action_type'])?></td><td><?=h($a['module_name'])?></td><td><?=h($a['details_json'])?></td></tr><?php endwhile; ?></tbody></table></div></section>
</main></body></html>
