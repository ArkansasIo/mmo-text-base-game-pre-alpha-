-- Universe Civilization: Empire at Wars population model
-- Planet population range: 100,000 to 5,000,000 by default.
-- Moon population range: 10,000 to 750,000 by default.
ALTER TABLE planets
  ADD COLUMN IF NOT EXISTS population BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS pop_cap BIGINT UNSIGNED NOT NULL DEFAULT 5000000;

ALTER TABLE moon_data
  ADD COLUMN IF NOT EXISTS population BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS pop_cap BIGINT UNSIGNED NOT NULL DEFAULT 750000;

UPDATE planets
SET population = FLOOR(100000 + (RAND() * 4900001)),
    pop_cap = 5000000
WHERE population <= 0;

UPDATE moon_data
SET population = FLOOR(10000 + (RAND() * 740001)),
    pop_cap = 750000
WHERE population <= 0;
