<?php
$today = date('Y-m-d');
$warn_days = 2; // dana do roka = žuto upozorenje
?>
<div class="topbar-admin">
  <div class="page-title">Interni zadaci</div>
  <div class="stats-bar">
    <span class="stat-pill">Svi: <strong><?= $svi ?></strong></span>
    <span class="stat-pill" style="background:#fffbeb;border-color:#fcd34d;color:#92400e;">
      Otvoreno: <strong><?= $otvoreno ?></strong>
    </span>
    <span class="stat-pill" style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;">
      U toku: <strong><?= $u_toku ?></strong>
    </span>
    <span class="stat-pill" style="background:#f0fdf4;border-color:#bbf7d0;color:#15803d;">
      Završeno: <strong><?= $zavrseno ?></strong>
    </span>
  </div>
</div>

<!-- NOVI ZADATAK -->
<div class="novi-zadatak-wrap">
  <textarea id="z-tekst" placeholder="Upiši novi zadatak..."></textarea>
  <div class="novi-zadatak-row">
    <input type="text" id="z-kat" placeholder="Kategorija (npr. Admin, IT...)"
           list="kat-list" autocomplete="off">
    <datalist id="kat-list">
      <?php foreach ($kategorije as $k): ?>
        <option value="<?= h($k) ?>">
      <?php endforeach; ?>
    </datalist>
    <input type="date" id="z-rok" title="Rok">
    <select id="z-dodeljeno">
      <option value="">— Dodeli osobi (opcionalno)</option>
      <?php foreach ($korisnici as $u): ?>
        <option value="<?= $u['id'] ?>"><?= h($u['ime']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="im-add-btn" onclick="dodajZadatak()" style="flex-shrink:0;">
      + Dodaj
    </button>
  </div>
  <div id="z-add-err" style="color:var(--red);font-size:12px;margin-top:6px;display:none;"></div>
</div>

<!-- FILTER BAR -->
<div class="z-filter-bar">
  <input type="text" id="z-search" placeholder="Pretraži zadatke..."
         value="<?= h($filters['q']) ?>" oninput="filterZadaci()">
  <button class="z-btn-filter <?= $filters['status']==='' ? 'active':'' ?>"
          onclick="setStatusFilter('')">Svi</button>
  <button class="z-btn-filter <?= $filters['status']==='otvoreno' ? 'active':'' ?>"
          onclick="setStatusFilter('otvoreno')">Otvoreno</button>
  <button class="z-btn-filter <?= $filters['status']==='u_toku' ? 'active':'' ?>"
          onclick="setStatusFilter('u_toku')">U toku</button>
  <button class="z-btn-filter <?= $filters['status']==='zavrseno' ? 'active':'' ?>"
          onclick="setStatusFilter('zavrseno')">Završeno</button>
</div>

<!-- LISTA ZADATAKA -->
<div class="zadaci-list" id="zadaci-list">
<?php if (empty($zadaci)): ?>
  <div style="text-align:center;padding:40px;color:var(--muted);">
    <div style="font-size:36px;margin-bottom:10px;">✅</div>
    <?= $filters['q'] ? 'Nema rezultata za "' . h($filters['q']) . '"' : 'Nema zadataka.' ?>
  </div>
<?php else: ?>
  <?php foreach ($zadaci as $z): ?>
  <?php
    $rok_klasa = '';
    if ($z['rok'] && $z['status'] !== 'zavrseno') {
      $diff = (strtotime($z['rok']) - strtotime($today)) / 86400;
      if ($diff < 0)      $rok_klasa = 'prekoracen';
      elseif ($diff <= $warn_days) $rok_klasa = 'blizu';
    }
  ?>
  <div class="zadatak-card status-<?= $z['status'] ?>" id="zcard-<?= $z['id'] ?>">
    <div class="zadatak-check <?= $z['status'] ?>" onclick="ciklajStatus(<?= $z['id'] ?>, '<?= $z['status'] ?>')"
         title="Klikni za promenu statusa">
      <?php if ($z['status'] === 'zavrseno'): ?>✓
      <?php elseif ($z['status'] === 'u_toku'): ?>●
      <?php endif; ?>
    </div>
    <div class="zadatak-body">
      <div class="zadatak-tekst" id="ztekst-<?= $z['id'] ?>"><?= h($z['tekst']) ?></div>
      <div class="zadatak-meta">
        <?php if ($z['kategorija']): ?>
          <span class="zadatak-tag">📁 <?= h($z['kategorija']) ?></span>
        <?php endif; ?>
        <?php if ($z['rok']): ?>
          <span class="zadatak-rok <?= $rok_klasa ?>">
            📅 <?= date('d.m.Y.', strtotime($z['rok'])) ?>
            <?php if ($rok_klasa === 'prekoracen'): ?> — PREKORAČEN!
            <?php elseif ($rok_klasa === 'blizu'): ?> — USKORO!
            <?php endif; ?>
          </span>
        <?php endif; ?>
        <?php if ($z['dodeljeno_ime']): ?>
          <span class="zadatak-dodeljeno">👤 <?= h($z['dodeljeno_ime']) ?></span>
        <?php endif; ?>
        <span class="zadatak-dodeljeno" style="margin-left:auto;font-size:10px;">
          <?= h($z['kreirao_ime']) ?> · <?= date('d.m.', strtotime($z['datum_kreiranja'])) ?>
        </span>
      </div>
    </div>
    <div class="zadatak-actions">
      <select class="z-status-sel" onchange="promeniStatus(<?= $z['id'] ?>, this.value)">
        <option value="otvoreno"  <?= $z['status']==='otvoreno'  ? 'selected':'' ?>>Otvoreno</option>
        <option value="u_toku"    <?= $z['status']==='u_toku'    ? 'selected':'' ?>>U toku</option>
        <option value="zavrseno"  <?= $z['status']==='zavrseno'  ? 'selected':'' ?>>Završeno</option>
      </select>
      <button class="btn-sm" onclick="otvoriEditZadatak(<?= $z['id'] ?>,
        '<?= h(addslashes($z['tekst'])) ?>',
        '<?= h(addslashes($z['kategorija'])) ?>',
        '<?= $z['status'] ?>',
        '<?= $z['rok'] ?? '' ?>',
        '<?= $z['dodeljeno_id'] ?? '' ?>')" title="Izmeni">✎</button>
      <button class="btn-sm del" onclick="obrisiZadatak(<?= $z['id'] ?>)" title="Obriši">🗑</button>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- MODAL: Izmena zadatka -->
