# Universe Civilization: Empire at Wars
## Game Design Document

**Document version:** 0.9.0-preview  
**Build reference:** 2026.08.17  
**Project:** Universe Civilization: Empire at Wars  
**Repository:** [github.com/ArkansasIo/mmo-text-base-game-pre-alpha-](https://github.com/ArkansasIo/mmo-text-base-game-pre-alpha-)  
**Document purpose:** Define the current playable systems, actual implementation limits, progression rules, combat model, account model, world model, administration tools, and planned expansion targets.

> **Important distinction:** This document separates limits that are currently enforced by code from design targets that are intended for future balancing. Where no hard maximum was found in the audited source, the system is marked **uncapped in the current build**, not treated as having an unlimited final design promise.

## 1. Product Vision

Universe Civilization: Empire at Wars is a browser-based persistent strategy game about building a civilization across planets, moons, starbases, moonbases, and space stations; managing industrial power; researching technologies; commanding fleets; conducting turn-based real-time-strategy-style engagements; defending worlds against attack waves; executing covert sabotage; and governing the game through a protected administrator control plane.

The experience combines a classic resource-and-technology progression loop with a tactical battle layer. Players develop infrastructure, expand their power grid, explore a deterministic seeded universe, build military forces, issue tactical orders, survive escalating waves, recover salvage, and use intelligence or sabotage to shape the strategic map.

## 2. Current Build Summary

| Area | Current implementation status |
|---|---|
| Public title page | Active with Universe Civilization branding, redesigned galaxy art, redesigned logo, login gate, registration, release information, and audio control |
| Player account system | Active; login, registration, profile, preferences, password changes, security events, and logout |
| Administrator control plane | Active and protected; game settings, economy, combat, power, operations jobs, player governance, admin roles, metrics, and audit history |
| Infrastructure | Active legacy-compatible construction and resource systems |
| Power grid | Active for homeworlds, planets, moons, starbases, moonbases, and space stations |
| Procedural universe | Active deterministic seed, planet and moon taxonomy, biomes, intelligence profiles, and A–Z classification |
| RTS combat | Active persistent battles, units, orders, rounds, damage, shields, armor, morale, power consumption, AI, reports, and retreat |
| Battle campaigns | Active assault, defense, raid, intercept, siege, escort, and expedition mission types with persistent waves |
| Sabotage | Active covert missions, detection, temporary effects, counterintelligence, cooldowns, and recovery records |
| Audio | Active title ambient track, hover, click, confirmation, and warning effects with browser-safe user activation |

## 3. Maximum Levels and Limits

### 3.1 Direct answer

The current repository does **not** define one universal maximum level for every system. The core legacy construction, research, power, combat technology, combat installations, and world command fields are stored as integer levels and the audited code does not apply a universal upper bound. These systems are therefore **uncapped in the current build** unless an individual module or database deployment adds a narrower rule.

The most important enforced caps currently found are:

| System | Current maximum or limit | Type |
|---|---:|---|
| Universe taxonomy class | A–Z, 26 classes | Fixed content range |
| World size | 1–9 | Fixed content range |
| Battle campaign waves | 8 | Server-side clamp in RTS mission launch |
| Administrator turn interval | 1–1,440 minutes | Server-side clamp |
| Administrator resource grant | 100,000,000,000 | Server-side clamp |
| Administrator direct resource values | 1,000,000,000,000 per resource field | Server-side clamp |
| Administrator player access level input | 0–1 | Server-side clamp |
| RTS battle theater types | 5 | Fixed content catalog |
| RTS mission types | 7 | Fixed content catalog |
| RTS tactical order types | 16 | Fixed content catalog |
| RTS base fleet archetypes | 6 | Fixed content catalog |
| Sabotage operation types | 7 | Fixed content catalog |
| Player interface density | 3 modes | Fixed option set |
| Administrator session duration | 12 hours | Fixed session rule |
| Player password minimum | 8 characters | Validation minimum, not a level |

### 3.2 Systems currently uncapped by code

The following systems currently use integer level fields without a general maximum detected in the audited modules and schemas:

| System | Fields or examples | Current cap status |
|---|---|---|
| Infrastructure buildings | Legacy building levels and construction records | Uncapped in current build |
| Core research | Income, production, supply, covert, anti-covert, attack, defense, and related technology values | Uncapped in current build |
| Stargate and hyperspace development | Jump gate, stargate, core, route, and related progression | Uncapped in current build |
| Power grid | Reactor, storage, grid, and efficiency levels | Uncapped in current build |
| World command systems | Command and sensor levels on combat sites | Uncapped in current build |
| Combat technology | Weapons, shields, targeting, armor, reactor, and command levels | Uncapped in schema; migration must be applied to activate the table |
| Combat installations | Weapon, shield, and structure installation levels | Uncapped in current schema |
| Unit veterancy | Not yet represented as a separate persistent level | Not implemented |
| Research queue depth | No universal cap identified | Uncapped or module-dependent |
| Player resource stockpiles | Database integer/bigint capacity and admin clamps apply in different operations | No single gameplay cap |

### 3.3 Recommended release caps

For live balance, the following caps are recommended but are **not yet universal enforced caps**. They should be introduced through a future balance migration and shared validation service.

| Progression family | Recommended release cap |
|---|---:|
| Infrastructure buildings | 30 |
| Core research technologies | 30 |
| Stargate and hyperspace technologies | 25 |
| Power reactor, storage, grid, and efficiency | 25 |
| Combat technology | 30 |
| Combat-site command and sensors | 25 |
| Weapons, shields, and structures | 20 per installation |
| Player military rank | 50 |
| Unit veterancy | 10 |
| Maximum battle waves | 8 current hard cap |

The recommended caps should not be treated as active until implemented in both server-side validation and database-aware upgrade logic.

## 4. Player Progression Loop

A player begins on a homeworld with a basic resource reserve, a starting race, an initial home-world identity, and access to the command shell. The player expands infrastructure, increases production, establishes power capacity, researches military and covert technologies, commissions fleet units, explores the procedural universe, and chooses between economic, military, diplomatic, covert, or exploratory strategies.

The intended progression loop is:

```text
Create account → develop homeworld → establish power grid → research technologies
→ construct fleet and defenses → explore worlds → launch missions or sabotage
→ resolve battles and waves → recover salvage → expand civilization
```

Progression is divided into five strategic tiers:

| Tier | Strategic identity | Main unlock pattern |
|---|---|---|
| I. Foundation | Establish a viable civilization | Basic infrastructure, resources, homeworld power, starter fleet |
| II. Expansion | Build a connected regional presence | Planets, moons, stations, routes, stronger production and logistics |
| III. Military maturity | Control battle theaters | Combat technology, installations, advanced fleet archetypes, defense waves |
| IV. Strategic influence | Shape other civilizations | Sabotage, counterintelligence, raids, sieges, salvage economies |
| V. Empire command | Coordinate multi-world operations | High-level power networks, multi-wave campaigns, capital ships, administrative mastery |

## 5. Resources and Economy

The current game uses the classic strategic resources **metal**, **crystal**, and **deuterium**, with **energy** acting as the operational resource for power grids and combat activity. Resources support construction, research, fleet activity, power operations, missions, and recovery.

| Resource | Primary use |
|---|---|
| Metal | Hulls, infrastructure, installations, construction, and salvage value |
| Crystal | Research, targeting systems, shields, advanced components, and salvage value |
| Deuterium | Fleet movement, hyperspace, reactors, logistics, and salvage value |
| Energy | Power grids, battle-round consumption, shields, and operational readiness |

The administrator can tune production and fleet speed multipliers, apply combat damage multipliers, set defense repair ratios, grant bounded player resources, and queue economy refresh jobs. Economy changes must remain auditable and should be used for balancing or controlled operations rather than routine player progression.

## 6. Worlds and Procedural Universe

The universe is deterministic from a stored seed. World records support stars, planets, moons, discoveries, intelligence profiles, designs, effects, and classifications. The taxonomy uses A–Z classes and a world size range of 1–9.

### 6.1 World hierarchy

```text
Universe
 └── Star system
      ├── Star
      ├── Planet
      │    └── Moon
      ├── Starbase
      ├── Moonbase
      └── Spacestation
```

### 6.2 World attributes

Each world may expose a type, subtype, class, subclass, biome, sub-biome, size, orbital context, intelligence profile, strategic value, power node, combat site, and available designs. World intelligence is intended to reveal production, defense, power, anomalies, and strategic opportunities progressively.

### 6.3 World-based power

Power nodes support the types `homeworld`, `planet`, `moon`, `starbase`, `moonbase`, and `spacestation`. Each node has reactor, storage, grid, and efficiency levels, along with production rate, consumption rate, capacity, stored power, and grid status. Valid statuses are online, brownout, offline, and overloaded.

## 7. Infrastructure and Construction

Infrastructure is the civilization’s industrial foundation. Buildings increase resource output, storage, research, fleet capacity, power availability, defenses, and world functionality. The current legacy-compatible implementation does not enforce a universal maximum building level.

Future infrastructure categories should include:

| Category | Examples |
|---|---|
| Resource production | Metalworks, crystal arrays, deuterium refineries |
| Storage | Resource vaults, cryogenic tanks, orbital depots |
| Power | Reactors, fusion plants, solar collectors, grid relays |
| Research | Laboratories, intelligence centers, weapons labs |
| Fleet | Shipyards, construction bays, repair docks, carriers |
| Defense | Armor grids, shield arrays, weapons platforms, command bunkers |
| Logistics | Gate relays, depots, convoy hubs, hyperspace beacons |
| Civilization | Habitats, governance centers, cultural and population systems |

## 8. Research and Technology

Research is organized around economic, military, power, hyperspace, intelligence, and covert development. The current schema includes legacy core technology values and a dedicated offense-defense technology model with weapons, shields, targeting, armor, reactor, and command fields.

The dedicated `combat_technology` table exists in migration `14_offense_defense.sql` but must be present in the active database before those records can be used. This is a deployment prerequisite rather than a gameplay cap.

Recommended future research branches:

| Branch | Core attributes |
|---|---|
| Weapons | Attack power, armor penetration, accuracy, critical chance |
| Shields | Shield capacity, recharge, absorption, overload resistance |
| Armor | Hull integrity, damage reduction, structural resilience |
| Targeting | Range, initiative, accuracy, focus-fire efficiency |
| Reactor | Energy production, reserve, battle efficiency |
| Command | Morale, formations, order points, wave coordination |
| Covert | Infiltration, trace reduction, operation success |
| Counterintelligence | Detection, sensor coverage, recovery speed |
| Hyperspace | Speed, route range, expedition access, logistics |

## 9. Fleet and Unit Catalog

The current RTS catalog contains six reusable unit archetypes.

| Unit | Class | Hull | Shield | Armor | Attack | Defense | Range | Speed | Initiative | Energy draw |
|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| Raptor Interceptor | Strike Craft | 90 | 35 | 18 | 34 | 22 | 2 | 8 | 9 | 3 |
| Valkyrie Frigate | Escort | 220 | 90 | 45 | 60 | 48 | 3 | 6 | 7 | 8 |
| Argent Cruiser | Line Cruiser | 430 | 180 | 82 | 108 | 72 | 4 | 5 | 5 | 15 |
| Leviathan Battleship | Capital Ship | 760 | 300 | 145 | 190 | 105 | 5 | 3 | 3 | 26 |
| Aurora Carrier | Command Carrier | 620 | 260 | 115 | 125 | 120 | 4 | 4 | 4 | 30 |
| Aegis Dreadnought | Siege Hull | 1,100 | 480 | 230 | 300 | 160 | 6 | 2 | 2 | 45 |

Quantity is stored per battle-unit record. The current build does not define a universal player fleet-size maximum; practical limits come from resources, fleet infrastructure, power reserves, mission rules, and database capacity.

## 10. Offense and Defense Model

A combat unit is evaluated using primary attributes and sub-stats.

### 10.1 Primary attributes

| Attribute | Function |
|---|---|
| Hull | Structural integrity; reaching zero destroys or disables a unit |
| Shield | Absorbs incoming damage before armor and hull |
| Armor | Reduces damage after shield absorption |
| Attack | Base outgoing damage |
| Defense | Defensive rating used in accuracy and survivability checks |
| Range | Determines effective distance and attack eligibility |
| Speed | Movement and maneuver advantage |
| Initiative | Determines action order |
| Morale | Affects readiness, disruption, and combat performance |
| Ammunition | Limits sustained weapons use |
| Energy draw | Power consumed by the unit during battle resolution |
| Position | Tactical distance and formation location |

### 10.2 Combat sub-stats and future extensions

The battle resolver currently supports accuracy, range, weapon multiplier, armor mitigation, shield absorption, guard mitigation, morale loss, hull damage, initiative ordering, position changes, and power use. Future combat depth should add critical chance, penetration, evasion, electronic warfare, heat, reload, damage types, status effects, repair capacity, command radius, and formation bonuses.

## 11. Turn-Based RTS Battle Engine

Battles are persistent and stored in `rts_battles`, `rts_battle_units`, `rts_battle_orders`, `rts_battle_rounds`, and `rts_battle_reports`. A battle tracks theater, mission, status, round number, turn owner, scores, seed, power reserve, phase, wave, objective, and maximum wave count.

### 11.1 Battle theaters

The five current theaters are orbital, planetary, moon, station, and frontier.

### 11.2 Battle phases

| Phase | Purpose |
|---|---|
| Approach | Establish mission and target context |
| Orbital | Resolve fleet and orbital defense actions |
| Surface | Resolve planetary or moon installations |
| Extraction | Resolve salvage, retreat, escort, or withdrawal objectives |
| Complete | File the final report and settle rewards |

### 11.3 Round sequence

```text
1. Validate player session, battle ownership, status, and power
2. Load active units and queued orders
3. Sort actions by initiative and unit identity
4. Select valid opposing targets
5. Apply movement, attack, defense, support, and withdrawal effects
6. Resolve shields, armor, hull, morale, ammunition, and energy
7. Save unit state and round report
8. Check victory, defeat, draw, retreat, or next-wave conditions
9. Spawn reinforcements or settle salvage when required
```

### 11.4 Current tactical orders

The current order catalog contains 16 actions.

| Category | Orders |
|---|---|
| Attack | Attack, Bombard, Intercept, Flank, Siege, Blockade, Focus Fire, Jam |
| Maneuver | Advance |
| Defense | Guard, Shield Wall, Withdraw |
| Support | Escort, Reinforce, Repair, Countermeasure |

Orders are validated against the authenticated player, active battle, selected unit, current round, and allowed order enum. The resolver applies action-specific multipliers and support effects.

## 12. Attack and Defense Wave Campaigns

Wave campaigns extend a single engagement into a persistent sequence of attack or defense formations. The current hard maximum is **8 waves**, enforced when a mission is launched.

### 12.1 Mission types

| Mission | Default wave count | Objective |
|---|---:|---|
| Assault | 3 | Break every defense wave and secure the theater |
| Defense | 4 | Survive every attack wave and protect the command site |
| Raid | 2 | Strike the target, seize salvage, and withdraw |
| Intercept | 3 | Stop the hostile fleet before it reaches the gate |
| Siege | 5 | Destroy layered defenses across orbital and surface phases |
| Escort | 3 | Protect the convoy through every hostile wave |
| Expedition | Configurable, clamped to 1–8 | Explore the frontier and return with intelligence |

Wave records track wave number, side, wave type, status, strength index, composition, spawn time, and resolution time. The AI scales hostile groups by wave number. Clearing a hostile force before the maximum wave spawns the next reinforcement wave; only the final wave can complete the mission.

## 13. Sabotage and Counterintelligence

Sabotage provides a covert alternative to direct combat. The seven current operations are Power Blackout, Weapons Disruption, Shield Infiltration, Production Strike, Logistics Cut, Defense Breach, and Command Intrusion.

A sabotage mission evaluates infiltration power, target anti-covert defense, alert level, sensor bonus, trace bonus, mission variance, detection risk, cooldown, effect duration, and effect strength.

Successful operations can create temporary effects against power, weapons, shields, production, logistics, defenses, or command systems. Detected missions raise alert and trace values. Countermeasures can purge active effects and create recovery records.

No universal maximum sabotage level is currently enforced. The strategic constraint is mission action-turn availability, cooldowns, target eligibility, detection risk, and counterintelligence strength.

## 14. Account and Authentication Systems

Player login is enabled through `game_login_required=1`. The title page offers Civilization Login and Found Your Civilization. Player accounts use legacy-compatible credential records plus modern account settings and security-event records.

Player account options include profile updates, email, home-world name, display density, timezone, message notifications, battle notifications, online-status visibility, password changes, security history, and logout.

Passwords for new accounts require at least eight characters. Authentication uses the existing session model and account-security event logging. Production deployments should use HTTPS, rotate development credentials, and keep database secrets outside the repository.

## 15. Administrator Control Plane

The administrator console is separate from player login and is protected by administrator sessions, roles, expiration, server-side authorization, CSRF validation, prepared statements, bounded inputs, and audit records.

| Administrator capability | Current control |
|---|---|
| Game logic | Login gate, pause, registration, combat, expeditions, maintenance, turn interval, message |
| Economy | Production multiplier, resource grants, reserve editing, metrics refresh |
| Combat | Fleet speed, damage multiplier, defense repair ratio, combat toggle |
| Power | Power multiplier and recalculation jobs |
| Player governance | Access state and bounded resource administration |
| Operations | Universe index rebuild, economy refresh, power recalculation, combat-integrity repair |
| Security | Create administrators, activate/deactivate accounts, session cleanup, audit history |

The administrator roles are moderator, operator, and superadmin. The current server-side role hierarchy is moderator < operator < superadmin.

## 16. Public Title Page and Presentation

The title page uses the visible brand **Universe Civilization: Empire at Wars**. It includes redesigned galaxy panels, a redesigned 480 × 84 logo banner, Civilization Login, Found Your Civilization, Administrator Console, release information, patch information, Terms of Service, privacy information, GitHub attribution, and an Audio On/Audio Off control.

The audio layer contains a 115.7-second ambient space track and interface effects for hover, click, confirmation, and warning states. Playback begins after user interaction to respect browser autoplay policies.

## 17. Release and Patch Model

The current documented release identity is **Version 0.9.0 · Frontier Preview · Build 2026.08.17**. The release channel is stable preview, but the project remains under active development.

Future patch notes should classify changes as:

| Patch class | Meaning |
|---|---|
| Hotfix | Security, crash, data-integrity, or blocking gameplay correction |
| Balance | Resource, combat, power, wave, or progression tuning |
| Feature | New player-facing or administrator-facing system |
| Content | New worlds, unit archetypes, technologies, missions, or story content |
| Infrastructure | Deployment, migration, performance, compatibility, or tooling change |

## 18. Save, Persistence, and Data Integrity

Persistent systems use MariaDB/MySQL tables. Core game data includes players, userdata, bank, units, technology, power nodes, power assets, application settings, audit records, combat battles, units, orders, rounds, reports, missions, waves, targets, events, salvage, sabotage missions, sabotage effects, recovery logs, player account settings, administrator users, administrator sessions, and operational jobs.

Turn resolution and covert missions should remain transactional in future hardening work. The current engine persists intermediate records and reports but should receive explicit transaction boundaries, idempotency keys, and job-lock protection before high-concurrency production use.

## 19. Security and Operations Requirements

Production deployment requirements are HTTPS, strong administrator password rotation, environment-based database secrets, regular database backups, restricted administrator access, migration checks, CSRF protection for every state-changing form, prepared SQL statements, escaped output, and audit review.

The public GitHub repository must not contain real administrator passwords, private keys, production database credentials, or user secrets. The login guide intentionally documents the administrator username and setup process without committing the local development password.

## 20. Known Current Limitations

The current build is a feature-rich pre-alpha rather than a finished production game. The primary limitations are the absence of a single shared level-cap service, incomplete deep research progression, no persistent unit veterancy layer, limited player-versus-player matchmaking, limited diplomacy, no universal transaction wrapper around every state-changing operation, and a combat technology migration that must be applied to installations that do not yet contain the dedicated table.

The AI currently provides a deterministic frontier opponent and wave escalation rather than a complete strategic civilization AI. Resource and fleet capacity balancing remains configuration-driven. Some legacy modules remain compatible wrappers and should be progressively refactored.

## 21. Future Roadmap

### Release 0.9.x: Stability and balance

Add migration verification to health checks, introduce shared numeric validation, complete account settings persistence for all active players, add explicit combat transactions, expand automated smoke tests, and establish patch-note discipline.

### Release 1.0: Strategic beta

Implement universal level caps, construction queues, research queues, fleet capacity, persistent unit veterancy, formation bonuses, player target discovery, diplomacy, alliance governance, and improved mission matchmaking.

### Release 1.1: Civilization depth

Add population, culture, governance, world specialization, trade routes, treaties, espionage networks, intelligence degradation, multi-world production chains, and strategic command-radius systems.

### Release 1.2: Advanced warfare

Add damage types, heat and ammunition logistics, boarding, carriers and squadrons, orbital bombardment consequences, surface occupation, repair fleets, persistent wreck fields, and multi-theater campaigns.

### Release 1.3: Live operations

Add scheduled turns, background mission processing, notification queues, event-driven wave encounters, season rules, admin scenario tools, rate limits, operational dashboards, and production-grade backup/restore procedures.

## 22. Acceptance Criteria for the Current Build

The current build is considered functionally acceptable for pre-alpha when:

| Criterion | Current target |
|---|---|
| New player can register | Required |
| Player can log in and reach command shell | Required |
| Player can access account settings | Required |
| Player can view power and infrastructure systems | Required |
| Player can create a battle mission | Required |
| Player can issue tactical orders | Required |
| Player can resolve a battle round | Required |
| Player can progress through waves | Required |
| Player can view battle reports and salvage | Required |
| Player can open sabotage operations | Required |
| Unauthenticated admin access is blocked | Required |
| Title page branding and logo are correct | Required |
| Audio is user-controlled and browser-safe | Required |
| No obvious PHP warnings in smoke-tested responses | Required |

## 23. Glossary

| Term | Definition |
|---|---|
| Battle theater | The location context of an engagement: orbital, planetary, moon, station, or frontier |
| Combat site | A persistent defensive or offensive site associated with a world or power node |
| Mission | A multi-round objective with a target, phase, rewards, and wave plan |
| Wave | A persistent player or AI force entering a mission campaign |
| Power node | A world, base, or station energy-management object |
| Salvage | Resources and recovered units awarded after a successful mission |
| Counterintelligence | Detection and recovery systems opposing covert operations |
| Command console | The authenticated player interface for operating the civilization |
| Control plane | The protected administrator interface for managing game logic and operations |

## 24. Source Audit Notes

This document is based on the current repository schemas and modules, including the RTS catalog and mission logic in `modules/rtscombat.php`, world combat definitions in `database/sql/14_offense_defense.sql`, wave definitions in `database/sql/17_battle_waves_and_missions.sql`, player account definitions in `database/sql/19_player_accounts.sql`, power-grid definitions in `database/sql/10_power_grid.sql`, and administrator clamps in `admin/index.php`.

Where the source did not enforce a maximum, this document explicitly reports **uncapped in the current build** rather than inventing a level limit. Recommended release caps are design targets for future implementation.
