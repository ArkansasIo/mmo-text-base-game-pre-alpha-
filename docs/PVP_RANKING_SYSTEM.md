# Ranked PvP System

Universe Civilization: Empire At Wars now includes a durable seasonal PvP ladder. The active season is `S1-2026`, titled **Season 1: Frontier Ascension**.

## Rating rules

Every ranked commander begins at **1000 rating**. Battle results use deterministic ELO-style changes with a K-factor of 32. Wins increase rating, losses decrease rating, and draws apply a half-score result. Ratings are bounded between 100 and 5000.

| Rating | Division |
|---:|---|
| 100–1249 | Commander |
| 1250–1499 | Captain |
| 1500–1799 | Commodore |
| 1800–5000 | Admiral |

Each resolved battle records wins, losses, draws, rating before and after, rating delta, battle power for and against, and the season code. A unique `(season, battle, player)` key makes settlement idempotent.

## Anti-abuse behavior

Ranked attacks against the same opponent are blocked for 24 hours after an en-route or resolved battle involving either commander. Existing newcomer protection, attack cooldowns, target protection, fleet validation, and fitting validation remain active.

## Player interface

The Leaderboards and Achievements console now includes a **Ranked PvP Season** board. It shows commander name, rank, division, rating, win/loss/draw record, and battle-power totals. The logged-in commander receives a personal standing summary when a season record exists.

## Persistence

Migration 44 creates `pvp_seasons`, `pvp_rankings`, and `pvp_rating_history`, and adds the `ranking_settled` flag to `pvp_battles`. The PvP module also bootstraps the ranking tables for environments where migrations have not yet been executed.
