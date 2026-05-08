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
if ('serviceWorker' in navigator && 'PushManager' in navigator) {
  var swUrl = '<?= BASE_URL ?>/sw.js';
  console.log('[Push] Registrujem SW:', swUrl);
  navigator.serviceWorker.register(swUrl)
    .then(function(reg) {
      console.log('[Push] SW registrovan, scope:', reg.scope);
      reg.pushManager.getSubscription().then(function(sub) {
        console.log('[Push] Postojeća subscription:', sub ? 'DA' : 'NE');
        if (!sub) {
          setTimeout(function() { askPushPermission(reg); }, 3000);
        }
      });
    })
    .catch(function(e) { console.log('[Push] SW greška:', e.message); });
} else {
  console.log('[Push] SW nije podržan — serviceWorker:', ('serviceWorker' in navigator), 'PushManager:', ('PushManager' in navigator));
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
  fetch('<?= BASE_URL ?>/?page=push&action=vapid-key')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      var pubKey = data.publicKey;
      var appServerKey = urlBase64ToUint8Array(pubKey);
      return reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: appServerKey
      });
    })
    .then(function(sub) {
      return fetch('<?= BASE_URL ?>/?page=push&action=subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(sub.toJSON())
      });
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (d.ok) {
        var banner = document.getElementById('push-banner');
        if (banner) banner.style.display = 'none';
        console.log('Push notifikacije aktivirane!');
      }
    })
    .catch(function(e) { console.log('Push subscribe error:', e); });
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
</body>
</html>
