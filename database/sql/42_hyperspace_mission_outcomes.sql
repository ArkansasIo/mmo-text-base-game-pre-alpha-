-- Hyperspace missions now have distinct transfer, expedition, and colonization outcomes.
ALTER TABLE hyperspace_transits
  ADD COLUMN IF NOT EXISTS outcome VARCHAR(32) NOT NULL DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS outcome_text VARCHAR(255) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS hyperspace_colonies (
  colony_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  route_id INT NOT NULL,
  transit_id INT NOT NULL,
  world_name VARCHAR(80) NOT NULL,
  population BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(16) NOT NULL DEFAULT 'founded',
  founded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (colony_id),
  UNIQUE KEY uq_colony_transit (transit_id),
  KEY idx_colony_uid (uid, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO achievement_definitions (achievement_key, name, description, target_value)
VALUES ('frontier_founder', 'Frontier Founder', 'Establish a hyperspace colony.', 1); 
