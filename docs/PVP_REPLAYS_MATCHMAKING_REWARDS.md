# PvP Replays, Matchmaking, and Season Rewards

The ranked PvP system now stores structured combat replay events for resolved battles. Each replay contains launch, engagement, and resolution phases with event timing, labels, power snapshots, and loss totals. The events are idempotent through a unique battle and sequence key.

Ranked matchmaking queues a commander’s origin planet, target planet, fleet, fitting, season, rating, and division. The queue begins with a 150-point ELO search window and expands by 100 points for each minute of waiting, up to 600 points. The PvP worker converts eligible queued pairs into timed battles.

When a ranked season ends, the reward worker distributes durable top-three placements. Rewards include Dark Matter and core resources, with Champion, Vice Champion, and War Marshal tiers. Claims lock the reward row, credit resources transactionally, and mark the reward claimed so a commander cannot receive it twice.

Migration 45 creates `pvp_replay_events`, `pvp_matchmaking_queue`, and `pvp_season_rewards`. The locked cron dispatcher exposes `pvp_rewards_tick`, while `pvp_tick` processes matchmaking before resolving due battles.
