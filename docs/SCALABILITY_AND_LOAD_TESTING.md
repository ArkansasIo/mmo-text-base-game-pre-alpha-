# Universe Civilization: Empire At Wars — Scalability and Load Testing

## Executive assessment

The current architecture has no hard-coded total-player limit, but the present game-tick implementation is likely to reach a throughput ceiling long before the `users` UID range. The dominant risk is **per-player work inside one serialized cron process**. The tick scans every UID in `bank`, then performs several reads and writes for each player, including resource calculations, hyperspace transit checks, and state updates. At larger populations this becomes an N+1 query pattern and makes one tick’s duration grow approximately linearly with the number of active accounts.

The existing `flock` protection correctly prevents overlapping workers, but it also means a slow tick delays the next tick rather than increasing capacity. The recommended sequence is to measure first, add the highest-value indexes, reduce per-user queries, then shard or queue tick work while preserving idempotency.

## 1. How to measure real concurrent-player capacity

Run the test against a **staging clone**, not the live game. Use a database snapshot with realistic player data, including planets, resources, fleets, guilds, market orders, trade routes, notifications, and in-flight transits. Disable outbound webhooks and external side effects, or point them to a local sink. Do not use real player credentials or production data.

A useful capacity test has four workloads rather than one synthetic page request:

| Workload | What it measures | Suggested mix |
|---|---|---:|
| Public and authentication | PHP startup, login/session creation, registration checks | 10% |
| Authenticated reads | Dashboard, resource HQ, fleet, guild, notifications, account settings | 55% |
| Authenticated writes | Training, fleet actions, market orders, messages, guild actions | 25% |
| Tick-sensitive operations | Arrivals, combat reports, trade settlement, alert reads | 10% |

Create a pool of test accounts and reuse authenticated cookies. A test that repeatedly logs in for every request measures authentication overhead, not real player concurrency. Each virtual user should perform a small user journey: log in once, load several modules, wait a randomized interval, submit an allowed action, and poll notifications.

Use staged ramps such as 25, 50, 100, 250, 500, and 1,000 concurrent virtual users. Hold each stage for 10–15 minutes after a short warm-up. Stop increasing concurrency when any of the following occurs for two consecutive stages: p95 authenticated request latency exceeds 1 second, p99 exceeds 3 seconds, HTTP error rate exceeds 1%, database connection saturation persists, lock waits grow continuously, or the game tick misses its scheduled deadline.

ApacheBench is acceptable for a basic endpoint smoke test because its `-c` option controls concurrent requests and its output includes failed requests and requests per second [4]. It is not sufficient for a realistic logged-in game journey. Use a cookie-aware scenario runner such as k6, Locust, JMeter, or a custom PHP/Node harness for the full test.

Record these metrics per stage:

| Layer | Metrics |
|---|---|
| HTTP/PHP | requests per second, p50/p95/p99 latency, error rate, timeouts, PHP-FPM workers, CPU, memory |
| MariaDB | QPS, slow-query count, rows examined, buffer-pool hit rate, active threads, running transactions, lock waits, deadlocks, history-list length |
| Tick/cron | tick duration, users processed, rows changed, queries per user, skipped/overlapping workers, job lag, webhook latency |
| Game correctness | duplicate rewards, double settlements, missed arrivals, negative resources, duplicate notifications, inconsistent guild balances |

Measure the tick independently with 1k, 5k, 10k, and 50k synthetic accounts. Run one dry-run profile to measure reads and one write profile inside a transaction-safe staging copy. The key result is not just “maximum requests”; it is the largest population for which **the worst-case tick finishes before the next scheduled tick while interactive p95 latency remains within target**.

A basic public-endpoint probe can use:

```bash
ab -k -c 25 -n 1000 -e public-25.csv https://staging.example.invalid/
ab -k -c 100 -n 5000 -e public-100.csv https://staging.example.invalid/
```

Do not use this command against production without an explicit maintenance window and approval. It does not reproduce CSRF-protected writes, sessions, AJAX journeys, or database-heavy tick activity.

## 2. Database bottlenecks and indexing strategy

### Current bottlenecks visible in the code

The player tick currently selects every UID from `bank` and then loops through players. For each player it performs multiple reads: `userdata`/`units`/`planets`/`race`/`technology`, a planet count, resource structures, hyperspace bonuses, player resources, and transit queries. It may then issue resource updates, transit reward updates, and transit completion updates. This is the primary scaling concern because the number of SQL statements grows with the number of players.

The current guild and market migrations already contain several useful indexes. For example, market orders have guild/status and territory/status indexes, trade routes have status/arrival and guild/status indexes, dynamic events have status/end-time and guild/status indexes, and notifications have guild/status/created-time coverage. These should be confirmed with `SHOW INDEX`, and their real usefulness should be verified with `EXPLAIN` or `ANALYZE`; MariaDB documents that `EXPLAIN` reveals the optimizer’s join order, possible keys, selected key, estimated rows, and access type, while `ANALYZE` executes the statement and reports actual row statistics [2].

