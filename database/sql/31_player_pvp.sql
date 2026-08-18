-- Player-versus-player warfare: scheduled attacks, reports, protections, and alerts
CREATE TABLE IF NOT EXISTS pvp_player_state (
  uid INT NOT NULL,
  protected_until DATETIME NULL,
  last_attack_at DATETIME NULL,
  attacks_today INT UNSIGNED NOT NULL DEFAULT 0,
  attack_day DATE NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (uid),
  KEY idx_pvp_protection (protected_until),
  CONSTRAINT fk_pvp_state_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pvp_battles (
  battle_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  attacker_uid INT NOT NULL,
  defender_uid INT NOT NULL,
  target_planet_id INT NOT NULL,
  origin_planet_id INT NOT NULL,
  fleet_json LONGTEXT NOT NULL,
  attack_power INT UNSIGNED NOT NULL DEFAULT 0,
  defense_power INT UNSIGNED NOT NULL DEFAULT 0,
  outcome ENUM('pending','attacker_victory','defender_victory','draw','cancelled','protected') NOT NULL DEFAULT 'pending',
  status ENUM('enroute','resolved','cancelled') NOT NULL DEFAULT 'enroute',
  loot_metal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  loot_crystal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  loot_deuterium BIGINT UNSIGNED NOT NULL DEFAULT 0,
  attacker_losses INT UNSIGNED NOT NULL DEFAULT 0,
  defender_losses INT UNSIGNED NOT NULL DEFAULT 0,
  launched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolves_at DATETIME NOT NULL,
  resolved_at DATETIME NULL,
  report TEXT NULL,
  PRIMARY KEY (battle_id),
  KEY idx_pvp_due (status,resolves_at,battle_id),
  KEY idx_pvp_attacker (attacker_uid,status,launched_at),
  KEY idx_pvp_defender (defender_uid,status,launched_at),
  CONSTRAINT fk_pvp_battle_attacker FOREIGN KEY (attacker_uid) REFERENCES users(uid) ON DELETE CASCADE,
  CONSTRAINT fk_pvp_battle_defender FOREIGN KEY (defender_uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pvp_alerts (
  alert_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  battle_id BIGINT UNSIGNED NOT NULL,
  alert_type ENUM('incoming_attack','battle_result','attack_launched') NOT NULL,
  title VARCHAR(160) NOT NULL,
  body VARCHAR(500) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (alert_id),
  KEY idx_pvp_alert_uid (uid,is_read,created_at),
  CONSTRAINT fk_pvp_alert_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE,
  CONSTRAINT fk_pvp_alert_battle FOREIGN KEY (battle_id) REFERENCES pvp_battles(battle_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
