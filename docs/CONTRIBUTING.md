# Contributing

## Project principles

Universe Civilization: Empire at Wars is a legacy-compatible browser strategy project. Contributions should improve clarity and reliability without breaking existing sessions, database records, module routes, or the blue/cyan industrial command experience.

## Development workflow

Create a focused branch, inspect the affected module and schema, make the smallest coherent change, and document any new game rule. New systems should include a migration, server-side validation, safe output escaping, an authenticated access check, and a browser smoke test when the feature is user-facing.

## PHP and database standards

Use full PHP tags, prepared statements for user-controlled values, strict integer validation, bounded numeric inputs, and explicit authentication checks. Use `htmlspecialchars` for rendered user content and CSRF validation for state-changing forms. Do not trust hidden fields, JavaScript checks, or client-side maximums.

Database migrations belong in `database/sql/` and use ordered numeric filenames. New tables should include appropriate indexes, explicit defaults, and foreign keys where the legacy schema permits them. Runtime defensive bootstraps may support an existing installation, but they do not replace a migration.

## Testing

Before opening a pull request, run syntax checks, the backend health check, relevant automated tests, and whitespace validation:

```bash
find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l
./scripts/backend/healthcheck.sh
php tests/progression_caps_test.php
git diff --check
```

Feature changes should test both a valid operation and a rejected operation. State-changing tests should use temporary records and clean up after themselves. Never use production credentials or personal data in tests.

## User interface

Preserve the left-side navigation structure, responsive behavior, accessible labels, and industrial blue/cyan visual language. New modules should use the shared AJAX request contract and should render useful feedback when authentication, validation, power, resources, or permissions prevent an action.

## Pull requests

A pull request should explain the player-facing change, data migration, server-side validation, test commands, compatibility impact, and rollback plan. Include screenshots or response examples for substantial interface changes. Do not commit passwords, API keys, session files, database dumps, generated private exports, or temporary test credentials.
