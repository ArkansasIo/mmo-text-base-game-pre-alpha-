-- Guild technology, diplomacy, and warfare systems
CREATE TABLE IF NOT EXISTS guild_technology_levels (
  guild_id INT NOT NULL,
  tech_key VARCHAR(48) NOT NULL,
  level TINYINT UNSIGNED NOT NULL DEFAULT 0,
  research_started_at DATETIME NULL,
  research_completed_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (guild_id, tech_key),
  CONSTRAINT fk_gtech_guild FOREIGN KEY (guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_diplomacy (
  diplomacy_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_low_id INT NOT NULL,
  guild_high_id INT NOT NULL,
  relation ENUM('proposed','ally','non_aggression','war','ended') NOT NULL DEFAULT 'proposed',
  proposed_by INT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (diplomacy_id),
  UNIQUE KEY uq_diplomacy_pair (guild_low_id,guild_high_id),
  CONSTRAINT fk_diplomacy_low FOREIGN KEY (guild_low_id) REFERENCES guilds(guild_id) ON DELETE CASCADE,
  CONSTRAINT fk_diplomacy_high FOREIGN KEY (guild_high_id) REFERENCES guilds(guild_id) ON DELETE CASCADE,
  CONSTRAINT fk_diplomacy_user FOREIGN KEY (proposed_by) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_wars (
  war_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  attacker_guild_id INT NOT NULL,
  defender_guild_id INT NOT NULL,
  declared_by INT NOT NULL,
  status ENUM('active','ended','cooldown') NOT NULL DEFAULT 'active',
  declared_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ends_at DATETIME NULL,
  cooldown_until DATETIME NULL,
  PRIMARY KEY (war_id),
  KEY idx_war_pair (attacker_guild_id,defender_guild_id,status),
  CONSTRAINT fk_war_attacker FOREIGN KEY (attacker_guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE,
  CONSTRAINT fk_war_defender FOREIGN KEY (defender_guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE,
  CONSTRAINT fk_war_user FOREIGN KEY (declared_by) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_raids (
  raid_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  war_id BIGINT UNSIGNED NOT NULL,
  attacker_guild_id INT NOT NULL,
  defender_guild_id INT NOT NULL,
  target_territory_id BIGINT UNSIGNED NOT NULL,
  launched_by INT NOT NULL,
  status ENUM('enroute','resolved','repelled','cancelled') NOT NULL DEFAULT 'enroute',
  attack_power INT UNSIGNED NOT NULL DEFAULT 100,
  defense_power INT UNSIGNED NOT NULL DEFAULT 100,
  loot_metal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  loot_crystal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  launched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolves_at DATETIME NOT NULL,
  resolved_at DATETIME NULL,
  PRIMARY KEY (raid_id),
  KEY idx_raid_resolution (status,resolves_at),
  CONSTRAINT fk_raid_war FOREIGN KEY (war_id) REFERENCES guild_wars(war_id) ON DELETE CASCADE,
  CONSTRAINT fk_raid_target FOREIGN KEY (target_territory_id) REFERENCES guild_territories(territory_id) ON DELETE CASCADE,
  CONSTRAINT fk_raid_user FOREIGN KEY (launched_by) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE guild_territories
  ADD COLUMN IF NOT EXISTS defense_level INT UNSIGNED NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS raid_protection_until DATETIME NULL;

INSERT IGNORE INTO guild_technology_levels (guild_id,tech_key,level)
SELECT guild_id,'industrial_logistics',0 FROM guilds;
INSERT IGNORE INTO guild_technology_levels (guild_id,tech_key,level)
SELECT guild_id,'military_doctrine',0 FROM guilds;
INSERT IGNORE INTO guild_technology_levels (guild_id,tech_key,level)
SELECT guild_id,'fortress_networks',0 FROM guilds;
INSERT IGNORE INTO guild_technology_levels (guild_id,tech_key,level)
SELECT guild_id,'diplomatic_protocols',0 FROM guilds;
