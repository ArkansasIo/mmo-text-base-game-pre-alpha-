(function () {
  'use strict';
  var audio = new Audio('audio/title_ambient_space.wav');
  audio.loop = true;
  audio.volume = 0.22;
  var muted = localStorage.getItem('uc_audio_muted') === '1';
  var lastHover = 0;

  function effect(name, volume) {
    if (muted) return;
    var sound = new Audio('audio/' + name + '.wav');
    sound.volume = volume || 0.28;
    sound.play().catch(function () {});
  }
  function updateButton() {
    var button = document.getElementById('audio-toggle');
    if (!button) return;
    button.textContent = muted ? 'Audio Off' : 'Audio On';
    button.setAttribute('aria-pressed', muted ? 'true' : 'false');
    button.title = muted ? 'Enable ambient music and interface sounds' : 'Mute ambient music and interface sounds';
  }
  function startAmbient() {
    if (muted) return;
    audio.play().catch(function () {});
  }
  function toggleAudio() {
    muted = !muted;
    localStorage.setItem('uc_audio_muted', muted ? '1' : '0');
    if (muted) audio.pause(); else startAmbient();
    updateButton();
    if (!muted) effect('ui_confirm', 0.22);
  }
  function bind() {
    var button = document.getElementById('audio-toggle');
    if (button) button.addEventListener('click', function (event) { event.preventDefault(); toggleAudio(); });
    document.querySelectorAll('.public-btn, .auth-submit, .public-hero a').forEach(function (el) {
      el.addEventListener('mouseenter', function () {
        var now = Date.now();
        if (now - lastHover > 100) { lastHover = now; effect('ui_hover', 0.12); }
      });
      el.addEventListener('click', function () { startAmbient(); effect('ui_click', 0.18); });
    });
    var login = document.getElementById('login-form');
    if (login) login.addEventListener('submit', function () { startAmbient(); effect('ui_confirm', 0.24); });
    var register = document.getElementById('register-form');
    if (register) register.addEventListener('submit', function () { startAmbient(); effect('ui_confirm', 0.24); });
    updateButton();
  }
  document.addEventListener('submit', function (event) {
    if (event.target && (event.target.id === 'login-form' || event.target.id === 'register-form')) {
      startAmbient();
      effect('ui_confirm', 0.24);
    }
  }, true);
  document.addEventListener('DOMContentLoaded', bind);
  window.titleAudio = { start: startAmbient, effect: effect };
})();
