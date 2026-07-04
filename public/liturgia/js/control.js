/* Halaman kontrol — bisa dibuka bersamaan di komputer OBS, HP, dan tablet.
   Semua controller sinkron via websocket (state juga di-broadcast balik). */
(function () {
  var state = null;
  var rundown = null;
  var rundownMassId = null;

  function $(id) { return document.getElementById(id); }

  function getJSON(url, cb) {
    fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); }).then(cb)
      ['catch'](function (e) { console.error(url, e); });
  }

  function post(action, body, cb) {
    body = body || {};
    body.pin = localStorage.getItem('lg_pin') || '';
    body.operator = localStorage.getItem('lg_operator') || '';
    fetch('/api/control/' + action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(body)
    }).then(function (r) {
      if (r.status === 403) { alert('PIN salah — isi PIN di "Pengaturan operator".'); throw new Error('pin'); }
      return r.json();
    }).then(function (s) {
      onState(s);
      if (cb) cb(s);
    })['catch'](function () { /* sudah dilaporkan */ });
  }

  /* ---------- render ---------- */

  function renderNow() {
    if (!state) return;
    var item = rundown && rundown.items[state.item];
    $('now-header').textContent = state.quick ? (state.quick.header || 'QUICK TEXT')
      : (item ? (item.header || '') : '');
    $('now-title').textContent = state.quick ? state.quick.text.slice(0, 60)
      : (item ? (item.title || '') : '—');
    var pos = '';
    if (item) {
      pos = 'Blok ' + (state.block + 1) + '/' + item.blocks.length +
        ' · Item ' + (state.item + 1) + '/' + rundown.items.length;
    }
    if (state.mode === 'clear') pos += (pos ? ' · ' : '') + 'LAYAR CLEAR';
    $('now-pos').textContent = pos;
    $('lastby').textContent = state.updated_by ? 'oleh ' + state.updated_by : '';

    var btns = document.querySelectorAll('#modes button');
    for (var i = 0; i < btns.length; i++) {
      btns[i].className = btns[i].getAttribute('data-mode') === state.mode
        ? btns[i].className.replace(/ ?active/, '') + ' active'
        : btns[i].className.replace(/ ?active/, '');
    }
    if (document.activeElement !== $('preset')) $('preset').value = state.preset || 'scrim';
    if (document.activeElement !== $('badge')) $('badge').value = state.badge || 'accent';
    var abtns = document.querySelectorAll('#aligns button');
    for (var a = 0; a < abtns.length; a++) {
      abtns[a].className = abtns[a].getAttribute('data-align') === (state.align || 'center')
        ? 'active' : '';
    }
    if (state.theme && document.activeElement !== $('theme')) $('theme').value = state.theme.id;
    if (document.activeElement !== $('mass') && state.mass_id) $('mass').value = state.mass_id;
  }

  function renderRundown() {
    var box = $('rundown');
    if (!rundown) { box.innerHTML = '<div class="muted">Pilih misa dulu.</div>'; return; }
    box.innerHTML = '';
    for (var i = 0; i < rundown.items.length; i++) {
      (function (i) {
        var it = rundown.items[i];
        var div = document.createElement('div');
        div.className = 'item' + (state && state.item === i && !state.quick ? ' active' : '');
        var html = '<div class="h">' + (i + 1) + ' · ' + esc(it.header || '—') + '</div>';
        if (it.title) html += '<div class="t">' + esc(it.title) + '</div>';
        div.innerHTML = html;

        if (it.blocks.length > 1) {
          var chips = document.createElement('div');
          chips.className = 'chips';
          for (var b = 0; b < it.blocks.length; b++) {
            (function (b) {
              var chip = document.createElement('button');
              chip.className = 'chip' + (state && state.item === i && state.block === b && !state.quick ? ' active' : '');
              chip.textContent = (b + 1) + ' · ' + blockPreview(it.blocks[b]);
              chip.onclick = function (e) {
                e.stopPropagation();
                post('goto', { item: i, block: b });
              };
              chips.appendChild(chip);
            })(b);
          }
          div.appendChild(chips);
        }

        div.onclick = function () { post('goto', { item: i, block: 0 }); };
        box.appendChild(div);
      })(i);
    }
  }

  /* Cuplikan isi blok untuk tombol loncat: label (Refren/Ayat 1),
     lirik dari suku kata not angka, atau awal teksnya. */
  function blockPreview(b) {
    var prefix = b.label ? b.label + ' — ' : '';
    var t = '';
    if (b.type === 'img') {
      return prefix + '[GAMBAR — full layar]';
    }
    if (b.type === 'not') {
      if (b.lines && b.lines[0]) {
        t = String(b.lines[0].syl || '')
          .replace(/_/g, ' ')
          .replace(/(\S)- +/g, '$1')
          .replace(/\s+/g, ' ')
          .trim();
      }
      t = '♪ ' + (t || 'not angka');
    } else {
      t = String(b.text || '').replace(/\s+/g, ' ').trim();
    }
    var s = prefix + t;
    return s.length > 46 ? s.slice(0, 45) + '…' : s;
  }

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function render() { renderNow(); renderRundown(); }

  /* ---------- state ---------- */

  function onState(s) {
    state = s;
    if (s.mass_id && s.mass_id !== rundownMassId) {
      getJSON('/api/rundown/' + s.mass_id, function (rd) {
        rundown = rd; rundownMassId = s.mass_id; render();
      });
    } else {
      if (!s.mass_id) { rundown = null; rundownMassId = null; }
      render();
    }
  }

  function refresh() { getJSON('/api/state', onState); }

  /* ---------- init ---------- */

  function loadMasses() {
    getJSON('/api/masses', function (list) {
      var sel = $('mass');
      sel.innerHTML = '<option value="">— pilih misa —</option>';
      for (var i = 0; i < list.length; i++) {
        var m = list[i];
        var o = document.createElement('option');
        o.value = m.id;
        o.textContent = (m.is_template ? '[TEMPLATE] ' : '') + m.title +
          (m.celebrated_at ? ' · ' + m.celebrated_at : '');
        sel.appendChild(o);
      }
      if (state && state.mass_id) sel.value = state.mass_id;
    });
  }

  function loadThemes() {
    getJSON('/api/themes', function (list) {
      var sel = $('theme');
      sel.innerHTML = '<option value="">(ikut misa)</option>';
      for (var i = 0; i < list.length; i++) {
        var o = document.createElement('option');
        o.value = list[i].id;
        o.textContent = list[i].name;
        sel.appendChild(o);
      }
      if (state && state.theme) sel.value = state.theme.id;
    });
  }

  $('mass').onchange = function () { post('mass', { mass_id: this.value || 0 }); };
  $('next').onclick = function () { post('next'); };
  $('prev').onclick = function () { post('prev'); };
  $('preset').onchange = function () { post('preset', { preset: this.value }); };
  $('badge').onchange = function () { post('badge', { badge: this.value }); };
  var alignBtns = document.querySelectorAll('#aligns button');
  for (var ai = 0; ai < alignBtns.length; ai++) {
    alignBtns[ai].onclick = function () {
      post('align', { align: this.getAttribute('data-align') });
    };
  }
  $('theme').onchange = function () { post('theme', { theme_id: this.value || 0 }); };

  var modeBtns = document.querySelectorAll('#modes button');
  for (var i = 0; i < modeBtns.length; i++) {
    modeBtns[i].onclick = function () {
      var m = this.getAttribute('data-mode');
      post(m === 'clear' ? 'clear' : 'mode', { mode: m });
    };
  }

  $('q-show').onclick = function () {
    var text = $('q-text').value.trim();
    if (!text) return;
    post('quick', { header: $('q-header').value.trim(), text: text, target: $('q-target').value });
  };

  var opInput = $('operator'), pinInput = $('pin');
  opInput.value = localStorage.getItem('lg_operator') || '';
  pinInput.value = localStorage.getItem('lg_pin') || '';
  opInput.onchange = function () { localStorage.setItem('lg_operator', this.value); };
  pinInput.onchange = function () { localStorage.setItem('lg_pin', this.value); };

  // Keyboard: panah / spasi untuk operator di komputer
  document.addEventListener('keydown', function (e) {
    var tag = (e.target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
    if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'PageDown') { e.preventDefault(); post('next'); }
    if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); post('prev'); }
  });

  var sock = new LiturgiaSocket({ key: window.__CLIENT.key, port: window.__CLIENT.port });
  sock.on('state.updated', onState);
  sock.on('_connected', function () { $('conn').className = 'dot on'; refresh(); });
  sock.on('_disconnected', function () { $('conn').className = 'dot'; });

  refresh();
  loadMasses();
  loadThemes();
  setInterval(refresh, 30000);
})();
