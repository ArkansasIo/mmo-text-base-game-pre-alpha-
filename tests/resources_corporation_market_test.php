<?php
require_once __DIR__.'/../base/ResourcePolicy.class.php';require_once __DIR__.'/../base/FleetPolicy.class.php';require_once __DIR__.'/../base/ModuleFittingPolicy.class.php';require_once __DIR__.'/../base/CorporationMarketPolicy.class.php';$passed=0;$failed=0;function rcm_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$gameTick=file_get_contents(__DIR__.'/../scripts/backend/game_tick.php');$migration=file_get_contents(__DIR__.'/../database/sql/38_strategic_resources_dark_matter.sql');$orderMigration=file_get_contents(__DIR__.'/../database/sql/39_corporation_rare_orderbook.sql');$market=file_get_contents(__DIR__.'/../modules/corporation_market.php');$resourceModule=file_get_contents(__DIR__.'/../modules/resources.php');$nav=file_get_contents(__DIR__.'/../templates/index.tpl');
rcm_check(count(ResourcePolicy::STRATEGIC)===5&&count(ResourcePolicy::ALL)===10,'resource policy exposes five strategic resources plus Dark Matter');
rcm_check(ResourcePolicy::isPremium('dark_matter')&&!ResourcePolicy::isPremium('plasma')&&ResourcePolicy::defaultProduction('dark_matter')===0,'Dark Matter is premium-only and not ordinary tick production');
rcm_check(ResourcePolicy::valid('antimatter')&&ResourcePolicy::valid('dark_matter')&&!ResourcePolicy::valid('unobtainium'),'resource allowlist validation');
rcm_check(strpos($migration,'ADD COLUMN IF NOT EXISTS antimatter')!==false&&strpos($migration,'ADD COLUMN IF NOT EXISTS dark_matter')!==false,'resource migration adds strategic and premium balances');
rcm_check(strpos($gameTick,"'antimatter'")!==false&&strpos($gameTick,"'exotic_matter'")!==false&&strpos($gameTick,"dark_matter' => 0")!==false,'game tick generates strategic resources and excludes Dark Matter');
rcm_check(strpos($resourceModule,'ResourcePolicy::labels')!==false&&strpos($resourceModule,'dark_matter')!==false,'resource command panel displays all resource balances');
rcm_check(CorporationMarketPolicy::validItem('blueprint','scout',0)&&CorporationMarketPolicy::validItem('module','shield_hardener',1)&&!CorporationMarketPolicy::validItem('module','unknown',1),'rare item validation uses blueprint and module catalogs');
rcm_check(CorporationMarketPolicy::fee(10000)===500&&CorporationMarketPolicy::quantity(0)===1&&CorporationMarketPolicy::price(0)===1,'order-book fee and bound rules');
rcm_check(strpos($orderMigration,'corporation_market_orders')!==false&&strpos($orderMigration,'corporation_market_trades')!==false&&strpos($orderMigration,'idx_orderbook_match')!==false,'order-book migration defines matching and history indexes');
rcm_check(strpos($market,'escrow_dark_matter')!==false&&strpos($market,'FOR UPDATE')!==false&&strpos($market,'CorporationMarketPolicy::fee')!==false,'order book reserves Dark Matter and settles with locks and fees');
rcm_check(strpos($market,"side='ask'")!==false&&strpos($market,"side='bid'")!==false&&strpos($market,'remaining_quantity')!==false,'order book supports asks bids and partial fills');
rcm_check(strpos($market,'corporation_members')!==false&&strpos($market,'corpId<=0')!==false,'order book is corporation-only');
rcm_check(strpos($nav,"sendData('corporation_market','get','mainDisplay')")!==false&&strpos($nav,"sendData('resources','get','mainDisplay')")!==false,'resource and corporation order-book panels are reachable from navigation');
if($failed){fwrite(STDERR,"$failed resource/market checks failed; $passed passed.\n");exit(1);}echo "All $passed resource/market checks passed.\n";
?>
