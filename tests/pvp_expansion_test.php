<?php
require_once __DIR__.'/../base/PvPRankingPolicy.class.php';require_once __DIR__.'/../base/PvPExpansionPolicy.class.php';
$policy=file_get_contents(__DIR__.'/../base/PvPExpansionPolicy.class.php');$match=file_get_contents(__DIR__.'/../base/PvPMatchmakingService.class.php');$season=file_get_contents(__DIR__.'/../base/PvPSeasonService.class.php');$resolver=file_get_contents(__DIR__.'/../base/PvPResolver.class.php');$migration=file_get_contents(__DIR__.'/../database/sql/45_pvp_replays_matchmaking_rewards.sql');$passed=0;$failed=0;
function expansion_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
expansion_check(PvPExpansionPolicy::matchRange(1000,0)===150&&PvPExpansionPolicy::matchRange(1000,180)>=450,'matchmaking expands ELO range with wait time');
expansion_check(PvPExpansionPolicy::eligible(1000,1150,0)&&!PvPExpansionPolicy::eligible(1000,1300,0),'matchmaking accepts and rejects rating gaps correctly');
expansion_check(PvPExpansionPolicy::rewardForPlace(1)['dark_matter']>PvPExpansionPolicy::rewardForPlace(3)['dark_matter'],'top season reward is larger than third place');
$events=PvPExpansionPolicy::replayEvents(['attack_power'=>100,'defense_power'=>80],'attacker_victory',2,4);expansion_check(count($events)===3&&$events[0]['phase']==='launch'&&$events[2]['phase']==='resolution','replay policy creates ordered combat phases');
expansion_check(strpos($resolver,'pvp_replay_events')!==false&&strpos($resolver,'INSERT IGNORE')!==false,'resolver persists duplicate-safe replay events');
expansion_check(strpos($match,'matchDue')!==false&&strpos($match,'pvp_matchmaking_queue')!==false&&strpos($match,'pvp_battles')!==false,'matchmaking service converts queued players into battles');
expansion_check(strpos($season,'distributeEnded')!==false&&strpos($season,'pvp_season_rewards')!==false&&strpos($season,'claimed_at IS NULL')!==false,'season service distributes and claims rewards idempotently');
expansion_check(strpos($migration,'pvp_replay_events')!==false&&strpos($migration,'pvp_matchmaking_queue')!==false&&strpos($migration,'pvp_season_rewards')!==false,'expansion migration defines all durable tables');
if($failed){fwrite(STDERR,"$failed PvP expansion checks failed; $passed passed.\n");exit(1);}echo "All $passed PvP expansion checks passed.\n";
?>
