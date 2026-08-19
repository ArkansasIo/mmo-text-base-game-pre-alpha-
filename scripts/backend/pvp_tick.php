<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); echo "CLI only\n"; exit(1); }
$root = dirname(__DIR__, 2);
require_once $root . '/config.php';
require_once $root . '/base/FleetPolicy.class.php';
require_once $root . '/base/PvPPolicy.class.php';
require_once $root . '/base/PvPResolver.class.php';
require_once $root . '/base/PvPMatchmakingService.class.php';
require_once $root . '/base/PvPRankingPolicy.class.php';
$db = new mysqli($conf['db_server'], $conf['db_username'], $conf['db_password'], $conf['db_name']);
if ($db->connect_error) { fwrite(STDERR, "DB connect error: {$db->connect_error}\n"); exit(1); }
$db->set_charset('utf8mb4');
$db->query("CREATE TABLE IF NOT EXISTS pvp_player_state (uid INT NOT NULL,protected_until DATETIME NULL,last_attack_at DATETIME NULL,attacks_today INT UNSIGNED NOT NULL DEFAULT 0,attack_day DATE NULL,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY(uid)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$db->query("CREATE TABLE IF NOT EXISTS pvp_battles (battle_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,attacker_uid INT NOT NULL,defender_uid INT NOT NULL,target_planet_id INT NOT NULL,origin_planet_id INT NOT NULL,fleet_json LONGTEXT NOT NULL,fitting_json LONGTEXT NOT NULL DEFAULT '{}',attack_power INT UNSIGNED NOT NULL DEFAULT 0,defense_power INT UNSIGNED NOT NULL DEFAULT 0,outcome ENUM('pending','attacker_victory','defender_victory','draw','cancelled','protected') NOT NULL DEFAULT 'pending',status ENUM('enroute','resolved','cancelled') NOT NULL DEFAULT 'enroute',loot_metal BIGINT UNSIGNED NOT NULL DEFAULT 0,loot_crystal BIGINT UNSIGNED NOT NULL DEFAULT 0,loot_deuterium BIGINT UNSIGNED NOT NULL DEFAULT 0,attacker_losses INT UNSIGNED NOT NULL DEFAULT 0,defender_losses INT UNSIGNED NOT NULL DEFAULT 0,launched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,resolves_at DATETIME NOT NULL,resolved_at DATETIME NULL,report TEXT NULL,PRIMARY KEY(battle_id),KEY idx_pvp_due(status,resolves_at,battle_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$db->query("ALTER TABLE pvp_battles ADD COLUMN IF NOT EXISTS fitting_json LONGTEXT NOT NULL DEFAULT '{}' AFTER fleet_json");
$db->query("CREATE TABLE IF NOT EXISTS pvp_alerts (alert_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,uid INT NOT NULL,battle_id BIGINT UNSIGNED NOT NULL,alert_type ENUM('incoming_attack','battle_result','attack_launched') NOT NULL,title VARCHAR(160) NOT NULL,body VARCHAR(500) NOT NULL,is_read TINYINT(1) NOT NULL DEFAULT 0,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(alert_id),KEY idx_pvp_alert_uid(uid,is_read,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$matched = PvPMatchmakingService::matchDue($db, PvPRankingPolicy::SEASON_CODE, 100);
$count = PvPResolver::resolveDue($db, 250);
echo "PvP tick complete\nMatched battles: $matched\nResolved battles: $count\n";
$db->close();
?>
