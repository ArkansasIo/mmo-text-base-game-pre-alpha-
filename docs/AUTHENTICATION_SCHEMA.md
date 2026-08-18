# Authentication and Account Schema

This document describes the account tables used by **Universe Civilization: Empire at Wars**. The application uses the legacy-compatible `users` and `userdata` records for login identity and game-session state, with companion tables for account settings and security history.

## Authentication relationship map

```text
users
 ├── userdata        player session/game metadata
 ├── bank            currency balances
 ├── units           trained and untrained population units
 ├── technology      player research and military technology
 ├── power            calculated power totals
 ├── rank             calculated rankings
 ├── planets         home worlds and population
 ├── player_account_settings
 └── player_security_events

admin_users
 └── admin_sessions  separate administrator authentication plane
```

## Core player identity

| Table | Key fields | Purpose |
|---|---|---|
| `users` | `uid`, `uname`, `email`, `password`, `alevel`, `allyid`, `arank`, `lastLogin`, `ip` | Login identity, salted legacy-compatible password value, player access level, guild/alliance association, rank, and login metadata |
| `userdata` | `uid`, `link`, `actionTurns`, `rid`, `uname`, `cid`, `progress` | Player-facing game state, race, action turns, unique profile link, and progress values |
| `admin_users` | `id`, `username`, `email`, `password_hash`, `role`, `is_active`, `last_login_at` | Separate administrator identity and role control; passwords use `password_hash`/`password_verify` |
| `admin_sessions` | `session_id`, `admin_id`, `token_hash`, `expires_at`, `created_at`, `last_seen_at` | Expiring administrator sessions with hashed random tokens |

The player login accepts either username or email. Player passwords follow the existing legacy-compatible hashing method in `base/User.class.php`. Administrator passwords are managed separately by `scripts/backend/create_admin.php` and must be supplied through environment variables.

## Player account companion tables

| Table | Key fields | Purpose |
|---|---|---|
| `bank` | `uid`, `inbank`, `onHand` | Player currency balances |
| `units` | `uid`, `attack`, `defense`, `covert`, `anticovert`, `untrained` and related fields | Military and population unit counts; new players receive the configured starting untrained reserve |
| `technology` | `uid`, `income`, `unitProd`, combat, covert, defense, and capacity fields | Player technology progression and combat-related modifiers |
| `power` | `uid`, `overall`, `mil_atk`, `mil_def`, `mil_cov`, `mil_anti`, `mil_total` | Calculated power aggregates |
| `rank` | `uid`, `overall`, military category totals | Calculated ranking aggregates |
| `planets` | `uid`, `plnt_name`, `isHome`, `pid`, `population`, `pop_cap`, world fields | Player home worlds and planetary population state |
| `player_account_settings` | `uid`, `theme`, `density`, `timezone`, `landing_page`, audio flags, notification flags, `profile_visibility`, `session_timeout_minutes` | Account interface, sound, alert routing, privacy, and session preferences |
| `player_security_events` | `event_id`, `uid`, `event_type`, `details`, `created_at` | Login, logout, password, profile, and preference security history |

## Authentication flow

1. The title page receives a username or email and password.
2. The server loads the matching `users` row and verifies the submitted password using the project’s compatible password helper.
3. The server creates the authenticated player session with the user ID, username, access level, race, and game-state values.
4. Protected modules require the authenticated session and validate CSRF tokens for state-changing operations.
5. Account changes update the relevant companion table and write a security-event record.

## Player account settings

The in-game **Account Settings** panel is available from the player navigation after authentication. It provides profile editing for email and home-world name; interface controls for theme, density, timezone, default command view, interface sounds, ambient music, and reduced motion; alert routing for messages, battles, guild communications, celestial events, market activity, and raids; privacy controls for profile visibility and online status; session timeout selection; and password changes.

All state-changing forms require the per-session account CSRF token. Theme, density, landing-page, timezone, visibility, and timeout values are checked against server-side allowlists. The settings table is created by migration `19_player_accounts.sql`, while the runtime module contains compatibility DDL for older local databases that already have the original settings table.

## Local test-user provisioning

Use the local-only provisioning command from the repository root:

```bash
APP_ENV=local \
SGW_TEST_USERNAME=tessssssst \
SGW_TEST_EMAIL=tessssssst@example.local \
SGW_TEST_PASSWORD='TestPassword!123' \
SGW_TEST_HOMEWORLD='Test World' \
php scripts/backend/create_test_user.php
```

The command defaults to the same values shown above, but the password can be replaced through `SGW_TEST_PASSWORD`. It refuses to run when `APP_ENV` is not `local` or `development`, unless `ALLOW_TEST_USER=1` is explicitly set. It invokes the normal `User::addUser()` path, so it initializes the bank, units, technology, power, rank, home planet, and userdata records consistently with player registration.

> Do not use the local test-user password on a public deployment. Create a unique production account through the normal registration flow and keep administrator passwords outside the repository.

## Web authentication API

The JSON endpoint is available at `/api/auth.php` and accepts POST requests. Player login uses `mode=player`, while administrator login uses `mode=admin`.

```bash
curl -sS -X POST http://127.0.0.1:8080/api/auth.php \
  -H 'Content-Type: application/json' \
  -d '{"mode":"player","action":"login","username":"tessssssst","password":"TestPassword!123"}'
```

Administrator login uses the same endpoint with `mode=admin`. Successful responses contain only the authenticated account ID, username, access or role, and mode. Failed login responses are intentionally generic. Logout is available with `{"mode":"player","action":"logout"}` or the corresponding administrator mode.

## Default administrator provisioning

The existing administrator provisioner can be used with explicit environment values. A convenience wrapper supplies `admin`, `admin@example.local`, and `superadmin` as defaults while requiring the password from the environment:

```bash
SGW_ADMIN_PASSWORD='use-a-unique-12-character-password' \
php scripts/backend/create_default_admin.php
```

The wrapper never contains a default administrator password. It delegates to the existing password-hashing and upsert logic in `scripts/backend/create_admin.php`.

## Authentication tests

Run the source-level authentication test suite with:

```bash
php tests/authentication_test.php
php tests/account_settings_test.php
```
