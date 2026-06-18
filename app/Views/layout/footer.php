  </div><!-- end .main -->
</div><!-- end .admin-wrap -->

<!-- ══ MODAL: Slanje maila ══ -->
<div id="mail-modal" class="mail-modal-overlay">
  <div class="mail-modal-box" style="position:relative;">
    <button onclick="closeMailModal()" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;">✕</button>
    <h3>✉ Pošalji e-mail</h3>
    <div class="mail-field">
      <label>Prima</label>
      <input type="text" id="mail-to" readonly style="background:#f0f4f9;cursor:default;">
    </div>
    <div class="mail-field">
      <label>Naslov</label>
      <input type="text" id="mail-subject" placeholder="Unesite naslov...">
    </div>
    <div class="mail-field">
      <label>Poruka</label>
      <textarea id="mail-body" placeholder="Unesite tekst poruke..."></textarea>
    </div>
    <div id="mail-attach-wrap" style="display:none;">
      <div class="mail-field">
        <label>Prilozi (max 50 MB po fajlu)</label>
        <label class="file-upload-box" for="mail-fajlovi" style="cursor:pointer;">
          <input type="file" id="mail-fajlovi" multiple style="display:none;">
          <span class="file-upload-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
              <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
          </span>
          <span class="file-upload-text">
            <strong>Kliknite za izbor fajlova</strong><br>
            <span style="font-size:11px;color:var(--muted);">Možete izabrati više fajlova — max 50 MB po fajlu</span>
          </span>
        </label>
        <div id="mail-file-list" style="margin-top:6px;display:flex;flex-direction:column;gap:3px;"></div>
        <div id="mail-file-err" style="font-size:12px;color:#dc2626;margin-top:4px;display:none;"></div>
      </div>
    </div>
    <div class="mail-preview" style="font-family:Arial,sans-serif;font-size:13px;color:#1a2d42;">
      Srdačan pozdrav,<br>
      <strong style="font-size:14px;"><?= htmlspecialchars(\Core\Auth::ime(), ENT_QUOTES, 'UTF-8') ?></strong><br>
      <?php if (\Core\Auth::telefon()): ?>
        <span style="font-size:13px;color:#5d7a96;"><?= htmlspecialchars(\Core\Auth::telefon(), ENT_QUOTES, 'UTF-8') ?></span><br>
      <?php endif; ?>
      <br><img src="<?= BASE_URL ?>/mika_logo_1.png" alt="Ekošarna" style="height:36px;width:auto;">
    </div>
    <div class="mail-err" id="mail-err"></div>
    <div class="mail-ok"  id="mail-ok">✓ Mail je uspešno poslat!</div>
    <div class="mail-actions">
      <button class="mail-cancel-btn" onclick="closeMailModal()">Odustani</button>
      <button class="mail-send-btn" onclick="sendMail()" id="mail-send-btn">Pošalji</button>
    </div>
  </div>
</div>

<!-- ══ MODAL: Prikaz fajla ══ -->
<div id="fajl-modal" onclick="closeModal()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center;padding:20px;">
  <div onclick="event.stopPropagation()" style="position:relative;max-width:92vw;max-height:92vh;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 24px 80px #000a;">
    <button onclick="closeModal()" style="position:absolute;top:10px;right:10px;background:#00000066;border:none;color:#fff;width:32px;height:32px;border-radius:50%;font-size:18px;cursor:pointer;z-index:1;display:flex;align-items:center;justify-content:center;">✕</button>
    <a id="modal-download" href="#" target="_blank" style="position:absolute;top:10px;left:10px;background:#00000066;color:#fff;padding:6px 12px;border-radius:99px;font-size:12px;font-weight:700;text-decoration:none;z-index:1;">⬇ Preuzmi</a>
    <img id="modal-img" src="" style="display:none;max-width:90vw;max-height:88vh;object-fit:contain;display:block;">
    <iframe id="modal-pdf" src="" style="display:none;width:88vw;height:88vh;border:none;"></iframe>
  </div>
</div>

<script src="<?= BASE_URL ?>/public/js/app.js"></script>

<script>
// ── PWA Service Worker + Push Notifikacije ──
if ('serviceWorker' in navigator && 'PushManager' in window) {
  var swUrl = '<?= BASE_URL ?>/sw.js';
  console.log('[Push] Registrujem SW:', swUrl);
  navigator.serviceWorker.register(swUrl)
    .then(function(reg) {
      console.log('[Push] SW registrovan, scope:', reg.scope);
      if (Notification.permission === 'granted') {
        // Notifikacije su već dozvoljene — uvek osveži i sačuvaj uređaj na serveru
        subscribePush(reg);
      } else if (Notification.permission !== 'denied') {
        // Još nije odlučeno — ponudi baner za uključivanje
        setTimeout(function() { askPushPermission(reg); }, 3000);
      }
    })
    .catch(function(e) { console.log('[Push] SW greška:', e.message); });
} else {
  console.log('[Push] SW nije podržan — serviceWorker:', ('serviceWorker' in navigator), 'PushManager:', ('PushManager' in window));
}

function askPushPermission(reg) {
  if (Notification.permission === 'granted') {
    subscribePush(reg);
    return;
  }
  if (Notification.permission === 'denied') return;

  // Prikaži UI baner
  var banner = document.getElementById('push-banner');
  if (banner) {
    banner.style.display = 'flex';
    banner._reg = reg;
  }
}

