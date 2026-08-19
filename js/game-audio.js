(function (window, document) {
  'use strict';
  var root = 'audio/';
  var muted = localStorage.getItem('uc_game_audio_muted') === '1';
  var volume = parseFloat(localStorage.getItem('uc_game_audio_volume') || '0.55');
  if (!isFinite(volume)) volume = 0.55;
  var music = null;
  var currentTrack = '';
  var lastPlayed = {};
  var tracks = {
    command: 'command_center_ambient.mp3',
    alert: 'fleet_alert_tension.mp3'
  };
  var effects = {
    click: 'ui_click.wav', hover: 'ui_hover.wav', confirm: 'ui_confirm.wav', warning: 'ui_warning.wav',
    dispatch: 'mission_dispatch.wav', success: 'mission_success.wav', combat: 'combat_alert.wav',
    research: 'research_complete.wav', trade: 'market_trade.wav', notification: 'notification_ping.wav'
  };
  function clamp(value, min, max) { return Math.max(min, Math.min(max, value)); }
  function effectVolume(multiplier) { return clamp(volume * (multiplier || 0.45), 0, 1); }
  function updateControls() {
    var toggle = document.getElementById('game-audio-toggle');
    if (toggle) {
      toggle.textContent = muted ? 'Audio: Off' : 'Audio: On';
      toggle.setAttribute('aria-pressed', muted ? 'true' : 'false');
      toggle.title = muted ? 'Enable music and interface sounds' : 'Mute music and interface sounds';
    }
    var slider = document.getElementById('game-audio-volume');
    if (slider) slider.value = String(Math.round(volume * 100));
  }
  function start(track) {
    track = tracks[track || 'command'] ? (track || 'command') : 'command';
    if (muted) return;
    if (!music || currentTrack !== track) {
      if (music) { music.pause(); music.currentTime = 0; }
      music = new Audio(root + tracks[track]); music.loop = true; currentTrack = track;
    }
    music.volume = clamp(volume * 0.38, 0, 1);
    var promise = music.play();
    if (promise && promise.catch) promise.catch(function () {});
  }
  function stop() { if (music) music.pause(); }
  function play(name, multiplier) {
    if (muted || !effects[name]) return;
    var now = Date.now();
    if (lastPlayed[name] && now - lastPlayed[name] < 90) return;
    lastPlayed[name] = now;
    var sound = new Audio(root + effects[name]); sound.volume = effectVolume(multiplier);
    var promise = sound.play(); if (promise && promise.catch) promise.catch(function () {});
  }
  function toggle() {
    muted = !muted; localStorage.setItem('uc_game_audio_muted', muted ? '1' : '0');
    if (muted) stop(); else { start(currentTrack || 'command'); play('confirm', 0.35); }
    updateControls();
  }
  function setVolume(value) {
    volume = clamp(parseInt(value, 10) / 100, 0, 1); localStorage.setItem('uc_game_audio_volume', String(volume));
    if (music) music.volume = volume * 0.38; updateControls();
  }
  function route(page, id, action) {
    var value = String(page || '') + ' ' + String(id || '') + ' ' + String(action || '');
    if (/attack|combat|raid|pvp|warfare|sabotage/i.test(value)) { start('alert'); play('combat', 0.6); }
    else if (/dispatch|fleet|hyperspace|wormhole|expedition|patrol|spy/i.test(value)) { start('command'); play('dispatch', 0.48); }
    else if (/research|technology|blueprint/i.test(value)) { play('research', 0.48); }
    else if (/market|trade|corporation_market/i.test(value)) { play('trade', 0.42); }
    else { start('command'); play('click', 0.3); }
  }
  function bind() {
    var toggle = document.getElementById('game-audio-toggle');
    if (toggle) toggle.addEventListener('click', function (event) { event.preventDefault(); toggleAudio(); });
    var slider = document.getElementById('game-audio-volume');
    if (slider) slider.addEventListener('input', function () { setVolume(this.value); });
    document.addEventListener('click', function (event) {
      var target = event.target.closest ? event.target.closest('a,button,input[type=submit]') : null;
      if (!target || target.id === 'game-audio-toggle') return;
      if (target.matches('a,button,input[type=submit]')) play('click', 0.22);
    }, true);
    updateControls();
  }
  function toggleAudio() { toggle(); }
  document.addEventListener('DOMContentLoaded', bind);
  window.UCGameAudio = { start: start, stop: stop, play: play, route: route, toggle: toggleAudio, setVolume: setVolume, isMuted: function () { return muted; } };
})(window, document);
