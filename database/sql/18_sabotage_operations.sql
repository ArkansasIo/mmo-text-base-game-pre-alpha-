-- Stargate Wars sabotage and counterintelligence systems.
CREATE TABLE IF NOT EXISTS sabotage_missions (
  mission_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  target_uid INT NOT NULL,
  mission_type ENUM('power_blackout','weapons_disruption','shield_infiltration','production_strike','logistics_cut','defense_breach','command_intrusion') NOT NULL,
  target_name VARCHAR(120) NOT NULL DEFAULT 'Unknown Target',
  status ENUM('queued','resolved','detected','failed','recovered','cancelled') NOT NULL DEFAULT 'queued',
  infiltration_score INT NOT NULL DEFAULT 0,
  detection_risk INT NOT NULL DEFAULT 0,
  effect_strength INT NOT NULL DEFAULT 0,
  turns_cost INT NOT NULL DEFAULT 1,
  cooldown_seconds INT NOT NULL DEFAULT 3600,
  effect_until DATETIME NULL,
  resolved_at DATETIME NULL,
  report TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (mission_id), KEY idx_sabotage_uid(uid), KEY idx_sabotage_target(target_uid), KEY idx_sabotage_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sabotage_effects (
  effect_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  mission_id BIGINT UNSIGNED NOT NULL,
  target_uid INT NOT NULL,
  effect_type ENUM('power_blackout','weapons_disruption','shield_infiltration','production_strike','logistics_cut','defense_breach','command_intrusion') NOT NULL,
  magnitude INT NOT NULL DEFAULT 0,
  status ENUM('active','expired','purged') NOT NULL DEFAULT 'active',
  starts_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ends_at DATETIME NOT NULL,
  PRIMARY KEY (effect_id), KEY idx_sabotage_effect_target(target_uid), KEY idx_sabotage_effect_status(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS counterintelligence_profiles (
  uid INT NOT NULL,
  alert_level INT NOT NULL DEFAULT 0,
  sensor_bonus INT NOT NULL DEFAULT 0,
  trace_bonus INT NOT NULL DEFAULT 0,
  last_breach_at DATETIME NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sabotage_recovery_log (
  recovery_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  effect_id BIGINT UNSIGNED NOT NULL,
  target_uid INT NOT NULL,
  recovery_type ENUM('automatic','countermeasure','admin_purge') NOT NULL,
  restored_percent INT NOT NULL DEFAULT 100,
  recovered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (recovery_id), KEY idx_sabotage_recovery_target(target_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
