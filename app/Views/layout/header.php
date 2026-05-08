<!DOCTYPE html>
<html lang="sr">
<head>
<meta charset="UTF-8">
<link rel="manifest" href="<?= BASE_URL ?>/public/manifest.json">
<meta name="theme-color" content="#1a3a6e">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ekošarna — Admin panel</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin.css">

</head>
<body>
<!-- MOBILNI HEADER -->
<div class="mob-header">
  <div class="mob-logo">Ekošarna<span>.</span></div>
  <button class="mob-burger" onclick="toggleSidebar()" aria-label="Meni">
    <span></span><span></span><span></span>
  </button>
</div>
<div class="mob-overlay" id="mob-overlay" onclick="closeSidebar()"></div>

<div class="admin-wrap">
  <div class="sidebar">
    <div class="sidebar-logo">Ekošarna<span>.</span></div>

    <?php
    $neprocitane_poruke = \Controllers\PorukeController::neprocitane(\Core\Auth::id());
    $je_elektricar      = \Core\Auth::isElektricar();
    ?>

    <?php if ($je_elektricar): ?>
    <a href="<?= BASE_URL ?>/?page=danas" class="<?= $active_page === 'danas' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Raspored
    </a>

    <a href="<?= BASE_URL ?>/?page=poruke" class="<?= $active_page === 'poruke' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
      Poruke
      <span class="badge" id="notif-badge" style="<?= $neprocitane_poruke > 0 ? '' : 'display:none' ?>"><?= $neprocitane_poruke ?></span>
    </a>

    <a href="<?= BASE_URL ?>/?page=hr" class="<?= $active_page === 'hr' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Moj profil
    </a>

    <?php else: ?>
    <?php $neprocitano_count = \Models\KontaktForma::getUnreadCount(); ?>

    <a href="<?= BASE_URL ?>/?page=kontakt" class="<?= $active_page === 'kontakt' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
      Kontakt forme
      <?php if ($neprocitano_count > 0): ?>
        <span class="badge"><?= $neprocitano_count ?></span>
      <?php endif; ?>
    </a>

    <a href="<?= BASE_URL ?>/?page=poruke" class="<?= $active_page === 'poruke' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
      Poruke
      <?php if ($neprocitane_poruke > 0): ?>
        <span class="badge"><?= $neprocitane_poruke ?></span>
      <?php endif; ?>
    </a>

    <hr class="sidebar-sep">

    <a href="<?= BASE_URL ?>/?page=raspored" class="<?= $active_page === 'raspored' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Raspored
    </a>

    <a href="<?= BASE_URL ?>/?page=gradilista" class="<?= $active_page === 'gradilista' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Gradilišta
    </a>

    <a href="<?= BASE_URL ?>/?page=hr" class="<?= $active_page === 'hr' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Zaposleni
    </a>

    <?php if ($is_admin): ?>
    <a href="<?= BASE_URL ?>/?page=tim" class="<?= $active_page === 'tim' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      Tim
    </a>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>/?page=zadaci" class="<?= $active_page === 'zadaci' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
      Zadaci
      <?php
        try {
          $z_open = (int)\Core\Database::get()->query(
            "SELECT COUNT(*) FROM interni_zadaci WHERE status != 'zavrseno'"
          )->fetchColumn();
          if ($z_open > 0) echo '<span class="badge">' . $z_open . '</span>';
        } catch(\Exception $e) {}
      ?>
    </a>

    <?php if ($can_imenik): ?>
    <hr class="sidebar-sep">
    <a href="<?= BASE_URL ?>/?page=imenik" class="<?= $active_page === 'imenik' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
      Imenik
    </a>
    <?php endif; ?>

    <?php endif; ?>

    <hr class="sidebar-sep">
    <a href="<?= BASE_URL ?>/?logout=1" style="color:#f87171;">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      Odjava
    </a>

    <div class="sidebar-footer">
      <div style="padding:0 8px;margin-bottom:6px;font-size:11px;color:#7baacf;line-height:1.4;">
        <strong style="color:#b0ccdf;display:block;"><?= htmlspecialchars($user_ime, ENT_QUOTES, 'UTF-8') ?></strong>
        <?= htmlspecialchars($user_uloga, ENT_QUOTES, 'UTF-8') ?>
      </div>
      <?php if (!$je_elektricar): ?>
      <div style="padding:0 8px;margin-bottom:10px;">
        <img src="<?= BASE_URL ?>/mika_logo_1.png" alt="Ekošarna"
             style="height:32px;width:auto;display:block;mix-blend-mode:lighten;opacity:.9;">
      </div>
      <?php endif; ?>

    </div>
  </div>
  <div class="main">

