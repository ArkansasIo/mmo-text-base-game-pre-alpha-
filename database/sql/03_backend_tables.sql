-- Backend service tables
USE sgw;

CREATE TABLE IF NOT EXISTS app_migrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  migration_key VARCHAR(128) NOT NULL,
  executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_app_migrations_key (migration_key)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key VARCHAR(128) NOT NULL,
  setting_value TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS app_audit_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT(11) NULL,
  action_type VARCHAR(64) NOT NULL,
  module_name VARCHAR(64) NOT NULL,
  details_json TEXT NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_app_audit_uid (uid),
  KEY idx_app_audit_action_type (action_type),
  KEY idx_app_audit_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS app_server_jobs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_name VARCHAR(128) NOT NULL,
  status VARCHAR(32) NOT NULL,
  payload_json TEXT NOT NULL,
  last_error TEXT NULL,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_app_server_jobs_status (status),
  KEY idx_app_server_jobs_name (job_name)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS app_daily_economy_metrics (
  metric_date DATE NOT NULL,
  total_players INT(11) NOT NULL,
  total_onhand BIGINT NOT NULL,
  total_inbank BIGINT NOT NULL,
  total_untrained BIGINT NOT NULL,
  total_attack BIGINT NOT NULL,
  total_defense BIGINT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (metric_date)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
