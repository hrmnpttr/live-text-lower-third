<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Cek Kompatibilitas — Liturgia Live</title>
<link rel="stylesheet" href="/liturgia/css/liturgia.css">
</head>
<body class="out-check">
<h1>Cek kompatibilitas browser source</h1>
<p>Buka halaman ini satu kali di browser source OBS untuk memastikan semua fitur jalan.</p>
<table id="hasil"></table>

<script>window.__CLIENT = @json($client);</script>
<script src="/liturgia/js/ws.js"></script>
<script>
(function () {
  var rows = [];
  function add(name, ok, detail) {
    rows.push('<tr><td>' + name + '</td><td class="' + (ok ? 'ok' : 'fail') + '">' +
      (ok ? 'OK' : 'GAGAL') + '</td><td>' + (detail || '') + '</td></tr>');
    document.getElementById('hasil').innerHTML = rows.join('');
  }

  add('User agent', true, navigator.userAgent);

  var d = document.createElement('div');
  d.style.display = 'flex';
  add('Flexbox', d.style.display === 'flex');

  var supportsVar = window.CSS && CSS.supports && CSS.supports('--a', '0');
  add('CSS variables', !!supportsVar, supportsVar ? '' : 'tema warna tidak akan berubah');

  add('WebSocket API', 'WebSocket' in window);
  add('fetch API', 'fetch' in window);
  add('CSS transition', 'transition' in d.style || 'webkitTransition' in d.style);

  fetch('/api/state').then(function (r) { return r.json(); }).then(function () {
    add('API /api/state', true);
  })['catch'](function () { add('API /api/state', false); });

  var connected = false;
  var sock = new LiturgiaSocket({ key: window.__CLIENT.key, port: window.__CLIENT.port });
  sock.on('_connected', function () {
    if (!connected) { connected = true; add('Websocket Reverb (port ' + window.__CLIENT.port + ')', true); }
  });
  setTimeout(function () {
    if (!connected) add('Websocket Reverb (port ' + window.__CLIENT.port + ')', false,
      'pastikan "php artisan reverb:start" jalan & port tidak diblokir firewall');
  }, 4000);
})();
</script>
</body>
</html>
