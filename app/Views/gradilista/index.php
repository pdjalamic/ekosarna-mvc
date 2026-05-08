<?php
$statusLabele = [
    'aktivno'  => 'Aktivno',
    'pauza'    => 'Pauza',
    'zavrseno' => 'Završeno',
];
$statusBadge = [
    'aktivno'  => 'badge-ok',
    'pauza'    => 'badge-new',
    'zavrseno' => 'badge-operator',
];
?>

<div class="topbar-admin">
    <div class="page-title">Gradilišta</div>
    <?php if (\Core\Auth::isAdmin()): ?>
    <button type="button" class="btn-primary" onclick="openDodaj()">+ Novo gradilište</button>
    <?php endif; ?>
</div>

<!-- Filter bar -->
<div class="filter-bar" style="margin-bottom:16px;">
    <?php
    $statusi = ['sve' => 'Sva', 'aktivno' => 'Aktivna', 'pauza' => 'Pauza', 'zavrseno' => 'Završena'];
    foreach ($statusi as $val => $lab):
        $count = '';
        if ($val !== 'sve' && isset($brojevi[$val])) $count = ' (' . $brojevi[$val] . ')';
    ?>
    <a href="?page=gradilista&status=<?= $val ?><?= $search ? '&q='.urlencode($search) : '' ?>"
       class="filter-btn <?= $status === $val ? 'active' : '' ?>">
        <?= $lab . $count ?>
    </a>
    <?php endforeach; ?>

    <form method="GET" class="search-wrap" style="margin-left:auto;">
        <input type="hidden" name="page" value="gradilista">
        <input type="hidden" name="status" value="<?= h($status) ?>">
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Pretraži..." class="search-input">
        <button type="submit" class="search-btn">Traži</button>
    </form>
</div>

<!-- Lista gradilišta -->
<?php if (empty($gradilista)): ?>
    <div class="empty"><big>🏗️</big>Nema gradilišta.</div>
<?php else: ?>
<div class="g-grid">
    <?php foreach ($gradilista as $g): ?>
    <div class="g-card" data-id="<?= $g['id'] ?>">
        <?php if ($g['prva_slika']): ?>
        <div class="g-card__slika">
            <img src="<?= h($g['prva_slika']) ?>" alt="<?= h($g['naziv']) ?>">
        </div>
        <?php else: ?>
        <div class="g-card__slika g-card__slika--empty">🏗️</div>
        <?php endif; ?>

        <div class="g-card__body">
            <div class="g-card__header">
                <span class="<?= $statusBadge[$g['status']] ?? 'badge-operator' ?>">
                    <?= $statusLabele[$g['status']] ?? $g['status'] ?>
                </span>
                <?php if (\Core\Auth::isAdmin()): ?>
                <div class="g-card__actions">
                    <button type="button" class="btn-sm" onclick="openEdit(<?= $g['id'] ?>)">✏️</button>
                    <button type="button" class="btn-sm del" onclick="obrisiGradiliste(<?= $g['id'] ?>, '<?= h(addslashes($g['naziv'])) ?>')">🗑️</button>
                </div>
                <?php endif; ?>
            </div>
            <div class="g-card__naziv"><?= h($g['naziv']) ?></div>
            <div class="g-card__adresa">📍 <?= h($g['adresa']) ?></div>
            <?php if ($g['pocetak'] || $g['kraj']): ?>
            <div class="g-card__period">
                📅 <?= $g['pocetak'] ? date('d.m.Y', strtotime($g['pocetak'])) : '?' ?>
                → <?= $g['kraj'] ? date('d.m.Y', strtotime($g['kraj'])) : 'u toku' ?>
            </div>
            <?php endif; ?>
            <?php if ($g['napomena']): ?>
            <div class="g-card__napomena"><?= h($g['napomena']) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- MODAL -->
