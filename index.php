<?php
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    $endpoints = [
        'games' => 'https://api.m.nintendo.com/catalog/games:all?lang=en-US&country=US&sortRule=RECENT'
    ];
    $key = $_GET['api'];
    if (!isset($endpoints[$key])) {
        http_response_code(404);
        echo json_encode(['error' => 'Unknown API']);
        exit;
    }
    $ch = curl_init($endpoints[$key]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Nintendo/1.0']);
    $resp = curl_exec($ch);
    curl_close($ch);
    echo $resp;
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Nintendo Music App Viewer</title>

  <meta property="og:type"   content="website">
  <meta property="og:url"    content="https://openrbxl.com/nm/">
  <meta property="og:title"  content="Nintendo Music App Viewer">
  <meta property="og:description" content="Browse Nintendo game music, news, playlists and play top tracks directly in your browser.">
  <meta property="og:image"  content="https://openrbxl.com/nm/assets/cover.png">

  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:url"         content="https://openrbxl.com/nm/">
  <meta name="twitter:title"       content="Nintendo Music App Viewer">
  <meta name="twitter:description" content="Browse Nintendo game music, news, playlists and play top tracks directly in your browser.">
  <meta name="twitter:image"       content="https://openrbxl.com/nm/assets/cover.png">

  <style>
    :root {
      --bg: #121212;
      --fg: #e0e0e0;
      --card-bg: #1e1e1e;
      --accent: #E60012;
    }
    * { box-sizing: border-box; }
    body { margin:0; background:var(--bg); color:var(--fg); font-family:sans-serif; }
    header {
      background:var(--accent); padding:1rem;
      display:flex; justify-content:space-between; align-items:center;
    }
    header h1 { margin:0; font-size:1.25rem; color:#fff; }
    .btn-group button {
      background:#fff; color:var(--accent); border:none;
      padding:.5rem 1rem; margin-left:.5rem;
      border-radius:4px; cursor:pointer; font-weight:bold;
    }
    main {
      padding:1rem; display:flex; flex-wrap:wrap;
      gap:1rem; justify-content:center;
    }
    .card {
      background:var(--card-bg); border-radius:8px;
      box-shadow:0 2px 5px rgba(0,0,0,0.5);
      width:280px; overflow:hidden; display:flex;
      flex-direction:column;
    }
    .card img {
      width:100%; height:160px; object-fit:cover;
    }
    .card .content { padding:.75rem; flex:1; }
    .card h3 { margin:0 0 .5rem; font-size:1.1rem; color:#fff; }
    .card p { margin:0 0 .75rem; font-size:.9rem; color:var(--fg); }
    .actions {
      padding:0 .75rem  .75rem;
      display:flex; gap:.5rem; justify-content:space-between;
    }
    .actions button {
      flex:1; background:var(--accent); color:#fff;
      border:none; padding:.5rem; border-radius:4px;
      cursor:pointer; font-size:.9rem;
    }
    .loading {
      text-align:center; color:#888; width:100%;
      padding:2rem;
    }
  </style>
</head>
<body>
  <header>
    <h1>Nintendo Music Viewer</h1>
    <div class="btn-group">
      <button onclick="location.href='news'">News</button>
      <button onclick="location.href='notices'">Notices</button>
    </div>
  </header>

  <main id="games-section">
    <div class="loading" id="games-loading">Loading recent games…</div>
  </main>

  <script>
    function makeCardHTML(game) {
      return `
        <img src="${game.thumbnailURL}?im=a" alt="${game.name}">
        <div class="content">
          <h3>${game.name}</h3>
          <p>${game.formalHardware}</p>
        </div>
        <div class="actions">
          <button onclick="location.href='related/${game.id}'">Related</button>
          <button onclick="location.href='playlist/${game.id}'">Playlist Info</button>
        </div>`;
    }

    function initGames() {
      fetch('?api=games')
        .then(res => res.json())
        .then(list => {
          const container = document.getElementById('games-section');
          container.innerHTML = '';
          list.forEach(game => {
            const card = document.createElement('div');
            card.className = 'card';
            card.innerHTML = makeCardHTML(game);
            container.appendChild(card);
          });
        })
        .catch(() => {
          document.getElementById('games-loading').textContent = 'Failed to load games.';
        });
    }

    initGames();
  </script>
</body>
</html>