### Users table recommendations

First verify the deployed schema instead of assuming the legacy imported schema is identical everywhere:

```sql
SHOW CREATE TABLE users;
SHOW INDEX FROM users;
SELECT TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users';
```

The `users` table should have:

```sql
-- Add only if SHOW INDEX confirms these are missing.
ALTER TABLE users ADD UNIQUE KEY uq_users_uname (uname);
ALTER TABLE users ADD UNIQUE KEY uq_users_email (email);
ALTER TABLE users ADD KEY idx_users_ip (ip);
ALTER TABLE users ADD KEY idx_users_allyid (allyid);
ALTER TABLE users ADD KEY idx_users_lastlogin (lastLogin);
```

The username and email indexes directly support login and duplicate-registration checks. The IP index supports the current one-account-per-IP registration guard. The alliance/guild index supports player-to-guild lookups and administrative filtering. The `lastLogin` index helps cleanup and account-activity reports but is lower priority than the identity indexes. Do not create duplicates if equivalent indexes already exist.

MariaDB recommends matching indexes to `WHERE`, `JOIN`, and `ORDER BY` clauses, using `EXPLAIN`, and avoiding over-indexing because every extra index consumes storage and increases write cost [1]. InnoDB stores table data around the primary key and includes primary-key columns in secondary index records, so a compact numeric primary key is beneficial for secondary-index storage [1] [3].

### High-value dependent-table indexes

Before adding anything, compare each recommendation against `SHOW INDEX` and query plans:

| Table/query pattern | Recommended index if missing | Reason |
|---|---|---|
| `hyperspace_transits WHERE uid=? AND status='enroute' AND eta_at<=NOW()` | `(uid,status,eta_at,transit_id)` | Makes per-player arrival scans selective and ordered |
| `hyperspace_transits WHERE uid=? AND status='arrived' AND return_at<=NOW()` | `(uid,status,return_at,transit_id)` | Avoids scanning all a player’s transit rows |
| `guild_members WHERE guild_id=? ORDER BY rank_level, contribution_total` | `(guild_id,rank_level,contribution_total,uid)` | Supports guild roster ordering |
| `guild_members WHERE uid=?` | `(uid,guild_id)` | Fast membership and invite checks |
| `guild_market_orders WHERE status='open' AND expires_at>NOW()` | `(status,expires_at,order_id)` | Supports settlement/expiry sweeps |
| `guild_trade_routes WHERE status='enroute' AND arrive_at<=NOW()` | `(status,arrive_at,route_id)` | Supports global delivery batching |
| `guild_territory_events WHERE status='active' AND ends_at<=NOW()` | `(status,ends_at,event_id)` | Supports event expiry processing |
| `guild_webhook_deliveries WHERE status='pending' AND next_attempt_at<=NOW()` | `(status,next_attempt_at,delivery_id)` | Supports retry workers |
| security/event history by player and newest first | `(uid,event_id)` | Supports account history reads |

The existing single-column or two-column indexes may already be sufficient at current scale. Composite indexes should follow the actual predicate order and be validated with `EXPLAIN FORMAT=JSON` or `ANALYZE`. Avoid adding indexes to every flag column; low-cardinality fields such as a standalone `status` often need a time or ownership column beside them to be selective.

### Query and schema practices

Use prepared statements for repeated queries and keep a small statement cache where practical. Replace `SELECT *` in hot paths with the exact columns needed. Avoid applying functions to indexed columns in predicates when a range predicate can be used. Add foreign-key indexes for every child-table reference if the schema does not already create them. Archive or partition append-only histories such as security events, webhook deliveries, combat logs, and ledger rows once their retention policy is defined.

## 3. Game-tick and cron optimization roadmap

### Priority 0 — instrument before changing behavior

Add a correlation ID and timing around each job phase. Emit structured fields such as `job`, `run_id`, `started_at`, `duration_ms`, `users_selected`, `users_processed`, `rows_changed`, `query_count`, `deadlocks`, and `lag_seconds`. Enable the slow query log temporarily in staging and capture `SHOW PROCESSLIST`, `SHOW ENGINE INNODB STATUS`, and representative `EXPLAIN` output during the load test.

### Priority 1 — eliminate the full-bank scan and per-player N+1 pattern

The current `SELECT uid FROM bank` loop should be replaced with a due-work queue. Add a scheduling field such as `next_tick_at` or `last_tick_at` to `player_resources`, index it, and select only due rows in bounded batches:

```sql
ALTER TABLE player_resources ADD COLUMN next_tick_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE player_resources ADD KEY idx_resources_due (next_tick_at, uid);

SELECT uid
FROM player_resources
WHERE next_tick_at <= NOW()
ORDER BY next_tick_at, uid
LIMIT 500;
```

