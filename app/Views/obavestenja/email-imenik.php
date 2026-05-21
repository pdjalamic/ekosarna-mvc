<div class="topbar-admin">
    <div class="page-title">📧 Email iz imenika</div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

<!-- ═══ LEVO: Primaoci ══════════════════════════════════════ -->
<div>
    <div style="background:#fff;border:1.5px solid var(--light2);border-radius:12px;overflow:hidden;">
        <div style="padding:14px 16px;border-bottom:1.5px solid var(--light2);display:flex;align-items:center;justify-content:space-between;">
            <span style="font-weight:700;font-size:14px;color:var(--blue);">Primaoci</span>
            <div style="display:flex;gap:8px;">
                <button onclick="selektujSve(true)"  class="btn-sm mark" style="font-size:11px;">✓ Svi</button>
                <button onclick="selektujSve(false)" class="btn-sm"      style="font-size:11px;">✗ Nijedan</button>
            </div>
        </div>

        <div style="max-height:520px;overflow-y:auto;">
        <?php if (empty($firme)): ?>
            <p style="padding:20px;color:var(--muted);font-size:13px;">Nema kontakata sa email adresom u imeniku.</p>
        <?php else: ?>
        <?php foreach ($firme as $f): ?>
        <div class="ob-firma-blok" data-firma="<?= $f['id'] ?>">
            <!-- Firma header -->
            <div class="ob-firma-header" onclick="toggleFirma(<?= $f['id'] ?>)">
                <label class="ob-firma-check" onclick="event.stopPropagation()">
                    <input type="checkbox" class="ob-firma-cb" data-firma="<?= $f['id'] ?>"
                        onchange="toggleFirmaKontakti(<?= $f['id'] ?>, this.checked)">
                </label>
                <span class="ob-firma-naziv"><?= h($f['naziv']) ?></span>
                <span class="ob-firma-count"><?= count($f['kontakti']) ?></span>
                <span class="ob-firma-arrow" id="arrow-<?= $f['id'] ?>">▼</span>
            </div>

            <!-- Kontakti -->
            <div class="ob-kontakti-wrap" id="kontakti-<?= $f['id'] ?>" style="display:none;">
                <?php foreach ($f['kontakti'] as $k): ?>
                <label class="ob-kontakt-row">
                    <input type="checkbox" class="ob-kontakt-cb" value="<?= $k['id'] ?>"
                        data-firma="<?= $f['id'] ?>"
                        onchange="azurirajFirmaCb(<?= $f['id'] ?>); azurirajBrojac()">
                    <div class="ob-kontakt-info">
                        <span class="ob-kontakt-ime"><?= h($k['ime'] ?: '—') ?></span>
                        <span class="ob-kontakt-email"><?= h($k['email']) ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>

        <div style="padding:10px 16px;border-top:1.5px solid var(--light2);font-size:12px;color:var(--muted);">
            Selektovano: <strong id="ob-brojac">0</strong> primaoca
        </div>
    </div>
</div>

<!-- ═══ DESNO: Poruka ════════════════════════════════════════ -->
<div>
    <div style="background:#fff;border:1.5px solid var(--light2);border-radius:12px;padding:20px;">
        <div style="font-weight:700;font-size:14px;color:var(--blue);margin-bottom:16px;">Poruka</div>

        <div class="tim-form-group">
            <label>Naslov</label>
            <input type="text" id="ob-naslov" maxlength="255"
                placeholder="npr. Obaveštenje o promeni adrese"
                style="border:1.5px solid var(--light2);border-radius:7px;padding:8px 12px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;">
        </div>

        <div class="tim-form-group">
            <label>Tekst poruke</label>
            <textarea id="ob-tekst" rows="10"
                placeholder="Poštovani,&#10;&#10;Obaveštavamo Vas da smo promenili adresu..."
                style="border:1.5px solid var(--light2);border-radius:7px;padding:8px 12px;font-size:13px;font-family:inherit;outline:none;background:var(--light);width:100%;resize:vertical;box-sizing:border-box;"></textarea>
        </div>

        <div class="tim-form-group">
            <label>Prilozi (opciono)</label>
            <input type="file" id="ob-attachmenti" multiple
                style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 12px;font-size:13px;background:var(--light);width:100%;box-sizing:border-box;">
            <div style="font-size:11px;color:var(--muted);margin-top:4px;">Možete selektovati više fajlova odjednom.</div>
        </div>

        <div id="ob-err" style="display:none;color:#dc2626;font-size:12px;background:#fef2f2;border-radius:6px;padding:8px 12px;margin-bottom:10px;"></div>
        <div id="ob-ok"  style="display:none;color:#059669;font-size:12px;background:#f0fdf4;border-radius:6px;padding:8px 12px;margin-bottom:10px;"></div>

        <div style="display:flex;justify-content:flex-end;">
            <button onclick="posaljiEmail()" class="btn-primary" id="ob-btn-posalji" style="padding:11px 28px;font-size:14px;">
                📧 Pošalji
            </button>
        </div>
    </div>
</div>

</div><!-- end grid -->

<style>
.ob-firma-blok { border-bottom:1px solid var(--light2); }
.ob-firma-blok:last-child { border-bottom:none; }

