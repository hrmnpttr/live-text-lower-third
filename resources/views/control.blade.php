<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
<title>Kontrol — Liturgia Live</title>
<style>
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
html, body { margin: 0; padding: 0; }
body { font-family: 'Segoe UI', system-ui, Arial, sans-serif; background: #12151c; color: #e9e9e9; }
.wrap { max-width: 1100px; margin: 0 auto; padding: 12px; }
@media (min-width: 900px) {
  .wrap { display: grid; grid-template-columns: 380px 1fr; gap: 16px; align-items: start; }
}
.card { background: #1b1f29; border: 1px solid #2b3040; border-radius: 12px; padding: 14px; margin-bottom: 12px; }
h1 { font-size: 16px; margin: 0; font-weight: 600; }
label { font-size: 12px; color: #9aa1b0; display: block; margin: 8px 0 4px; }
input, select, textarea, button { font: inherit; border-radius: 8px; border: 1px solid #343a4c; background: #12151c; color: #e9e9e9; padding: 9px 10px; width: 100%; }
textarea { min-height: 90px; resize: vertical; }
button { cursor: pointer; background: #232837; }
button:active { transform: scale(.98); }
button.primary { background: #b89b4a; border-color: #b89b4a; color: #17130a; font-weight: 600; }
button.danger { background: transparent; border-color: #7a3030; color: #e08080; }
.row { display: flex; gap: 8px; }
.row > * { flex: 1; }
.topbar { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.dot { width: 10px; height: 10px; border-radius: 50%; background: #e05c5c; flex-shrink: 0; }
.dot.on { background: #6fbf73; }
.muted { color: #9aa1b0; font-size: 12px; }
#now-header { color: #d8c07a; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; }
#now-title { font-size: 17px; font-weight: 600; margin: 4px 0; min-height: 22px; }
.transport { display: flex; gap: 8px; margin-top: 10px; }
.transport button { height: 60px; font-size: 17px; }
.transport .next { flex: 2; }
.modes { display: flex; gap: 6px; margin-top: 8px; }
.modes button { font-size: 13px; padding: 8px 4px; }
.modes button.active { background: #b89b4a; border-color: #b89b4a; color: #17130a; }
#rundown { max-height: 70vh; overflow-y: auto; }
.item { padding: 10px 12px; border: 1px solid #2b3040; border-radius: 10px; margin-bottom: 6px; cursor: pointer; }
.item.active { border-color: #b89b4a; background: #262331; }
.item .h { font-size: 11px; letter-spacing: .07em; color: #d8c07a; text-transform: uppercase; }
.item .t { font-size: 14px; margin-top: 2px; }
.chips { margin-top: 6px; display: flex; flex-direction: column; gap: 3px; }
.chip { width: 100%; padding: 5px 9px; font-size: 12px; border-radius: 6px; text-align: left;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #c6cbd6; }
.chip.active { background: #b89b4a; color: #17130a; border-color: #b89b4a; }
details summary { cursor: pointer; color: #9aa1b0; font-size: 13px; }
</style>
</head>
<body>
<div class="wrap">
  <div>
    <div class="topbar card" style="margin-bottom:12px">
      <span class="dot" id="conn"></span>
      <h1>Liturgia Live — Kontrol</h1>
      <span class="muted" id="lastby" style="margin-left:auto"></span>
    </div>

    <div class="card">
      <label>Misa / ibadah</label>
      <select id="mass"></select>

      <div id="now" style="margin-top:12px">
        <div id="now-header"></div>
        <div id="now-title"></div>
        <div class="muted" id="now-pos"></div>
      </div>

      <div class="transport">
        <button id="prev">&#8592; Prev</button>
        <button id="next" class="primary next">Next &#8594;</button>
      </div>

      <div class="modes" id="modes">
        <button data-mode="both">Tayang</button>
        <button data-mode="full">Full saja</button>
        <button data-mode="lower">Lower saja</button>
        <button data-mode="clear" class="danger">Clear</button>
      </div>

      <label style="margin-top:10px">Perataan (posisi pembaca)</label>
      <div class="modes" style="margin-top:0" id="aligns">
        <button data-align="left">Kiri</button>
        <button data-align="center">Tengah</button>
        <button data-align="right">Kanan</button>
      </div>

      <div class="row" style="margin-top:10px">
        <div>
          <label>Preset lower third</label>
          <select id="preset">
            <option value="scrim">Scrim gradasi</option>
            <option value="transparan">Transparan</option>
            <option value="glass">Box glass</option>
            <option value="solid">Box solid</option>
            <option value="emas">Box emas</option>
            <option value="reveal">Garis reveal</option>
            <option value="bertingkat">Bertingkat</option>
            <option value="pita">Pita</option>
            <option value="timpa">Pita timpa</option>
            <option value="plakat">Plakat tengah</option>
            <option value="panel">Panel</option>
          </select>
        </div>
        <div>
          <label>Tema warna</label>
          <select id="theme"></select>
        </div>
        <div>
          <label>Warna header/pita</label>
          <select id="badge">
            <option value="accent">Ikut tema</option>
            <option value="gold">Gold</option>
            <option value="silver">Silver</option>
            <option value="emerald">Emerald</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card">
      <label style="margin-top:0">Quick text — tayang cepat</label>
      <input id="q-header" placeholder="Judul kecil (mis. PENGUMUMAN)">
      <textarea id="q-text" placeholder="Ketik teks lalu tayangkan..." style="margin-top:8px"></textarea>
      <div class="row" style="margin-top:8px">
        <select id="q-target">
          <option value="both">Kedua output</option>
          <option value="lower">Lower third</option>
          <option value="full">Full layar</option>
        </select>
        <button id="q-show" class="primary">Tayangkan</button>
      </div>
    </div>

    <div class="card">
      <details>
        <summary>Pengaturan operator</summary>
        <label>Nama operator</label>
        <input id="operator" placeholder="nama Anda">
        <label>PIN kontrol</label>
        <input id="pin" placeholder="PIN dari .env (LITURGIA_PIN)">
      </details>
    </div>
  </div>

  <div class="card">
    <label style="margin-top:0">Rundown — tap untuk loncat (bebas urutan)</label>
    <div id="rundown"><div class="muted">Pilih misa dulu.</div></div>
  </div>
</div>

<script>window.__CLIENT = @json($client);</script>
<script src="/liturgia/js/ws.js?v={{ filemtime(public_path('liturgia/js/ws.js')) }}"></script>
<script src="/liturgia/js/control.js?v={{ filemtime(public_path('liturgia/js/control.js')) }}"></script>
</body>
</html>
