-- Renamed player races, race bonuses, and selectable governments
ALTER TABLE race ADD COLUMN IF NOT EXISTS attack_bonus INT NOT NULL DEFAULT 0;
ALTER TABLE race ADD COLUMN IF NOT EXISTS defense_bonus INT NOT NULL DEFAULT 0;
ALTER TABLE race ADD COLUMN IF NOT EXISTS upkeep_bonus INT NOT NULL DEFAULT 0;
CREATE TABLE IF NOT EXISTS governments (
  government_id TINYINT UNSIGNED NOT NULL,
  government_name VARCHAR(80) NOT NULL,
  income_bonus INT NOT NULL DEFAULT 0,
  attack_bonus INT NOT NULL DEFAULT 0,
  defense_bonus INT NOT NULL DEFAULT 0,
  PRIMARY KEY(government_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO governments(government_id,government_name,income_bonus,attack_bonus,defense_bonus) VALUES
 (1,'Parliamentary Republic',4,1,2),(2,'Imperial Directorate',1,5,0),(3,'Federated Commonwealth',3,2,2),(4,'Technocratic Compact',2,2,5),(5,'Merchant League',6,0,1),(6,'Militarized Protectorate',0,6,1),(7,'Theocratic Dominion',2,3,4),(8,'Hive Council',3,4,3),(9,'Frontier Confederacy',4,2,3)
ON DUPLICATE KEY UPDATE government_name=VALUES(government_name),income_bonus=VALUES(income_bonus),attack_bonus=VALUES(attack_bonus),defense_bonus=VALUES(defense_bonus);
ALTER TABLE userdata ADD COLUMN IF NOT EXISTS government_id TINYINT UNSIGNED NOT NULL DEFAULT 1;
UPDATE race SET r_name='Astraeans',income_bonus=8,up_bonus=4,attack_bonus=3,defense_bonus=2 WHERE rid=1;
UPDATE race SET r_name='Noxari',income_bonus=4,up_bonus=2,attack_bonus=1,defense_bonus=6 WHERE rid=2;
UPDATE race SET r_name='Terran Union',income_bonus=6,up_bonus=5,attack_bonus=5,defense_bonus=4 WHERE rid=3;
UPDATE race SET r_name='Asgardian Remnant',income_bonus=3,up_bonus=3,attack_bonus=7,defense_bonus=7 WHERE rid=4;
UPDATE race SET r_name='Tokari Syndicate',income_bonus=7,up_bonus=1,attack_bonus=4,defense_bonus=3 WHERE rid=5;