<script>
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('mob-open');
  document.getElementById('mob-overlay').classList.toggle('open');
  document.body.style.overflow = document.querySelector('.sidebar').classList.contains('mob-open') ? 'hidden' : '';
}
function closeSidebar() {
  document.querySelector('.sidebar').classList.remove('mob-open');
  document.getElementById('mob-overlay').classList.remove('open');
  document.body.style.overflow = '';
}
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.sidebar a').forEach(function(a) {
    a.addEventListener('click', function() {
      if (window.innerWidth <= 768) closeSidebar();
    });
  });
});

// ── POLLING: proverava nove poruke svakih 30s ──
var _poslednjiBroj = -1;

function proveritNovePoruke() {
  fetch('/mvc/check_notifications.php', { credentials: 'same-origin' })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    if (!d.ok) return;

    var ukupno = d.ukupno || 0;

    // Ažuriraj badge u meniju
    var badge = document.getElementById('notif-badge');
    if (badge) {
      if (ukupno > 0) {
        badge.textContent = ukupno;
        badge.style.display = 'inline-block';
      } else {
        badge.style.display = 'none';
      }
    }

    // Osvezi badge-ove na stavkama rasporeda
    if (d.neprocitane_raspored > 0) {
      var fd_r = new FormData();
      fd_r.append('_action', 'raspored_badge_refresh');
      fd_r.append('id', 0);
      fetch('/mvc/?page=raspored', { method: 'POST', credentials: 'same-origin', body: fd_r })
      .then(function(r) { return r.json(); })
      .then(function(bd) {
        if (!bd.stavke) return;
        bd.stavke.forEach(function(s) {
          var btn = document.getElementById('rs-pb-' + s.id);
          if (!btn) return;
          var spans = btn.querySelectorAll('span');
          if (spans[0]) {
            spans[0].textContent = s.poruka_count;
            spans[0].style.display = s.poruka_count > 0 ? 'inline' : 'none';
          }
          if (spans[1]) {
            spans[1].textContent = s.nove_poruke_count;
            spans[1].style.display = s.nova_poruka ? 'inline' : 'none';
          } else if (s.nova_poruka) {
            var sp = document.createElement('span');
            sp.style.cssText = 'background:#e53935;color:#fff;border-radius:99px;font-size:10px;padding:1px 5px;margin-left:2px;font-weight:700;';
            sp.textContent = s.nove_poruke_count;
            btn.appendChild(sp);
          }
        });
      }).catch(function(){});
    }

    // Ako se broj povećao — obavesti korisnika
    if (_poslednjiBroj >= 0 && ukupno > _poslednjiBroj) {
      // Vibracija na mobilnom
      if (navigator.vibrate) navigator.vibrate([200, 100, 200]);

      // Zvuk (kratak beep)
      try {
        var ctx = new (window.AudioContext || window.webkitAudioContext)();
        var osc = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.3);
      } catch(e) {}

      // Toast obaveštenje
      prikaziToast('📩 Nova poruka! Imate ' + ukupno + ' neprocitanih.', ukupno);
    }

    _poslednjiBroj = ukupno;
  })
  .catch(function() {});
}

function prikaziToast(tekst, broj) {
  // Ukloni stari toast ako postoji
  var stari = document.getElementById('notif-toast');
  if (stari) stari.remove();

  var toast = document.createElement('div');
  toast.id = 'notif-toast';
  toast.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);' +
    'background:#1a3a6e;color:#fff;padding:18px 28px;border-radius:14px;font-size:16px;' +
    'font-weight:600;z-index:99999;box-shadow:0 8px 32px rgba(0,0,0,.4);' +
    'display:flex;align-items:center;gap:16px;min-width:280px;justify-content:space-between;';

  var tekst_div = document.createElement('span');
  tekst_div.textContent = tekst;

  var ok_btn = document.createElement('button');
  ok_btn.textContent = 'OK';
  ok_btn.style.cssText = 'background:#fff;color:#1a3a6e;border:none;border-radius:8px;' +
    'padding:6px 16px;font-size:14px;font-weight:700;cursor:pointer;flex-shrink:0;';
  ok_btn.onclick = function() {
    toast.remove();
    // Prikaži crveni badge
    var badge = document.getElementById('notif-badge');
    if (badge && broj > 0) {
      badge.textContent = broj;
      badge.style.display = 'inline-block';
    }
  };

  toast.appendChild(tekst_div);
  toast.appendChild(ok_btn);
  document.body.appendChild(toast);

  // Ažuriraj badge sa brojem novih
  var badge = document.getElementById('notif-badge');
  if (badge && broj > 0) {
    badge.textContent = broj;
    badge.style.display = 'inline-block';
  }
}

// Pokreni odmah i onda svakih 10s
proveritNovePoruke();
setInterval(proveritNovePoruke, 10000);
</script>
