-- Persist per-dispatch module loadouts for reproducible combat resolution.
ALTER TABLE pvp_battles ADD COLUMN IF NOT EXISTS fitting_json LONGTEXT NOT NULL DEFAULT '{}' AFTER fleet_json;
ALTER TABLE fleet_deployments ADD COLUMN IF NOT EXISTS fitting_json LONGTEXT NOT NULL DEFAULT '{}' AFTER fleet_json;