<div id="g-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:540px;box-shadow:0 24px 80px #000a;position:relative;max-height:90vh;overflow-y:auto;">

        <button type="button" onclick="closeGModal()" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>

        <h3 id="g-modal-title" style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:18px;">Novo gradilište</h3>
        <input type="hidden" id="g-id" value="0">

        <div class="tim-form-group">
            <label>Naziv *</label>
            <input type="text" id="g-naziv" placeholder="npr. Stambeni objekat Centar" maxlength="200">
        </div>
        <div class="tim-form-group">
            <label>Adresa</label>
            <input type="text" id="g-adresa" placeholder="Ulica i broj, Grad" maxlength="300">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
            <div class="tim-form-group">
                <label>Status</label>
                <select id="g-status">
                    <option value="aktivno">Aktivno</option>
                    <option value="pauza">Pauza</option>
                    <option value="zavrseno">Završeno</option>
                </select>
            </div>
            <div class="tim-form-group">
                <label>Početak</label>
                <input type="date" id="g-pocetak">
            </div>
            <div class="tim-form-group">
                <label>Kraj</label>
                <input type="date" id="g-kraj">
            </div>
        </div>
        <div class="tim-form-group">
            <label>Napomena</label>
            <textarea id="g-napomena" rows="2" style="border:1.5px solid var(--light2);border-radius:7px;padding:8px 12px;font-size:13px;font-family:inherit;outline:none;background:var(--light);width:100%;resize:vertical;" placeholder="Opciona napomena..."></textarea>
        </div>

        <!-- Slike — samo pri izmeni -->
        <div id="g-slike-wrap" style="display:none;">
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--blue);display:block;margin-bottom:6px;">
                Slike <span style="font-weight:400;color:var(--muted);text-transform:none;">(max 4, prva se prikazuje na kartici)</span>
            </label>
            <div class="g-slike-grid" id="g-slike-grid"></div>
            <label class="g-upload-btn" id="g-upload-label">
                + Dodaj sliku
                <input type="file" id="g-upload-input" accept="image/jpeg,image/png,image/webp" style="display:none">
            </label>
        </div>

        <div id="g-err" style="color:#dc2626;font-size:12px;margin-top:8px;display:none;"></div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
            <button type="button" class="mail-cancel-btn" onclick="closeGModal()">Odustani</button>
            <button type="button" class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="sacuvajGradiliste()">Sačuvaj</button>
        </div>
    </div>
</div>

<style>
.g-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; }
.g-card { background:#fff; border-radius:12px; border:1.5px solid var(--light2); overflow:hidden; display:flex; flex-direction:column; transition:box-shadow .15s,border-color .15s; }
.g-card:hover { box-shadow:0 4px 16px #1a3a6e14; border-color:#a0bcd8; }
.g-card__slika { width:100%; height:160px; overflow:hidden; background:var(--light); }
.g-card__slika img { width:100%; height:100%; object-fit:cover; display:block; }
.g-card__slika--empty { display:flex; align-items:center; justify-content:center; font-size:48px; color:var(--light2); }
.g-card__body { padding:14px; display:flex; flex-direction:column; gap:7px; }
.g-card__header { display:flex; align-items:center; justify-content:space-between; }
.g-card__actions { display:flex; gap:4px; }
.g-card__naziv { font-size:16px; font-weight:700; color:var(--blue); }
.g-card__adresa { font-size:12px; color:var(--muted); }
.g-card__period { font-size:12px; color:var(--muted); }
.g-card__napomena { font-size:12px; color:#555; font-style:italic; border-top:1px solid var(--light2); padding-top:8px; }

.g-slike-grid { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:10px; }
.g-slika-thumb { position:relative; width:100px; height:80px; border-radius:8px; overflow:hidden; border:1.5px solid var(--light2); }
.g-slika-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
.g-slika-thumb__del { position:absolute; top:3px; right:3px; background:rgba(0,0,0,.55); color:#fff; border:none; border-radius:4px; font-size:11px; cursor:pointer; padding:2px 5px; }
.g-upload-btn { display:inline-block; padding:7px 14px; border:1.5px dashed var(--light2); border-radius:8px; font-size:13px; color:var(--blue); cursor:pointer; }
.g-upload-btn:hover { background:var(--light); }
.g-upload-btn.disabled { opacity:.5; pointer-events:none; }

@media(max-width:600px) { .g-grid { grid-template-columns:1fr; } .g-card__slika { height:130px; } }
</style>

<script>
let trenutniId = 0;

function closeGModal() {
    document.getElementById('g-modal').style.display = 'none';
}

function openDodaj() {
    trenutniId = 0;
    document.getElementById('g-modal-title').textContent = 'Novo gradilište';
    document.getElementById('g-id').value = '0';
    document.getElementById('g-naziv').value = '';
    document.getElementById('g-adresa').value = '';
    document.getElementById('g-status').value = 'aktivno';
    document.getElementById('g-pocetak').value = '';
    document.getElementById('g-kraj').value = '';
    document.getElementById('g-napomena').value = '';
    document.getElementById('g-err').style.display = 'none';
    document.getElementById('g-slike-wrap').style.display = 'none';
    document.getElementById('g-modal').style.display = 'flex';
}

function openEdit(id) {
    trenutniId = id;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_action=gradiliste_get&id=' + id
    })
    .then(r => r.json())
    .then(g => {
        document.getElementById('g-modal-title').textContent = 'Izmeni gradilište';
        document.getElementById('g-id').value       = g.id;
        document.getElementById('g-naziv').value    = g.naziv || '';
        document.getElementById('g-adresa').value   = g.adresa || '';
        document.getElementById('g-status').value   = g.status || 'aktivno';
        document.getElementById('g-pocetak').value  = g.pocetak || '';
        document.getElementById('g-kraj').value     = g.kraj || '';
        document.getElementById('g-napomena').value = g.napomena || '';
        document.getElementById('g-err').style.display = 'none';
        document.getElementById('g-slike-wrap').style.display = 'block';
        renderSlike(g.slike || []);
        document.getElementById('g-modal').style.display = 'flex';
    });
}

function renderSlike(slike) {
    const grid = document.getElementById('g-slike-grid');
    grid.innerHTML = '';
    slike.forEach(s => {
        const div = document.createElement('div');
        div.className = 'g-slika-thumb';
        div.innerHTML = `<img src="${s.putanja}"><button class="g-slika-thumb__del" onclick="obrisiSliku(${s.id}, this)">✕</button>`;
        grid.appendChild(div);
    });
    document.getElementById('g-upload-label').classList.toggle('disabled', slike.length >= 4);
}

function obrisiSliku(slikaId, btn) {
    if (!confirm('Obriši ovu sliku?')) return;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_action=gradiliste_del_sliku&id=' + slikaId
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            btn.closest('.g-slika-thumb').remove();
            const grid = document.getElementById('g-slike-grid');
            document.getElementById('g-upload-label').classList.toggle('disabled', grid.children.length >= 4);
        }
    });
}

