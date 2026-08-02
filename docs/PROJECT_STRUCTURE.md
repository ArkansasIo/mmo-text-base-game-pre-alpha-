# Project Structure

## Top-Level Layout

- `base/`: core PHP classes (`Game`, `User`, DB helpers, utility functions).
- `modules/`: gameplay and UI modules loaded through AJAX routing.
- `templates/`: shell/header/footer templates.
- `js/`: client-side module navigation and UI logic.
- `images/` and `fonts/`: static UI assets.
- `database/`: SQL schema, migration layers, and export templates.
- `scripts/backend/`: operational shell scripts (db init/migrate/backup/healthcheck/export).
- `tools/backend/`: backend helper PHP tools used by ops scripts.
- `exports/`: generated SQL/CSV exports.
- `indexpages/`: login/register/update entry pages.

## Compatibility Rule

This repository is legacy-runtime sensitive. Prefer additive organization (docs, indexes, wrappers) instead of moving executable files unless module includes and routes are updated together.
