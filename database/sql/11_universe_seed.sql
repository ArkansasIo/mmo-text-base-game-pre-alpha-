CREATE TABLE IF NOT EXISTS universe_meta (
  universe_id INT NOT NULL AUTO_INCREMENT,
  seed VARCHAR(80) NOT NULL,
  universe_name VARCHAR(120) NOT NULL,
  galaxy_count INT NOT NULL DEFAULT 3,
  generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (universe_id),
  UNIQUE KEY uq_universe_seed (seed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS universe_stars (
  star_id INT NOT NULL AUTO_INCREMENT,
  universe_id INT NOT NULL,
  galaxy_no INT NOT NULL,
  system_no INT NOT NULL,
  star_name VARCHAR(120) NOT NULL,
  star_class VARCHAR(32) NOT NULL,
  spectral_color VARCHAR(20) NOT NULL,
  luminosity INT NOT NULL,
  hazard VARCHAR(80) NOT NULL,
  has_station TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (star_id),
  UNIQUE KEY uq_universe_star_coord (universe_id,galaxy_no,system_no),
  CONSTRAINT fk_universe_star_meta FOREIGN KEY (universe_id) REFERENCES universe_meta(universe_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS universe_bodies (
  body_id BIGINT NOT NULL AUTO_INCREMENT,
  star_id INT NOT NULL,
  body_no INT NOT NULL,
  body_type ENUM('planet','moon','station','anomaly') NOT NULL,
  body_name VARCHAR(140) NOT NULL,
  seed_code VARCHAR(80) NOT NULL,
  climate VARCHAR(64) NOT NULL,
  biome VARCHAR(64) NOT NULL,
  resource_primary VARCHAR(64) NOT NULL,
  resource_secondary VARCHAR(64) NOT NULL,
  lifeform VARCHAR(80) NOT NULL,
  hazard VARCHAR(80) NOT NULL,
  atmosphere VARCHAR(80) NOT NULL,
  richness INT NOT NULL DEFAULT 50,
  habitability INT NOT NULL DEFAULT 50,
  parent_body_id BIGINT NULL,
  discovered_by INT NULL,
  discovered_at DATETIME NULL,
  PRIMARY KEY (body_id),
  UNIQUE KEY uq_universe_body_seed (seed_code),
  KEY idx_universe_body_star (star_id),
  KEY idx_universe_body_parent (parent_body_id),
  CONSTRAINT fk_universe_body_star FOREIGN KEY (star_id) REFERENCES universe_stars(star_id) ON DELETE CASCADE,
  CONSTRAINT fk_universe_body_parent FOREIGN KEY (parent_body_id) REFERENCES universe_bodies(body_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS universe_discoveries (
  discovery_id BIGINT NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  body_id BIGINT NOT NULL,
  discovery_type ENUM('scan','landing','resource','lifeform','anomaly','artifact') NOT NULL,
  notes VARCHAR(255) NOT NULL,
  discovered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (discovery_id),
  UNIQUE KEY uq_user_body_discovery (uid,body_id,discovery_type),
  CONSTRAINT fk_universe_discovery_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE,
  CONSTRAINT fk_universe_discovery_body FOREIGN KEY (body_id) REFERENCES universe_bodies(body_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
