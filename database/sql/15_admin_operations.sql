-- Extended administrator operations and tunable game logic.
INSERT INTO app_settings (setting_key, setting_value) VALUES
 ('resource_production_multiplier','1'),
 ('fleet_speed_multiplier','1'),
 ('combat_damage_multiplier','1'),
 ('defense_repair_ratio','0.70'),
 ('power_grid_multiplier','1'),
 ('combat_enabled','1'),
 ('expedition_enabled','1'),
 ('maintenance_mode','0')
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);

CREATE TABLE IF NOT EXISTS admin_operation_jobs (
  job_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id INT NOT NULL,
  job_type VARCHAR(64) NOT NULL,
  target_uid INT NULL,
  status ENUM('queued','running','completed','failed','cancelled') NOT NULL DEFAULT 'queued',
  payload_json TEXT NOT NULL,
  result_json TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  PRIMARY KEY (job_id),
  KEY idx_admin_job_status (status),
  KEY idx_admin_job_type (job_type),
  CONSTRAINT fk_admin_job_admin FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
