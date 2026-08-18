-- Player account settings, security events, and restored title-page login gate.
CREATE TABLE IF NOT EXISTS player_account_settings (
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
  PRIMARY KEY (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS player_security_events (
  event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  event_type ENUM('login','logout','password_change','profile_update','preference_update','failed_login') NOT NULL,
  ip_address VARCHAR(64) NOT NULL DEFAULT '',
  details VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id), KEY idx_player_security_uid(uid), KEY idx_player_security_type(event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('game_login_required', '1')
ON DUPLICATE KEY UPDATE setting_value='1';
