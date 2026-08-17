-- Stargate Wars: world offense and defense systems
CREATE TABLE IF NOT EXISTS combat_sites (
  site_id BIGINT NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  power_node_id BIGINT NULL,
  world_type VARCHAR(32) NOT NULL DEFAULT 'homeworld',
  world_ref VARCHAR(80) NOT NULL,
  site_name VARCHAR(120) NOT NULL,
  doctrine VARCHAR(32) NOT NULL DEFAULT 'balanced',
  command_level INT NOT NULL DEFAULT 1,
  sensor_level INT NOT NULL DEFAULT 1,
  armor_rating INT NOT NULL DEFAULT 100,
  hull_rating INT NOT NULL DEFAULT 100,
  morale INT NOT NULL DEFAULT 100,
  stealth_rating INT NOT NULL DEFAULT 10,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (site_id),
  UNIQUE KEY uq_combat_site (uid, world_ref),
  KEY idx_combat_site_uid (uid),
  CONSTRAINT fk_combat_site_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS combat_technology (
  uid INT NOT NULL,
  weapons_level INT NOT NULL DEFAULT 1,
  shields_level INT NOT NULL DEFAULT 1,
  targeting_level INT NOT NULL DEFAULT 1,
  armor_level INT NOT NULL DEFAULT 1,
  reactor_level INT NOT NULL DEFAULT 1,
  command_level INT NOT NULL DEFAULT 1,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (uid),
  CONSTRAINT fk_combat_tech_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS combat_installations (
  installation_id BIGINT NOT NULL AUTO_INCREMENT,
  site_id BIGINT NOT NULL,
  item_key VARCHAR(64) NOT NULL,
  item_kind ENUM('weapon','shield','structure') NOT NULL,
  level INT NOT NULL DEFAULT 1,
  quantity INT NOT NULL DEFAULT 0,
  power_draw INT NOT NULL DEFAULT 0,
  integrity INT NOT NULL DEFAULT 100,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (installation_id),
  UNIQUE KEY uq_site_installation (site_id,item_key),
  CONSTRAINT fk_installation_site FOREIGN KEY (site_id) REFERENCES combat_sites(site_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS combat_engagements (
  engagement_id BIGINT NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  attacker_site_id BIGINT NOT NULL,
  defender_site_id BIGINT NOT NULL,
  outcome ENUM('attacker_victory','defender_victory','draw') NOT NULL,
  attacker_score INT NOT NULL DEFAULT 0,
  defender_score INT NOT NULL DEFAULT 0,
  attacker_damage INT NOT NULL DEFAULT 0,
  defender_damage INT NOT NULL DEFAULT 0,
  report_json LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (engagement_id),
  KEY idx_engagement_uid (uid),
  CONSTRAINT fk_engagement_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
