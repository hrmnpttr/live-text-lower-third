<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Output Full — Liturgia Live</title>
<link rel="stylesheet" href="/liturgia/css/liturgia.css?v={{ filemtime(public_path('liturgia/css/liturgia.css')) }}">
</head>
<body class="acc-garis wm-salib">
<img id="bg-img" alt="">
<div id="bg"></div>
<div id="acc-top" class="acc-line"></div>
<div id="acc-bottom" class="acc-line"></div>
<div id="wm-circle"></div>
<img id="wm-img" alt="">

<div id="frame">
  <div id="kicker"></div>
  <div id="title"></div>
  <div id="content"></div>
  <div id="meta">
    <img id="logo" alt="" style="display:none">
    <span id="meta-text"></span>
  </div>
</div>
<div id="pageinfo"></div>
<img id="img-full" alt="">

<script>window.__CLIENT = @json($client);</script>
<script src="/liturgia/js/ws.js?v={{ filemtime(public_path('liturgia/js/ws.js')) }}"></script>
<script src="/liturgia/js/notangka.js?v={{ filemtime(public_path('liturgia/js/notangka.js')) }}"></script>
<script src="/liturgia/js/output.js?v={{ filemtime(public_path('liturgia/js/output.js')) }}"></script>
<script>LiturgiaOutput.init({ page: 'full', client: window.__CLIENT });</script>
</body>
</html>
