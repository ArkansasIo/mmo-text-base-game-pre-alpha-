CREATE TABLE IF NOT EXISTS player_blueprint_research (
  uid INT NOT NULL,
  blueprint_key VARCHAR(64) NOT NULL,
  source ENUM('starter','research','market','trade','legacy') NOT NULL DEFAULT 'research',
  status ENUM('locked','unlocked') NOT NULL DEFAULT 'unlocked',
  unlocked_at DATETIME NULL,
  PRIMARY KEY (uid, blueprint_key),
  KEY idx_blueprint_research_status (uid, status, blueprint_key),
  CONSTRAINT fk_blueprint_research_uid FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO player_blueprint_research(uid, blueprint_key, source, status, unlocked_at)
SELECT uid, blueprint_key, 'legacy', 'unlocked', NOW()
FROM player_blueprints
WHERE quantity > 0;