<div id="z-edit-modal"
  style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:center;justify-content:center;padding:16px;">
  <div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:500px;box-shadow:0 24px 80px #000a;position:relative;">
    <button onclick="document.getElementById('z-edit-modal').style.display='none'"
      style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
    <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:16px;">✎ Izmeni zadatak</h3>
    <input type="hidden" id="z-edit-id">
    <div class="tim-form-group" style="margin-bottom:10px;">
      <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--blue);display:block;margin-bottom:4px;">Tekst</label>
      <textarea id="z-edit-tekst" rows="3"
        style="width:100%;border:1.5px solid var(--light2);border-radius:8px;padding:9px 12px;font-size:14px;font-family:inherit;resize:vertical;outline:none;background:var(--light);"></textarea>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
      <input type="text" id="z-edit-kat" placeholder="Kategorija" list="kat-list-edit"
        style="flex:1;min-width:120px;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;font-family:inherit;background:var(--light);outline:none;">
      <datalist id="kat-list-edit">
        <?php foreach ($kategorije as $k): ?>
          <option value="<?= h($k) ?>">
        <?php endforeach; ?>
      </datalist>
      <input type="date" id="z-edit-rok"
        style="flex:1;min-width:120px;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;font-family:inherit;background:var(--light);outline:none;">
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
      <select id="z-edit-status"
        style="flex:1;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;font-family:inherit;background:var(--light);outline:none;">
        <option value="otvoreno">Otvoreno</option>
        <option value="u_toku">U toku</option>
        <option value="zavrseno">Završeno</option>
      </select>
      <select id="z-edit-dodeljeno"
        style="flex:1;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;font-family:inherit;background:var(--light);outline:none;">
        <option value="">— Dodeli osobi</option>
        <?php foreach ($korisnici as $u): ?>
          <option value="<?= $u['id'] ?>"><?= h($u['ime']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="color:var(--red);font-size:12px;margin-bottom:8px;display:none;" id="z-edit-err"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;">
      <button class="mail-cancel-btn" onclick="document.getElementById('z-edit-modal').style.display='none'">Odustani</button>
      <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="sacuvajZadatak()">Sačuvaj</button>
    </div>
  </div>
</div>

<script>
// ── Dodavanje ──
function dodajZadatak() {
  var tekst = document.getElementById('z-tekst').value.trim();
  var err   = document.getElementById('z-add-err');
  err.style.display = 'none';
  if (!tekst) { err.textContent = 'Upiši tekst zadatka.'; err.style.display = 'block'; return; }
  post({
    _action: 'zadatak_add',
    tekst: tekst,
    kategorija: document.getElementById('z-kat').value.trim(),
    rok: document.getElementById('z-rok').value,
    dodeljeno_id: document.getElementById('z-dodeljeno').value,
  }).then(function(d) {
    if (d.ok) {
      document.getElementById('z-tekst').value = '';
      document.getElementById('z-kat').value   = '';
      document.getElementById('z-rok').value   = '';
      document.getElementById('z-dodeljeno').value = '';
      location.reload();
    } else {
      err.textContent = d.err || 'Greška.'; err.style.display = 'block';
    }
  });
}

// Enter u textarea dodaje zadatak (Shift+Enter = novi red)
document.getElementById('z-tekst').addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); dodajZadatak(); }
});

