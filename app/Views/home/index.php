<?php
/** @var array $counts  role-aware brojači (poruke/kontakt/zadaci/nabavka) */
$je_elektricar = \Core\Auth::isElektricar();

// SVG ikonice (iste kao u sidebar-u)
$ico = [
  'raspored'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
  'poruke'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
  'zadaci'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>',
  'nabavka'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.98 1.61H19a2 2 0 001.98-1.61L23 6H6"/></svg>',
  'kontakt'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
  'gradilista' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
  'zaposleni'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
  'evidencija' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
  'profil'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
  'tim'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>',
  'magacin'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>',
  'imenik'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>',
  'obavestenja'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
];

// Pločice po ulozi: [url, label, ikona, badge_id|null, count, boja, opis]
// $primarni = koliko prvih pločica je uvek vidljivo; ostatak se otkriva strelicom.
$je_direktor = (\Core\Auth::uloga() === 'Direktor');

if ($je_direktor) {
    // Direktor: 6 osnovnih ikonica (2 reda × 3) + ostatak iza strelice "Prikaži još"
    $tiles = [
        // — osnovnih 6 —
        ['zadaci',     'Zadaci',        $ico['zadaci'],     'home-badge-zadaci',  $counts['zadaci']  ?? 0, '#2563eb', 'Pregled i upravljanje zadacima'],
        ['raspored',   'Raspored',      $ico['raspored'],   null,                 0,                       '#7c3aed', 'Planiranje i pregled rasporeda'],
        ['evidencija', 'Evidencija',    $ico['evidencija'], null,                 0,                       '#16a34a', 'Evidencija i izveštaji'],
        ['poruke',     'Poruke',        $ico['poruke'],     'home-badge-poruke',  $counts['poruke']  ?? 0, '#0d9488', 'Komunikacija i razmena poruka'],
        ['imenik',     'Imenik',        $ico['imenik'],     null,                 0,                       '#c026d3', 'Kontakti i poslovni partneri'],
        ['magacin',    'Magacin',       $ico['magacin'],    null,                 0,                       '#475569', 'Stanje i kretanje magacina'],
        // — ostatak (iza strelice) —
        ['nabavka',    'Nabavka',       $ico['nabavka'],    'home-badge-nabavka', $counts['nabavka'] ?? 0, '#ea580c', 'Zahtevi i porudžbine nabavke'],
        ['kontakt',    'Kontakt forme', $ico['kontakt'],    'home-badge-kontakt', $counts['kontakt'] ?? 0, '#db2777', 'Primljene kontakt forme'],
        ['gradilista', 'Gradilišta',    $ico['gradilista'], null,                 0,                       '#b45309', 'Pregled i upravljanje gradilištima'],
        ['hr',         'Zaposleni',     $ico['zaposleni'],  null,                 0,                       '#0891b2', 'Evidencija i pregled zaposlenih'],
        ['tim',        'Tim',           $ico['tim'],        null,                 0,                       '#4f46e5', 'Upravljanje timovima i ulogama'],
        ['obavestenja','Obaveštenja',   $ico['obavestenja'],null,                 0,                       '#dc2626', 'Sva obaveštenja na jednom mestu'],
    ];
    $primarni = 6;
} elseif ($je_elektricar) {
    $tiles = [
        ['danas',      'Raspored',   $ico['raspored'],   null,                 0,                       '#7c3aed', 'Tvoj raspored i zaduženja'],
        ['poruke',     'Poruke',     $ico['poruke'],     'home-badge-poruke',  $counts['poruke']  ?? 0, '#0d9488', 'Komunikacija i razmena poruka'],
        ['nabavka',    'Nabavka',    $ico['nabavka'],    'home-badge-nabavka', $counts['nabavka'] ?? 0, '#ea580c', 'Zahtevi i porudžbine nabavke'],
        ['evidencija', 'Evidencija', $ico['evidencija'], null,                 0,                       '#16a34a', 'Evidencija i izveštaji'],
        ['hr',         'Moj profil', $ico['profil'],     null,                 0,                       '#0891b2', 'Tvoji lični podaci'],
    ];
    $primarni = count($tiles);
} else {
    $tiles = [
        ['zadaci',     'Zadaci',        $ico['zadaci'],     'home-badge-zadaci', $counts['zadaci']  ?? 0, '#2563eb', 'Pregled i upravljanje zadacima'],
        ['nabavka',    'Nabavka',       $ico['nabavka'],    'home-badge-nabavka',$counts['nabavka'] ?? 0, '#ea580c', 'Zahtevi i porudžbine nabavke'],
        ['raspored',   'Raspored',      $ico['raspored'],   null,                0,                       '#7c3aed', 'Planiranje i pregled rasporeda'],
        ['poruke',     'Poruke',        $ico['poruke'],     'home-badge-poruke', $counts['poruke']  ?? 0, '#0d9488', 'Komunikacija i razmena poruka'],
        ['kontakt',    'Kontakt forme', $ico['kontakt'],    'home-badge-kontakt',$counts['kontakt'] ?? 0, '#db2777', 'Primljene kontakt forme'],
        ['gradilista', 'Gradilišta',    $ico['gradilista'], null,                0,                       '#b45309', 'Pregled i upravljanje gradilištima'],
        ['hr',         'Zaposleni',     $ico['zaposleni'],  null,                0,                       '#0891b2', 'Evidencija i pregled zaposlenih'],
        ['evidencija', 'Evidencija',    $ico['evidencija'], null,                0,                       '#16a34a', 'Evidencija i izveštaji'],
    ];
    if ($is_admin) {
        $tiles[] = ['tim',         'Tim',         $ico['tim'],         null, 0, '#4f46e5', 'Upravljanje timovima i ulogama'];
        $tiles[] = ['magacin',     'Magacin',     $ico['magacin'],     null, 0, '#475569', 'Stanje i kretanje magacina'];
        $tiles[] = ['imenik',      'Imenik',      $ico['imenik'],      null, 0, '#c026d3', 'Kontakti i poslovni partneri'];
        $tiles[] = ['obavestenja', 'Obaveštenja', $ico['obavestenja'], null, 0, '#dc2626', 'Sva obaveštenja na jednom mestu'];
    }
    $primarni = count($tiles);
}
$ime_prvo = explode(' ', $user_ime)[0];
?>

