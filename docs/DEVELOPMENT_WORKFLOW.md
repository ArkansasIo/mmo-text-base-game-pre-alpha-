# Development Workflow

## 1. Change Scope

- Keep gameplay updates isolated to relevant modules.
- Avoid mixing infrastructure and gameplay changes in one commit.

## 2. Validation

- Syntax check before commit:

```bash
find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l
```

- Backend verification:

```bash
./scripts/backend/healthcheck.sh
```

## 3. Push Flow

```bash
git add <target-files>
git commit -m "<clear summary>"
git push arkansas master
```

## 4. Documentation Rule

When adding new systems, add or update the nearest folder README.md and update [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md) if responsibilities changed.