Process 250–1,000 users per transaction-sized batch, then advance each row’s `next_tick_at`. Use a lease token or `SELECT ... FOR UPDATE SKIP LOCKED` where supported by the deployed MariaDB version, so multiple workers cannot process the same player. If `SKIP LOCKED` is unavailable, use an atomic claim update with `claimed_until` and `claim_token`.

Preload data for a batch with set-based joins instead of one query per UID. Resource structures, technology, planets, race bonuses, hyperspace systems, and player resources should be fetched for all selected UIDs using `WHERE uid IN (...)`, calculated in memory, and persisted with batched or multi-row updates. Keep a transaction boundary per batch rather than one transaction for the entire population.

### Priority 2 — batch global settlements

Trade routes, event expiry, fleet arrivals, shipyard completion, notifications, and webhook retries should be processed with global due queries rather than inside every player loop. Existing indexes such as `(status,arrive_at)` are aligned with this design. Each worker should claim a bounded set, settle it idempotently, and mark it completed in the same transaction.

For rewards and resource transfers, use unique settlement keys and conditional updates. For example, a route settlement should update only when `status='enroute'`; a second worker then affects zero rows. This is safer than relying only on the outer cron lock and allows horizontal worker scaling later.

### Priority 3 — separate job schedules by cost and urgency

The current runner serializes named jobs under locks. Keep one lock per job, but split work by cadence:

| Job | Cadence | Scaling approach |
|---|---:|---|
| Due resource accrual | Every 1–5 minutes | Batched due queue; shard by UID hash or queue lease |
| Fleet/transit/shipyard arrivals | Every minute | Global due-row worker with idempotent transitions |
| Market/trade settlement | Every minute | Claim rows by arrival/expiry index |
| Guild events and raids | Every 1–5 minutes | Claim active/due events in batches |
| Notifications/webhook delivery | Continuous or every 15–30 seconds | Separate retry worker and rate limit per endpoint |
| Leaderboards and achievements | Every 5–15 minutes | Incremental deltas; full rebuild less frequently |
| Cleanup/archive | Hourly/daily | Low-priority maintenance window |

Do not run expensive leaderboard refreshes, full achievement scans, and all-player resource processing in the same critical tick. The `flock` lock should protect each idempotent job or shard, not force unrelated jobs to wait behind a long global operation.

### Priority 4 — reduce cron overlap risk without reducing throughput

Keep the existing lock and timeout behavior, but add explicit lock-age and lag metrics. A skipped job should report the owning run ID and age. If a job exceeds its schedule interval, alert the operator rather than silently accumulating lag. For sharding, use locks such as `game_tick_00` through `game_tick_15`, with each shard owning a deterministic UID range or hash bucket.

### Priority 5 — cache immutable and slow-changing data

Race bonuses, government bonuses, unit catalogs, technology definitions, and static universe taxonomy should be loaded once per worker or cached in process. Do not cache mutable player resources without a version or transaction strategy. A small application cache for static definitions reduces repeated reads without risking economic duplication.

## 4. Acceptance targets for the next capacity milestone

For a pre-alpha production target, establish explicit service-level objectives before claiming a player number:

| Metric | Initial target |
|---|---:|
| Authenticated read p95 | < 750 ms |
| Authenticated write p95 | < 1,000 ms |
| HTTP error rate under sustained load | < 1% |
| Game tick lateness | < 10% of scheduled interval |
| Tick duplicate settlement count | 0 |
| Deadlocks requiring retry | < 0.1% of transactions |
| Database CPU during steady state | < 70% |
| Database connection pool saturation | < 80% |

The supported active-user number is the highest tested stage that satisfies these targets for at least 30 minutes, including a full tick cycle and representative settlement activity. Report registered accounts, concurrent sessions, requests per second, and active players separately; they are not interchangeable measurements.

## Recommended implementation order

1. Add structured timing and query-count instrumentation to the cron runner and game tick.
2. Capture `SHOW CREATE TABLE`, `SHOW INDEX`, slow queries, and `EXPLAIN` plans in staging.
3. Add only verified missing identity and due-work indexes.
4. Introduce a bounded due-player queue and replace the full `bank` scan.
5. Batch-load player state and batch-write resource/tick results.
6. Convert transit, trade, fleet, event, and webhook processing to claimed idempotent due queues.
7. Split expensive analytics jobs from the resource/combat tick.
8. Run the staged mixed workload and publish the supported concurrency envelope.

## References

[1]: https://mariadb.com/docs/server/mariadb-quickstart-guides/mariadb-indexes-guide "MariaDB — Getting Started with Indexes Guide"

[2]: https://mariadb.com/docs/server/reference/sql-statements/administrative-sql-statements/analyze-and-explain-statements/explain "MariaDB — EXPLAIN"

[3]: https://mariadb.com/docs/server/architecture/server-constraints/primary-key-constraints "MariaDB — PRIMARY KEY Constraints"

[4]: https://httpd.apache.org/docs/2.4/programs/ab.html "Apache HTTP Server — ab benchmarking tool"
