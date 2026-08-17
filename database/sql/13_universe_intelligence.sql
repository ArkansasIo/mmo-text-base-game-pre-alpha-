ALTER TABLE universe_bodies
  ADD COLUMN IF NOT EXISTS geology_profile VARCHAR(160) NOT NULL DEFAULT 'Stable crust with common mineral strata',
  ADD COLUMN IF NOT EXISTS ecology_profile VARCHAR(160) NOT NULL DEFAULT 'Sparse native ecology',
  ADD COLUMN IF NOT EXISTS climate_profile VARCHAR(160) NOT NULL DEFAULT 'Seasonal temperate cycle',
  ADD COLUMN IF NOT EXISTS atmosphere_profile VARCHAR(160) NOT NULL DEFAULT 'Standard survey atmosphere',
  ADD COLUMN IF NOT EXISTS resource_profile VARCHAR(160) NOT NULL DEFAULT 'Moderate extractive potential',
  ADD COLUMN IF NOT EXISTS hazard_profile VARCHAR(160) NOT NULL DEFAULT 'Routine navigation risk',
  ADD COLUMN IF NOT EXISTS faction_presence VARCHAR(120) NOT NULL DEFAULT 'Unclaimed frontier',
  ADD COLUMN IF NOT EXISTS settlement_class VARCHAR(80) NOT NULL DEFAULT 'Survey Outpost',
  ADD COLUMN IF NOT EXISTS technology_level TINYINT NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS power_affinity INT NOT NULL DEFAULT 50,
  ADD COLUMN IF NOT EXISTS economy_value INT NOT NULL DEFAULT 50,
  ADD COLUMN IF NOT EXISTS survey_difficulty INT NOT NULL DEFAULT 50,
  ADD COLUMN IF NOT EXISTS gameplay_effect VARCHAR(255) NOT NULL DEFAULT 'No special modifier detected';

CREATE TABLE IF NOT EXISTS universe_designs (
  design_id BIGINT NOT NULL AUTO_INCREMENT,
  body_id BIGINT NOT NULL,
  design_type ENUM('habitat','mine','research','power','defense','trade','terraforming','anomaly_lab') NOT NULL,
  design_name VARCHAR(120) NOT NULL,
  design_level INT NOT NULL DEFAULT 1,
  metal_cost BIGINT NOT NULL DEFAULT 0,
  crystal_cost BIGINT NOT NULL DEFAULT 0,
  deuterium_cost BIGINT NOT NULL DEFAULT 0,
  power_draw BIGINT NOT NULL DEFAULT 0,
  output_bonus INT NOT NULL DEFAULT 0,
  unlocked TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (design_id),
  UNIQUE KEY uq_body_design (body_id,design_type),
  CONSTRAINT fk_universe_design_body FOREIGN KEY (body_id) REFERENCES universe_bodies(body_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS universe_effects (
  effect_id BIGINT NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  body_id BIGINT NOT NULL,
  effect_key VARCHAR(80) NOT NULL,
  effect_value INT NOT NULL DEFAULT 0,
  source_discovery_id BIGINT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (effect_id),
  UNIQUE KEY uq_user_body_effect (uid,body_id,effect_key),
  CONSTRAINT fk_universe_effect_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE,
  CONSTRAINT fk_universe_effect_body FOREIGN KEY (body_id) REFERENCES universe_bodies(body_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
