<div class="topbar-admin" style="flex-wrap:wrap;gap:10px;">
    <div class="page-title">📊 Evidencija radova</div>
</div>

<!-- Filteri -->
<form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px;align-items:flex-end;">
    <input type="hidden" name="page" value="evidencija">
    <input type="hidden" name="tab" value="<?= h($tab) ?>">

    <div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:4px;">Od</div>
        <input type="date" name="od" value="<?= h($filter_od) ?>"
            style="border:1.5px solid var(--light2);border-radius:7px;padding:6px 10px;font-size:13px;outline:none;background:#fff;">
    </div>
    <div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:4px;">Do</div>
        <input type="date" name="do" value="<?= h($filter_do) ?>"
            style="border:1.5px solid var(--light2);border-radius:7px;padding:6px 10px;font-size:13px;outline:none;background:#fff;">
    </div>

    <?php if ($je_kancelarija): ?>
    <div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:4px;">Osoba</div>
        <select name="radnik" style="border:1.5px solid var(--light2);border-radius:7px;padding:6px 10px;font-size:13px;outline:none;background:#fff;">
            <option value="0">— Svi —</option>
            <?php foreach ($radnici as $r): ?>
            <option value="<?= $r['id'] ?>" <?= $filter_radnik == $r['id'] ? 'selected' : '' ?>><?= h($r['ime']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:4px;">Gradilište</div>
        <select name="gradiliste" style="border:1.5px solid var(--light2);border-radius:7px;padding:6px 10px;font-size:13px;outline:none;background:#fff;">
            <option value="0">— Sva —</option>
            <?php foreach ($gradilista as $g): ?>
            <option value="<?= $g['id'] ?>" <?= $filter_grad == $g['id'] ? 'selected' : '' ?>><?= h($g['naziv']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" class="btn-primary" style="padding:8px 18px;">Filtriraj</button>
    <a href="?page=evidencija&tab=<?= h($tab) ?>" class="btn-secondary">Resetuj</a>
</form>

<!-- Tabovi -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid var(--light2);">
    <a href="?page=evidencija&tab=vreme&od=<?= h($filter_od) ?>&do=<?= h($filter_do) ?>&radnik=<?= $filter_radnik ?>&gradiliste=<?= $filter_grad ?>"
       style="padding:9px 18px;font-size:13px;font-weight:600;text-decoration:none;border-radius:8px 8px 0 0;
              <?= $tab === 'vreme' ? 'background:#fff;color:var(--blue);border:2px solid var(--light2);border-bottom:2px solid #fff;margin-bottom:-2px;' : 'color:var(--muted);' ?>">
        🕐 Radni sati
        <span style="background:var(--light);border-radius:99px;font-size:11px;padding:1px 7px;margin-left:4px;"><?= count($vreme_unosi) ?></span>
    </a>
    <a href="?page=evidencija&tab=materijal&od=<?= h($filter_od) ?>&do=<?= h($filter_do) ?>&radnik=<?= $filter_radnik ?>&gradiliste=<?= $filter_grad ?>"
       style="padding:9px 18px;font-size:13px;font-weight:600;text-decoration:none;border-radius:8px 8px 0 0;
              <?= $tab === 'materijal' ? 'background:#fff;color:var(--blue);border:2px solid var(--light2);border-bottom:2px solid #fff;margin-bottom:-2px;' : 'color:var(--muted);' ?>">
        📦 Utrošak materijala
        <span style="background:var(--light);border-radius:99px;font-size:11px;padding:1px 7px;margin-left:4px;"><?= count($mat_unosi) ?></span>
    </a>
</div>

<?php if ($tab === 'vreme'): ?>
<!-- ═══ RADNI SATI ══════════════════════════════════════════ -->
<div style="margin-bottom:12px;display:flex;align-items:center;gap:16px;">
    <span style="font-size:13px;color:var(--muted);">
        Ukupno sati u periodu:
        <strong style="color:var(--blue);font-size:15px;"><?= number_format($ukupno_sati, 1, ',', '.') ?>h</strong>
    </span>
</div>

<div class="rs-tabela-wrap">
<?php if (empty($vreme_unosi)): ?>
    <div style="padding:40px;text-align:center;color:var(--muted);">Nema unosa radnog vremena za izabrani period.</div>
<?php else: ?>
<table class="rs-tabela">
    <thead>
        <tr>
            <th style="width:36px;text-align:center;">#</th>
            <th style="width:100px;">Datum</th>
            <th style="width:160px;">Ime i prezime</th>
            <th>Gradilište / Zadatak</th>
            <th style="width:100px;text-align:center;">Vreme</th>
            <th style="width:60px;text-align:center;">Sati</th>
            <th>Opis rada</th>
            <?php if ($is_admin): ?><th style="width:80px;text-align:right;"></th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($vreme_unosi as $i => $v): ?>
    <tr class="rs-red">
        <td style="text-align:center;font-size:11px;color:var(--muted);"><?= $i + 1 ?></td>
        <td style="font-size:12px;color:var(--muted);"><?= date('d.m.Y', strtotime($v['datum'])) ?></td>
        <td style="font-size:13px;font-weight:600;"><?= h($v['radnik_ime']) ?></td>
        <td style="font-size:12px;">
            <?php if ($v['gradiliste_naziv']): ?>
            <span style="color:var(--blue);">🏗️ <?= h($v['gradiliste_naziv']) ?></span>
            <?php endif; ?>
            <?php if ($v['zadatak_opis']): ?>
            <div style="color:var(--muted);font-size:11px;margin-top:2px;"><?= h(mb_substr($v['zadatak_opis'], 0, 60)) ?><?= mb_strlen($v['zadatak_opis']) > 60 ? '...' : '' ?></div>
            <?php endif; ?>
        </td>
        <td style="text-align:center;font-size:12px;">
            <?= $v['vreme_od'] ? substr($v['vreme_od'],0,5) : '?' ?>–<?= $v['vreme_do'] ? substr($v['vreme_do'],0,5) : '?' ?>
        </td>
        <td style="text-align:center;font-weight:700;font-size:13px;color:var(--blue);">
            <?= $v['ukupno_sati'] ? number_format($v['ukupno_sati'], 1, ',', '.') : '—' ?>
        </td>
        <td style="font-size:12px;">
            <?php if ($v['napomena_original'] ?? null): ?>
            <div style="color:var(--muted);font-style:italic;margin-bottom:4px;">
                📝 <?= h($v['napomena_original']) ?>
            </div>
            <?php endif; ?>
            <?php if ($v['napomena'] ?? null): ?>
            <div style="color:#1a3a6e;font-weight:600;">
                🏢 <?= h($v['napomena']) ?>
            </div>
            <?php elseif (!($v['napomena_original'] ?? null)): ?>
            <span style="color:var(--muted);">—</span>
            <?php endif; ?>
        </td>
        <?php if ($is_admin): ?>
        <td style="text-align:right;white-space:nowrap;">
            <button class="btn-sm" onclick="openIzmeniVreme(<?= $v['id'] ?>, '<?= h(addslashes($v['datum'])) ?>', '<?= h(addslashes($v['vreme_od'] ?? '')) ?>', '<?= h(addslashes($v['vreme_do'] ?? '')) ?>', <?= (float)($v['ukupno_sati'] ?? 0) ?>, '<?= h(addslashes($v['napomena'] ?? '')) ?>')" title="Izmeni">✏️</button>
            <button class="btn-sm del" onclick="obrisiVreme(<?= $v['id'] ?>)" title="Obriši" style="margin-left:4px;">🗑</button>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</div>

<?php elseif ($tab === 'materijal'): ?>
<!-- ═══ UTROŠAK MATERIJALA ══════════════════════════════════ -->
<div class="rs-tabela-wrap">
<?php if (empty($mat_unosi)): ?>
    <div style="padding:40px;text-align:center;color:var(--muted);">Nema unosa materijala za izabrani period.</div>
<?php else: ?>
<table class="rs-tabela">
    <thead>
        <tr>
            <th style="width:36px;text-align:center;">#</th>
            <th style="width:100px;">Datum</th>
            <th style="width:160px;">Ime i prezime</th>
            <th>Gradilište / Zadatak</th>
            <th>Artikal</th>
            <th style="width:80px;text-align:center;">Količina</th>
            <th style="width:50px;text-align:center;">JM</th>
            <?php if ($is_admin): ?><th style="width:80px;text-align:right;"></th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($mat_unosi as $i => $m): ?>
    <tr class="rs-red">
        <td style="text-align:center;font-size:11px;color:var(--muted);"><?= $i + 1 ?></td>
        <td style="font-size:12px;color:var(--muted);"><?= date('d.m.Y', strtotime($m['datum'])) ?></td>
        <td style="font-size:13px;font-weight:600;"><?= h($m['radnik_ime']) ?></td>
        <td style="font-size:12px;">
            <?php if ($m['gradiliste_naziv']): ?>
            <span style="color:var(--blue);">🏗️ <?= h($m['gradiliste_naziv']) ?></span>
            <?php endif; ?>
            <?php if ($m['zadatak_opis']): ?>
            <div style="color:var(--muted);font-size:11px;margin-top:2px;"><?= h(mb_substr($m['zadatak_opis'], 0, 60)) ?><?= mb_strlen($m['zadatak_opis']) > 60 ? '...' : '' ?></div>
            <?php endif; ?>
        </td>
        <td style="font-size:13px;"><?= h($m['naziv']) ?></td>
        <td style="text-align:center;font-weight:700;font-size:13px;"><?= number_format($m['kolicina'], 2, ',', '.') ?></td>
        <td style="text-align:center;font-size:12px;color:var(--muted);"><?= h($m['jm']) ?></td>
        <?php if ($is_admin): ?>
        <td style="text-align:right;white-space:nowrap;">
            <button class="btn-sm" onclick="openIzmeniMat(<?= $m['id'] ?>, '<?= h(addslashes($m['datum'])) ?>', '<?= h(addslashes($m['naziv'])) ?>', <?= (float)$m['kolicina'] ?>, '<?= h(addslashes($m['jm'])) ?>')" title="Izmeni">✏️</button>
            <button class="btn-sm del" onclick="obrisiMat(<?= $m['id'] ?>)" title="Obriši" style="margin-left:4px;">🗑</button>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</div>
<?php endif; ?>

<!-- MODAL: Izmeni radni sat -->
<div id="modal-vreme" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:440px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('modal-vreme').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:16px;">✏️ Izmeni radni sat</h3>
        <input type="hidden" id="ev-vreme-id">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="tim-form-group"><label>Datum</label><input type="date" id="ev-v-datum" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;"></div>
            <div class="tim-form-group"><label>Sati</label><input type="number" id="ev-v-sati" step="0.5" min="0" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;"></div>
            <div class="tim-form-group"><label>Vreme od</label><input type="time" id="ev-v-od" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;"></div>
            <div class="tim-form-group"><label>Vreme do</label><input type="time" id="ev-v-do" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;"></div>
        </div>
        <div class="tim-form-group"><label>Opis rada</label><input type="text" id="ev-v-napomena" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        <div style="background:#fef3c7;border-radius:6px;padding:8px 12px;font-size:12px;color:#92400e;margin-bottom:12px;">⚠️ Izmena će biti zabeležena u log sa Vašim imenom i vremenom izmene.</div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('modal-vreme').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="sacuvajIzmenuVreme()" class="btn-primary">Sačuvaj izmenu</button>
        </div>
    </div>
</div>

<!-- MODAL: Izmeni materijal -->
<div id="modal-mat" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:440px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('modal-mat').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:16px;">✏️ Izmeni materijal</h3>
        <input type="hidden" id="ev-mat-id">
        <div class="tim-form-group"><label>Datum</label><input type="date" id="ev-m-datum" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;"></div>
        <div class="tim-form-group"><label>Naziv artikla</label><input type="text" id="ev-m-naziv" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;">
            <div class="tim-form-group"><label>Količina</label><input type="number" id="ev-m-kolicina" step="0.01" min="0" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;"></div>
            <div class="tim-form-group"><label>JM</label><input type="text" id="ev-m-jm" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        </div>
        <div style="background:#fef3c7;border-radius:6px;padding:8px 12px;font-size:12px;color:#92400e;margin-bottom:12px;">⚠️ Izmena će biti zabeležena u log sa Vašim imenom i vremenom izmene.</div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('modal-mat').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="sacuvajIzmenuMat()" class="btn-primary">Sačuvaj izmenu</button>
        </div>
    </div>
</div>

<script>
function openIzmeniVreme(id, datum, od, do_, sati, napomena) {
    document.getElementById('ev-vreme-id').value   = id;
    document.getElementById('ev-v-datum').value     = datum;
    document.getElementById('ev-v-od').value        = od;
    document.getElementById('ev-v-do').value        = do_;
    document.getElementById('ev-v-sati').value      = sati;
    document.getElementById('ev-v-napomena').value  = napomena;
    document.getElementById('modal-vreme').style.display = 'flex';
}

function sacuvajIzmenuVreme() {
    var id = document.getElementById('ev-vreme-id').value;
    var fd = new FormData();
    fd.append('_action', 'evidencija_izmeni_vreme');
    fd.append('id', id);
    fd.append('datum',       document.getElementById('ev-v-datum').value);
    fd.append('vreme_od',    document.getElementById('ev-v-od').value);
    fd.append('vreme_do',    document.getElementById('ev-v-do').value);
    fd.append('ukupno_sati', document.getElementById('ev-v-sati').value);
    fd.append('napomena',    document.getElementById('ev-v-napomena').value);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.ok) { document.getElementById('modal-vreme').style.display = 'none'; location.reload(); }
        else alert(d.err || 'Greška.');
    });
}