<style>
.home-screen { padding:2px 0 24px; max-width:600px; margin:0 auto; }
.home-greet { margin:0 0 4px; font-size:22px; font-weight:800; color:#1a3a6e; }
.home-greet .wave { font-weight:400; }
.home-sub { margin:0 0 18px; font-size:13.5px; color:var(--muted); }

/* Pretraga */
.home-search { position:relative; margin-bottom:20px; }
.home-search .hs-ico { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); display:flex; }
.home-search input {
  width:100%; box-sizing:border-box;
  padding:13px 16px 13px 42px;
  border:1.5px solid var(--light2); border-radius:12px;
  background:#fff; font-size:14px; color:#1f2a44;
  box-shadow:0 1px 3px rgba(20,40,80,.04);
}
.home-search input:focus { outline:none; border-color:#93c5fd; }

/* Mreža pločica */
.home-grid {
  display:grid;
  /* Uvek 3 ikonice u redu — isto na uskom (sklopljen Fold) i širokom (rasklopljen) ekranu */
  grid-template-columns:repeat(3, 1fr);
  gap:14px;
}
.home-tile {
  position:relative;
  display:flex; flex-direction:column; align-items:center; justify-content:flex-start;
  gap:8px;
  background:#fff;
  border:1px solid var(--light2);
  border-radius:16px;
  padding:16px 10px 14px;
  text-decoration:none;
  color:#1f2a44;
  box-shadow:0 1px 3px rgba(20,40,80,.05);
  transition:transform .08s, box-shadow .15s;
}
.home-tile:active { transform:scale(.96); }
.home-tile:hover { box-shadow:0 4px 14px rgba(20,40,80,.12); }
.home-tile .ht-ico {
  width:46px; height:46px; border-radius:13px;
  display:flex; align-items:center; justify-content:center;
  background:color-mix(in srgb, var(--c) 14%, #fff);
}
.home-tile .ht-ico svg { width:24px; height:24px; color:var(--c); }
.home-tile .ht-label { font-size:13px; font-weight:700; text-align:center; line-height:1.2; }
.home-tile .ht-desc  { font-size:10px; color:var(--muted); text-align:center; line-height:1.3; }
.home-tile .ht-badge {
  position:absolute; top:9px; right:9px;
  min-width:18px; height:18px; padding:0 5px;
  background:#e53935; color:#fff;
  border-radius:99px;
  font-size:10.5px; font-weight:800;
  display:flex; align-items:center; justify-content:center;
  box-shadow:0 1px 4px rgba(229,57,53,.45);
}
.home-noresults { display:none; padding:30px; text-align:center; color:var(--muted); font-size:14px; }

/* Dodatne pločice (iza strelice) — sakrivene dok se mreža ne raširi */
.home-tile--extra { display:none; }
.home-grid.expanded .home-tile--extra { display:flex; }

/* Dugme "Prikaži još" sa strelicom */
.home-toggle {
  display:flex; align-items:center; justify-content:center; gap:7px;
  width:100%; margin-top:14px; padding:11px 16px;
  background:#fff; border:1.5px solid var(--light2); border-radius:12px;
  color:#1a3a6e; font-size:13.5px; font-weight:700; cursor:pointer;
  box-shadow:0 1px 3px rgba(20,40,80,.05);
}
.home-toggle:active { transform:scale(.98); }
.home-toggle .ht-toggle-chevron { transition:transform .2s; }
.home-toggle.open .ht-toggle-chevron { transform:rotate(180deg); }

/* Promo baner */
.home-promo {
  display:flex; align-items:center; gap:14px;
  margin-top:22px; padding:16px 18px;
  background:linear-gradient(135deg,#ecfdf5,#eff6ff);
  border:1px solid #d1fae5; border-radius:16px;
}
.home-promo .hp-emoji { font-size:30px; flex-shrink:0; }
.home-promo .hp-txt { flex:1; min-width:0; }
.home-promo .hp-txt b { display:block; font-size:14px; color:#1a3a6e; }
.home-promo .hp-txt span { font-size:12px; color:var(--muted); }
.home-promo .hp-btn {
  flex-shrink:0; background:#16a34a; color:#fff; text-decoration:none;
  font-size:12.5px; font-weight:700; border-radius:10px; padding:9px 14px; white-space:nowrap;
}

@media (max-width:480px) {
  .home-grid { gap:10px; }
  .home-greet { font-size:20px; }
  .home-tile .ht-desc { font-size:9px; }
}
</style>

<div class="home-screen">
  <h1 class="home-greet">Zdravo, <?= h($ime_prvo) ?> <span class="wave">👋</span></h1>
  <p class="home-sub">Dobrodošao nazad u Ekošarnu.</p>

  <div class="home-search">
    <span class="hs-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
    <input type="text" id="home-pretraga" placeholder="Pretraži..." autocomplete="off" oninput="filtrirajPlocice(this.value)">
  </div>

  <div class="home-grid" id="home-grid">
    <?php foreach ($tiles as $i => [$url, $label, $svg, $badgeId, $cnt, $color, $desc]): ?>
      <a class="home-tile<?= $i >= $primarni ? ' home-tile--extra' : '' ?>" data-naziv="<?= h(mb_strtolower($label)) ?>" href="<?= BASE_URL ?>/?page=<?= h($url) ?>" style="--c:<?= h($color) ?>;">
        <?php if ($badgeId !== null): ?>
          <span class="ht-badge" id="<?= h($badgeId) ?>" style="<?= $cnt > 0 ? '' : 'display:none;' ?>"><?= (int)$cnt ?></span>
        <?php endif; ?>
        <span class="ht-ico"><?= $svg ?></span>
        <span class="ht-label"><?= h($label) ?></span>
        <span class="ht-desc"><?= h($desc) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if (count($tiles) > $primarni): ?>
    <button type="button" class="home-toggle" id="home-toggle" onclick="toggleExtra()">
      <span class="ht-toggle-text">Prikaži još</span>
      <svg class="ht-toggle-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
  <?php endif; ?>
  <div class="home-noresults" id="home-noresults">Nema rezultata za pretragu.</div>

  <div class="home-promo">
    <span class="hp-emoji">📋</span>
    <div class="hp-txt">
      <b>Sve na jednom mestu</b>
      <span>Brz pristup svim funkcijama koje koristite svaki dan.</span>
    </div>
    <a class="hp-btn" href="<?= BASE_URL ?>/?page=novosti">Prikaži novosti</a>
  </div>
</div>

<script>
function toggleExtra() {
  var grid = document.getElementById('home-grid');
  var btn = document.getElementById('home-toggle');
  var open = grid.classList.toggle('expanded');
  if (btn) {
    btn.classList.toggle('open', open);
    var tt = btn.querySelector('.ht-toggle-text');
    if (tt) tt.textContent = open ? 'Prikaži manje' : 'Prikaži još';
  }
}

function filtrirajPlocice(q) {
  q = (q || '').trim().toLowerCase();
  var grid = document.getElementById('home-grid');
  var prazno = document.getElementById('home-noresults');
  var btn = document.getElementById('home-toggle');
  // Tokom pretrage prikaži i sakrivene pločice (i sakrij dugme); po brisanju vrati nazad
  if (q) {
    grid.classList.add('expanded');
    if (btn) btn.style.display = 'none';
  } else {
    grid.classList.remove('expanded');
    if (btn) {
      btn.style.display = '';
      btn.classList.remove('open');
      var tt = btn.querySelector('.ht-toggle-text');
      if (tt) tt.textContent = 'Prikaži još';
    }
  }
  var vidljivih = 0;
  grid.querySelectorAll('.home-tile').forEach(function(t) {
    var naziv = t.getAttribute('data-naziv') || '';
    var ok = !q || naziv.indexOf(q) !== -1;
    t.style.display = ok ? '' : 'none';
    if (ok) vidljivih++;
  });
  prazno.style.display = (q && vidljivih === 0) ? 'block' : 'none';
}
// Bottom-bar "Pretraga" fokusira polje
if (new URLSearchParams(location.search).get('pretraga') === '1') {
  var hp = document.getElementById('home-pretraga');
  if (hp) { hp.focus(); }
}
</script>
