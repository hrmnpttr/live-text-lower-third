/* Klien websocket ringan berprotokol Pusher untuk Laravel Reverb.
   Ditulis ES5 agar jalan di CEF/Chromium lama (OBS browser source). */
(function () {
  function LiturgiaSocket(config) {
    this.key = config.key;
    this.host = config.host || window.location.hostname;
    this.port = config.port || 8080;
    this.handlers = {};
    this.retry = 0;
    this._connect();
  }

  LiturgiaSocket.prototype.on = function (event, fn) {
    (this.handlers[event] = this.handlers[event] || []).push(fn);
    return this;
  };

  LiturgiaSocket.prototype._emit = function (event, data) {
    var hs = this.handlers[event] || [];
    for (var i = 0; i < hs.length; i++) {
      try { hs[i](data); } catch (e) { if (window.console) console.error(e); }
    }
  };

  LiturgiaSocket.prototype._connect = function () {
    var self = this;
    var url = 'ws://' + this.host + ':' + this.port + '/app/' + this.key +
      '?protocol=7&client=liturgia&version=1.0';
    var ws;

    try { ws = new WebSocket(url); } catch (e) { return this._reconnect(); }
    this.ws = ws;

    ws.onopen = function () { self.retry = 0; };

    ws.onmessage = function (msg) {
      var frame;
      try { frame = JSON.parse(msg.data); } catch (e) { return; }
      var data = frame.data;
      if (typeof data === 'string') {
        try { data = JSON.parse(data); } catch (e) { /* biarkan string */ }
      }
      if (frame.event === 'pusher:connection_established') {
        ws.send(JSON.stringify({ event: 'pusher:subscribe', data: { channel: 'liturgia' } }));
        self._emit('_connected', null);
      } else if (frame.event === 'pusher:ping') {
        ws.send(JSON.stringify({ event: 'pusher:pong', data: {} }));
      } else {
        self._emit(frame.event, data);
      }
    };

    ws.onclose = function () {
      self._emit('_disconnected', null);
      self._reconnect();
    };

    ws.onerror = function () {
      try { ws.close(); } catch (e) { /* noop */ }
    };
  };

  LiturgiaSocket.prototype._reconnect = function () {
    var self = this;
    var delay = Math.min(1000 + this.retry * 1000, 8000);
    this.retry++;
    setTimeout(function () { self._connect(); }, delay);
  };

  window.LiturgiaSocket = LiturgiaSocket;
})();