document.getElementById('g-upload-input').addEventListener('change', function() {
    if (!this.files[0] || !trenutniId) return;
    const formData = new FormData();
    formData.append('_action', 'gradiliste_upload_sliku');
    formData.append('id', trenutniId);
    formData.append('slika', this.files[0]);
    this.value = '';

    fetch('', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            const grid = document.getElementById('g-slike-grid');
            const div = document.createElement('div');
            div.className = 'g-slika-thumb';
            div.innerHTML = `<img src="${res.putanja}"><button class="g-slika-thumb__del" onclick="obrisiSliku(${res.slika_id}, this)">✕</button>`;
            grid.appendChild(div);
            document.getElementById('g-upload-label').classList.toggle('disabled', grid.children.length >= 4);
            document.getElementById('g-err').style.display = 'none';
        } else {
            const err = document.getElementById('g-err');
            err.textContent = res.err || 'Greška pri uploadu.';
            err.style.display = 'block';
        }
    });
});

function sacuvajGradiliste() {
    const id    = document.getElementById('g-id').value;
    const naziv = document.getElementById('g-naziv').value.trim();
    const err   = document.getElementById('g-err');

    if (!naziv) {
        err.textContent = 'Naziv je obavezan.';
        err.style.display = 'block';
        return;
    }

    const action = id === '0' ? 'gradiliste_add' : 'gradiliste_edit';
    const body = new URLSearchParams({
        _action:  action,
        id:       id,
        naziv:    naziv,
        adresa:   document.getElementById('g-adresa').value,
        status:   document.getElementById('g-status').value,
        pocetak:  document.getElementById('g-pocetak').value,
        kraj:     document.getElementById('g-kraj').value,
        napomena: document.getElementById('g-napomena').value,
    });

    fetch('', { method: 'POST', body })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            location.reload();
        } else {
            err.textContent = res.err || 'Greška.';
            err.style.display = 'block';
        }
    });
}

function obrisiGradiliste(id, naziv) {
    if (!confirm('Obriši gradilište "' + naziv + '"?')) return;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_action=gradiliste_del&id=' + id
    })
    .then(r => r.json())
    .then(res => { if (res.ok) location.reload(); });
}
</script>
