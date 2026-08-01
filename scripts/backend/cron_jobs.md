# Cron Jobs

## Game Tick

Run every 5 minutes to process:
- resource economy ticks (30-minute cadence)
- food/water/energy upkeep and population penalties
- hyperspace transit arrivals and returns
- expedition rewards

Command:

```bash
php /home/codespace/Stargate-Wars/scripts/backend/game_tick.php
```

Dry-run test:

```bash
php /home/codespace/Stargate-Wars/scripts/backend/game_tick.php --dry-run
```

Single player test:

```bash
php /home/codespace/Stargate-Wars/scripts/backend/game_tick.php --uid=1
```

Example crontab:

```cron
*/5 * * * * /usr/bin/php /home/codespace/Stargate-Wars/scripts/backend/game_tick.php >> /home/codespace/Stargate-Wars/exports/game_tick.log 2>&1
```
