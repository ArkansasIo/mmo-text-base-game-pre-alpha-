-- Stargate Wars campaign missions and attack/defense wave layer.
ALTER TABLE rts_battles
  ADD COLUMN IF NOT EXISTS mission_type ENUM('assault','defense','raid','intercept','siege','escort','expedition') NOT NULL DEFAULT 'assault',
  ADD COLUMN IF NOT EXISTS phase ENUM('approach','orbital','surface','extraction','complete') NOT NULL DEFAULT 'approach',
  ADD COLUMN IF NOT EXISTS current_wave INT NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS max_waves INT NOT NULL DEFAULT 3,
  ADD COLUMN IF NOT EXISTS objective VARCHAR(180) NOT NULL DEFAULT 'Break the opposing force and secure the theater';

CREATE TABLE IF NOT EXISTS rts_missions (
  mission_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  uid INT NOT NULL,
  mission_type ENUM('assault','defense','raid','intercept','siege','escort','expedition') NOT NULL DEFAULT 'assault',
  objective VARCHAR(180) NOT NULL,
  phase ENUM('approach','orbital','surface','extraction','complete') NOT NULL DEFAULT 'approach',
  status ENUM('planning','active','victory','defeat','aborted') NOT NULL DEFAULT 'planning',
  current_wave INT NOT NULL DEFAULT 1,
  max_waves INT NOT NULL DEFAULT 3,
  target_name VARCHAR(140) NOT NULL DEFAULT 'Uncharted theater',
  target_class VARCHAR(64) NOT NULL DEFAULT 'frontier',
  reward_metal INT NOT NULL DEFAULT 0,
  reward_crystal INT NOT NULL DEFAULT 0,
  reward_deuterium INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (mission_id), KEY idx_rts_mission_uid (uid), KEY idx_rts_mission_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rts_waves (
  wave_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  mission_id BIGINT UNSIGNED NOT NULL,
  wave_no INT NOT NULL,
  side ENUM('player','ai') NOT NULL,
  wave_type ENUM('vanguard','line','siege','reinforcement','interceptor','retreat_guard') NOT NULL DEFAULT 'line',
  status ENUM('queued','active','destroyed','withdrawn','resolved') NOT NULL DEFAULT 'queued',
  strength_index INT NOT NULL DEFAULT 100,
  composition_json LONGTEXT NOT NULL,
  spawned_at DATETIME NULL,
  resolved_at DATETIME NULL,
  PRIMARY KEY (wave_id), UNIQUE KEY uq_rts_wave (mission_id,wave_no,side), KEY idx_rts_wave_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rts_battle_targets (
  target_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  target_type ENUM('planet','moon','station','fleet','convoy','structure','command_ship') NOT NULL DEFAULT 'fleet',
  target_name VARCHAR(140) NOT NULL,
  priority INT NOT NULL DEFAULT 50,
  hull INT NOT NULL DEFAULT 100,
  max_hull INT NOT NULL DEFAULT 100,
  shield INT NOT NULL DEFAULT 0,
  status ENUM('hidden','identified','engaged','disabled','secured','destroyed') NOT NULL DEFAULT 'identified',
  PRIMARY KEY (target_id), KEY idx_rts_target_battle (battle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rts_battle_events (
  event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  mission_id BIGINT UNSIGNED NULL,
  wave_no INT NOT NULL DEFAULT 0,
  event_type VARCHAR(64) NOT NULL,
  message TEXT NOT NULL,
  payload_json LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id), KEY idx_rts_event_battle (battle_id), KEY idx_rts_event_mission (mission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rts_salvage_records (
  salvage_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  mission_id BIGINT UNSIGNED NULL,
  uid INT NOT NULL,
  metal INT NOT NULL DEFAULT 0,
  crystal INT NOT NULL DEFAULT 0,
  deuterium INT NOT NULL DEFAULT 0,
  recovered_units INT NOT NULL DEFAULT 0,
  claimed TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (salvage_id), KEY idx_rts_salvage_uid (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
