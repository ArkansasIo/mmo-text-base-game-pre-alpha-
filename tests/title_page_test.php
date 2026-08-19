<?php
$page=file_get_contents(__DIR__.'/../index.php');$css=file_get_contents(__DIR__.'/../main.css');$passed=0;$failed=0;
function title_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
title_check(strpos($page,'title-briefing')!==false&&strpos($page,'Build a civilization that survives the frontier.')!==false,'title page contains mission briefing');
title_check(strpos($page,'Procedural universes')!==false&&strpos($page,'90 ship blueprints')!==false&&strpos($page,'Industrial production')!==false,'title page explains core game systems');
title_check(strpos($page,'Corporations and warfare')!==false&&strpos($page,'Wormhole expeditions')!==false&&strpos($page,'Persistent command loop')!==false,'title page explains MMO progression systems');
title_check(strpos($page,'$publicStatus')!==false&&strpos($page,'COMMAND NETWORK ONLINE')!==false,'title page exposes backend status');
title_check(strpos($page,"mainUpdate('register','Register To Play')")!==false&&strpos($page,"mainUpdate('login','Login')")!==false,'title actions open registration and login flows');
title_check(strpos($page,'id="systems"')!==false&&strpos($page,'id="roadmap"')!==false,'title page includes navigable systems and roadmap anchors');
title_check(strpos($css,'.title-system-grid')!==false&&strpos($css,'.title-roadmap')!==false&&strpos($css,'@media(max-width:600px)')!==false,'title page has responsive industrial-blue styles');
if($failed){fwrite(STDERR,"$failed title-page checks failed; $passed passed.\n");exit(1);}echo "All $passed title-page checks passed.\n";
?>
