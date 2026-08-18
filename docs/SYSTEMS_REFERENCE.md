# Systems Reference

## Runtime structure

The application is a legacy-compatible PHP 8.3 and MariaDB/MySQL browser game. `index.php` provides the public title page and authenticated shell. `base/` contains shared classes, `modules/` contains AJAX-loaded feature modules, `templates/` contains shell layouts, and `js/` contains browser request and interface helpers.

| Layer | Primary responsibility |
|---|---|
| `index.php` | Title page, login gate, registration entry points, public branding, audio controller, and media links |
| `base/User.class.php` | Legacy player authentication, registration, identity, and account compatibility |
| `base/Game.class.php` | Player state, resources, turns, technology, ranks, military calculations, and legacy game operations |
| `base/AdminAuth.class.php` | Administrator sessions, roles, authorization, and audit handling |
| `base/ProgressionCaps.class.php` | Server-side recommended progression policy and upgrade boundary validation |
| `modules/` | Feature-specific account, infrastructure, power, universe, combat, sabotage, communications, and reporting consoles |
| `database/sql/` | Ordered schema migrations, catalog seeds, defaults, and feature persistence |
| `js/main.js` | Shared module loading and AJAX request contract |

## Authentication and sessions

Player access is controlled by the `game_login_required` setting. The expected public-deployment value is `1`. Player and administrator sessions are separate. Administrator access is not granted by player status, and player access is not granted by administrator credentials.

## Persistence map

| System | Main persistence |
|---|---|
| Player identity | `users`, `userdata`, `player_account_settings`, `player_security_events` |
| Resources and economy | `bank`, `resources`, technology and legacy economy tables |
| Technology | `technology`, `combat_technology`, legacy research fields |
| Power | Power-grid migration tables and node records |
| Universe | Seed, world, intelligence, blueprint, and exploration migration tables |
| RTS combat | Battle, unit, order, round, report, mission, wave, target, event, and salvage tables |
| Sabotage | Mission, effect, counterintelligence, cooldown, and recovery tables |
| Communications | `messages`, `message_preferences`, `guild_channels`, `guild_messages`, `guild_message_reads`, and moderation records |
| Administration | Administrator users, sessions, settings, jobs, and audit records |

## Server authority

The browser renders commands, but PHP and MariaDB remain authoritative. Every state-changing feature must validate the authenticated UID, target ownership or membership, allowed enum values, numeric ranges, resource availability, action turns, CSRF tokens where applicable, and current system state. Client-side limits are informative only.

## Combat resolution

RTS combat is persistent and round-based. A battle has a theater, status, current round, energy cost, and player ownership. Units have combat attributes and orders. Resolution calculates initiative, target selection, range, accuracy, weapon output, shield absorption, armor mitigation, morale, hull damage, power consumption, and AI response. Lethal hull damage is clamped to zero, destroyed units are excluded from later actions, and round reports persist combat events.

## Mission and wave resolution

A mission associates a battle with a type, objective, target theater, wave count, and reward. Waves are stored separately and may spawn reinforcements after a defending force is cleared. The current wave maximum is eight. Final mission completion creates a salvage record and applies the configured reward according to the mission outcome.

## Covert operations

Sabotage compares infiltration capability with the target’s anti-covert profile, alert level, sensors, trace, and mission variance. Successful operations create temporary effects with expiry timestamps. Detected operations raise counterintelligence pressure. The operation console requires opposing-player targets and consumes action turns.

## Communications

The legacy `messages` table supports player-to-player private mail and remains available for compatibility. The Communications console adds a modern inbox/composer view and guild channels backed by `guild_messages`. Guild membership is currently represented by `users.allyid`. A player without a nonzero guild ID cannot post to guild channels.

## Progression policy

`ProgressionCaps` currently defines the recommended values and clamps negative inputs to zero. The main legacy technology upgrade path applies the shared maximum to technology families. Individual construction, installation, rank, and veterancy endpoints should use the same helper as those systems continue to be modernized.

## Request contract

The shared shell uses AJAX-style module requests containing a module name, request type, target display area, and time value. Direct module testing should include the same query parameters used by `sendData`, including `id`, `atype`, and `time`. Missing authentication or required routing values should produce the expected redirect rather than a fatal error.

## Guild command system

Guilds support a maximum of **150 members**. Membership is stored in `guild_members`, while `users.allyid` remains synchronized for compatibility with the legacy alliance roster. Guild ranks are Founder, Marshal, Officer, and Member. Officers can invite players; senior officers can later be extended with promotion, removal, treasury, and governance workflows.

Guild contributions increase the shared treasury and contribution score. The server calculates bounded guild bonuses from member count, guild level, and contribution activity. The current bonus families are production, defense, research, and fleet recovery. All membership and contribution mutations require an authenticated session, a valid CSRF token, server-side numeric limits, and current membership state.

## Population and army rules

New accounts begin with **2,500,000 untrained units**. This is the recruitable population reserve and is separate from the trained army. New home planets receive a server-generated population count. Colonized planets receive a new random population count, and persistent moon records receive their own random population count when initialized. Planet populations are bounded from 100,000 to 10,000,000 by the model, while moon populations are bounded from 10,000 to 2,000,000.

The base trained-army size is **250,000 units**. Recruitment consumes untrained units, but a recruitment operation is rejected when the combined attack, defense, covert, and anti-covert corps would exceed the trained-army capacity. The limit is enforced in PHP through `ArmyPolicy`, so browser-supplied quantities cannot bypass it.

## Territory production and taxation

