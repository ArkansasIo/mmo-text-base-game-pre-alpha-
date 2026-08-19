<?php
$audioDir=__DIR__.'/../audio';$manager=file_get_contents(__DIR__.'/../js/game-audio.js');$main=file_get_contents(__DIR__.'/../js/main.js');$header=file_get_contents(__DIR__.'/../templates/header.tpl');$passed=0;$failed=0;
function audio_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
foreach(['command_center_ambient.mp3','fleet_alert_tension.mp3','mission_dispatch.wav','mission_success.wav','combat_alert.wav','research_complete.wav','market_trade.wav','notification_ping.wav','ui_click.wav','ui_confirm.wav','ui_hover.wav','ui_warning.wav'] as $asset) audio_check(is_file($audioDir.'/'.$asset)&&filesize($audioDir.'/'.$asset)>100,'audio asset exists: '.$asset);
audio_check(strpos($manager,'localStorage.getItem(\'uc_game_audio_muted\')')!==false&&strpos($manager,'uc_game_audio_volume')!==false,'audio preferences persist locally');
audio_check(strpos($manager,'music.play()')!==false&&strpos($manager,'promise.catch')!==false,'music playback handles autoplay rejection safely');
audio_check(strpos($manager,"tracks =")!==false&&strpos($manager,"effects =")!==false,'manager defines music tracks and event effects');
audio_check(strpos($manager,"/attack|combat|raid|pvp|warfare|sabotage/")!==false&&strpos($manager,"/research|technology|blueprint/")!==false,'manager routes combat and research actions');
audio_check(strpos($header,'game-audio.js')!==false&&strpos($header,'game-audio-toggle')!==false&&strpos($header,'game-audio-volume')!==false,'authenticated shell exposes audio controls');
audio_check(strpos($main,'UCGameAudio.route')!==false&&strpos($main,'UCGameAudio.play')!==false,'AJAX navigation sends events to audio manager');
if($failed){fwrite(STDERR,"$failed audio checks failed; $passed passed.\n");exit(1);}echo "All $passed audio checks passed.\n";
?>