.ob-firma-header {
    display:flex;align-items:center;gap:10px;
    padding:10px 14px;cursor:pointer;
    background:#fff;transition:background .1s;
    user-select:none;
}
.ob-firma-header:hover { background:var(--light); }

.ob-firma-check { display:flex;align-items:center;flex-shrink:0; }
.ob-firma-naziv { flex:1;font-size:13px;font-weight:600;color:var(--text); }
.ob-firma-count {
    font-size:11px;background:var(--light2);border-radius:99px;
    padding:1px 7px;color:var(--muted);flex-shrink:0;
}
.ob-firma-arrow { font-size:10px;color:var(--muted);flex-shrink:0;transition:transform .2s; }
.ob-firma-arrow.open { transform:rotate(180deg); }

.ob-kontakti-wrap { background:#f8faff;border-top:1px solid var(--light2); }

.ob-kontakt-row {
    display:flex;align-items:center;gap:10px;
    padding:8px 14px 8px 36px;cursor:pointer;
    transition:background .1s;
}
.ob-kontakt-row:hover { background:#eff6ff; }

.ob-kontakt-info { display:flex;flex-direction:column;gap:1px; }
.ob-kontakt-ime   { font-size:12px;font-weight:600;color:var(--text); }
.ob-kontakt-email { font-size:11px;color:var(--muted); }
</style>

<script>
function toggleFirma(firmaId) {
    var wrap  = document.getElementById('kontakti-' + firmaId);
    var arrow = document.getElementById('arrow-' + firmaId);
    var open  = wrap.style.display !== 'none';
    wrap.style.display  = open ? 'none' : 'block';
    arrow.classList.toggle('open', !open);
}

function toggleFirmaKontakti(firmaId, checked) {
    document.querySelectorAll('.ob-kontakt-cb[data-firma="' + firmaId + '"]').forEach(function(cb) {
        cb.checked = checked;
    });
    azurirajBrojac();
}

function azurirajFirmaCb(firmaId) {
    var sve  = document.querySelectorAll('.ob-kontakt-cb[data-firma="' + firmaId + '"]');
    var cekd = document.querySelectorAll('.ob-kontakt-cb[data-firma="' + firmaId + '"]:checked');
    var firmaCb = document.querySelector('.ob-firma-cb[data-firma="' + firmaId + '"]');
    if (!firmaCb) return;
    firmaCb.checked       = cekd.length === sve.length;
    firmaCb.indeterminate = cekd.length > 0 && cekd.length < sve.length;
}

function selektujSve(checked) {
    document.querySelectorAll('.ob-kontakt-cb').forEach(function(cb) { cb.checked = checked; });
    document.querySelectorAll('.ob-firma-cb').forEach(function(cb) {
        cb.checked = checked;
        cb.indeterminate = false;
    });
    azurirajBrojac();
}

function azurirajBrojac() {
    var n = document.querySelectorAll('.ob-kontakt-cb:checked').length;
    document.getElementById('ob-brojac').textContent = n;
}

function posaljiEmail() {
    var naslov = document.getElementById('ob-naslov').value.trim();
    var tekst  = document.getElementById('ob-tekst').value.trim();
    var err    = document.getElementById('ob-err');
    var ok     = document.getElementById('ob-ok');
    var btn    = document.getElementById('ob-btn-posalji');

    err.style.display = 'none';
    ok.style.display  = 'none';

    var primaoci = [];
    document.querySelectorAll('.ob-kontakt-cb:checked').forEach(function(cb) {
        primaoci.push(parseInt(cb.value));
    });

    if (!naslov) { err.textContent = 'Unesite naslov poruke.'; err.style.display = 'block'; return; }
    if (!tekst)  { err.textContent = 'Unesite tekst poruke.';  err.style.display = 'block'; return; }
    if (!primaoci.length) { err.textContent = 'Izaberite bar jednog primaoca.'; err.style.display = 'block'; return; }

    btn.disabled    = true;
    btn.textContent = '⏳ Slanje...';

    var fd = new FormData();
    fd.append('_action',  'obavestenja_posalji_email');
    fd.append('id',       0);
    fd.append('naslov',   naslov);
    fd.append('tekst',    tekst);
    fd.append('primaoci', JSON.stringify(primaoci));

    var files = document.getElementById('ob-attachmenti').files;
    for (var i = 0; i < files.length; i++) {
        fd.append('attachmenti[]', files[i]);
    }

    fetch('', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        btn.disabled    = false;
        btn.textContent = '📧 Pošalji';

        if (d.ok) {
            var msg = '✅ Poslato ' + d.poslato + ' od ' + d.ukupno + ' poruka.';
            if (d.greske && d.greske.length) {
                msg += ' Greške: ' + d.greske.join(', ');
            }
            ok.textContent    = msg;
            ok.style.display  = 'block';
            // Reset forme
            document.getElementById('ob-naslov').value = '';
            document.getElementById('ob-tekst').value  = '';
            document.getElementById('ob-attachmenti').value = '';
            selektujSve(false);
        } else {
            err.textContent   = d.err || 'Greška pri slanju.';
            err.style.display = 'block';
        }
    })
    .catch(function() {
        btn.disabled    = false;
        btn.textContent = '📧 Pošalji';
        err.textContent   = 'Mrežna greška, pokušaj ponovo.';
        err.style.display = 'block';
    });
}
</script>
