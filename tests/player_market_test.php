<?php
require_once __DIR__ . '/../base/PlayerMarketPolicy.class.php';
$passed=0;$failed=0;
function market_check(bool $value,string $name):void{global $passed,$failed;if($value){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$module=file_get_contents(__DIR__.'/../modules/player_market.php');
$migration=file_get_contents(__DIR__.'/../database/sql/34_player_market_trading.sql');
$nav=file_get_contents(__DIR__.'/../templates/index.tpl');
market_check(PlayerMarketPolicy::validType('blueprint')&&PlayerMarketPolicy::validType('module')&&!PlayerMarketPolicy::validType('resource'),'market item type allowlist');
market_check(PlayerMarketPolicy::validItem('blueprint','scout')&&PlayerMarketPolicy::validItem('module','railgun_array')&&!PlayerMarketPolicy::validItem('module','unknown'),'blueprint and module key validation');
market_check(PlayerMarketPolicy::normalizeQuantity(0)===1&&PlayerMarketPolicy::normalizeQuantity(1000000)===100000,'quantity bounds');
market_check(PlayerMarketPolicy::normalizePrice(0)===1&&PlayerMarketPolicy::normalizePrice(1000000000000)===100000000000,'price bounds');
market_check(PlayerMarketPolicy::label('module','shield_hardener',5)==='Shield Hardener Mk 5','module labels preserve crafted level');
market_check(strpos($migration,'player_market_listings')!==false&&strpos($migration,'player_trade_offers')!==false&&strpos($migration,'player_blueprints')!==false,'market migration defines listing, trade, and blueprint tables');
market_check(strpos($module,"FOR UPDATE")!==false&&strpos($module,"begin_transaction")!==false&&strpos($module,"MARKET_FEE_PERCENT")!==false,'market uses locking, transactions, and fees');
market_check(strpos($module,'pm_change_item')!==false&&strpos($module,'pm_add_item')!==false&&strpos($module,"status='accepted'")!==false,'market and trade settlement transfer item ownership');
market_check(strpos($module,"item_type")!==false&&strpos($module,'module')!==false&&strpos($module,'item_level')!==false,'fitted modules are first-class tradable items');
market_check(strpos($nav,"sendData('player_market','get','mainDisplay')")!==false,'player exchange is reachable from navigation');
if($failed){fwrite(STDERR,"$failed market checks failed; $passed passed.\n");exit(1);}echo "All $passed player-market checks passed.\n";
?>
