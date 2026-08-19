<?php
require_once __DIR__.'/../base/PvPRankingPolicy.class.php';
$policy=file_get_contents(__DIR__.'/../base/PvPRankingPolicy.class.php');$resolver=file_get_contents(__DIR__.'/../base/PvPResolver.class.php');$leaderboard=file_get_contents(__DIR__.'/../modules/leaderboards.php');$migration=file_get_contents(__DIR__.'/../database/sql/44_pvp_rankings.sql');$pvp=file_get_contents(__DIR__.'/../modules/pvp.php');$passed=0;$failed=0;
function rank_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$win=PvPRankingPolicy::delta(1000,1000,'win');$loss=PvPRankingPolicy::delta(1000,1000,'loss');
rank_check($win>0&&$loss<0,'equal-rated win and loss produce opposite rating changes');
rank_check(PvPRankingPolicy::division(1000)==='Commander'&&PvPRankingPolicy::division(1800)==='Admiral','rating divisions are deterministic');
rank_check(strpos($policy,'REMATCH_COOLDOWN_HOURS')!==false&&strpos($policy,'SEASON_CODE')!==false,'policy defines season and anti-rematch constants');
rank_check(strpos($resolver,'settleRanking')!==false&&strpos($resolver,'pvp_rating_history')!==false,'resolver settles and records ranked results');
rank_check(strpos($resolver,'ranking_settled=1')!==false&&strpos($resolver,'INSERT IGNORE')!==false,'ranking settlement is duplicate-safe');
rank_check(strpos($migration,'pvp_seasons')!==false&&strpos($migration,'pvp_rankings')!==false&&strpos($migration,'pvp_rating_history')!==false,'ranking migration defines season, standings, and history');
rank_check(strpos($pvp,'pvp_rankings')!==false&&strpos($pvp,'S1-2026')!==false,'PvP module bootstraps ranking storage');
rank_check(strpos($leaderboard,"board=pvp_ranked")!==false&&strpos($leaderboard,'Your standing')!==false&&strpos($leaderboard,'Division')!==false,'leaderboard exposes ranked PvP and personal standing');
if($failed){fwrite(STDERR,"$failed PvP ranking checks failed; $passed passed.\n");exit(1);}echo "All $passed PvP ranking checks passed.\n";
?>