function obrisiVreme(id) {
    if (!confirm('Obrisati ovaj unos radnog vremena? Akcija će biti zabeležena u log.')) return;
    var fd = new FormData();
    fd.append('_action', 'evidencija_obrisi_vreme');
    fd.append('id', id);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); else alert(d.err || 'Greška.'); });
}

function openIzmeniMat(id, datum, naziv, kolicina, jm) {
    document.getElementById('ev-mat-id').value      = id;
    document.getElementById('ev-m-datum').value     = datum;
    document.getElementById('ev-m-naziv').value     = naziv;
    document.getElementById('ev-m-kolicina').value  = kolicina;
    document.getElementById('ev-m-jm').value        = jm;
    document.getElementById('modal-mat').style.display = 'flex';
}

function sacuvajIzmenuMat() {
    var id = document.getElementById('ev-mat-id').value;
    var fd = new FormData();
    fd.append('_action', 'evidencija_izmeni_materijal');
    fd.append('id', id);
    fd.append('datum',    document.getElementById('ev-m-datum').value);
    fd.append('naziv',    document.getElementById('ev-m-naziv').value);
    fd.append('kolicina', document.getElementById('ev-m-kolicina').value);
    fd.append('jm',       document.getElementById('ev-m-jm').value);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.ok) { document.getElementById('modal-mat').style.display = 'none'; location.reload(); }
        else alert(d.err || 'Greška.');
    });
}

function obrisiMat(id) {
    if (!confirm('Obrisati ovaj unos materijala? Akcija će biti zabeležena u log.')) return;
    var fd = new FormData();
    fd.append('_action', 'evidencija_obrisi_materijal');
    fd.append('id', id);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); else alert(d.err || 'Greška.'); });
}
</script>
