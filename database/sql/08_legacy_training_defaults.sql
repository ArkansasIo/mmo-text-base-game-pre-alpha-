-- Compatibility defaults for legacy training/personnel modules.
INSERT INTO unitnames (rid, attack, superAttack, attackMercs, defense, superDefense, defenseMercs, covert, superCovert, anticovert, superAnticovert) VALUES
(1,'Warrior','Elite Warrior','Attack Mercenary','Guardian','Elite Guardian','Defense Mercenary','Spy','Master Spy','Counter-Spy','Master Counter-Spy'),
(2,'Nox Warrior','Nox Champion','Nox Attack Mercenary','Nox Guardian','Nox Elite Guardian','Nox Defense Mercenary','Nox Spy','Nox Master Spy','Nox Counter-Spy','Nox Master Counter-Spy'),
(3,'Marine','Marine Commander','Marine Mercenary','Defender','Defense Commander','Defense Mercenary','Field Agent','Intelligence Officer','Counter-Agent','Counter-Intelligence Officer'),
(4,'Asgard Warrior','Asgard Elite','Asgard Mercenary','Asgard Shield','Asgard Warden','Asgard Defense Mercenary','Asgard Observer','Asgard Seer','Asgard Counter-Observer','Asgard Counter-Seer'),
(5,"Tok'ra Operative","Tok'ra Elite","Tok'ra Mercenary","Tok'ra Guardian","Tok'ra Warden","Tok'ra Defense Mercenary","Tok'ra Spy","Tok'ra Master Spy","Tok'ra Counter-Spy","Tok'ra Master Counter-Spy")
ON DUPLICATE KEY UPDATE attack=VALUES(attack), superAttack=VALUES(superAttack), attackMercs=VALUES(attackMercs), defense=VALUES(defense), superDefense=VALUES(superDefense), defenseMercs=VALUES(defenseMercs), covert=VALUES(covert), superCovert=VALUES(superCovert), anticovert=VALUES(anticovert), superAnticovert=VALUES(superAnticovert);

INSERT INTO unitcost (rid, attack, superAttack, defense, superDefense, covert, superCovert, anticovert, superAnticovert) VALUES
(1,1500,5000,1500,5000,2000,6000,2000,6000),
(2,1500,5000,1500,5000,2000,6000,2000,6000),
(3,1500,5000,1500,5000,2000,6000,2000,6000),
(4,1500,5000,1500,5000,2000,6000,2000,6000),
(5,1500,5000,1500,5000,2000,6000,2000,6000)
ON DUPLICATE KEY UPDATE attack=VALUES(attack), superAttack=VALUES(superAttack), defense=VALUES(defense), superDefense=VALUES(superDefense), covert=VALUES(covert), superCovert=VALUES(superCovert), anticovert=VALUES(anticovert), superAnticovert=VALUES(superAnticovert);
