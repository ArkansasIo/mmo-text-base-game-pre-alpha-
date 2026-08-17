
<div class="app-shell">
  <div class="window-bar window-bar-global"><span class="window-lights"><i></i><i></i><i></i></span><strong>STARGATE WARS // COMMAND CONSOLE</strong><span class="window-status">ONLINE</span></div>
  <div class="top-header">
    <div class="top-brand">
      <img src="images/logo.gif" alt="Universe: Empires at War" />
      <div>
        <h1>Universe: Empires at War</h1>
        <p>Strategic war console and empire operations</p>
      </div>
    </div>
    <div class="top-stats">
      <div class="stat-pill"><span>Rank</span><strong id="isRank"></strong></div>
      <div class="stat-pill"><span>Turns</span><strong id="turns"></strong></div>
      <div class="stat-pill"><span>Naquadah</span><strong id="inHand"></strong></div>
      <div class="stat-pill"><span>In Bank</span><strong id="inBank"></strong></div>
      <div class="stat-pill"><span>Metal</span><strong id="metal"></strong></div>
      <div class="stat-pill"><span>Crystal</span><strong id="crystal"></strong></div>
      <div class="stat-pill"><span>Deuterium</span><strong id="deuterium"></strong></div>
      <div class="stat-pill"><span>Food</span><strong id="food"></strong></div>
      <div class="stat-pill"><span>Water</span><strong id="water"></strong></div>
      <div class="stat-pill"><span>Population</span><strong id="population"></strong></div>
      <div class="stat-pill"><span>Energy</span><strong id="energy"></strong></div>
      <div class="stat-pill"><span>Server Time</span><strong id="serverTime"></strong></div>
      <div class="stat-pill"><span>Next Turn</span><strong id="next">&nbsp;</strong></div>
      <div class="stat-pill"><span>Messages</span><strong><a href="javascript:void(0)" onclick="sendData('messages','get','mainDisplay'); return false" id="messages"></a></strong></div>
    </div>
  </div>

  <div class="top-sub-header">
    <div class="top-sub-header-left">
      <form name="form1" action="javascript:void(0);">
        <input id="keyword" name="keyword" autocomplete="on" placeholder="Search pilot by name" />
        <div class="autocompleteContainer">
          <div id="autocomplete" class="autocomplete"></div>
        </div>
        <input type="hidden" name="userID" id="userID" value="" />
        <input type="button" value="Get Info" onclick="sendData('user','get',userID.value); return false;" />
      </form>
    </div>
    <div class="top-sub-header-right">
      <span id="time"></span>
      <a href="?logout=true">Logout</a>
    </div>
  </div>

  <div class="quick-access-header">
    <div class="window-bar window-bar-compact"><span class="window-lights"><i></i><i></i><i></i></span><strong>QUICK ACCESS</strong><span class="window-status">READY</span></div>
    <div class="quick-access-links">
      <a href="javascript:void(0)" onclick="sendData('pages','get','empire','home'); return false"><span class="footer-icon-link"><img src="images/ui/empire.svg" alt="Home" /><span>Home</span></span></a>
      <a href="javascript:void(0)" onclick="sendData('pages','get','universe','galaxies'); return false"><span class="footer-icon-link"><img src="images/ui/universe.svg" alt="Universe" /><span>Universe</span></span></a>
      <a href="javascript:void(0)" onclick="sendData('stations','get','mainDisplay'); return false"><span class="footer-icon-link"><img src="images/ui/military.svg" alt="Stations" /><span>Stations</span></span></a>
      <a href="javascript:void(0)" onclick="sendData('rtscombat','get','mainDisplay'); return false"><span class="footer-icon-link"><img src="images/ui/military.svg" alt="Combat" /><span>RTS Combat</span></span></a>
      <a href="javascript:void(0)" onclick="sendData('powergrid','get','mainDisplay'); return false">Power Grid</a>
      <a href="javascript:void(0)" onclick="sendData('hyperspace','get','mainDisplay'); return false"><span class="footer-icon-link"><img src="images/ui/universe.svg" alt="Hyperspace" /><span>Hyperspace</span></span></a>
      <a href="javascript:void(0)" onclick="sendData('universe_seed','get','mainDisplay'); return false">Universe Seed</a>
      <a href="javascript:void(0)" onclick="sendData('pages','get','research','tree'); return false"><span class="footer-icon-link"><img src="images/ui/research.svg" alt="Research" /><span>Research</span></span></a>
      <a href="javascript:void(0)" onclick="sendData('pages','get','research','stargate'); return false">Stargate</a>
      <a href="javascript:void(0)" onclick="sendData('megaforge','get','mainDisplay'); return false">Forge</a>
      <a href="javascript:void(0)" onclick="sendData('resourcehq','get','mainDisplay'); return false">Resource HQ</a>
      <a href="javascript:void(0)" onclick="sendData('pages','get','economy','banking'); return false"><span class="footer-icon-link"><img src="images/ui/economy.svg" alt="Bank" /><span>Bank</span></span></a>
      <a href="javascript:void(0)" onclick="sendData('stargatetech','get','mainDisplay'); return false">Stargate Tech</a>
      <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','messages'); return false"><span class="footer-icon-link"><img src="images/ui/diplomacy.svg" alt="Messages" /><span>Messages</span></span></a>
      <a href="javascript:void(0)" onclick="sendData('pages','get','operations','logs'); return false">Logs</a>
      <a href="javascript:void(0)" onclick="sendData('pages','get','help','newplayer'); return false"><span class="footer-icon-link"><img src="images/ui/help.svg" alt="Help" /><span>Help</span></span></a>
      <a href="forums/" target="_blank">Forums</a>
    </div>
  </div>

  <div class="main-layout">
    <aside class="left-menu window-panel">
      <div class="window-bar"><span class="window-lights"><i></i><i></i><i></i></span><strong>COMMAND MODULES</strong><span class="window-status">NAV</span></div>
      <h3>Main Navigation</h3>

      <div class="menu-section-title"><img src="images/ui/core-command.svg" alt="Core" /><span>Core Command</span></div>
      <details open>
        <summary><span class="menu-summary"><img src="images/ui/empire.svg" alt="Empire" /><span>Empire</span></span></summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','empire','home'); return false">Empire Home</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','empire','overview'); return false">Operations Overview</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','empire','planets'); return false">Planet Management</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','empire','command'); return false">Command Structure</a>
        <a href="javascript:void(0)" onclick="sendData('account','get','mainDisplay'); return false">Account Settings</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','empire','progress'); return false">Empire Progress</a>
        <details>
          <summary><span class="menu-summary"><span>Empire Suites</span></span></summary>
          <a href="javascript:void(0)" onclick="sendData('pages','get','empire','logistics'); return false">Logistics Hub</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','empire','doctrine'); return false">Doctrine Board</a>
        </details>
      </details>

      <details>
        <summary><span class="menu-summary"><img src="images/ui/universe.svg" alt="Universe" /><span>Universe</span></span></summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','universe','galaxies'); return false">Galaxy Clusters</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','universe','planets'); return false">Planets &amp; Moons</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','universe','objects'); return false">Interstellar Objects</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','universe','expedition'); return false">Expedition Control</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','universe','bases'); return false">Stations &amp; Bases</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','universe','travel'); return false">Jumpgate &amp; Hyperspace</a>
        <details>
          <summary><span class="menu-summary"><span>Universe Suites</span></span></summary>
          <a href="javascript:void(0)" onclick="sendData('pages','get','universe','lanes'); return false">Transit Lanes</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','universe','anomalies'); return false">Anomaly Index</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','universe','seeds'); return false">Universe Seeds</a>
        </details>
      </details>

      <div class="menu-section-title"><img src="images/ui/warfare-systems.svg" alt="Warfare" /><span>Warfare Systems</span></div>

      <details>
        <summary><span class="menu-summary"><img src="images/ui/military.svg" alt="Military" /><span>Military</span></span></summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','military','personnel'); return false">Personnel</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','military','armory'); return false">Armory</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','military','training'); return false">Training</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','military','fleet'); return false">Fleet</a>
        <a href="javascript:void(0)" onclick="sendData('rtscombat','get','mainDisplay'); return false">RTS Combat Engine</a>
        <a href="javascript:void(0)" onclick="sendData('stations','get','mainDisplay'); return false">Stations Command</a>
        <a href="javascript:void(0)" onclick="sendData('powergrid','get','mainDisplay'); return false">Power Grid Control</a>
        <a href="javascript:void(0)" onclick="sendData('hyperspace','get','mainDisplay'); return false">Hyperspace Command</a>
        <a href="javascript:void(0)" onclick="sendData('universe_seed','get','mainDisplay'); return false">Procedural Universe Explorer</a>
        <a href="javascript:void(0)" onclick="sendData('megaforge','get','mainDisplay'); return false">Mega Forge 90/90/90</a>
        <details>
          <summary><span class="menu-summary"><span>Military Suites</span></span></summary>
          <a href="javascript:void(0)" onclick="sendData('pages','get','military','navy'); return false">Navy Ops</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','military','defensegrid'); return false">Defense Grid</a>
        </details>
      </details>

      <details>
        <summary><span class="menu-summary"><img src="images/ui/operations.svg" alt="Operations" /><span>Operations</span></span></summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','operations','attack'); return false">Attack Missions</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','operations','raid'); return false">Raid Missions</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','operations','spy'); return false">Spy Network</a>
        <a href="javascript:void(0)" onclick="sendData('sabotage','get','mainDisplay'); return false">Sabotage Operations</a>
        <a href="javascript:void(0)" onclick="sendData('communications','get','mainDisplay','inbox'); return false">Communications</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','operations','logs'); return false">Combat Logs</a>
        <details>
          <summary><span class="menu-summary"><span>Operations Suites</span></span></summary>
          <a href="javascript:void(0)" onclick="sendData('pages','get','operations','commandqueue'); return false">Command Queue</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','operations','diplomacyops'); return false">Diplomatic Ops</a>
        </details>
      </details>

      <details>
        <summary><span class="menu-summary"><img src="images/ui/intelligence.svg" alt="Intelligence" /><span>Intelligence</span></span></summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','intel','rankings'); return false">Rankings</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','intel','reports'); return false">Battle Reports</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','intel','threats'); return false">Threat Matrix</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','intel','map'); return false">Sector Map</a>
        <details>
          <summary><span class="menu-summary"><span>Intel Suites</span></span></summary>
          <a href="javascript:void(0)" onclick="sendData('pages','get','intel','signals'); return false">Signal Watch</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','intel','dossiers'); return false">Target Dossiers</a>
        </details>
      </details>

      <div class="menu-section-title"><img src="images/ui/economy-research.svg" alt="Economy" /><span>Economy and Research</span></div>

      <details>
        <summary><span class="menu-summary"><img src="images/ui/economy.svg" alt="Economy" /><span>Economy</span></span></summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','economy','banking'); return false">Banking</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','economy','market'); return false">Market</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','economy','technology'); return false">Technology</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','economy','production'); return false">Production</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','economy','resources'); return false">Resource Hub</a>
        <a href="javascript:void(0)" onclick="sendData('resourcehq','get','mainDisplay'); return false">Resource HQ Command</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','economy','buildings'); return false">Infrastructure Systems</a>
        <a href="javascript:void(0)" onclick="sendData('infrastructure','get','mainDisplay'); return false">Infrastructure Command</a>
        <details>
          <summary><span class="menu-summary"><span>Economy Suites</span></span></summary>
          <a href="javascript:void(0)" onclick="sendData('pages','get','economy','logistics'); return false">Supply Logistics</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','economy','treasury'); return false">Treasury Policy</a>
        </details>
      </details>

      <details>
        <summary><span class="menu-summary"><img src="images/ui/research.svg" alt="Research" /><span>Research</span></span></summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','research','tree'); return false">Research Tree</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','research','techlib'); return false">Technology Tree</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','research','classes'); return false">Class Library</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','research','talents'); return false">Talent Library</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','research','stargate'); return false">Stargate Tech</a>
        <a href="javascript:void(0)" onclick="sendData('stargatetech','get','mainDisplay'); return false">Stargate Tech Command</a>
        <details>
          <summary><span class="menu-summary"><span>Research Suites</span></span></summary>
          <a href="javascript:void(0)" onclick="sendData('pages','get','research','projects'); return false">Research Projects</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','research','labs'); return false">Lab Network</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','research','blueprints'); return false">Blueprint Systems</a>
        </details>
      </details>

      <div class="menu-section-title"><img src="images/ui/diplomacy-help.svg" alt="Diplomacy" /><span>Diplomacy and Help</span></div>

      <details>
        <summary><span class="menu-summary"><img src="images/ui/diplomacy.svg" alt="Diplomacy" /><span>Diplomacy</span></span></summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','alliance'); return false">Alliance</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','relations'); return false">Relations</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','messages'); return false">Messages</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','commander'); return false">Commander Chain</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','governance'); return false">Commander Governance</a>
        <details>
          <summary><span class="menu-summary"><span>Diplomacy Suites</span></span></summary>
          <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','treaties'); return false">Treaties</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','councils'); return false">Councils</a>
        </details>
      </details>

      <details>
        <summary><span class="menu-summary"><img src="images/ui/community.svg" alt="Community" /><span>Community</span></span></summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','community','forums'); return false">Forums</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','community','updates'); return false">Updates</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','community','contact'); return false">Contact</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','community','faq'); return false">FAQ</a>
        <details>
          <summary><span class="menu-summary"><span>Community Suites</span></span></summary>
          <a href="javascript:void(0)" onclick="sendData('pages','get','community','events'); return false">Events</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','community','academy'); return false">Academy</a>
        </details>
      </details>

      <details>
        <summary><span class="menu-summary"><img src="images/ui/help.svg" alt="Help" /><span>Help</span></span></summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','help','newplayer'); return false">New Player Guide</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','help','mechanics'); return false">Game Mechanics</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','help','glossary'); return false">Glossary</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','help','support'); return false">Support</a>
        <details>
          <summary><span class="menu-summary"><span>Help Suites</span></span></summary>
          <a href="javascript:void(0)" onclick="sendData('pages','get','help','troubleshooting'); return false">Troubleshooting</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','help','hotkeys'); return false">Quick Commands</a>
        </details>
      </details>

      <div class="menu-section-title"><img src="images/ui/universe.svg" alt="Command Trees" /><span>Command Trees</span></div>

      <details>
        <summary><span class="menu-summary"><img src="images/ui/empire.svg" alt="Empire Tree" /><span>Empire Tree</span></span></summary>
        <details>
          <summary><span class="menu-summary"><span>Empire Sub Pages</span></span></summary>
          <a href="javascript:void(0)" onclick="sendData('pages','get','empire','logistics'); return false">Logistics Hub</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','empire','doctrine'); return false">Doctrine Board</a>
          <details>
            <summary><span class="menu-summary"><span>Deep Empire Menus</span></span></summary>
            <a href="javascript:void(0)" onclick="sendData('pages','get','economy','treasury'); return false">Treasury Policy</a>
            <a href="javascript:void(0)" onclick="sendData('pages','get','operations','commandqueue'); return false">Command Queue</a>
          </details>
        </details>
        <details>
          <summary><span class="menu-summary"><span>Universe Sub Pages</span></span></summary>
          <a href="javascript:void(0)" onclick="sendData('pages','get','universe','lanes'); return false">Transit Lanes</a>
          <a href="javascript:void(0)" onclick="sendData('pages','get','universe','anomalies'); return false">Anomaly Index</a>
        </details>
      </details>

      <div class="menu-section-title"><img src="images/ui/core-command.svg" alt="Direct Tools" /><span>Direct Tools</span></div>

      <details>
        <summary><span class="menu-summary"><img src="images/ui/diplomacy.svg" alt="Diplomacy Tools" /><span>Diplomacy Tools</span></span></summary>
        <a href="javascript:void(0)" onclick="sendData('communications','get','mainDisplay','compose'); return false">Communications</a>
        <a href="javascript:void(0)" onclick="sendData('sendmessage','get','mainDisplay'); return false">Send Message</a>
        <a href="javascript:void(0)" onclick="sendData('c_ally','get','mainDisplay'); return false">Create Alliance</a>
        <a href="javascript:void(0)" onclick="sendData('ally_mlist','get','mainDisplay'); return false">Alliance Member List</a>
        <a href="javascript:void(0)" onclick="sendData('recruit','get','mainDisplay'); return false">Recruit Commander</a>
      </details>

      <details>
        <summary><span class="menu-summary"><img src="images/ui/community.svg" alt="Legacy Tools" /><span>Legacy Tools</span></span></summary>
        <a href="javascript:void(0)" onclick="sendData('actionLogs','get','mainDisplay'); return false">Action Logs</a>
        <a href="javascript:void(0)" onclick="sendData('progressinfo','get','mainDisplay'); return false">Progress Info</a>
        <a href="javascript:void(0)" onclick="sendData('action','get','mainDisplay'); return false">Actions</a>
      </details>
    </aside>

    <section class="content-panel window-panel">
      <div class="window-bar"><span class="window-lights"><i></i><i></i><i></i></span><strong>TACTICAL DISPLAY</strong><span class="window-status">LIVE</span></div>
      <div class="content-header">
        <h2>Command Feed</h2>
        <p>Select a page or submenu on the left to load a section and sub page.</p>
      </div>
      <div class="window-surface"><div id="mainDisplay"></div></div>
    </section>
  </div>


  <footer class="site-footer">
    <div>
      <strong>Universe: Empires at War</strong> tactical operations network
    </div>
    <div>
      &quot;Because it is so clear it takes a long time to realise it.&quot;
    </div>
  </footer>
</div>

