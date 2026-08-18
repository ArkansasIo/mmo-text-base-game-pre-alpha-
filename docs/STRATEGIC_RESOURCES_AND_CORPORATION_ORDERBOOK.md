# Strategic Resources and Corporation Order Book

## Resource model

The economy now includes five strategic resources in addition to the existing industrial resources:

| Resource | Role | Ordinary tick production |
|---|---|---:|
| Antimatter | High-energy propulsion and advanced power systems | Yes, through Antimatter Refinery |
| Iridium | Dense armor and precision electronics | Yes, through Iridium Mine |
| Tritanium | Structural hull and industrial construction material | Yes, through Tritanium Forge |
| Plasma | Weapons, shields, and reactor systems | Yes, through Plasma Harvester |
| Exotic Matter | Advanced research and anomalous technology | Yes, through Exotic Matter Lab |
| Dark Matter | Premium corporation exchange reserve | No; controlled rewards and administration only |

Migration `38_strategic_resources_dark_matter.sql` adds these balances to `player_resources` and `player_resource_wallet`, adds production structures, and adds `shared_dark_matter` to corporations. The scheduled game tick calculates strategic-resource production from dedicated structure levels. It explicitly leaves Dark Matter at zero ordinary production.

The **Strategic Resource Command** panel displays all ten resource balances and explains the acquisition rules.

## Corporation rare-item exchange

Migration `39_corporation_rare_orderbook.sql` adds corporation inventory, order, and trade-history tables. Corporations can deposit player-owned fitted modules and blueprint copies into shared corporate inventories, then place asks or bids.

| Mechanism | Rule |
|---|---|
| Tradable items | Fitted modules with levels and blueprint copies |
| Currency | Shared corporation Dark Matter |
| Ask orders | Reserve the item from corporation inventory |
| Bid orders | Reserve the full Dark Matter bid value in escrow |
| Matching | Best ask and best bid, then earliest order ID |
| Partial fills | Supported through remaining quantities and bid escrow reduction |
| Settlement fee | 5% of gross trade value |
| Cancellation | Returns remaining item quantity or bid escrow |
| Access | Corporation members only |
| Concurrency | Matching and escrow use row locks and transactions |
| History | Every completed match is recorded in `corporation_market_trades` |

The **Corporation Rare Order Book** provides a filterable live order table, order placement forms, match controls, cancellation controls, and recent trade history. Completed matches notify the seller and buyer representatives through the player notification system.

## Validation

Run the focused checks with:

```bash
php tests/resources_corporation_market_test.php
```

Run the complete repository suite with:

```bash
for test in tests/*_test.php; do php "$test"; done
for test in tests/*_test.sh; do bash "$test"; done
```
