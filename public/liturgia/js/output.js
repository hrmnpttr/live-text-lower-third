/* Logika halaman output (full & lower third). ES5, CEF-safe.
   - Ambil state via /api/state, dengar event websocket "state.updated"
   - Full: paginasi blok otomatis sesuai tinggi layar, blok aktif di-highlight
   - Lower: tampil satu blok aktif, font menyusut otomatis bila kepanjangan */
(function () {
  var S = {
    page: 'full',
    state: null,
    rundown: null,
    rundownMassId: null,
    lastKey: '',
    pageCache: {}
  };

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function getJSON(url, cb) {
    fetch(url, { credentials: 'same-origin' }).then(function (r) {
      return r.json();
    }).then(cb)['catch'](function (e) {
      if (window.console) console.error('fetch gagal', url, e);
    });
  }

  function textNode(b) {
    var d = document.createElement('div');
    d.className = 'block';
    if (b.label) {
      var l = document.createElement('div');
      l.className = 'block-label';
      l.textContent = b.label;
      d.appendChild(l);
    }
    if (b.type === 'img') {
      var ph = document.createElement('div');
      ph.className = 'block-text';
      ph.innerHTML = '<span class="cue">(gambar full layar)</span>';
      d.appendChild(ph);
    } else if (b.type === 'not') {
      d.appendChild(window.NotAngka.renderBlock(b.lines));
    } else {
      var p = document.createElement('div');
      p.className = 'block-text';
      var lines = String(b.text || '').split('\n');
      var html = '';
      for (var i = 0; i < lines.length; i++) {
        var line = esc(lines[i]);
        if (/^\(.*\)$/.test(lines[i])) html += '<span class="cue">' + line + '</span>';
        else html += (i > 0 ? '<br>' : '') + line;
      }
      p.innerHTML = html;
      d.appendChild(p);
    }
    return d;
  }

  function applyTheme(theme) {
    if (!theme) return;
    var root = document.documentElement;
    root.style.setProperty('--acc', theme.accent || '#c9b878');
    root.style.setProperty('--tint', theme.bg_tint || 'rgba(13,27,46,.92)');
    var body = document.body;
    body.className = body.className.replace(/\bacc-(garis|bulat)\b/g, '');
    body.className += ' acc-' + (theme.accent_style || 'garis');

    setImg('logo', theme.logo);
    setImg('bg-img', theme.background);
    setImg('wm-img', theme.watermark_image);
  }

  function setImg(id, url) {
    var el = document.getElementById(id);
    if (!el) return;
    if (url) {
      if (el.getAttribute('src') !== url) el.src = url;
      el.style.display = 'block';
    } else {
      el.style.display = 'none';
    }
  }

  function applyPreset(preset) {
    var body = document.body;
    body.className = body.className.replace(/\bpreset-[a-z]+\b/g, '');
    body.className += ' preset-' + (preset || 'scrim');
  }

  function currentItem() {
    if (!S.rundown || !S.state) return null;
    return S.rundown.items[S.state.item] || null;
  }

  /* Blok gambar aktif? → tampilkan menutup layar penuh (kedua output). */
  function activeImageUrl(st) {
    if (st.mode === 'clear' || st.quick) return null;
    var item = currentItem();
    if (!item) return null;
    var b = item.blocks[st.block];
    return (b && b.type === 'img') ? b.src : null;
  }

  function overlayImage(url) {
    var el = document.getElementById('img-full');
    if (!el) return;
    if (url) {
      if (el.getAttribute('src') !== url) el.src = url;
      el.style.display = 'block';
      setTimeout(function () { el.style.opacity = '1'; }, 30);
    } else if (el.style.display !== 'none') {
      el.style.opacity = '0';
      setTimeout(function () {
        if (el.style.opacity === '0') el.style.display = 'none';
      }, 400);
    }
  }

  /* ---------- FULL ---------- */

  function paginate(item, availH) {
    var key = item.id + ':' + availH;
    if (S.pageCache[key]) return S.pageCache[key];

    var content = document.getElementById('content');
    var meas = document.createElement('div');
    meas.style.position = 'absolute';
    meas.style.visibility = 'hidden';
    meas.style.width = content.offsetWidth + 'px';
    document.body.appendChild(meas);

    var pages = [];
    var page = [];
    var used = 0;

    for (var i = 0; i < item.blocks.length; i++) {
      var node = textNode(item.blocks[i]);
      meas.appendChild(node);
      var h = node.offsetHeight;
      if (window.getComputedStyle) {
        h += parseFloat(window.getComputedStyle(node).marginBottom) || 0;
      } else {
        h += 34;
      }
      meas.removeChild(node);

      if (page.length > 0 && used + h > availH) {
        pages.push(page);
        page = [];
        used = 0;
      }
      page.push(i);
      used += h;
    }
    if (page.length) pages.push(page);
    document.body.removeChild(meas);

    if (!pages.length) pages = [[0]];
    S.pageCache[key] = pages;
    return pages;
  }

  function renderFull() {
    var st = S.state;
    var frame = document.getElementById('frame');
    var content = document.getElementById('content');
    var kicker = document.getElementById('kicker');
    var title = document.getElementById('title');
    var pageinfo = document.getElementById('pageinfo');
    var meta = document.getElementById('meta-text');

    var visible = st.mode === 'both' || st.mode === 'full';
    var quick = st.quick && (st.quick.target === 'both' || st.quick.target === 'full') ? st.quick : null;

    var key, build;

    if (!visible) {
      key = 'off';
      build = function () {
        kicker.innerHTML = '';
        title.innerHTML = '';
        content.innerHTML = '';
        pageinfo.innerHTML = '';
      };
    } else if (quick) {
      key = 'q:' + quick.header + ':' + quick.text;
      build = function () {
        kicker.textContent = quick.header || '';
        title.innerHTML = '';
        content.innerHTML = '';
        content.appendChild(textNode({ type: 'text', text: quick.text }));
        pageinfo.innerHTML = '';
      };
    } else {
      var item = currentItem();
      if (!item) {
        key = 'empty';
        build = function () {
          kicker.innerHTML = '';
          title.innerHTML = '';
          content.innerHTML = '';
          pageinfo.innerHTML = '';
        };
      } else {
        var availH = content.offsetHeight || 600;
        var pages = paginate(item, availH);
        var pi = 0;
        for (var p = 0; p < pages.length; p++) {
          if (pages[p].indexOf(st.block) >= 0) { pi = p; break; }
        }
        key = 'i:' + st.item + ':p' + pi;
        var pageBlocks = pages[pi];
        build = function () {
          kicker.textContent = item.header || '';
          title.textContent = item.title_only ? '' : (item.title || '');
          if (item.title_only) title.textContent = '';
          content.innerHTML = '';
          var stagger = S.animSwap && document.body.className.indexOf('fx-rich') >= 0;
          for (var j = 0; j < pageBlocks.length; j++) {
            var bi = pageBlocks[j];
            var node = textNode(item.blocks[bi]);
            if (bi < st.block) node.className += ' passed';
            else if (bi > st.block) node.className += ' upcoming';
            if (stagger) {
              node.className += ' stagger-in';
              node.style.animationDelay = (j * 110) + 'ms';
              node.style.webkitAnimationDelay = (j * 110) + 'ms';
            }
            content.appendChild(node);
          }
          S.animSwap = false;
          pageinfo.textContent = pages.length > 1 ? (pi + 1) + ' / ' + pages.length : '';
        };
      }
    }

    if (meta && S.rundown) {
      meta.textContent = (S.rundown.title || '') +
        (S.rundown.priest ? '  ·  ' + S.rundown.priest : '');
    }

    swap(content.parentNode ? content : frame, key, build, content);
  }

  /* Ganti isi dengan transisi fade+slide bila key berubah;
     kalau hanya highlight blok yang bergeser di halaman sama, update halus. */
  function swap(el, key, build, animEl) {
    var target = animEl || el;
    if (key === S.lastKey) { build(); return; }
    var isBlockShift = key.indexOf('i:') === 0 && S.lastKey.indexOf('i:') === 0 &&
      key.split(':p')[0] === S.lastKey.split(':p')[0] &&
      key.split(':p')[1] === S.lastKey.split(':p')[1];
    S.lastKey = key;
    if (isBlockShift) { build(); return; }
    S.animSwap = true;
    target.className += ' hidden-anim';
    setTimeout(function () {
      build();
      target.className = target.className.replace(/\s*hidden-anim/g, '');
    }, 300);
  }

  /* ---------- LOWER ---------- */

  function renderLower() {
    var st = S.state;
    var lt = document.getElementById('lt');
    var kicker = document.getElementById('lt-kicker');
    var contentEl = document.getElementById('lt-content');

    var visible = st.mode === 'both' || st.mode === 'lower';
    var quick = st.quick && (st.quick.target === 'both' || st.quick.target === 'lower') ? st.quick : null;

    var key, itemKey = null, kickerText = '', block = null;

    if (!visible) {
      key = 'off';
    } else if (quick) {
      key = 'q:' + quick.header + ':' + quick.text;
      itemKey = 'q:' + quick.header;
      kickerText = quick.header || '';
      block = { type: 'text', text: quick.text };
    } else {
      var item = currentItem();
      if (!item) { key = 'empty'; }
      else {
        key = 'i:' + st.item + ':b' + st.block;
        itemKey = 'i:' + st.item;
        kickerText = (item.header || '') + (item.title ? ' · ' + item.title : '');
        block = item.blocks[st.block] || null;
      }
    }

    if (key === S.lastKey) return;

    // Masih di lagu/item yang sama & box sedang tampil?
    // → hanya teks yang beranimasi, judul kecil tetap diam.
    var sameItem = block && itemKey && itemKey === S.lastItemKey &&
      lt.className.indexOf('hidden-anim') < 0;

    S.lastKey = key;
    S.lastItemKey = block ? itemKey : null;

    if (!block) {
      lt.className = 'hidden-anim';
      return;
    }

    if (sameItem) {
      contentEl.className = 'swap-out';
      setTimeout(function () {
        contentEl.innerHTML = '';
        contentEl.appendChild(textNode(block));
        fitLower(contentEl);
        contentEl.className = '';
      }, 220);
    } else {
      lt.className = 'hidden-anim';
      setTimeout(function () {
        kicker.textContent = kickerText;
        contentEl.innerHTML = '';
        contentEl.appendChild(textNode(block));
        fitLower(contentEl);
        lt.className = '';
      }, 250);
    }
  }

  /* Lower third dijamin maksimal 1/3 tinggi layar:
     font menyusut bertahap; kalau masih lebih, tinggi box dikunci. */
  function fitLower(contentEl) {
    var box = document.getElementById('lt-box');
    var maxH = Math.floor(window.innerHeight / 3);

    contentEl.style.fontSize = '';
    box.style.maxHeight = '';
    box.style.overflow = '';

    var size = 100;
    var guard = 0;
    while (box.offsetHeight > maxH && size > 42 && guard < 20) {
      size -= 5;
      contentEl.style.fontSize = size + '%';
      guard++;
    }

    // Pengaman terakhir untuk teks ekstrem panjang
    if (box.offsetHeight > maxH) {
      box.style.maxHeight = maxH + 'px';
      box.style.overflow = 'hidden';
    }
  }

  /* ---------- ORKESTRASI ---------- */

  function render() {
    if (!S.state) return;
    applyTheme(S.state.theme);
    var body = document.body;
    body.className = body.className.replace(/\balign-(left|center|right)\b/g, '');
    body.className += ' align-' + (S.state.align || 'center');
    body.className = body.className.replace(/\bbadge-[a-z]+\b/g, '');
    if (S.state.badge && S.state.badge !== 'accent') {
      body.className += ' badge-' + S.state.badge;
    }
    if (S.page === 'lower') applyPreset(S.state.preset);

    var imgUrl = (S.state.mode === 'both' ||
      S.state.mode === (S.page === 'full' ? 'full' : 'lower'))
      ? activeImageUrl(S.state) : null;
    overlayImage(imgUrl);
    if (imgUrl) {
      if (S.page === 'lower') {
        document.getElementById('lt').className = 'hidden-anim';
        S.lastKey = 'img:' + imgUrl;
        S.lastItemKey = null;
      }
      return;
    }

    if (S.page === 'full') renderFull();
    else renderLower();
  }

  function onState(state) {
    S.state = state;
    if (state.mass_id && state.mass_id !== S.rundownMassId) {
      getJSON('/api/rundown/' + state.mass_id, function (rd) {
        S.rundown = rd;
        S.rundownMassId = state.mass_id;
        S.pageCache = {};
        render();
      });
    } else {
      if (!state.mass_id) { S.rundown = null; S.rundownMassId = null; }
      render();
    }
  }

  function refresh() {
    getJSON('/api/state', onState);
  }

  window.LiturgiaOutput = {
    init: function (opts) {
      S.page = opts.page;
      document.body.className += ' out-' + opts.page;

      // Efek kaya hanya untuk full screen di browser modern (bukan OBS/CEF).
      if (opts.page === 'full') {
        var q = window.location.search || '';
        var rich = q.indexOf('fx=rich') >= 0 ||
          (q.indexOf('fx=lite') < 0 && !/OBS/i.test(navigator.userAgent));
        if (rich) document.body.className += ' fx-rich';
      }

      refresh();

      var sock = new window.LiturgiaSocket({ key: opts.client.key, port: opts.client.port });
      sock.on('state.updated', onState);
      sock.on('_connected', refresh);

      // Pengaman: resync berkala + saat ukuran berubah
      setInterval(refresh, 30000);
      window.addEventListener('resize', function () {
        S.pageCache = {};
        S.lastKey = '';
        render();
      });
    }
  };
})();
