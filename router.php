<?php
header('Cache-Control: no-cache');

$path     = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$segments = explode('/', $path);
if (isset($segments[0]) && $segments[0] === 'nm') {
    array_shift($segments);
}
$route = $segments[0] ?? '';

if ($route === 'playlist' && isset($segments[2], $segments[4]) && $segments[2] === 'tracks' && $segments[4] === 'track') {
    $route = 'playlist/track';
} elseif ($route === 'playlist' && isset($segments[2]) && $segments[2] === 'tracks') {
    $route = 'playlist/tracks';
}

function proxy(string $url): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Nintendo/1.0']);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true) ?: [];
}

function currentUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return "{$scheme}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
}

function outputHead(string $title, string $desc, string $img): void {
    $url   = htmlspecialchars(currentUrl(), ENT_QUOTES);
    $title = htmlspecialchars($title,       ENT_QUOTES);
    $desc  = htmlspecialchars($desc,        ENT_QUOTES);
    $img   = htmlspecialchars($img,         ENT_QUOTES);
    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{$title}</title>
  <meta property="og:type"        content="website">
  <meta property="og:url"         content="{$url}">
  <meta property="og:title"       content="{$title}">
  <meta property="og:description" content="{$desc}">
  <meta property="og:image"       content="{$img}">
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:url"         content="{$url}">
  <meta name="twitter:title"       content="{$title}">
  <meta name="twitter:description" content="{$desc}">
  <meta name="twitter:image"       content="{$img}">
  <style>
    body{background:#121212;color:#e0e0e0;font-family:sans-serif;margin:0;padding:0}
    .card{background:#1e1e1e;padding:1rem;margin:1rem auto;border-radius:8px;max-width:600px}
    img{width:100%;border-radius:4px;margin-bottom:1rem}
    h2{margin:0 0 .5rem;color:#fff}
    p,a{color:#e0e0e0;text-decoration:none}
    a.back{display:block;text-align:center;color:#E60012;margin:1rem 0}
    .player{display:flex;align-items:center;gap:1rem}
    .player button{background:#E60012;border:none;color:#fff;padding:.5rem 1rem;border-radius:4px;cursor:pointer}
    .progress{flex:1;height:4px;background:#333;position:relative;cursor:pointer}
    .progress .filled{width:0;height:100%;background:#E60012}
    .time{width:4rem;text-align:right;font-size:.9rem}
    .drm-message{color:#FFA500;text-align:center;margin-top:1rem;font-size:.9rem}
  </style>
</head>
<body>
  <h1 style="text-align:center">{$title}</h1>
HTML;
}

function renderFooter(): void {
    echo "</body></html>";
}

switch ($route) {
  case 'news':
    $data     = proxy('https://api.m.nintendo.com/catalog/notices?lang=en-US&country=US');
    $firstImg = $data['items'][0]['bannerImageURL'] ?? '';
    outputHead('News', 'Latest Nintendo Music news and announcements.', $firstImg);
    foreach ($data['items'] as $item) {
      $date  = date('Y-m-d', $item['publishAt'] ?? 0);
      $title = htmlspecialchars($item['title'] ?? '');
      $body  = nl2br(htmlspecialchars($item['article'] ?? ''));
      echo "<div class='card'><h2>{$title}<br><small>{$date}</small></h2><p>{$body}</p></div>";
    }
    echo "<a href='/nm/news' class='back'>← Home</a>";
    renderFooter();
    break;

  case 'notices':
    $data     = proxy('https://api.m.nintendo.com/catalog/contentNotices?country=US&lang=en-US&limit=999');
    $firstImg = $data['items'][0]['bannerImageURL'] ?? '';
    outputHead('Notices', 'All current Nintendo service notices and updates.', $firstImg);
    foreach ($data['items'] as $item) {
      $date   = date('Y-m-d', $item['publishAt'] ?? 0);
      $banner = $item['bannerImageURL'] ?? '';
      $type   = htmlspecialchars($item['summary']['type'] ?? '');
      $body   = nl2br(htmlspecialchars($item['article'] ?? ''));
      echo "<div class='card'>";
      if ($banner) echo "<img src='".htmlspecialchars($banner)."?im=a'>";
      echo "<h2>{$type}<br><small>{$date}</small></h2><p>{$body}</p></div>";
    }
    echo "<a href='/nm/notices' class='back'>← Home</a>";
    renderFooter();
    break;

  case 'related':
    $id    = $segments[1] ?? '';
    if (!$id) { header("HTTP/1.1 400 Bad Request"); exit('Missing ID'); }
    $game  = proxy("https://api.m.nintendo.com/catalog/games/{$id}?lang=en-US&country=US");
    $list  = proxy("https://api.m.nintendo.com/catalog/games/{$id}/relatedGames?country=US&lang=en-US");
    $gName = htmlspecialchars($game['name'] ?? '');
    $gImg  = $game['thumbnailURL'] ?? '';
    outputHead("Related to {$gName}", "Games related to {$gName}.", $gImg);
    foreach ($list as $g) {
      $img = ($g['thumbnailURL'] ?? '') ?: $gImg;
      $nm  = htmlspecialchars($g['name'] ?? '');
      $hw  = htmlspecialchars($g['formalHardware'] ?? '');
      $gid = urlencode($g['id'] ?? '');
      echo "<div class='card'><img src='{$img}?im=a'><h2>{$nm}<br><small>{$hw}</small></h2>"
         ."<a href='/nm/playlist/{$gid}'>View playlists →</a></div>";
    }
    echo "<a href='/nm/news' class='back'>← Home</a>";
    renderFooter();
    break;

  case 'playlist':
    $game  = $segments[1] ?? '';
    if (!$game) { header("HTTP/1.1 400 Bad Request"); exit('Missing game ID'); }
    $g     = proxy("https://api.m.nintendo.com/catalog/games/{$game}?lang=en-US&country=US");
    $pls   = proxy("https://api.m.nintendo.com/catalog/games/{$game}/relatedPlaylists?country=US&lang=en-US&sdkVersion=android-1.4.0_3e8b373-1&membership=BASIC&packageType=dash_cbcs");
    $gName = htmlspecialchars($g['name'] ?? '');
    $gImg  = $g['thumbnailURL'] ?? '';
    outputHead("{$gName} Playlists", "Browse music playlists for {$gName}.", $gImg);
    foreach ($pls as $pl) {
      if (($pl['name'] ?? '') === 'All tracks') continue;
      $img  = htmlspecialchars($pl['thumbnailURL'] ?? '');
      $name = htmlspecialchars($pl['name'] ?? '');
      $pid  = urlencode($pl['id'] ?? '');
      echo "<div class='card'>";
      if ($img) echo "<img src='{$img}?im=a'>";
      echo "<h2>{$name}</h2>";
      echo "<a href='/nm/playlist/{$game}/tracks/{$pid}'>View all tracks →</a>";
      echo "</div>";
    }
    echo "<a href='javascript:history.back()' class='back'>← Back</a>";
    renderFooter();
    break;

  case 'playlist/tracks':
    $game = $segments[1] ?? '';
    $pid  = $segments[3] ?? '';
    if (!$game || !$pid) { header("HTTP/1.1 400 Bad Request"); exit('Missing IDs'); }
    $pls = proxy(
      "https://api.m.nintendo.com/catalog/games/{$game}/relatedPlaylists" .
      "?country=US&lang=en-US&sdkVersion=android-1.4.0_3e8b373-1&membership=BASIC&packageType=dash_cbcs"
    );
    $tracks = [];
    $thumb  = '';
    foreach ($pls as $pl) {
      if (($pl['id'] ?? '') === $pid) {
        $tracks = $pl['tracks'] ?? [];
        $thumb  = $pl['thumbnailURL'] ?? '';
        break;
      }
    }
    outputHead("Tracks", "All tracks for this playlist.", $thumb);
    foreach ($tracks as $t) {
      $tid  = urlencode($t['id'] ?? '');
      if (!$tid) continue;
      $name = htmlspecialchars($t['name'] ?? 'Untitled');
      $img  = htmlspecialchars(($t['thumbnailURL'] ?? '') ?: ($t['game']['thumbnailURL'] ?? ''));
      echo "<div class='card'>";
      if ($img) echo "<img src='{$img}?im=a'>";
      echo "<h2>{$name}</h2>";
      echo "<a href='/nm/playlist/{$game}/tracks/{$pid}/track/{$tid}'>Play →</a>";
      echo "</div>";
    }
    echo "<a href='javascript:history.back()' class='back'>← Back to playlists</a>";
    renderFooter();
    break;

  case 'playlist/track':
    $game = $segments[1] ?? '';
    $pid  = $segments[3] ?? '';
    $tid  = $segments[5] ?? '';
    if (!$tid) { header("HTTP/1.1 400 Bad Request"); exit('Missing track ID'); }
    $tr   = proxy("https://api.m.nintendo.com/catalog/tracks/{$tid}?country=US&lang=en-US&sdkVersion=android-1.4.0_3e8b373-1&membership=BASIC&packageType=dash_cbcs");
    $gm   = $tr['game'] ?? [];
    $dur  = ceil(($tr['media']['payloadList'][0]['durationMillis'] ?? 0) / 1000);
    $mpd  = ($tr['media']['payloadList'][0]['loopableMedia']['composed']['presentationURL'] ?? '') . 'master.mpd';
    $name = htmlspecialchars($tr['name'] ?? '');
    $gName = htmlspecialchars($gm['name'] ?? '');
    $thumb = $tr['thumbnailURL'] ?? $gm['thumbnailURL'] ?? '';
    outputHead("Now Playing: {$name}", "{$name} from {$gName}, duration {$dur}s.", $thumb);
    echo "<div class='card'><h2>{$name}</h2>"
       ."<p><strong>Game:</strong> {$gName}</p>"
       ."<p><strong>Duration:</strong> {$dur}s</p>"
       ."<div class='player'><button id='play'>►</button><div class='progress' id='prog'><div class='filled'></div></div><div class='time' id='time'>0:00</div></div>"
       ."<div id='drm' class='drm-message' style='display:none;'>This track is Widevine-protected. Currently trying to find a way to play it with the license key/URL. If you know how, shoot me a DM on Discord (Tag: GhostyTongue).</div>"
       ."<audio id='audio'></audio></div>"
       ."<script>
          const audio=document.getElementById('audio'),
                play=document.getElementById('play'),
                prog=document.getElementById('prog'),
                filled=prog.querySelector('.filled'),
                time=document.getElementById('time'),
                drm=document.getElementById('drm'),
                url=".json_encode($mpd).";
          fetch(url,{method:'HEAD'}).then(r=>{
            if(r.status===403) drm.style.display='block';
            else audio.src=url;
          }).catch(_=>drm.style.display='block');
          play.onclick=()=>audio.paused?(audio.play(),play.textContent='❚❚'):(audio.pause(),play.textContent='►');
          audio.ontimeupdate=()=>{const pct=audio.currentTime/audio.duration*100;filled.style.width=pct+'%';let m=Math.floor(audio.currentTime/60),s=Math.floor(audio.currentTime%60).toString().padStart(2,'0');time.textContent=m+':'+s;};
          prog.onclick=e=>audio.currentTime=(e.offsetX/prog.clientWidth)*audio.duration;
        </script>"
       ."<a href='javascript:history.back()' class='back'>← Back</a>";
    renderFooter();
    break;

  default:
    header("HTTP/1.1 404 Not Found");
    echo "Page not found.";
    break;
}