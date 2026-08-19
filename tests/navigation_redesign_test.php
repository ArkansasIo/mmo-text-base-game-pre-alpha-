<?php
$passed=0;$failed=0;function nav_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$template=file_get_contents(__DIR__.'/../templates/index.tpl');$pages=file_get_contents(__DIR__.'/../modules/pages.php');$css=file_get_contents(__DIR__.'/../main.css');$js=file_get_contents(__DIR__.'/../js/main.js');
foreach(['COMMAND BRIDGE // EMPIRE CONTROL','CONFLICT GRID // FLEET &amp; COMBAT','RESOURCE GRID // ECONOMY &amp; RESEARCH','POLITICAL NETWORK // COMMUNITY &amp; HELP','DIRECT ACTIONS // QUICK TOOLS'] as $label)nav_check(strpos($template,$label)!==false,'main menu domain exists: '.$label);
foreach(['Empire Subsystems','Universe Subsystems','Military Subsystems','Operations Subsystems','Economy Subsystems','Research Subsystems','Diplomacy Subsystems','Community Subsystems','Help Subsystems'] as $label)nav_check(strpos($template,$label)!==false,'submenu taxonomy exists: '.$label);
foreach(['mainBriefs','system-detail-panel','What the commander can do','What the page exposes','How the server resolves it','Subsystems and Page Routes'] as $token)nav_check(strpos($pages,$token)!==false,'page architecture token exists: '.$token);
foreach(['redesigned-page-head','.system-detail-grid','.system-function-list','.system-feature-list','.system-logic-list','.page-head-status'] as $token)nav_check(strpos($css,$token)!==false,'industrial-blue style exists: '.$token);
nav_check(substr_count($pages,'renderInfoBlock(')===2,'redesigned detail renderer is defined and called once');
nav_check(strpos($template,'DEEP ROUTES // COMMAND TREES')===false,'duplicate deep command tree is removed');
nav_check(strpos($template,'Legacy Tools')===false&&strpos($template,'Sub Menu Groups')===false,'legacy duplicate menu labels are removed');
nav_check(substr_count($template,'class="quick-access-links"')===1&&substr_count($template,'class="quick-access-links"')<2,'quick access is a single condensed rail');
nav_check(strpos($pages,'$visibleSubLabels')!==false&&strpos($pages,'$subLabels')!==false,'visible subroutes are separated from full route registry');
nav_check(strpos($js,'activeRequest')!==false&&strpos($js,'routeRequestToken')!==false,'rapid route requests cancel stale responses');
nav_check(strpos($css,'min-height:44px')!==false&&strpos($css,'@media (max-width:700px)')!==false,'mobile navigation uses touch-safe target sizing');
if($failed){fwrite(STDERR,"$failed navigation checks failed; $passed passed.\n");exit(1);}echo "All $passed navigation redesign checks passed.\n";
?>
