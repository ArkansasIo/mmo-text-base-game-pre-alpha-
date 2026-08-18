# Wormhole Scanning and Exploration

## Overview

The Wormhole Scanner and Exploration console gives each commander a controlled way to discover temporary wormhole signatures and launch probes into them. The mechanic uses the existing Dark Matter resource and hyperspace progression rather than ordinary industrial production.

## Scanning

A scan costs **25 Dark Matter** plus five Dark Matter per Hyperspace Core level. A scan consumes the balance transactionally, creates a 24-hour signature, and applies a cooldown. Jump Gate levels shorten the cooldown. Every signature has a class, difficulty, stability value, and unique code.

The available signature classes are:

| Class | Intended profile |
|---|---|
| Stable | Lower-risk, predictable corridor |
| Unstable | Higher variance and moderate rewards |
| Ancient | Strong rewards with advanced hazards |
| Null | Difficult-to-read signal with uncertain outcome |
| Quantum | High difficulty and high potential reward |

## Exploration

Launching a probe costs **50 Dark Matter plus three times the signature difficulty**. The probe’s scan strength is based on signature stability, Hyperspace Core, and Stargate levels. One active probe is permitted per player. The probe is resolved asynchronously by `scripts/backend/wormhole_tick.php`.

During transit, stability degrades according to elapsed exploration time and the signature class. Stable signatures decay slowly; Unstable, Ancient, Null, and Quantum signatures decay progressively faster. At settlement, the worker persists elapsed minutes, degraded collapse risk, and the predicted exotic reward tier on the expedition record for auditability.

The settlement chance is bounded between 10% and 95% and is calculated from scan strength minus signature difficulty after the collapse check. A collapse consumes the dispatch cost and returns no expedition reward. Successful probes return Dark Matter, Exotic Matter, and Tritanium; higher difficulty and risk can produce Exotic Matter reward tiers up to Tier 5. The console exposes predicted risk at dispatch and the resolved tier in expedition history.

## Notifications and safety

The scanner, probe launch, and resolution each create deduplicated player alerts. All resource deductions and expedition state transitions occur inside transactions. The worker locks each due expedition before resolving it, preventing duplicate rewards when workers overlap.

## Deployment

Migration `40_wormhole_exploration.sql` creates signature and expedition tables and adds a scan cooldown to `hyperspace_systems`. The locked cron dispatcher exposes:

```bash
scripts/backend/cron_runner.sh wormhole_tick
```

Run focused checks with:

```bash
php tests/wormhole_exploration_test.php
```
