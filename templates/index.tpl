
<div class="app-shell">
  <div class="top-header">
    <div class="top-brand">
      <img src="images/logo.gif" alt="Pegus Galaxy" />
      <div>
        <h1>Pegus Galaxy Command</h1>
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

  <div class="main-layout">
    <aside class="left-menu">
      <h3>Main Navigation</h3>

      <details>
        <summary>Universe</summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','universe','galaxies'); return false">Galaxy Clusters</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','universe','planets'); return false">Planets &amp; Moons</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','universe','objects'); return false">Interstellar Objects</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','universe','expedition'); return false">Expedition Control</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','universe','bases'); return false">Stations &amp; Bases</a>
      </details>

      <details open>
        <summary>Empire</summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','empire','overview'); return false">Base Overview</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','empire','planets'); return false">Planet Management</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','empire','command'); return false">Command Structure</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','empire','progress'); return false">Empire Progress</a>
      </details>

      <details>
        <summary>Military</summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','military','personnel'); return false">Personnel</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','military','armory'); return false">Armory</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','military','training'); return false">Training</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','military','fleet'); return false">Fleet</a>
        <a href="javascript:void(0)" onclick="sendData('stations','get','mainDisplay'); return false">Stations Command</a>
        <a href="javascript:void(0)" onclick="sendData('megaforge','get','mainDisplay'); return false">Mega Forge 90/90/90</a>
      </details>

      <details>
        <summary>Operations</summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','operations','attack'); return false">Attack Missions</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','operations','raid'); return false">Raid Missions</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','operations','spy'); return false">Spy Network</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','operations','logs'); return false">Combat Logs</a>
      </details>

      <details>
        <summary>Economy</summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','economy','banking'); return false">Banking</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','economy','market'); return false">Market</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','economy','technology'); return false">Technology</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','economy','production'); return false">Production</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','economy','resources'); return false">Resource Hub</a>
      </details>

      <details>
        <summary>Research</summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','research','tree'); return false">Research Tree</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','research','techlib'); return false">Technology Tree</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','research','classes'); return false">Class Library</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','research','talents'); return false">Talent Library</a>
      </details>

      <details>
        <summary>Diplomacy</summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','alliance'); return false">Alliance</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','relations'); return false">Relations</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','messages'); return false">Messages</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','commander'); return false">Commander Chain</a>
      </details>

      <details>
        <summary>Intelligence</summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','intel','rankings'); return false">Rankings</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','intel','reports'); return false">Battle Reports</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','intel','threats'); return false">Threat Matrix</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','intel','map'); return false">Sector Map</a>
      </details>

      <details>
        <summary>Community</summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','community','forums'); return false">Forums</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','community','updates'); return false">Updates</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','community','contact'); return false">Contact</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','community','faq'); return false">FAQ</a>
      </details>

      <details>
        <summary>Help</summary>
        <a href="javascript:void(0)" onclick="sendData('pages','get','help','newplayer'); return false">New Player Guide</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','help','mechanics'); return false">Game Mechanics</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','help','glossary'); return false">Glossary</a>
        <a href="javascript:void(0)" onclick="sendData('pages','get','help','support'); return false">Support</a>
      </details>
    </aside>

    <section class="content-panel">
      <div class="content-header">
        <h2>Command Feed</h2>
        <p>Select a page or submenu on the left to load a section and sub page.</p>
      </div>
      <div id="mainDisplay"></div>
    </section>
  </div>

  <div class="bottom-header">
    <a href="javascript:void(0)" onclick="sendData('pages','get','empire','overview'); return false">Home</a>
    <a href="javascript:void(0)" onclick="sendData('pages','get','universe','galaxies'); return false">Universe</a>
    <a href="javascript:void(0)" onclick="sendData('stations','get','mainDisplay'); return false">Stations</a>
    <a href="javascript:void(0)" onclick="sendData('pages','get','research','tree'); return false">Research</a>
    <a href="javascript:void(0)" onclick="sendData('megaforge','get','mainDisplay'); return false">Forge</a>
    <a href="javascript:void(0)" onclick="sendData('pages','get','economy','banking'); return false">Bank</a>
    <a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','messages'); return false">Messages</a>
    <a href="javascript:void(0)" onclick="sendData('pages','get','operations','logs'); return false">Logs</a>
    <a href="javascript:void(0)" onclick="sendData('pages','get','help','newplayer'); return false">Help</a>
    <a href="forums/" target="_blank">Forums</a>
  </div>

  <footer class="site-footer">
    <div>
      <strong>Pegus Galaxy</strong> tactical operations network
    </div>
    <div>
      &quot;Because it is so clear it takes a long time to realise it.&quot;
    </div>
  </footer>
</div>

