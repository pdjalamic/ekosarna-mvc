<style>
.set-wrap { max-width:620px; }
.set-h1 { font-size:20px; color:#1a3a6e; margin:0 0 16px; }
.set-card { background:#fff; border:1.5px solid var(--light2); border-radius:14px; margin-bottom:14px; overflow:hidden; }
.set-card-h { padding:12px 16px; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); border-bottom:1px solid var(--light2); }
.set-row { display:flex; align-items:center; gap:12px; padding:14px 16px; }
.set-row + .set-row { border-top:1px solid var(--light2); }
.set-row .sr-ico { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.set-row .sr-txt { flex:1; min-width:0; }
.set-row .sr-txt b { display:block; font-size:14px; color:#1f2a44; }
.set-row .sr-txt span { font-size:12px; color:var(--muted); }
.set-btn { border:none; border-radius:10px; padding:9px 16px; font-size:13px; font-weight:700; cursor:pointer; white-space:nowrap; }
.set-btn.on  { background:#16a34a; color:#fff; }
.set-btn.off { background:#f1f5f9; color:#475569; border:1.5px solid var(--light2); }
.set-btn:disabled { opacity:.6; cursor:default; }
.set-status { font-size:12px; padding:0 16px 14px; }
.set-status.err { color:#dc2626; }
.set-status.ok  { color:#15803d; }
.set-soon { font-size:11px; color:#94a3b8; font-style:italic; }
.set-logout { display:block; text-align:center; color:#dc2626; font-weight:700; text-decoration:none; padding:14px; background:#fff; border:1.5px solid #fecaca; border-radius:12px; }
</style>

<div class="set-wrap">
  <h1 class="set-h1">⚙️ Podešavanja</h1>

  <!-- Obaveštenja (funkcionalno) -->
  <div class="set-card">
    <div class="set-card-h">Obaveštenja</div>
    <div class="set-row">
      <span class="sr-ico" style="background:#fef3c7;color:#d97706;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
      </span>
      <span class="sr-txt">
        <b>Push obaveštenja</b>
        <span>Primaj obaveštenja o novim zadacima i nabavkama</span>
      </span>
      <button id="push-btn" class="set-btn off" disabled onclick="togglePush()">Proveravam…</button>
    </div>
    <div class="set-status" id="push-status"></div>
  </div>

  <!-- Nalog -->
  <div class="set-card">
    <div class="set-card-h">Nalog</div>
    <div class="set-row">
      <span class="sr-ico" style="background:#e0e7ff;color:#4f46e5;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </span>
      <span class="sr-txt"><b>Profil</b> <span class="set-soon">uskoro</span></span>
    </div>
    <div class="set-row">
      <span class="sr-ico" style="background:#dcfce7;color:#16a34a;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
      </span>
      <span class="sr-txt">
        <b>Odjavi sa svih uređaja</b>
        <span>Poništi zapamćene prijave na svim telefonima i računarima</span>
      </span>
      <button id="logout-all-btn" class="set-btn off" onclick="logoutAll()">Odjavi sve</button>
    </div>
    <div class="set-status" id="logout-all-status"></div>
  </div>

  <a class="set-logout" href="<?= BASE_URL ?>/?logout=1">Odjavi se</a>
</div>

<script>
(function () {
  var BASE = '<?= BASE_URL ?>';
  var btn    = document.getElementById('push-btn');
  var status = document.getElementById('push-status');
  var reg = null;

  function setStatus(msg, kind) {
    status.textContent = msg || '';
    status.className = 'set-status' + (kind ? ' ' + kind : '');
  }
  function setBtn(label, on, disabled) {
    btn.textContent = label;
    btn.className = 'set-btn ' + (on ? 'on' : 'off');
    btn.disabled = !!disabled;
  }

  function b64ToUint8(b64) {
    var pad = '='.repeat((4 - b64.length % 4) % 4);
    var s = (b64 + pad).replace(/-/g, '+').replace(/_/g, '/');
    var raw = atob(s), arr = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
    return arr;
  }

  // Provera podrške i trenutnog stanja
  function init() {
    var det = 'secure=' + window.isSecureContext
            + ', sw=' + ('serviceWorker' in navigator)
            + ', push=' + ('PushManager' in window)
            + ', notif=' + ('Notification' in window);
    if (!window.isSecureContext) {
      setBtn('Nedostupno', false, true);
      setStatus('Push radi samo preko HTTPS adrese. (' + det + ')', 'err');
      return;
    }
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      setBtn('Nedostupno', false, true);
      setStatus('Ovaj uređaj/pregledač ne podržava push. (' + det + ')', 'err');
      return;
    }
    navigator.serviceWorker.register(BASE + '/sw.js').then(function (r) {
      reg = r;
      return r.pushManager.getSubscription();
    }).then(function (sub) {
      if (sub) { setBtn('Isključi', true, false); setStatus('Obaveštenja su uključena na ovom uređaju.', 'ok'); }
      else if (Notification.permission === 'denied') {
        setBtn('Blokirano', false, true);
        setStatus('Obaveštenja su blokirana u podešavanjima sajta. Uključi ih u Chrome → podešavanja sajta → Obaveštenja.', 'err');
      } else { setBtn('Uključi', false, false); setStatus(''); }
    }).catch(function (e) {
      setBtn('Greška', false, true);
      setStatus('Service worker nije registrovan: ' + e.message, 'err');
    });
  }

  window.togglePush = function () {
    if (btn.classList.contains('on')) { disable(); } else { enable(); }
  };

  function enable() {
    setBtn('Uključujem…', false, true);
    Notification.requestPermission().then(function (p) {
      if (p !== 'granted') { setBtn('Uključi', false, false); setStatus('Dozvola nije data.', 'err'); return; }
      fetch(BASE + '/?page=push&action=vapid-key').then(function (r) { return r.json(); }).then(function (d) {
        if (!d.publicKey) throw new Error('VAPID ključ nije podešen na serveru.');
        return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64ToUint8(d.publicKey) });
      }).then(function (sub) {
        return fetch(BASE + '/?page=push&action=subscribe', {
          method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(sub.toJSON())
        });
      }).then(function (r) { return r.json(); }).then(function (d) {
        if (d.ok) { setBtn('Isključi', true, false); setStatus('Obaveštenja su uključena! 🎉', 'ok'); }
        else throw new Error(d.err || 'Server nije sačuvao pretplatu.');
      }).catch(function (e) {
        setBtn('Uključi', false, false); setStatus('Greška: ' + e.message, 'err');
      });
    });
  }

  function disable() {
    setBtn('Isključujem…', true, true);
    reg.pushManager.getSubscription().then(function (sub) {
      if (!sub) return null;
      var ep = sub.endpoint;
      return sub.unsubscribe().then(function () {
        return fetch(BASE + '/?page=push&action=unsubscribe', {
          method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ endpoint: ep })
        });
      });
    }).then(function () {
      setBtn('Uključi', false, false); setStatus('Obaveštenja su isključena.', '');
    }).catch(function (e) {
      setBtn('Isključi', true, false); setStatus('Greška: ' + e.message, 'err');
    });
  }

  init();
})();

// Odjava sa svih uređaja
window.logoutAll = function () {
  if (!confirm('Odjaviti sa svih uređaja? Moraćeš ponovo da se prijaviš svuda.')) return;
  var b = document.getElementById('logout-all-btn');
  var st = document.getElementById('logout-all-status');
  b.disabled = true; b.textContent = 'Odjavljujem…'; st.textContent = '';
  var fd = new FormData(); fd.append('_action', 'auth_logout_all');
  fetch('<?= BASE_URL ?>/', { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (d && d.ok) { window.location.href = '<?= BASE_URL ?>/'; }
      else { b.disabled = false; b.textContent = 'Odjavi sve'; st.className = 'set-status err'; st.textContent = (d && d.err) || 'Greška.'; }
    })
    .catch(function () { b.disabled = false; b.textContent = 'Odjavi sve'; st.className = 'set-status err'; st.textContent = 'Greška u komunikaciji.'; });
};
</script>
