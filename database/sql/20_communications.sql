-- Universe Civilization: Empire at Wars communications layer
CREATE TABLE IF NOT EXISTS message_preferences (
  uid INT NOT NULL PRIMARY KEY,
  allow_private_messages TINYINT(1) NOT NULL DEFAULT 1,
  allow_guild_messages TINYINT(1) NOT NULL DEFAULT 1,
  muted_until DATETIME NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_msg_pref_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_channels (
  channel_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_id INT NOT NULL,
  channel_name VARCHAR(80) NOT NULL,
  channel_topic VARCHAR(180) NOT NULL DEFAULT '',
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (channel_id),
  UNIQUE KEY uq_guild_channel (guild_id, channel_name),
  KEY idx_guild_channel_guild (guild_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_messages (
  guild_message_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  channel_id BIGINT UNSIGNED NOT NULL,
  guild_id INT NOT NULL,
  from_uid INT NOT NULL,
  body TEXT NOT NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (guild_message_id),
  KEY idx_guild_messages_channel (channel_id, guild_message_id),
  KEY idx_guild_messages_guild (guild_id),
  CONSTRAINT fk_guild_message_channel FOREIGN KEY (channel_id) REFERENCES guild_channels(channel_id) ON DELETE CASCADE,
  CONSTRAINT fk_guild_message_user FOREIGN KEY (from_uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_message_reads (
  channel_id BIGINT UNSIGNED NOT NULL,
  uid INT NOT NULL,
  last_read_message_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (channel_id, uid),
  CONSTRAINT fk_guild_read_channel FOREIGN KEY (channel_id) REFERENCES guild_channels(channel_id) ON DELETE CASCADE,
  CONSTRAINT fk_guild_read_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS communication_moderation (
  moderation_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_id INT NULL,
  message_type ENUM('private','guild') NOT NULL,
  message_id BIGINT UNSIGNED NOT NULL,
  moderator_uid INT NOT NULL,
  action_type ENUM('hide','restore','mute') NOT NULL,
  reason VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (moderation_id),
  KEY idx_comm_mod_message (message_type, message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
