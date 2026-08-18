-- High-concurrency indexes for player actions and PvP settlement.
-- Each index is conditional so this migration is safe to re-run on MariaDB versions that support IF NOT EXISTS.
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_uname (uname);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_email (email);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_ip (ip);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_allyid (allyid);
ALTER TABLE player_fleet_inventory ADD INDEX IF NOT EXISTS idx_fleet_uid_planet_ship (uid,planet_id,ship_type);
ALTER TABLE fleet_deployments ADD INDEX IF NOT EXISTS idx_deploy_due (status,arrive_at,deployment_id);
ALTER TABLE hyperspace_transits ADD INDEX IF NOT EXISTS idx_transit_due_uid (uid,status,eta_at,transit_id);
ALTER TABLE hyperspace_transits ADD INDEX IF NOT EXISTS idx_transit_return_uid (uid,status,return_at,transit_id);
ALTER TABLE player_resources ADD INDEX IF NOT EXISTS idx_resources_tick (last_tick_at,uid);
ALTER TABLE pvp_battles ADD INDEX IF NOT EXISTS idx_pvp_due_status (status,resolves_at,battle_id);
ALTER TABLE pvp_alerts ADD INDEX IF NOT EXISTS idx_pvp_alert_feed (uid,is_read,created_at,alert_id);