function subscribePush(reg) {
  return fetch('<?= BASE_URL ?>/?page=push&action=vapid-key')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      var appServerKey = urlBase64ToUint8Array(data.publicKey);
      // Pokušaj prijavu sa aktuelnim ključem. Ako već postoji prijava sa DRUGIM
      // (starim) ključem, browser baca grešku — tada je obrišemo i napravimo novu.
      return reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: appServerKey
      }).catch(function(err) {
        console.log('[Push] Stara prijava sa drugim ključem — pravim novu:', err && err.name);
        return reg.pushManager.getSubscription()
          .then(function(old) { return old ? old.unsubscribe() : true; })
          .then(function() {
            return reg.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: appServerKey
            });
          });
      });
    })
    .then(function(sub) {
      // Uvek pošalji serveru (upsert) da baza ima aktuelan uređaj
      return fetch('<?= BASE_URL ?>/?page=push&action=subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(sub.toJSON())
      });
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      var banner = document.getElementById('push-banner');
      if (d && d.ok) {
        if (banner) banner.style.display = 'none';
        console.log('[Push] Uređaj sačuvan na serveru ✓');
      } else {
        console.log('[Push] Server nije sačuvao uređaj:', d);
      }
    })
    .catch(function(e) { console.log('[Push] subscribe error:', e); });
}

function urlBase64ToUint8Array(base64String) {
  var padding = '='.repeat((4 - base64String.length % 4) % 4);
  var base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  var rawData = window.atob(base64);
  var arr     = new Uint8Array(rawData.length);
  for (var i = 0; i < rawData.length; ++i) arr[i] = rawData.charCodeAt(i);
  return arr;
}
</script>

<!-- Push baner (samo ako notifikacije nisu aktivirane) -->
<div id="push-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:8888;
  background:#1a3a6e;color:#fff;padding:12px 16px;align-items:center;gap:12px;flex-wrap:wrap;
  box-shadow:0 -4px 20px #0006;">
  <span style="flex:1;font-size:13px;min-width:180px;">
    🔔 Aktiviraj podsetnik za zadatke koji ističu
  </span>
  <button onclick="Notification.requestPermission().then(function(p){if(p==='granted')subscribePush(document.getElementById('push-banner')._reg);document.getElementById('push-banner').style.display='none';})"
    style="background:#f59e0b;border:none;color:#1a2d42;font-weight:700;border-radius:8px;padding:8px 16px;cursor:pointer;font-size:13px;white-space:nowrap;">
    Aktiviraj
  </button>
  <button onclick="document.getElementById('push-banner').style.display='none'"
    style="background:none;border:1px solid #ffffff44;color:#fff;border-radius:8px;padding:8px 12px;cursor:pointer;font-size:13px;">
    Ne hvala
  </button>
</div>

<!-- Baner za instalaciju aplikacije (prikazuje se kad je PWA instalacija moguća) -->
<div id="install-banner" style="display:none;position:fixed;top:0;left:0;right:0;z-index:8889;
  background:#16a34a;color:#fff;padding:12px 16px;align-items:center;gap:12px;flex-wrap:wrap;
  box-shadow:0 4px 20px #0006;">
  <span style="flex:1;font-size:13px;min-width:180px;">
    📲 Instaliraj Ekošarnu kao aplikaciju
  </span>
  <button onclick="instalirajApp()"
    style="background:#fff;border:none;color:#166534;font-weight:700;border-radius:8px;padding:8px 16px;cursor:pointer;font-size:13px;white-space:nowrap;">
    Instaliraj
  </button>
  <button onclick="document.getElementById('install-banner').style.display='none'"
    style="background:none;border:1px solid #ffffff66;color:#fff;border-radius:8px;padding:8px 12px;cursor:pointer;font-size:13px;">
    Ne sada
  </button>
</div>
<script>
// ── PWA instalacija (desktop/Android Chrome) ──
(function () {
  var deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', function (e) {
    // Sačuvaj događaj i prikaži naš baner umesto podrazumevanog
    e.preventDefault();
    deferredPrompt = e;
    var b = document.getElementById('install-banner');
    if (b) b.style.display = 'flex';
  });
  window.instalirajApp = function () {
    var b = document.getElementById('install-banner');
    if (!deferredPrompt) { if (b) b.style.display = 'none'; return; }
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then(function () {
      deferredPrompt = null;
      if (b) b.style.display = 'none';
    });
  };
  window.addEventListener('appinstalled', function () {
    var b = document.getElementById('install-banner');
    if (b) b.style.display = 'none';
  });
})();
</script>
<?php
  // Bottom tab bar (mobilni)
  $bb_page = $active_page ?? '';
  $bb_pretraga    = ($bb_page === 'home' && ($_GET['pretraga'] ?? '') === '1');
  $bb_act_home    = ($bb_page === 'home' && !$bb_pretraga);
  $bb_act_search  = $bb_pretraga;
  $bb_act_set     = ($bb_page === 'settings');
?>
<style>
.bottom-nav { display:none; }
.bottom-nav a { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:3px; text-decoration:none; color:#94a3b8; font-size:11px; font-weight:600; }
.bottom-nav a.active { color:#1d4ed8; }
.bottom-nav a svg { width:21px; height:21px; }
@media (max-width:768px) {
  .bottom-nav {
    display:flex; position:fixed; bottom:0; left:0; right:0; z-index:200;
    height:60px; background:#fff; border-top:1px solid var(--light2);
    box-shadow:0 -2px 12px rgba(20,40,80,.06);
    padding-bottom:env(safe-area-inset-bottom);
  }
  .main { padding-bottom:78px !important; }
}
</style>
<nav class="bottom-nav">
  <a href="<?= BASE_URL ?>/?page=home" class="<?= $bb_act_home ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    Početna
  </a>
  <a href="<?= BASE_URL ?>/?page=home&pretraga=1" class="<?= $bb_act_search ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    Pretraga
  </a>
  <a href="<?= BASE_URL ?>/?page=settings" class="<?= $bb_act_set ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
    Podešavanja
  </a>
</nav>
</body>
</html>
