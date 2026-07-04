<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Output Lower Third — Liturgia Live</title>
<link rel="stylesheet" href="/liturgia/css/liturgia.css?v={{ filemtime(public_path('liturgia/css/liturgia.css')) }}">
<style>html, body { background: transparent !important; }</style>
</head>
<body class="preset-scrim acc-garis wm-none">
<div id="lt" class="hidden-anim">
  <div id="lt-box">
    <div id="lt-kicker"></div>
    <div id="lt-content"></div>
  </div>
</div>

<img id="img-full" alt="">

<script>window.__CLIENT = @json($client);</script>
<script src="/liturgia/js/ws.js?v={{ filemtime(public_path('liturgia/js/ws.js')) }}"></script>
<script src="/liturgia/js/notangka.js?v={{ filemtime(public_path('liturgia/js/notangka.js')) }}"></script>
<script src="/liturgia/js/output.js?v={{ filemtime(public_path('liturgia/js/output.js')) }}"></script>
<script>LiturgiaOutput.init({ page: 'lower', client: window.__CLIENT });</script>
</body>
</html>
