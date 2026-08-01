-- Reporting views for dashboards and exports
USE sgw;

CREATE OR REPLACE VIEW vw_player_core AS
SELECT
    u.uid,
    u.uname,
    u.email,
    u.allyid,
    u.lastLogin,
    ud.actionTurns,
    b.onHand,
    b.inbank,
    un.attack,
    un.defense,
    un.covert,
    un.anticovert,
    un.untrained,
    t.income,
    t.unitProd,
    p.overall,
    p.mil_total
FROM users u
LEFT JOIN userdata ud ON ud.uid = u.uid
LEFT JOIN bank b ON b.uid = u.uid
LEFT JOIN units un ON un.uid = u.uid
LEFT JOIN technology t ON t.uid = u.uid
LEFT JOIN power p ON p.uid = u.uid;

CREATE OR REPLACE VIEW vw_player_economy AS
SELECT
    u.uid,
    u.uname,
    b.onHand,
    b.inbank,
    t.income AS incomeTech,
    t.unitProd AS unitProdTech,
    un.untrained,
    un.miners,
    un.lifers
FROM users u
LEFT JOIN bank b ON b.uid = u.uid
LEFT JOIN technology t ON t.uid = u.uid
LEFT JOIN units un ON un.uid = u.uid;

CREATE OR REPLACE VIEW vw_player_military AS
SELECT
    u.uid,
    u.uname,
    un.attack,
    un.superAttack,
    un.defense,
    un.superDefense,
    un.covert,
    un.superCovert,
    un.anticovert,
    un.superAnticovert,
    p.mil_atk,
    p.mil_def,
    p.mil_cov,
    p.mil_anti,
    p.mil_total
FROM users u
LEFT JOIN units un ON un.uid = u.uid
LEFT JOIN power p ON p.uid = u.uid;
