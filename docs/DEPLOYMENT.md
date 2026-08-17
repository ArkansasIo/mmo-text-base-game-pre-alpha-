# Deployment Guide

## Requirements

Use PHP 8.3 or later with `mysqli`, MariaDB/MySQL, a web server capable of serving PHP, and a modern browser. Production deployments should use HTTPS, a restricted administrator route, environment-based secrets, scheduled database backups, and monitored logs.

## Local deployment

From the project root, initialize the database, import the core schema, run migrations, and start PHP:

```bash
mysql -u root -p < database/sql/01_create_database.sql
mysql -u sgw -psgwpass sgw < game.sql
./scripts/backend/db_migrate.sh
./scripts/backend/healthcheck.sh
/usr/bin/php -S 0.0.0.0:8080
```

Open `http://127.0.0.1:8080/index.php`. Verify that the title page displays the Universe Civilization branding, logo, galaxy artwork, audio toggle, login, registration, release, terms, and privacy links.

## Configuration

Use `config.php` for shared defaults and a local override or environment variables for machine-specific values. Database credentials, administrator credentials, session secrets, and production URLs must never be committed. The player login setting should remain enabled with `game_login_required = 1` on a public deployment.

## Migration order

Apply SQL migrations in filename order. Recent feature migrations include player accounts, RTS combat, battle waves and missions, sabotage, and communications. The runtime feature modules contain defensive table creation for compatibility, but normal deployment should still apply every migration so indexes, constraints, and operational defaults are present.

## Production hardening

Change all development passwords, use a unique database password, restrict database network access, disable directory listing, configure secure and HTTP-only cookies, use HTTPS, restrict `/admin/` by network or gateway where practical, and ensure backups are encrypted. Remove temporary test players and review public error output before launch.

## Backup and rollback

Back up MariaDB before migrations, balance normalization, resource operations, or combat schema changes:

```bash
./scripts/backend/db_backup.sh
```

Record the application commit and migration state with the backup. Roll back by restoring the previous application release and database backup according to the release notes. Do not reverse uncertain combat or economy operations with ad hoc SQL.

## Verification checklist

Run the following before release:

```bash
find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l
./scripts/backend/healthcheck.sh
php tests/progression_caps_test.php
git diff --check
```

Then verify public title-page rendering, restored login gating, registration, player login, account settings, communications, power, infrastructure, RTS Combat, waves, sabotage, and unauthenticated administrator protection. Review the audit log and server error log after the first authenticated session.
