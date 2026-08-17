-- Dynamic guild territory events
CREATE TABLE IF NOT EXISTS guild_territory_events (
  event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_id INT NOT NULL,
  territory_id BIGINT UNSIGNED NOT NULL,
  event_type ENUM('celestial_anomaly','pirate_invasion') NOT NULL,
  severity TINYINT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('active','resolved','expired') NOT NULL DEFAULT 'active',
  effect_percent TINYINT NOT NULL DEFAULT 0,
  attack_power INT UNSIGNED NOT NULL DEFAULT 0,
  response_power INT UNSIGNED NOT NULL DEFAULT 0,
  generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ends_at DATETIME NOT NULL,
  resolved_at DATETIME NULL,
  PRIMARY KEY (event_id),
  KEY idx_event_active (status,ends_at),
  KEY idx_event_guild (guild_id,status),
  UNIQUE KEY uq_active_territory_event (territory_id,status),
  CONSTRAINT fk_event_guild FOREIGN KEY (guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE,
  CONSTRAINT fk_event_territory FOREIGN KEY (territory_id) REFERENCES guild_territories(territory_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE guild_territories
  ADD COLUMN IF NOT EXISTS event_production_penalty TINYINT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS event_defense_bonus TINYINT NOT NULL DEFAULT 0;