Each claimed territory produces metal, crystal, energy, and tax credits every 30-minute game tick. Output scales with control points and guild level. The territory tax rate is server-clamped from 0% through 25%; tax credits are calculated from the territory tax base, while metal, crystal, and energy are produced as strategic guild resources.

Accrual is processed by `scripts/backend/game_tick.php`. The settlement is idempotent under concurrent workers through an optimistic timestamp check, records each positive resource payment in `guild_resource_ledger`, and caps offline catch-up at 48 ticks. Territory production is deposited into the guild treasury rather than directly into a player account.

## Guild market and trade routes

Claimed territories can hold tradable metal, crystal, and energy stock. Guild members may post internal market orders with a bounded unit price and reserved cargo, then fulfill open orders into another claimed territory using guild treasury credits. Orders expire after seven days and are filled incrementally.

Trade routes move cargo between two claimed territories belonging to the same guild. The origin stock is reserved at dispatch, route time is deterministically calculated from the two sector codes, and delivery is finalized by the game tick. Routes are limited to supported resource types and a maximum cargo quantity; ownership, status, and stock checks are enforced server-side. Route delivery and market fulfillment create resource-ledger audit entries.

## Guild hierarchy, research, diplomacy, and warfare

Guild permissions are rank-based and server-enforced. Members can view the console, contribute, use the market, and dispatch trade. Officers can invite members, propose diplomacy, and start research. Marshals can manage officers, territories, diplomacy, warfare declarations, and treasury operations. The Founder retains the highest control tier.

The guild research tree contains Industrial Logistics, Military Doctrine, Fortress Networks, and Diplomatic Protocols. Each technology has a maximum level of 10, a credit cost that increases by level, a research duration, and prerequisite rules. Industrial Logistics increases territory production, Military Doctrine increases raid power, Fortress Networks increases territory defense, and Diplomatic Protocols expands diplomacy capability.

Guilds can establish alliances through normalized diplomacy pairs and declare seven-day wars against rival guilds. Active wars permit scheduled raids against enemy claimed territories. Raid resolution compares attacker power with territory defense, transfers a capped portion of metal and crystal on victory, reduces control points, and may mark a territory contested. Weaker raids are repelled. Research, rank, guild ownership, claimed status, CSRF, and authentication checks are evaluated on the server.

## Shipyards, fleet deployment, leaderboards, and achievements

Fleet construction is server-authoritative. Players use a resource wallet to queue Scout Corvettes, Frontier Frigates, Siege Destroyers, and Fleet Carriers. Each blueprint has metal, crystal, energy, construction-time, attack, defense, and cargo-capacity values. A player may maintain up to eight active construction jobs, and completed jobs are converted into fleet inventory by the game tick.

Fleet deployments validate blueprint keys, fleet power, total capacity, origin and destination identifiers, and travel timing before recording an interplanetary route. En-route deployments become arrived through an idempotent game-tick update and are available for future combat integration.

Leaderboard snapshots track territory power, guild power, member contribution, and member fleet performance. Snapshot records preserve score, rank position, subject type, subject identifier, and capture time. Achievement definitions include Territory Pioneer, Industrial Magnate, Fleet Commander, and War Hero; progress and unlock timestamps are stored per player.

## Player crafting and equipment upgrades

Players can craft Reinforced Hull, Targeting Array, Shield Matrix, and Cargo Frame modules. Each upgrade increases the module level up to 10, with resource costs scaling by level. Reinforced Hull increases defense, Targeting Array increases attack, Shield Matrix increases defense, and Cargo Frame increases fleet cargo capacity.

Crafting transactions deduct metal, crystal, and energy atomically from the player wallet and are recorded for audit purposes. Fleet deployments load the player’s equipment levels and include their combat and capacity modifiers in the server-calculated fleet power.

## Guild event notifications and webhooks

Pirate invasions and celestial anomalies create persistent guild notifications when generated by the game tick. Members can review unread alerts in the Alert Network console and mark them read through authenticated, CSRF-protected requests.

Guild webhooks accept HTTPS endpoints only. Delivery jobs are queued for enabled guild webhooks, sent as JSON payloads, and retried with exponential backoff up to five attempts. Private or reserved network targets are rejected, request timeouts are bounded, delivery failures are recorded, and successful deliveries reset the webhook failure counter.

## Races and government selection

Player registration now offers five renamed races: **Astraeans**, **Noxari**, **Terran Union**, **Asgardian Remnant**, and **Tokari Syndicate**. Each race contributes server-side income, upkeep, attack, and defense modifiers.

Registration also requires a government selection from **1–9**. The available governments are Parliamentary Republic, Imperial Directorate, Federated Commonwealth, Technocratic Compact, Merchant League, Militarized Protectorate, Theocratic Dominion, Hive Council, and Frontier Confederacy. Government bonuses combine with race bonuses and are persisted as `userdata.government_id`.

The server clamps invalid government IDs to the default government and rejects unsupported race selections by falling back to a valid player race. Client-side selectors are presentation only; account creation validates the values before writing the account.

## Local beta-data seeding

After migrations 28–30 are applied and the `beta_tester` account exists, a complete local beta profile can be seeded with:

```bash
APP_ENV=local php scripts/backend/seed_beta_data.php
```

The seed is idempotent and local-only by default. It provisions a minimum wallet of 5,000,000 metal, 3,000,000 crystal, and 1,500,000 energy; fleet inventory of 40 Scout Corvettes, 12 Frontier Frigates, 4 Siege Destroyers, and 1 Fleet Carrier; four equipment modules; two shipyard jobs; and one sample fleet deployment. Set `SGW_BETA_USERNAME` to seed another local player account. The script refuses to run when required fleet and crafting tables are missing.
