# Universe Civilization: Empire at Wars
## Login and Account Guide

This guide documents the public player account flow and the protected administrator control plane for **Universe Civilization: Empire at Wars**.

> **Security notice:** The administrator credentials below are development credentials inherited from the current project setup. Change the administrator password before exposing the site publicly, and never publish production credentials in a repository or deployment log.

## Access URLs

| Area | URL | Who can use it |
|---|---|---|
| Title page and player login | `http://127.0.0.1:8080/index.php` | Everyone |
| Player account registration | Title page → **Found Your Civilization** | New players |
| Player account settings | Logged-in game → Empire → **Account Settings** | Authenticated players |
| Administrator console | `http://127.0.0.1:8080/admin/` | Authenticated administrators |
| Project source | [github.com/ArkansasIo](https://github.com/ArkansasIo) | Project collaborators |

For a deployed website, replace `127.0.0.1:8080` with the configured public domain.

## Player Login

Player login is enabled. The active application setting is `game_login_required=1`, so unauthenticated visitors must sign in before entering the game command center.

From the title page, select **Civilization Login**. Enter either the player username or the account email address in the **Email or Username** field, enter the password, and submit the form. A successful login opens the main command console and enables the game modules.

Player sessions use the existing legacy-compatible account records in the `users` and `userdata` tables. The authentication flow establishes the player session, user ID, access level, race, and progress values required by the existing game engine.

## Create a Player Account

Select **Found Your Civilization** on the title page and provide the following information:

| Field | Requirement |
|---|---|
| Username | Required and must not already exist |
| Home Planet Name | Required |
| Password | Required; new passwords must contain at least eight characters |
| E-mail Address | Required and must be a valid email address |
| Race | Select one of the available player races |
| Validation code | Enter the code shown in the image |

The registration process creates the player record and initializes the required bank, unit, technology, rank, resource, world, and account-state records used by the game. Existing account hashes remain compatible with the legacy login system.

## Player Account Settings

After login, open **Empire → Account Settings**. The account console provides profile, preference, security, and session controls.

| Settings area | Available options |
|---|---|
| Profile | View username, update email address, and update home-world name |
| Interface | Choose compact, standard, or expanded display density; select timezone |
| Notifications | Enable or disable message notifications, battle notifications, and online-status visibility |
| Password security | Verify the current password and set a new password of at least eight characters |
| Security history | Review recent profile, preference, password, login, and logout events |
| Session | Sign out of the player account |

Account forms use CSRF tokens, authenticated-session checks, prepared SQL statements, escaped output, and bounded preference values. Player settings are stored in `player_account_settings`, and security activity is stored in `player_security_events`.

## Administrator Login

Open the administrator console at `/admin/`. Administrator authentication is separate from player authentication and uses the `admin_users` and `admin_sessions` tables.

### Current development administrator

| Field | Value |
|---|---|
| Username | `admin` |
| Password | Set through the local deployment environment; do not commit credentials |
| Console | `/admin/` |
| Initial role | Use the role assigned in the administrator database record |

The administrator password is validated with `password_verify`. A successful login creates a random administrator session token that expires after twelve hours. The console updates the administrator last-login time and records state-changing operations in the application audit log.

### Administrator roles

| Role | Capability level |
|---|---:|
| `moderator` | Read and moderate supported operational areas |
| `operator` | Manage game settings, operations jobs, player governance, economy, power, and combat tuning |
| `superadmin` | All operator controls plus administrator-account management |

Role checks are performed server-side. Hiding a control in the interface does not grant permission; every protected operation must pass the administrator authorization check.

## Administrator Control Plane

The administrator console includes controls for game logic, player access, economy, power, combat, maintenance, operations jobs, administrator accounts, metrics, and audit history.

| Control area | Examples |
|---|---|
| Game logic | Login requirement, maintenance mode, registration, combat and expedition toggles, turn interval, and maintenance message |
| Economy | Production multipliers, resource grants, and player resource reserve management |
| Combat | Fleet speed, damage multiplier, defense repair ratio, and combat enablement |
| Power | Power-grid multiplier and power recalculation jobs |
| Player governance | Player access state and bounded resource administration |
| Operations | Universe indexing, economy refresh, power recalculation, and combat-integrity repair jobs |
| Security | Administrator creation, account activation, session cleanup, and audit review |

Destructive or state-changing actions require an authenticated administrator session and CSRF validation. Inputs are validated and clamped server-side. Operations are rejected when the target player, setting, job type, or permission is invalid.

## Login Troubleshooting

If the title page does not display the login form, verify that the database contains the following setting:

```sql
SELECT setting_key, setting_value
FROM app_settings
WHERE setting_key = 'game_login_required';
```

The expected value is `1`. To restore it manually:

```sql
INSERT INTO app_settings (setting_key, setting_value)
VALUES ('game_login_required', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';
```

If a player cannot log in, confirm the account username or email, verify the password, check that the database connection is available, and review the PHP/application logs. If an administrator cannot log in, confirm that the administrator account is active and that the password matches the password hash stored in `admin_users`.

Do not disable the player login gate as a workaround on a public deployment. Use a controlled maintenance message or administrator-managed maintenance mode instead.

## Database Migrations

Apply the account migration during database setup:

```bash
./scripts/backend/db_migrate.sh
```

The account migration is:

```text
database/sql/19_player_accounts.sql
```

The migration creates player settings and security-event tables and restores the player login requirement. The account module also performs defensive table creation for existing installations, but normal deployments should still run all migrations in order.

## Local Development

Start the game server from the repository root:

```bash
/usr/bin/php -S 0.0.0.0:8080
```

Then open:

```text
http://127.0.0.1:8080/index.php
```

Before production deployment, change the development administrator password, configure the database through environment variables or `config.local.php`, use HTTPS, restrict administrator access, and keep backups of the database and migration state.
