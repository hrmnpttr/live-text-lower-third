/* Renderer not angka (notasi angka liturgi) — CSS murni, ES5.
   Sintaks markup:
     1-7   not, 0 istirahat        .    titik durasi
     1'    titik atas (oktaf naik) 1,   titik bawah (oktaf turun)
     [43]  garis atas satu (beam)  [[2127,]] garis dobel
     (x)   lengkung/slur di bawah kolom
     |     garis birama            ||   selesai
     _     (di baris syl) tanpa suku kata
   Baris "not:" berpasangan dengan "syl:"; suku kata rata di bawah notnya. */
(function () {
  function parseTokens(notLine) {
    var raw = String(notLine || '').split(/\s+/);
    var tokens = [];
    for (var i = 0; i < raw.length; i++) {
      var t = raw[i];
      if (!t) continue;
      if (t === '|' || t === '||') {
        tokens.push({ bar: t === '||' ? 2 : 1 });
        continue;
      }
      var slur = false;
      if (t.charAt(0) === '(') { slur = true; t = t.slice(1); }
      if (t.charAt(t.length - 1) === ')') { slur = true; t = t.slice(0, -1); }
      var beam = 0;
      if (t.indexOf('[[') === 0) { beam = 2; t = t.slice(2, t.length - 2); }
      else if (t.charAt(0) === '[') { beam = 1; t = t.slice(1, t.length - 1); }
      var notes = [];
      for (var j = 0; j < t.length; j++) {
        var c = t.charAt(j);
        if (c >= '0' && c <= '9') notes.push({ d: c, oct: 0 });
        else if (c === '.') notes.push({ d: '.', oct: 0 });
        else if (c === "'" && notes.length) notes[notes.length - 1].oct++;
        else if (c === ',' && notes.length) notes[notes.length - 1].oct--;
      }
      if (notes.length) tokens.push({ notes: notes, beam: beam, slur: slur });
    }
    return tokens;
  }

  function el(tag, cls, parent) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (parent) parent.appendChild(e);
    return e;
  }

  function renderLine(notLine, sylLine) {
    var tokens = parseTokens(notLine);
    var syls = String(sylLine || '').split(/\s+/).filter(function (s) {
      return s !== '' && s !== '|' && s !== '||';
    });
    var line = el('div', 'nk-line');
    var si = 0;

    for (var i = 0; i < tokens.length; i++) {
      var tk = tokens[i];

      if (tk.bar) {
        var bc = el('span', 'nk-col', line);
        var bl = el('span', 'nk-bars', bc);
        el('span', 'nk-bar', bl);
        if (tk.bar === 2) el('span', 'nk-bar', bl);
        el('span', 'nk-slurspace', bc);
        el('span', 'nk-syl', bc).innerHTML = '&nbsp;';
        continue;
      }

      var col = el('span', 'nk-col', line);
      var outer = el('span', 'nk-grp' + (tk.beam >= 1 ? ' nk-beam' : ''), col);
      var inner = el('span', 'nk-grp' + (tk.beam >= 2 ? ' nk-beam' : ''), outer);

      for (var j = 0; j < tk.notes.length; j++) {
        var n = tk.notes[j];
        var nw = el('span', 'nk-note', inner);
        el('span', 'nk-dot' + (n.oct > 0 ? ' on' : ''), nw);
        el('span', 'nk-digit', nw).textContent = n.d;
        el('span', 'nk-dot' + (n.oct < 0 ? ' on' : ''), nw);
      }

      el('span', 'nk-slurspace' + (tk.slur ? ' nk-slur' : ''), col);
      var s = si < syls.length ? syls[si++] : '';
      var sylEl = el('span', 'nk-syl', col);
      if (s === '_' || s === '') sylEl.innerHTML = '&nbsp;';
      else sylEl.textContent = s;
    }
    return line;
  }

  function renderBlock(lines) {
    var wrap = document.createElement('div');
    wrap.className = 'nk-block';
    for (var i = 0; i < lines.length; i++) {
      wrap.appendChild(renderLine(lines[i].not, lines[i].syl));
    }
    return wrap;
  }

  var api = {
    parseTokens: parseTokens,
    renderLine: renderLine,
    renderBlock: renderBlock
  };

  if (typeof window !== 'undefined') window.NotAngka = api;
  if (typeof module !== 'undefined' && module.exports) module.exports = api;
})();
