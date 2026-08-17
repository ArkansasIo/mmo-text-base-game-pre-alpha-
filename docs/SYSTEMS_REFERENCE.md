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
