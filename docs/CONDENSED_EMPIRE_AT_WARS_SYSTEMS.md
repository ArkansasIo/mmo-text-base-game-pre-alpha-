# Universe Civilization: Empire At Wars — Condensed Systems Specification

## Purpose

This document condenses the attached 3,232-line historical reconstruction into the small set of systems that should be visible, server-authoritative, and testable in the modern MMO. Historical values are used as balance defaults, not as unquestionable source code.

## Core loop

The player grows a realm through four linked loops: **economy** generates resources and population; **military** converts population, weapons, technology, and turns into attacks and loot; **covert operations** reveal or weaken targets; and **prestige** converts high-rank performance into Glory, Reputation, Ascension Points, and long-term titles.

| System | Primary functions | Key sub-features | Core logic |
|---|---|---|---|
| Command Center | Show complete realm state | Race, rank, resources, turns, personnel, income, alliance, protection | Read-only aggregation of server state; client submits intentions only |
| Turn Engine | Advance the persistent world | Turns, UU, income, covert regeneration, timed effects, rankings | 30-minute tick; turns cap at 10,000 and generate below 4,000 |
| Economy | Generate and spend Naquadah | Bank, miners, lifers, Unit Production, planet income, strategic resources | Natural income = UU×20 + (miners+livers)×80, then race/alert modifiers |
| Personnel | Convert UU into roles | Attack, defense, super units, miners, lifers, spies, anti-spies | Training consumes UU and resources; elite/lifer roles are persistent sinks |
| Military | Resolve player conflict | Attack, raid, casualties, loot, weapon durability, repair, combat reports | Deterministic server-side resolver using snapshots, action, turns, and seed |
| Covert | Gather intelligence and sabotage | Recon, spy, sabotage, detection, anti-covert, capacity, saturation | Success compares attacker covert power against defender anti-covert and alert |
| Technology | Compound realm specialization | Offense, defense, covert, anti-covert, unique, mercenary | Multiplicative modifiers applied to base power; costs scale progressively |
| Planets | Add economic and strategic assets | Explore, create, conquer, size, bonuses, defenses | Maximum 10 planets; ownership and conquest are server-authoritative |
| Mothership | Late-game fleet layer | Purchase, volley bays, shield bays, hangars, equipment, exploration | One mothership per player; action is installed shield plus weapon strength |
| Social hierarchy | Create player-driven organizations | Commander, officers, recruitment, alliances, diplomacy, messages | Commander chain and alliance membership are separate, governed relationships |
| Market | Convert and exchange value | Resource exchange, private trades, mercenaries, black market | Escrow resources, validate limits, lock rows, settle atomically, audit actions |
| Protection | Prevent abuse and preserve choice | PPT, vacation mode, attack cooldowns, same-IP rules | Protected/vacation targets reject hostile actions; universal timers continue |
| Rankings | Provide competitive direction | Attack, defense, covert, mothership, overall, glory, reputation | Overall rank averages the four major rankings; lower rank is better |
| Ascension | Provide prestige reset | Eligibility, conversion, lifers, ascended race, titles, re-ascension | Reset most realm assets while preserving lifers and prestige progression |
| Communications | Coordinate and notify | Direct mail, broadcasts, attachments, blacklist, intelligence logs | Rate-limit, validate, audit, notify, and preserve message history |

## Race and ascension identity

| Base race | Advantage | Tradeoff | Ascended race |
|---|---|---|---|
| Asgard | +25% defense | Lower attack emphasis | Ancient |
| Goa'uld | +25% income | Balanced military | System Lord |
| Replicator | +25% covert | Balanced military | NanoTiMaster |
| Tau’ri | +25% attack | Weaker defense emphasis | Tollan |

## Canonical formulas

```text
NaturalIncome = (UntrainedUnits × 20) + ((Miners + Lifers) × 80)
BankCapacity = NaturalIncome × 48 × 1.5
UnitProductionUpgradeCost = CurrentUP × 5000 + 10000
Strike = ((SuperAttackWeaponStrength × 10) + (NormalAttackWeaponStrength × 5))
         × OffenseTechnology × RaceModifier × PlanetSupport
Defense = ((SuperDefenseWeaponStrength × 10) + (NormalDefenseWeaponStrength × 5))
         × DefenseTechnology × RaceModifier × PlanetSupport
Covert = (((sqrt(2^SpyLevel) × SpyCount × CovertTechnology × RaceModifier) + SpyCount) × 10)
AntiCovert = (((sqrt(2^(AntiSpyLevel+2)) × AntiSpyCount × AntiCovertTechnology × RaceModifier) + AntiSpyCount) × 10)
OverallRank = (AttackRank + DefenseRank + CovertRank + MothershipRank) / 4
PlanetAttack = BasePlanetAttack + BonusLevel × 30000
PlanetDefense = BasePlanetDefense + BonusLevel × 25000
PlanetCovert = BasePlanetCovert + BonusLevel × 181000
PlanetIncome = 8000/turn + BonusLevel × 3840/day
```

All formula inputs and outputs must be recalculated on the server. The browser may display estimates, but it must never submit resulting balances, power, ownership, rank, Glory, Reputation, or Ascension state.

## Turn and protection rules

The normal turn interval is 30 minutes. A tick awards attack turns, generates untrained units and income, applies race, technology, commander, officer, planet, and DefCon modifiers, regenerates covert capacity, advances timed effects, updates rankings, and commits an audit event. Attack turns stop accumulating at 10,000 and should only be generated while the player is below 4,000 turns. DefCon levels are None, Low, Medium, High, and Critical: security rises approximately 0%, 10%, 20%, 40%, and 70%, while income is reduced by the same percentage.

PPT and vacation mode block hostile actions. Vacation pauses income, units, and progression for at least two days while universal protection timers continue. Repeated attacks, covert saturation, rank-range limits, same-IP relationships, daily message limits, officer caps, recruitment caps, and market-turn limits are anti-farming controls.

## Implementation policy

The existing project already contains modern fleet, guild, research, resource, wormhole, PvP, and mail systems. New work should add missing historical mechanics through shared policy/engine classes and migrations rather than replacing stable modules. The first implementation slices are: **historical formula policy**, **DefCon/protection state**, **weapon durability and repair**, **rank/Glory/Reputation snapshots**, **ascension state**, and a **condensed strategy codex panel** exposing the functions, sub-features, and game logic to players and administrators.

## Explicitly unresolved historical details

Exact weapon catalogs and prices, casualty and raid percentages, sabotage destruction tables, unique-ability probability tables, full black-market rules, historical planet-generation randomness, mothership catalogs, and Ascension Point conversion values require later balance verification. The implementation should use deterministic configurable defaults and record every outcome for audit and replay.
