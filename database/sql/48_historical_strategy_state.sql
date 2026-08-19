CREATE TABLE IF NOT EXISTS player_historical_state (
  uid INT NOT NULL,
  defcon ENUM('none','low','medium','high','critical') NOT NULL DEFAULT 'none',
  covert_capacity BIGINT UNSIGNED NOT NULL DEFAULT 0,
  covert_capacity_max BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ppt_until DATETIME NULL,
  vacation_until DATETIME NULL,
  glory BIGINT NOT NULL DEFAULT 0,
  reputation BIGINT NOT NULL DEFAULT 0,
  ascension_points BIGINT NOT NULL DEFAULT 0,
  ascension_count INT UNSIGNED NOT NULL DEFAULT 0,
  ascended_race VARCHAR(64) NULL,
  prestige_title VARCHAR(128) NOT NULL DEFAULT 'Commander',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(uid),
  KEY idx_historical_protection(ppt_until,vacation_until),
  CONSTRAINT fk_historical_state_user FOREIGN KEY(uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS historical_strategy_events (
  event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NULL,
  event_type VARCHAR(48) NOT NULL,
  aggregate_key VARCHAR(96) NOT NULL,
  payload_json JSON NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(event_id),
  KEY idx_strategy_events_uid(uid,created_at),
  KEY idx_strategy_events_type(event_type,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
