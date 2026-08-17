-- Universe Civilization: Empire at Wars guild command system
CREATE TABLE IF NOT EXISTS guilds (
  guild_id INT NOT NULL AUTO_INCREMENT,
  guild_name VARCHAR(80) NOT NULL,
  guild_tag VARCHAR(12) NOT NULL,
  description VARCHAR(500) NOT NULL DEFAULT '',
  founder_uid INT NOT NULL,
  guild_level TINYINT UNSIGNED NOT NULL DEFAULT 1,
  contribution_score BIGINT UNSIGNED NOT NULL DEFAULT 0,
  shared_credits BIGINT UNSIGNED NOT NULL DEFAULT 0,
  shared_metal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  shared_crystal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  shared_energy BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (guild_id),
  UNIQUE KEY uq_guild_name (guild_name),
  UNIQUE KEY uq_guild_tag (guild_tag),
  KEY idx_guild_founder (founder_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_members (
  guild_id INT NOT NULL,
  uid INT NOT NULL,
  rank_level TINYINT UNSIGNED NOT NULL DEFAULT 1,
  contribution_total BIGINT UNSIGNED NOT NULL DEFAULT 0,
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_active_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (guild_id, uid),
  UNIQUE KEY uq_guild_member_uid (uid),
  KEY idx_guild_members_rank (guild_id, rank_level),
  CONSTRAINT fk_guild_member_guild FOREIGN KEY (guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE,
  CONSTRAINT fk_guild_member_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_invites (
  invite_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_id INT NOT NULL,
  invited_uid INT NOT NULL,
  invited_by INT NOT NULL,
  status ENUM('pending','accepted','declined','expired','cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  PRIMARY KEY (invite_id),
  UNIQUE KEY uq_pending_guild_invite (guild_id, invited_uid, status),
  KEY idx_invite_recipient (invited_uid, status),
  CONSTRAINT fk_guild_invite_guild FOREIGN KEY (guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE,
  CONSTRAINT fk_guild_invite_user FOREIGN KEY (invited_uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_contributions (
  contribution_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_id INT NOT NULL,
  uid INT NOT NULL,
  resource_type ENUM('credits','metal','crystal','energy') NOT NULL,
  amount BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (contribution_id),
  KEY idx_guild_contribution_guild (guild_id, created_at),
  CONSTRAINT fk_guild_contribution_guild FOREIGN KEY (guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE,
  CONSTRAINT fk_guild_contribution_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_resource_ledger (
  ledger_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_id INT NOT NULL,
  uid INT NOT NULL,
  action_type ENUM('contribute','withdraw','bonus','admin_adjust') NOT NULL,
  resource_type ENUM('credits','metal','crystal','energy') NOT NULL,
  amount BIGINT NOT NULL,
  reason VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ledger_id),
  KEY idx_guild_ledger (guild_id, created_at),
  CONSTRAINT fk_guild_ledger_guild FOREIGN KEY (guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_events (
  event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_id INT NOT NULL,
  actor_uid INT NOT NULL,
  event_type VARCHAR(40) NOT NULL,
  target_uid INT NULL,
  details VARCHAR(500) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id),
  KEY idx_guild_events (guild_id, event_id),
  CONSTRAINT fk_guild_event_guild FOREIGN KEY (guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_bonuses (
  guild_id INT NOT NULL PRIMARY KEY,
  production_percent TINYINT UNSIGNED NOT NULL DEFAULT 2,
  defense_percent TINYINT UNSIGNED NOT NULL DEFAULT 3,
  research_percent TINYINT UNSIGNED NOT NULL DEFAULT 1,
  fleet_recovery_percent TINYINT UNSIGNED NOT NULL DEFAULT 2,
  recalculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_guild_bonus_guild FOREIGN KEY (guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