// ── Status ──
function promeniStatus(id, status) {
  post({ _action: 'zadatak_status', id: id, status: status }).then(function(d) {
    if (d.ok) location.reload();
  });
}

function ciklajStatus(id, current) {
  var next = current === 'otvoreno' ? 'u_toku' : current === 'u_toku' ? 'zavrseno' : 'otvoreno';
  promeniStatus(id, next);
}

// ── Filter ──
function setStatusFilter(status) {
  var url = new URL(window.location.href);
  if (status) url.searchParams.set('zstatus', status);
  else url.searchParams.delete('zstatus');
  url.searchParams.set('page', 'zadaci');
  window.location.href = url.toString();
}

var _searchTimer;
function filterZadaci() {
  clearTimeout(_searchTimer);
  _searchTimer = setTimeout(function() {
    var url = new URL(window.location.href);
    var q = document.getElementById('z-search').value.trim();
    if (q) url.searchParams.set('zq', q);
    else url.searchParams.delete('zq');
    url.searchParams.set('page', 'zadaci');
    window.location.href = url.toString();
  }, 400);
}

// ── Edit ──
function otvoriEditZadatak(id, tekst, kat, status, rok, dodeId) {
  document.getElementById('z-edit-id').value       = id;
  document.getElementById('z-edit-tekst').value    = tekst;
  document.getElementById('z-edit-kat').value      = kat;
  document.getElementById('z-edit-status').value   = status;
  document.getElementById('z-edit-rok').value      = rok;
  document.getElementById('z-edit-dodeljeno').value= dodeId || '';
  document.getElementById('z-edit-err').style.display = 'none';
  document.getElementById('z-edit-modal').style.display = 'flex';
}

function sacuvajZadatak() {
  var id    = document.getElementById('z-edit-id').value;
  var tekst = document.getElementById('z-edit-tekst').value.trim();
  var err   = document.getElementById('z-edit-err');
  err.style.display = 'none';
  if (!tekst) { err.textContent = 'Tekst je obavezan.'; err.style.display = 'block'; return; }
  post({
    _action: 'zadatak_edit', id: id,
    tekst: tekst,
    kategorija: document.getElementById('z-edit-kat').value.trim(),
    status: document.getElementById('z-edit-status').value,
    rok: document.getElementById('z-edit-rok').value,
    dodeljeno_id: document.getElementById('z-edit-dodeljeno').value,
  }).then(function(d) {
    if (d.ok) { document.getElementById('z-edit-modal').style.display = 'none'; location.reload(); }
    else { err.textContent = d.err || 'Greška.'; err.style.display = 'block'; }
  });
}

// ── Brisanje ──
function obrisiZadatak(id) {
  if (!confirm('Obriši zadatak?')) return;
  post({ _action: 'zadatak_delete', id: id }).then(function(d) {
    if (d.ok) document.getElementById('zcard-' + id).remove();
  });
}
</script>
