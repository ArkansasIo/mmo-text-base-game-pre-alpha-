<?php
require_once __DIR__ . '/../base/GuildPolicy.class.php';
require_once __DIR__ . '/../base/GuildResearchPolicy.class.php';
require_once __DIR__ . '/../base/GuildWarfarePolicy.class.php';
$passed=0;$failed=0;function ok(bool $v,string $n):void{global $passed,$failed;if($v){$passed++;echo "PASS: $n\n";}else{$failed++;echo "FAIL: $n\n";}}
ok(GuildPolicy::can(1,'use_market')&&!GuildPolicy::can(1,'declare_war'),'member permissions are limited');
ok(GuildPolicy::can(3,'declare_war')&&GuildPolicy::can(3,'manage_territory'),'marshal warfare and territory permissions');
ok(GuildPolicy::can(4,'founder_control'),'founder permission is highest');
$tree=GuildResearchPolicy::tree();ok(GuildResearchPolicy::canResearch('industrial_logistics',0,[]),'root technology can start');ok(!GuildResearchPolicy::canResearch('fortress_networks',0,[]),'prerequisite is enforced');ok(GuildResearchPolicy::canResearch('fortress_networks',0,['industrial_logistics'=>1]),'technology prerequisite unlocks node');$mods=GuildResearchPolicy::modifiers(['industrial_logistics'=>3,'military_doctrine'=>2]);ok($mods['production_percent']===6&&$mods['military_percent']===6,'research modifiers scale by level');ok(GuildWarfarePolicy::relationPair(8,3)===[3,8],'diplomacy pair normalization');ok(GuildWarfarePolicy::raidPower(500,2)===2500,'raid power includes military research');ok(GuildWarfarePolicy::loot(10000,200,100)===1000,'victorious raid loot is capped');ok(GuildWarfarePolicy::loot(10000,100,200)===0,'defended territory repels weaker raid');if($failed){fwrite(STDERR,"$failed test(s) failed; $passed passed.\n");exit(1);}echo "All $passed guild expansion tests passed.\n";
?>
