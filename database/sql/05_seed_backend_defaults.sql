USE sgw;

INSERT INTO app_settings (setting_key, setting_value)
VALUES
  ('site.maintenance_mode', 'off'),
  ('site.turn_interval_minutes', '30'),
  ('economy.default_reserve_ratio', '0.20'),
  ('operations.max_attack_turns_per_action', '15'),
  ('operations.max_covert_turns_per_action', '15')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO app_migrations (migration_key)
VALUES
  ('20260801_backend_tables_v1'),
  ('20260801_reporting_views_v1'),
  ('20260801_backend_seed_v1')
ON DUPLICATE KEY UPDATE migration_key = VALUES(migration_key);
