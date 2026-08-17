-- Stargate Wars turn-based RTS combat engine.
CREATE TABLE IF NOT EXISTS rts_battles (
  battle_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  attacker_site_id BIGINT NULL,
  defender_site_id BIGINT NULL,
  battle_type ENUM('orbital','planetary','moon','station','frontier') NOT NULL DEFAULT 'orbital',
  status ENUM('planning','active','attacker_victory','defender_victory','draw','retreated') NOT NULL DEFAULT 'planning',
  round_no INT NOT NULL DEFAULT 0,
  turn_owner ENUM('player','ai') NOT NULL DEFAULT 'player',
  attacker_score INT NOT NULL DEFAULT 0,
  defender_score INT NOT NULL DEFAULT 0,
  seed BIGINT NOT NULL DEFAULT 1,
  power_reserve INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (battle_id), KEY idx_rts_battle_uid (uid), KEY idx_rts_battle_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rts_battle_units (
  unit_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  side ENUM('player','ai') NOT NULL,
  unit_key VARCHAR(64) NOT NULL,
  unit_name VARCHAR(120) NOT NULL,
  class_name VARCHAR(64) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  hull INT NOT NULL DEFAULT 100,
  max_hull INT NOT NULL DEFAULT 100,
  shield INT NOT NULL DEFAULT 0,
  max_shield INT NOT NULL DEFAULT 0,
  armor INT NOT NULL DEFAULT 0,
  attack INT NOT NULL DEFAULT 1,
  defense INT NOT NULL DEFAULT 1,
  range_stat INT NOT NULL DEFAULT 1,
  speed INT NOT NULL DEFAULT 1,
  initiative INT NOT NULL DEFAULT 1,
  morale INT NOT NULL DEFAULT 100,
  ammo INT NOT NULL DEFAULT 100,
  energy_draw INT NOT NULL DEFAULT 1,
  position INT NOT NULL DEFAULT 2,
  status ENUM('ready','disabled','destroyed','retreated') NOT NULL DEFAULT 'ready',
  PRIMARY KEY (unit_id), KEY idx_rts_unit_battle (battle_id),
  CONSTRAINT fk_rts_unit_battle FOREIGN KEY (battle_id) REFERENCES rts_battles(battle_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rts_battle_orders (
  order_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  unit_id BIGINT UNSIGNED NOT NULL,
  order_type ENUM('attack','bombard','intercept','flank','siege','blockade','focus','jam','advance','guard','shield_wall','escort','reinforce','repair','countermeasure','retreat') NOT NULL,
  target_unit_id BIGINT UNSIGNED NULL,
  order_round INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (order_id), KEY idx_rts_order_battle (battle_id),
  CONSTRAINT fk_rts_order_battle FOREIGN KEY (battle_id) REFERENCES rts_battles(battle_id) ON DELETE CASCADE,
  CONSTRAINT fk_rts_order_unit FOREIGN KEY (unit_id) REFERENCES rts_battle_units(unit_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rts_battle_rounds (
  round_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  round_no INT NOT NULL,
  player_damage INT NOT NULL DEFAULT 0,
  ai_damage INT NOT NULL DEFAULT 0,
  player_losses INT NOT NULL DEFAULT 0,
  ai_losses INT NOT NULL DEFAULT 0,
  power_used INT NOT NULL DEFAULT 0,
  report_json LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (round_id), UNIQUE KEY uq_rts_round (battle_id,round_no),
  CONSTRAINT fk_rts_round_battle FOREIGN KEY (battle_id) REFERENCES rts_battles(battle_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rts_battle_reports (
  report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  uid INT NOT NULL,
  title VARCHAR(180) NOT NULL,
  summary TEXT NOT NULL,
  report_json LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (report_id), KEY idx_rts_report_uid (uid), KEY idx_rts_report_battle (battle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
