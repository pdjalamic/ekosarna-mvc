<?php
$tabs = [
    'stanje'  => '📦 Stanje zaliha',
    'primke'  => '📋 Ulaz robe',
    'nova'    => '➕ Novi ulaz',
];
if (!empty($jeAdmin)) $tabs['log'] = '🕘 Istorija';
$aktivan_tab = $_GET['tab'] ?? 'stanje';
if ($aktivan_tab === 'log' && empty($jeAdmin)) $aktivan_tab = 'stanje'; // log vide samo Direktor/AT/AF
?>

<style>
/* ── Mobilni: magacin tabele kao kartice (bez horizontalnog skrola) ── */
@media (max-width:720px) {
  .mag-card { display:block; width:100%; min-width:0 !important; }
  .mag-card > thead { display:none; }
  .mag-card > tbody { display:block; }
  .mag-card > tbody > tr.rs-red {
    display:block; background:#fff;
    border:1.5px solid var(--light2); border-radius:12px;
    margin-bottom:10px; padding:10px 14px;
  }
  .mag-card > tbody > tr > td {
    display:block; width:auto !important; text-align:left !important;
    padding:4px 0 !important; border:none !important; white-space:normal !important;
  }
  .mag-card > tbody > tr > td::before {
    display:block; font-weight:700; color:var(--muted);
    font-size:10px; text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px;
  }
  .mag-card > tbody > tr > td:first-child { display:none; } /* redni broj */

  /* STANJE: artikal kao naslov, brojevi u jednom redu */
  .mag-stanje > tbody > tr.rs-red { display:flex; flex-wrap:wrap; align-items:flex-start; }
  .mag-stanje > tbody > tr > td { flex:0 0 100%; }
  .mag-stanje > tbody > tr > td:nth-child(2) { font-weight:700; font-size:15px; }
  .mag-stanje > tbody > tr > td:nth-child(2)::before { display:none; }
  .mag-stanje > tbody > tr > td:nth-child(3),
  .mag-stanje > tbody > tr > td:nth-child(4),
  .mag-stanje > tbody > tr > td:nth-child(5),
  .mag-stanje > tbody > tr > td:nth-child(6) { flex:1 1 25%; text-align:center !important; }
  .mag-stanje > tbody > tr > td:nth-child(3)::before { content:"JM"; }
  .mag-stanje > tbody > tr > td:nth-child(4)::before { content:"Primljeno"; }
  .mag-stanje > tbody > tr > td:nth-child(5)::before { content:"Izdato"; }
  .mag-stanje > tbody > tr > td:nth-child(6)::before { content:"Stanje"; }
  .mag-stanje > tbody > tr > td:nth-child(7)::before { content:"Lokacija"; }
  .mag-stanje > tbody > tr > td:nth-child(8)::before { display:none; }

  /* PRIMKE (ulaz robe): dobavljač kao naslov */
  .mag-primke > tbody > tr > td:nth-child(3) { font-weight:700; font-size:15px; }
  .mag-primke > tbody > tr > td:nth-child(3)::before { display:none; }
  .mag-primke > tbody > tr > td:nth-child(2)::before { content:"Datum"; }
  .mag-primke > tbody > tr > td:nth-child(4)::before { content:"Br. dokumenta"; }
  .mag-primke > tbody > tr > td:nth-child(5)::before { content:"Tip"; }
  .mag-primke > tbody > tr > td:nth-child(6)::before { content:"Stavki"; }
  .mag-primke > tbody > tr > td:nth-child(7)::before { display:none; }
  /* Detalj red (proširenje primke) — bez labela, nested tabela puna širina */
  .mag-primke > tbody > tr[id^="primka-detalj-"] { display:block; } /* inline display:none ga drži skrivenim dok se ne otvori */
  /* display:block na ćeliji — inače je `td:first-child{display:none}` (redni broj) sakrije, jer detalj ima samo jedan colspan td */
  .mag-primke > tbody > tr[id^="primka-detalj-"] > td { display:block !important; padding:0 !important; }
  .mag-primke > tbody > tr[id^="primka-detalj-"] > td::before { display:none; }
  .mag-primke > tbody > tr[id^="primka-detalj-"] table { min-width:0 !important; width:100%; font-size:11px; }
}

/* "Namenjeno za" — vizuelno izdvojeno (toplo narandžasto), da se razlikuje od lokacije */
.mag-namenjeno { background:#fff7ed !important; border:1.5px solid #fb923c !important; color:#7c2d12; }
.mag-namenjeno-wrap { background:#fff7ed; border:1.5px dashed #fb923c; border-radius:8px; padding:8px 10px; }
.mag-namenjeno-wrap label { color:#c2410c !important; }
.st-namenjeno-chip { display:inline-block; background:#fff7ed; border:1px solid #fdba74; color:#c2410c; border-radius:5px; font-size:11px; padding:1px 7px; white-space:nowrap; }
</style>

<div class="topbar-admin">
    <div class="page-title">🏭 Magacin</div>
</div>

<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid var(--light2);padding-bottom:0;">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?page=magacin&tab=<?= $key ?>"
       style="padding:9px 18px;font-size:13px;font-weight:600;text-decoration:none;border-radius:8px 8px 0 0;
              <?= $aktivan_tab === $key
                ? 'background:#fff;color:var(--blue);border:2px solid var(--light2);border-bottom:2px solid #fff;margin-bottom:-2px;'
                : 'color:var(--muted);' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($aktivan_tab === 'stanje'): ?>
<style>
.lok-grupa { background:#fff; border:1.5px solid var(--light2); border-radius:12px; margin-bottom:10px; overflow:hidden; }
.lok-head { display:flex; align-items:center; gap:10px; padding:13px 16px; cursor:pointer; user-select:none; }
.lok-head:hover { background:var(--light); }
.lok-chevron { font-size:11px; color:var(--muted); transition:transform .15s; }
.lok-body { border-top:1px solid var(--light2); }
.st-row { display:flex; align-items:center; gap:10px; padding:10px 16px; }
.st-row + .st-row { border-top:1px solid var(--light2); }
.st-row .st-ime { flex:1; min-width:0; font-size:13px; font-weight:600; word-break:break-word; }
.st-row .st-jm  { width:48px; text-align:center; font-size:12px; color:var(--muted); }
.st-row .st-kol { width:90px; text-align:right; font-weight:700; font-size:14px; }
.st-row .st-akc { white-space:nowrap; }
</style>
<div style="margin-bottom:12px;">
    <input type="text" id="srch-stanje" placeholder="Pretraži artikal..."
        oninput="filtrirajStanje()"
        style="border:1.5px solid var(--light2);border-radius:8px;padding:8px 12px;font-size:13px;outline:none;background:#fff;width:280px;max-width:100%;box-sizing:border-box;">
</div>

<?php if (empty($stanjePoLokaciji)): ?>
    <div style="padding:40px;text-align:center;color:var(--muted);">Nema artikala na stanju.</div>
<?php else: ?>
<div id="stanje-lokacije">
<?php $li = 0; foreach ($stanjePoLokaciji as $lok => $artikli): $li++; $jeMagacin = ($lok === 'Magacin'); ?>
    <div class="lok-grupa">
        <div class="lok-head" onclick="toggleLok(<?= $li ?>)">
            <span class="lok-chevron" id="lok-chev-<?= $li ?>">▶</span>
            <span style="font-weight:800;font-size:14px;color:#1f2a44;"><?= $jeMagacin ? '🏭' : '📍' ?> <?= h($lok) ?></span>
            <span style="margin-left:auto;color:var(--muted);font-size:12px;"><?= count($artikli) ?> art.</span>
            <button class="btn-sm" title="Premesti sve sa ove lokacije"
                data-lok="<?= htmlspecialchars($lok, ENT_QUOTES) ?>"
                onclick="event.stopPropagation();openPremestiLok(this)">⇄</button>
        </div>
        <div class="lok-body" id="lok-body-<?= $li ?>" style="display:none;">
            <?php foreach ($artikli as $s): ?>
            <div class="st-row stanje-red"
                 data-naziv="<?= htmlspecialchars($s['naziv'], ENT_QUOTES) ?>"
                 data-naziv-lower="<?= htmlspecialchars(mb_strtolower($s['naziv']), ENT_QUOTES) ?>"
                 data-jm="<?= htmlspecialchars($s['jm'], ENT_QUOTES) ?>"
                 data-lokacija="<?= htmlspecialchars($lok, ENT_QUOTES) ?>"
                 data-gradiliste="<?= (int)$s['gradiliste_id'] ?>"
                 data-namenjeno="<?= (int)($s['namenjeno_gradiliste_id'] ?? 0) ?>"
                 data-namenjeno-naziv="<?= htmlspecialchars($s['namenjeno_naziv'] ?? '', ENT_QUOTES) ?>"
                 data-katalog="<?= (int)$s['katalog_id'] ?>"
                 data-stanje="<?= $s['stanje'] ?>">
                <span class="st-ime"><?= h($s['naziv']) ?><?php if (!empty($s['namenjeno_naziv'])): ?> <span class="st-namenjeno-chip">🎯 <?= h($s['namenjeno_naziv']) ?></span><?php endif; ?></span>
                <span class="st-jm"><?= h($s['jm']) ?></span>
                <span class="st-kol" style="color:<?= $s['stanje'] >= 0 ? '#059669' : '#dc2626' ?>;"><?= number_format($s['stanje'], 2, ',', '.') ?></span>
                <span class="st-akc">
                    <button class="btn-sm" onclick="openPrenos(this)" title="Prenos na drugu lokaciju">↔</button>
                    <button class="btn-sm" onclick="openIzmena(this)" title="Izmena stavke">✎</button>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php elseif ($aktivan_tab === 'primke'): ?>
<div class="rs-tabela-wrap">
<?php if (empty($primke)): ?>
    <div style="padding:40px;text-align:center;color:var(--muted);">Nema ulaza robe. Dodajte prvi ulaz u tabu "Novi ulaz".</div>
<?php else: ?>
    <table class="rs-tabela mag-card mag-primke">
        <thead>
            <tr>
                <th style="width:36px;text-align:center;">#</th>
                <th style="width:110px;">Datum</th>
                <th>Dobavljač</th>
                <th style="width:140px;">Broj dokumenta</th>
                <th style="width:100px;text-align:center;">Tip</th>
                <th style="width:70px;text-align:center;">Stavki</th>
                <th style="width:110px;text-align:right;"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($primke as $i => $pr): ?>
        <tr class="rs-red" style="cursor:pointer;" onclick="togglePrimka(<?= $i ?>)">
            <td style="text-align:center;font-size:11px;color:var(--muted);"><?= $i + 1 ?></td>
            <td style="font-size:12px;color:var(--muted);"><?= $pr['datum'] ? date('d.m.Y', strtotime($pr['datum'])) : '—' ?></td>
            <td style="font-weight:600;font-size:13px;"><?= h($pr['firma_naziv'] ?: '—') ?></td>
            <td style="font-size:12px;"><?= h($pr['broj_dokumenta'] ?: '—') ?></td>
            <td style="text-align:center;">
                <span style="background:<?= $pr['tip']==='racun' ? '#dbeafe' : '#dcfce7' ?>;color:<?= $pr['tip']==='racun' ? '#1e40af' : '#166534' ?>;border-radius:99px;font-size:11px;padding:2px 10px;">
                    <?= $pr['tip'] === 'racun' ? 'Račun' : 'Otpremnica' ?>
                </span>
            </td>
            <td style="text-align:center;font-size:12px;color:var(--muted);"><?= count($pr['stavke']) ?></td>
            <td style="text-align:right;white-space:nowrap;">
                <?php if ($pr['pdf_putanja']):
                    $docUrl = BASE_URL . '/uploads/magacin/' . $pr['pdf_putanja'];
                    $docExt = strtolower(pathinfo($pr['pdf_putanja'], PATHINFO_EXTENSION));
                    $docTip = in_array($docExt, ['jpg','jpeg','png','gif','webp','bmp']) ? 'img' : 'pdf';
                ?>
                <button type="button" class="btn-sm" title="Prikaži dokument"
                   onclick="event.stopPropagation();openModal('<?= h($docUrl) ?>','<?= $docTip ?>')"
                   style="margin-right:6px;">📄</button>
                <?php endif; ?>
                <button class="btn-sm del" title="Obriši"
                    onclick="event.stopPropagation();obrisiPrimku(<?= $pr['id'] ?>)"
                    style="margin-right:6px;">🗑</button>
                <span id="arrow-pr-<?= $i ?>" style="color:var(--muted);font-size:12px;">▼</span>
            </td>
        </tr>
        <tr id="primka-detalj-<?= $i ?>" style="display:none;">
            <td colspan="7" style="padding:0;">
                <div style="background:var(--light);border-top:1px solid var(--light2);padding:12px 16px;">
                    <table style="width:100%;font-size:12px;border-collapse:collapse;">
                        <thead>
                            <tr style="color:var(--muted);text-transform:uppercase;font-size:10px;letter-spacing:.05em;">
                                <th style="text-align:center;padding:4px 8px;width:32px;">#</th>
                                <th style="text-align:left;padding:4px 8px;">Artikal</th>
                                <th style="text-align:center;padding:4px 8px;width:90px;">Količina</th>
                                <th style="text-align:center;padding:4px 8px;width:50px;">JM</th>
                                <th style="text-align:left;padding:4px 8px;width:100px;">Lokacija</th>
                                <th style="text-align:left;padding:4px 8px;width:100px;color:#c2410c;">Namenjeno za</th>
                                <th style="padding:4px 8px;width:36px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pr['stavke'] as $si => $s): $lokCur = $s['lokacija_cur'] ?? $s['lokacija']; ?>
                        <tr style="border-top:1px solid var(--light2);">
                            <td style="text-align:center;padding:6px 8px;color:var(--muted);"><?= $si + 1 ?></td>
                            <td style="padding:6px 8px;"><?= h($s['naziv']) ?></td>
                            <td style="text-align:center;padding:6px 8px;font-weight:600;"><?= number_format($s['kolicina'], 2, ',', '.') ?></td>
                            <td style="text-align:center;padding:6px 8px;color:var(--muted);"><?= h($s['jm']) ?></td>
                            <td style="padding:6px 8px;"><span style="background:#fff;border:1px solid var(--light2);border-radius:4px;padding:1px 7px;"><?= h($lokCur) ?></span></td>
                            <td style="padding:6px 8px;"><?php if (!empty($s['namenjeno_naziv'])): ?><span class="st-namenjeno-chip">🎯 <?= h($s['namenjeno_naziv']) ?></span><?php else: ?><span style="color:var(--muted);">—</span><?php endif; ?></td>
                            <td style="text-align:right;padding:6px 8px;">
                                <button class="btn-sm" title="Izmeni stavku"
                                    data-id="<?= (int)$s['id'] ?>"
                                    data-naziv="<?= htmlspecialchars($s['naziv'], ENT_QUOTES) ?>"
                                    data-kolicina="<?= $s['kolicina'] ?>"
                                    data-jm="<?= htmlspecialchars($s['jm'], ENT_QUOTES) ?>"
                                    data-lokacija="<?= htmlspecialchars($lokCur, ENT_QUOTES) ?>"
                                    data-gradiliste="<?= (int)($s['gradiliste_cur'] ?? 0) ?>"
                                    data-namenjeno="<?= (int)($s['namenjeno_cur'] ?? 0) ?>"
                                    onclick="event.stopPropagation();openUrediStavku(this)">✎</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

<?php elseif ($aktivan_tab === 'nova'): ?>
<div style="max-width:700px;">
    <div id="upload-zona" style="border:2.5px dashed var(--light2);border-radius:12px;padding:40px;text-align:center;cursor:pointer;transition:border-color .15s;background:#fff;"
        onclick="document.getElementById('mag-file-input').click()"
        ondragover="event.preventDefault();this.style.borderColor='var(--blue)'"
        ondragleave="this.style.borderColor='var(--light2)'"
        ondrop="handleDrop(event)">
        <div style="font-size:40px;margin-bottom:12px;">📄</div>
        <div style="font-weight:700;font-size:15px;color:var(--blue);margin-bottom:6px;">Kliknite ili prevucite fajl ovde</div>
        <div style="font-size:12px;color:var(--muted);">PDF, JPG ili PNG · Max 10MB</div>
        <input type="file" id="mag-file-input" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" onchange="handleFileSelect(this.files[0])">
    </div>

    <div id="mag-loading" style="display:none;text-align:center;padding:30px;">
        <div style="font-size:14px;color:var(--blue);font-weight:600;">🤖 AI čita dokument...</div>
        <div style="font-size:12px;color:var(--muted);margin-top:6px;">Ovo može potrajati 15-20 sekundi</div>
    </div>

    <div id="mag-preview" style="display:none;margin-top:20px;">
        <div style="background:#fff;border:1.5px solid var(--light2);border-radius:12px;padding:20px;">
            <div style="font-weight:700;font-size:15px;color:var(--blue);margin-bottom:16px;">✅ Proveri i potvrdi</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div class="tim-form-group" style="margin:0;">
                    <label>Dobavljač</label>
                    <input type="text" id="prev-firma" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;">
                </div>
                <div class="tim-form-group" style="margin:0;">
                    <label>Broj dokumenta</label>
                    <input type="text" id="prev-broj" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;">
                </div>
                <div class="tim-form-group" style="margin:0;">
                    <label>Datum</label>
                    <input type="date" id="prev-datum" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;">
                </div>
                <div class="tim-form-group" style="margin:0;">
                    <label>Tip</label>
                    <select id="prev-tip" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;">
                        <option value="otpremnica">Otpremnica</option>
                        <option value="racun">Račun</option>
                        <option value="ostalo">Ostalo</option>
                    </select>
                </div>
            </div>
            <div class="tim-form-group">
                <label>Podrazumevana lokacija</label>
                <select id="prev-lokacija" onchange="podrazumevanaChange()"
                    style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:280px;box-sizing:border-box;">
                    <option value="magacin">Magacin</option>
                    <?php foreach ($gradilista as $g): ?>
                    <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:var(--muted);margin-top:4px;">Promena ovde postavlja lokaciju na svim stavkama ispod.</div>
            </div>
            <div class="tim-form-group mag-namenjeno-wrap" style="margin-bottom:16px;">
                <label>🎯 Podrazumevano: Namenjeno za (gradilište)</label>
                <select id="prev-namenjeno" onchange="podrazumevanaNamenjenoChange()" class="mag-namenjeno"
                    style="border-radius:7px;padding:7px 10px;font-size:13px;outline:none;width:280px;max-width:100%;box-sizing:border-box;">
                    <option value="">— Bez gradilišta —</option>
                    <?php foreach ($gradilista as $g): ?>
                    <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:#c2410c;margin-top:4px;">Za koje gradilište je roba namenjena (sa otpremnice). Postavlja vrednost na svim stavkama; možeš promeniti po stavci.</div>
            </div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:8px;">
                Stavke (<span id="prev-stavke-count">0</span>)
            </div>
            <div style="display:grid;grid-template-columns:24px 1fr 70px 48px 120px 120px 30px;gap:6px;padding:4px 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);">
                <span>#</span><span>Naziv</span><span style="text-align:center;">Količina</span><span style="text-align:center;">JM</span><span>Lokacija</span><span style="color:#c2410c;">Namenjeno za</span><span></span>
            </div>
            <div id="prev-stavke-lista" style="display:flex;flex-direction:column;gap:5px;margin-bottom:16px;max-height:360px;overflow-y:auto;"></div>
            <div id="mag-err" style="display:none;color:#dc2626;font-size:12px;background:#fef2f2;border-radius:6px;padding:8px 12px;margin-bottom:10px;"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button onclick="resetMagacin()" class="mail-cancel-btn">Otkaži</button>
                <button onclick="sacuvajKorak1()" class="btn-primary" id="mag-btn-sacuvaj" style="padding:10px 24px;">💾 Sačuvaj</button>
            </div>
        </div>
    </div>
</div>

<?php elseif ($aktivan_tab === 'log' && !empty($jeAdmin)): ?>
<?php
    $tipLabela  = ['primka'=>'Ulaz robe','stavka'=>'Stavka ulaza','stanje'=>'Stanje','prenos'=>'Prenos','lokacija'=>'Lokacija'];
    $akcijaBoja = ['kreiranje'=>['#dcfce7','#166534'],'izmena'=>['#fef3c7','#92400e'],'brisanje'=>['#fee2e2','#b91c1c'],'prenos'=>['#dbeafe','#1e40af'],'premesti'=>['#ede9fe','#6b21a8']];
    $fmtSnap = function ($json) {
        if ($json === null || $json === '') return '<span style="color:var(--muted);">—</span>';
        $d = json_decode($json, true);
        if (!is_array($d)) return h((string)$json);
        $parts = [];
        foreach ($d as $k => $v) {
            if ($v === null || $v === '') continue;
            if (is_bool($v)) $v = $v ? 'da' : 'ne';
            $parts[] = '<span style="color:var(--muted);">' . h($k) . ':</span> ' . h((string)$v);
        }
        return $parts ? implode('<br>', $parts) : '<span style="color:var(--muted);">—</span>';
    };
?>
<div style="margin-bottom:10px;font-size:12px;color:var(--muted);">Poslednjih <?= count($log) ?> izmena u magacinu (najnovije gore). Vide samo Direktor, AT i AF.</div>
<?php if (empty($log)): ?>
    <div style="padding:40px;text-align:center;color:var(--muted);">Log je prazan.</div>
<?php else: ?>
<div>
<?php foreach ($log as $l): $boja = $akcijaBoja[$l['akcija']] ?? ['#f1f5f9','#475569']; ?>
    <div style="background:#fff;border:1px solid var(--light2);border-radius:10px;padding:12px 14px;margin-bottom:10px;">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px;">
            <span style="background:<?= $boja[0] ?>;color:<?= $boja[1] ?>;font-size:11px;font-weight:700;padding:2px 9px;border-radius:5px;text-transform:uppercase;"><?= h($l['akcija']) ?></span>
            <span style="font-size:13px;font-weight:600;"><?= h($tipLabela[$l['tip']] ?? $l['tip']) ?></span>
            <span style="margin-left:auto;font-size:11px;color:var(--muted);"><?= h($l['korisnik_ime'] ?? '—') ?> · <?= h(date('d.m.Y. H:i', strtotime($l['created_at']))) ?></span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:12px;line-height:1.5;">
            <div><div style="font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:2px;">Pre</div><?= $fmtSnap($l['staro_stanje']) ?></div>
            <div><div style="font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#c2410c;margin-bottom:2px;">Posle</div><?= $fmtSnap($l['novo_stanje']) ?></div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- MODAL: Katalog provera ──────────────────────────────── -->
<div id="katalog-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9999;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:600px;box-shadow:0 24px 80px #000a;margin:auto;">
        <h3 style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:4px;">Provera kataloga</h3>
        <p style="font-size:12px;color:var(--muted);margin:0 0 16px;">Pronašli smo slične artikle. Odluči za svaku stavku.</p>
        <div id="katalog-stavke-lista" style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;"></div>
        <div id="katalog-err" style="display:none;color:#dc2626;font-size:12px;margin-bottom:8px;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:14px;border-top:1px solid var(--light2);">
            <button onclick="document.getElementById('katalog-modal').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="doSacuvajPrimku()" class="btn-primary" id="katalog-btn-sacuvaj">✅ Potvrdi i sačuvaj</button>
        </div>
    </div>
</div>

<!-- MODAL: Firma provera ─────────────────────────────────── -->
<div id="firma-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:500px;box-shadow:0 24px 80px #000a;">
        <h3 style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:6px;">🏢 Pronađene slične firme</h3>
        <p style="font-size:13px;color:var(--muted);margin-bottom:16px;">Firma "<strong id="firma-naziv-novi"></strong>" nije pronađena. Šta da radimo?</p>
        <div id="firma-slicne-lista" style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;"></div>
        <div style="border-top:1px solid var(--light2);padding-top:14px;display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
            <button onclick="firmaIzbor('nova')" class="btn-primary">➕ Dodaj kao novu firmu</button>
            <button onclick="firmaIzbor('none')" class="mail-cancel-btn">Bez firme</button>
        </div>
    </div>
</div>

<!-- MODAL: Prenos ─────────────────────────────────────────── -->
<div id="prenos-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:440px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('prenos-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 id="prenos-naslov" style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:16px;padding-right:32px;">↔ Prenos robe</h3>
        <input type="hidden" id="prenos-naziv"><input type="hidden" id="prenos-jm">
        <input type="hidden" id="prenos-katalog"><input type="hidden" id="prenos-lok-iz"><input type="hidden" id="prenos-gid-iz"><input type="hidden" id="prenos-namenjeno-iz">
        <div class="tim-form-group"><label>Sa lokacije</label>
            <input type="text" id="prenos-lok-iz-disp" disabled style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;background:#f1f5f9;color:#475569;width:100%;box-sizing:border-box;"></div>
        <div class="tim-form-group"><label>Količina <span id="prenos-max" style="color:var(--muted);font-size:11px;"></span></label>
            <input type="number" id="prenos-kolicina" min="0.001" step="0.001" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        <div class="tim-form-group"><label>Na lokaciju</label>
            <select id="prenos-lok-do" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;">
                <option value="magacin">Magacin</option>
                <?php foreach ($gradilista as $g): ?>
                <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="tim-form-group mag-namenjeno-wrap"><label>🎯 Namenjeno za (gradilište)</label>
            <select id="prenos-namenjeno" class="mag-namenjeno" style="border-radius:7px;padding:7px 10px;font-size:13px;outline:none;width:100%;box-sizing:border-box;">
                <option value="">— Bez gradilišta —</option>
                <?php foreach ($gradilista as $g): ?>
                <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="tim-form-group"><label>Datum</label><input type="date" id="prenos-datum" value="<?= date('Y-m-d') ?>" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
            <div class="tim-form-group"><label>Napomena</label><input type="text" id="prenos-napomena" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        </div>
        <div id="prenos-err" style="display:none;color:#dc2626;font-size:12px;margin-bottom:8px;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('prenos-modal').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="sacuvajPrenos()" class="btn-primary">Potvrdi prenos</button>
        </div>
    </div>
</div>

<!-- MODAL: Premesti celu lokaciju ─────────────────────────── -->
<div id="premesti-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:440px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('premesti-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:4px;padding-right:32px;">⇄ Premesti celu lokaciju</h3>
        <p style="font-size:12px;color:var(--muted);margin:0 0 16px;">Svi artikli sa lokacije <strong id="premesti-iz-disp"></strong> prelaze na izabranu lokaciju (vezivanje za pravo gradilište).</p>
        <input type="hidden" id="premesti-lok-iz">
        <div class="tim-form-group"><label>Premesti na</label>
            <select id="premesti-lok-do" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;">
                <option value="magacin">Magacin</option>
                <?php foreach ($gradilista as $g): ?>
                <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div id="premesti-err" style="display:none;color:#dc2626;font-size:12px;margin-bottom:8px;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('premesti-modal').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="sacuvajPremestiLok()" class="btn-primary">Premesti sve</button>
        </div>
    </div>
</div>

<!-- MODAL: Izmena stavke ──────────────────────────────────── -->
<div id="izmena-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:440px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('izmena-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:4px;padding-right:32px;">✎ Izmena stavke</h3>
        <p style="font-size:11px;color:var(--muted);margin:0 0 16px;">Promenu lokacije radi „Prenos". Svaka izmena se beleži u log.</p>
        <input type="hidden" id="izmena-stari-naziv"><input type="hidden" id="izmena-stari-jm">
        <input type="hidden" id="izmena-lokacija"><input type="hidden" id="izmena-gid"><input type="hidden" id="izmena-katalog"><input type="hidden" id="izmena-namenjeno-staro">
        <div class="tim-form-group"><label>Lokacija</label>
            <input type="text" id="izmena-lok-disp" disabled style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;background:#f1f5f9;color:#475569;width:100%;box-sizing:border-box;"></div>
        <div class="tim-form-group"><label>Naziv</label>
            <input type="text" id="izmena-naziv" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="tim-form-group"><label>Količina</label><input type="number" id="izmena-kolicina" step="0.001" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
            <div class="tim-form-group"><label>JM</label><input type="text" id="izmena-jm" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        </div>
        <div class="tim-form-group mag-namenjeno-wrap"><label>🎯 Namenjeno za (gradilište)</label>
            <select id="izmena-namenjeno" class="mag-namenjeno" style="border-radius:7px;padding:7px 10px;font-size:13px;outline:none;width:100%;box-sizing:border-box;">
                <option value="">— Bez gradilišta —</option>
                <?php foreach ($gradilista as $g): ?>
                <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div id="izmena-err" style="display:none;color:#dc2626;font-size:12px;margin-bottom:8px;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('izmena-modal').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="sacuvajIzmena()" class="btn-primary">Sačuvaj</button>
        </div>
    </div>
</div>

<!-- MODAL: Izmena stavke ulaza ────────────────────────────── -->
<div id="uredi-stavku-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:440px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('uredi-stavku-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:4px;padding-right:32px;">✎ Izmena stavke ulaza</h3>
        <p style="font-size:11px;color:var(--muted);margin:0 0 16px;">Menja stavku prijema i usklađuje stanje. Svaka izmena se beleži u log.</p>
        <input type="hidden" id="us-id">
        <div class="tim-form-group"><label>Naziv</label>
            <input type="text" id="us-naziv" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="tim-form-group"><label>Količina</label><input type="number" id="us-kolicina" min="0.001" step="0.001" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
            <div class="tim-form-group"><label>JM</label><input type="text" id="us-jm" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        </div>
        <div class="tim-form-group"><label>Lokacija</label>
            <select id="us-lokacija" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;">
                <option value="magacin">Magacin</option>
                <?php foreach ($gradilista as $g): ?>
                <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="tim-form-group mag-namenjeno-wrap"><label>🎯 Namenjeno za (gradilište)</label>
            <select id="us-namenjeno" class="mag-namenjeno" style="border-radius:7px;padding:7px 10px;font-size:13px;outline:none;width:100%;box-sizing:border-box;">
                <option value="">— Bez gradilišta —</option>
                <?php foreach ($gradilista as $g): ?>
                <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div id="us-err" style="display:none;color:#dc2626;font-size:12px;margin-bottom:8px;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('uredi-stavku-modal').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="sacuvajUrediStavku()" class="btn-primary">Sačuvaj</button>
        </div>
    </div>
</div>

<script>
var _magFajl      = null;
var _magParsed    = null;
var _firmaIzbor   = 'nova';
var _katalogOdluke = []; // odluke korisnika po stavkama
var _stavkeUlaz   = []; // stavke (sa lokacijom) prikupljene pre provere kataloga
var MAG_GRADILISTA = <?= json_encode(array_map(fn($g) => ['id' => (int)$g['id'], 'naziv' => $g['naziv']], $gradilista), JSON_UNESCAPED_UNICODE) ?>;

// HTML <option> lista lokacija (Magacin + gradilišta), sa obeleženim izborom
function lokOptionsHtml(sel) {
    var h = '<option value="magacin"' + (String(sel) === 'magacin' ? ' selected' : '') + '>Magacin</option>';
    MAG_GRADILISTA.forEach(function (g) {
        h += '<option value="' + g.id + '"' + (String(sel) === String(g.id) ? ' selected' : '') + '>' + esc(g.naziv) + '</option>';
    });
    return h;
}

// Iz <select> lokacije izvuci {lokacija (tekst), gradiliste_id}
function lokFromSelect(selEl) {
    var v = selEl.value;
    if (v === 'magacin') return { lokacija: 'Magacin', gradiliste_id: 0 };
    var opt = selEl.options[selEl.selectedIndex];
    return { lokacija: opt ? opt.text : 'Magacin', gradiliste_id: parseInt(v) || 0 };
}

// "Namenjeno za" opcije (gradilišta); prazna vrednost = bez namene
function namenjenoOptionsHtml(sel) {
    var h = '<option value=""' + (!sel || String(sel) === '0' ? ' selected' : '') + '>— Bez gradilišta —</option>';
    MAG_GRADILISTA.forEach(function (g) {
        h += '<option value="' + g.id + '"' + (String(sel) === String(g.id) ? ' selected' : '') + '>' + esc(g.naziv) + '</option>';
    });
    return h;
}

// Promena podrazumevanog "Namenjeno za" → primeni na sve stavke
function podrazumevanaNamenjenoChange() {
    var v = document.getElementById('prev-namenjeno').value;
    document.querySelectorAll('#prev-stavke-lista .st-namenjeno').forEach(function (s) { s.value = v; });
}

// Promena podrazumevane lokacije → primeni na sve stavke
function podrazumevanaChange() {
    var v = document.getElementById('prev-lokacija').value;
    document.querySelectorAll('#prev-stavke-lista .st-lokacija').forEach(function (s) { s.value = v; });
}

// ── Upload ───────────────────────────────────────────────────
function handleFileSelect(file) { if (file) { _magFajl = file; parseDocument(file); } }
function handleDrop(e) {
    e.preventDefault();
    document.getElementById('upload-zona').style.borderColor = 'var(--light2)';
    var file = e.dataTransfer.files[0];
    if (file) handleFileSelect(file);
}

function parseDocument(file) {
    document.getElementById('upload-zona').style.display = 'none';
    document.getElementById('mag-loading').style.display = 'block';
    document.getElementById('mag-preview').style.display = 'none';
    // resetuj error poruku
    var err = document.getElementById('mag-err');
    err.style.display = 'none';
    err.textContent = '';

    var fd = new FormData();
    fd.append('_action', 'magacin_parse_dokument');
    fd.append('id', 0);
    fd.append('dokument', file);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        document.getElementById('mag-loading').style.display = 'none';
        if (!d.ok) { alert('Greška: ' + (d.err || 'Nepoznata')); resetMagacin(); return; }
        _magParsed = d.data;
        _magParsed._fajl = d.fajl;
        prikaziPreview(_magParsed);
    })
    .catch(() => { document.getElementById('mag-loading').style.display = 'none'; alert('Mrežna greška.'); resetMagacin(); });
}

function prikaziPreview(data) {
    document.getElementById('prev-firma').value  = data.firma  || '';
    document.getElementById('prev-broj').value   = data.broj_dokumenta || '';
    document.getElementById('prev-datum').value  = data.datum  || '';
    document.getElementById('prev-tip').value    = data.tip    || 'otpremnica';
    var lista = document.getElementById('prev-stavke-lista');
    lista.innerHTML = '';
    var podrazumevana = document.getElementById('prev-lokacija').value || 'magacin';
    var podrNamenjeno = document.getElementById('prev-namenjeno').value || '';
    (data.stavke || []).forEach(function(s, i) {
        var row = document.createElement('div');
        row.style.cssText = 'display:grid;grid-template-columns:24px 1fr 70px 48px 120px 120px 30px;gap:6px;align-items:center;background:var(--light);border-radius:7px;padding:6px 8px;';
        row.innerHTML = `
            <span style="font-size:11px;color:var(--muted);text-align:center;">${i+1}</span>
            <input type="text" value="${esc(s.naziv)}" class="st-naziv" style="border:1.5px solid var(--light2);border-radius:6px;padding:5px 8px;font-size:12px;outline:none;background:#fff;width:100%;box-sizing:border-box;">
            <input type="number" value="${s.kolicina}" class="st-kolicina" min="0" step="0.01" style="border:1.5px solid var(--light2);border-radius:6px;padding:5px 8px;font-size:12px;outline:none;background:#fff;width:100%;box-sizing:border-box;">
            <input type="text" value="${esc(s.jm)}" class="st-jm" style="border:1.5px solid var(--light2);border-radius:6px;padding:5px 8px;font-size:12px;outline:none;background:#fff;width:100%;box-sizing:border-box;">
            <select class="st-lokacija" style="border:1.5px solid var(--light2);border-radius:6px;padding:5px 6px;font-size:12px;outline:none;background:#fff;width:100%;box-sizing:border-box;">${lokOptionsHtml(podrazumevana)}</select>
            <select class="st-namenjeno mag-namenjeno" title="Namenjeno za" style="border-radius:6px;padding:5px 6px;font-size:12px;outline:none;width:100%;box-sizing:border-box;">${namenjenoOptionsHtml(podrNamenjeno)}</select>
            <button onclick="ukloniStavku(this)" style="background:#fee2e2;border:none;border-radius:6px;padding:5px 6px;font-size:13px;cursor:pointer;color:#dc2626;" title="Ukloni">✕</button>
        `;
        lista.appendChild(row);
    });
    document.getElementById('prev-stavke-count').textContent = (data.stavke || []).length;
    document.getElementById('mag-preview').style.display = 'block';
}

function ukloniStavku(btn) {
    btn.closest('div').remove();
    var rows = document.querySelectorAll('#prev-stavke-lista > div');
    rows.forEach(function(r, i) { r.querySelector('span').textContent = i + 1; });
    document.getElementById('prev-stavke-count').textContent = rows.length;
}

// ── Korak 1: firma provera + katalog provera ─────────────────
function sacuvajKorak1() {
    var firmaNaziv = document.getElementById('prev-firma').value.trim();
    _firmaIzbor = 'nova';
    if (!firmaNaziv) { _firmaIzbor = 'none'; sacuvajKorak2(); return; }

    var fd = new FormData();
    fd.append('_action', 'magacin_proveri_firmu');
    fd.append('id', 0);
    fd.append('naziv', firmaNaziv);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) { sacuvajKorak2(); return; }
        if (d.exact) { _firmaIzbor = 'existing:' + d.exact.id; sacuvajKorak2(); return; }
        if (!d.firme || d.firme.length === 0) { _firmaIzbor = 'nova'; sacuvajKorak2(); return; }
        prikaziFirmaModal(firmaNaziv, d.firme);
    })
    .catch(() => sacuvajKorak2());
}

function prikaziFirmaModal(naziv, firme) {
    document.getElementById('firma-naziv-novi').textContent = naziv;
    var lista = document.getElementById('firma-slicne-lista');
    lista.innerHTML = '';
    firme.forEach(function(f) {
        var row = document.createElement('div');
        row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;background:var(--light);border:1.5px solid var(--light2);border-radius:8px;padding:10px 14px;gap:10px;';
        row.innerHTML = `<div><div style="font-size:13px;font-weight:600;">${esc(f.naziv)}</div><div style="font-size:11px;color:var(--muted);">Sličnost: ${f.slicnost}%</div></div><button onclick="firmaIzbor('existing:${f.id}')" class="btn-sm mark" style="flex-shrink:0;">Koristi ovu</button>`;
        lista.appendChild(row);
    });
    document.getElementById('firma-modal').style.display = 'flex';
}

function firmaIzbor(izbor) {
    _firmaIzbor = izbor;
    document.getElementById('firma-modal').style.display = 'none';
    sacuvajKorak2();
}

// ── Korak 2: katalog provera ─────────────────────────────────
function sacuvajKorak2() {
    var stavke = pokupiStavke();
    if (!stavke.length) {
        var err = document.getElementById('mag-err');
        err.textContent = 'Nema stavki.';
        err.style.display = 'block';
        return;
    }
    _stavkeUlaz = stavke; // zapamti lokacije (server vraća rezultate istim redosledom)

    var fd = new FormData();
    fd.append('_action', 'magacin_proveri_katalog');
    fd.append('id', 0);
    fd.append('stavke', JSON.stringify(stavke));
    fd.append('dobavljac', document.getElementById('prev-firma').value.trim());

    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) { alert('Greška: ' + (d.err || 'Nepoznata')); return; }
        if (d.ima_predloga) {
            prikaziKatalogModal(d.rezultati);
        } else {
            // Nema predloga — sve automatski
            _katalogOdluke = d.rezultati.map(function(r, i) {
                var sv = _stavkeUlaz[i] || {};
                return {
                    naziv:         r.naziv,
                    kolicina:      r.kolicina,
                    jm:            r.jm,
                    lokacija:      sv.lokacija || 'Magacin',
                    gradiliste_id: sv.gradiliste_id || 0,
                    namenjeno_gradiliste_id: sv.namenjeno_gradiliste_id || 0,
                    master_id:     r.status === 'tacno' ? null : 'novi',
                    rucni_naziv:   ''
                };
            });
            doSacuvajPrimku();
        }
    })
    .catch(() => alert('Mrežna greška.'));
}

function prikaziKatalogModal(rezultati) {
    var lista = document.getElementById('katalog-stavke-lista');
    lista.innerHTML = '';

    rezultati.forEach(function(r, i) {
        var sv = _stavkeUlaz[i] || {};
        var div = document.createElement('div');
        div.style.cssText = 'border:1px solid var(--light2);border-radius:8px;padding:12px 14px;';
        div.dataset.index = i;

        var badgeBg    = r.status === 'tacno' ? '#dcfce7' : r.status === 'predlog' ? '#fef3c7' : '#f1f5f9';
        var badgeColor = r.status === 'tacno' ? '#166534' : r.status === 'predlog' ? '#92400e' : '#475569';
        var badgeTxt   = r.status === 'tacno' ? 'Pronađeno' : r.status === 'predlog' ? 'Predlog' : 'Novi';

        var html = `<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <span style="background:${badgeBg};color:${badgeColor};font-size:11px;padding:2px 8px;border-radius:5px;">${badgeTxt}</span>
            <span style="font-size:13px;font-weight:600;">${esc(r.naziv)}</span>
            <span style="font-size:11px;color:var(--muted);margin-left:auto;">${r.kolicina} ${esc(r.jm)}</span>
        </div>`;

        if (r.status === 'tacno') {
            html += `<div style="font-size:12px;color:var(--muted);">Vezuje se za: <strong>${esc(r.master.naziv)}</strong> (${esc(r.master.dobavljac)})</div>`;
            html += `<input type="hidden" class="kat-odluka" data-index="${i}" value="tacno">`;
        } else if (r.status === 'predlog') {
            html += `<select class="kat-select" data-index="${i}" onchange="katSelectChange(this, ${i})" style="width:100%;border:1.5px solid var(--light2);border-radius:6px;padding:6px 8px;font-size:12px;margin-bottom:6px;">`;
            r.predlozi.forEach(function(p) {
                html += `<option value="existing:${p.id}">${esc(p.naziv)} — ${esc(p.dobavljac)} (${p.slicnost}%)</option>`;
            });
            html += `<option value="novi">— Dodaj kao novi master —</option>`;
            html += `<option value="rucno">— Upiši ručno —</option>`;
            html += `</select>`;
            html += `<div class="kat-rucni-wrap" id="kat-rucni-${i}" style="display:none;">
                <input type="text" class="kat-rucni-input" placeholder="Upiši naziv master artikla..."
                    style="width:100%;border:1.5px solid var(--light2);border-radius:6px;padding:6px 8px;font-size:12px;box-sizing:border-box;">
                <div style="font-size:11px;color:var(--muted);margin-top:3px;">Ovaj naziv postaje master artikal u katalogu.</div>
            </div>`;
        } else {
            // novi
            html += `<div style="font-size:12px;color:var(--muted);">Nije pronađen — dodaje se kao novi master artikal.</div>`;
            html += `<input type="hidden" class="kat-odluka" data-index="${i}" value="novi">`;
        }

        div.innerHTML = html;
        div.dataset.naziv       = r.naziv;
        div.dataset.kolicina    = r.kolicina;
        div.dataset.jm          = r.jm;
        div.dataset.lokacija    = sv.lokacija || 'Magacin';
        div.dataset.gradilisteId = sv.gradiliste_id || 0;
        div.dataset.namenjenoId = sv.namenjeno_gradiliste_id || 0;
        div.dataset.status      = r.status;
        lista.appendChild(div);
    });

    document.getElementById('katalog-modal').style.display = 'flex';
}

function katSelectChange(sel, i) {
    var rucniWrap = document.getElementById('kat-rucni-' + i);
    if (rucniWrap) rucniWrap.style.display = sel.value === 'rucno' ? 'block' : 'none';
}

function doSacuvajPrimku() {
    // Ako dolazimo iz katalog modala — pokupi odluke
    if (document.getElementById('katalog-modal').style.display !== 'none') {
        var stavkeDivs = document.querySelectorAll('#katalog-stavke-lista > div');
        _katalogOdluke = [];
        var greska = false;

        stavkeDivs.forEach(function(div) {
            var status   = div.dataset.status;
            var naziv    = div.dataset.naziv;
            var kolicina = parseFloat(div.dataset.kolicina);
            var jm       = div.dataset.jm;
            var lokacija = div.dataset.lokacija;
            var gradiliste_id = parseInt(div.dataset.gradilisteId) || 0;
            var namenjeno_gradiliste_id = parseInt(div.dataset.namenjenoId) || 0;

            var master_id   = null;
            var rucni_naziv = '';

            if (status === 'tacno') {
                master_id = null; // koristi tačno poklapanje
            } else if (status === 'predlog') {
                var sel = div.querySelector('.kat-select');
                if (sel.value === 'rucno') {
                    var inp = div.querySelector('.kat-rucni-input');
                    rucni_naziv = inp ? inp.value.trim() : '';
                    if (!rucni_naziv) { greska = true; return; }
                    master_id = 'novi';
                } else if (sel.value === 'novi') {
                    master_id = 'novi';
                } else if (sel.value.startsWith('existing:')) {
                    master_id = parseInt(sel.value.replace('existing:', ''));
                }
            } else {
                master_id = 'novi';
            }

            _katalogOdluke.push({ naziv, kolicina, jm, lokacija, gradiliste_id, namenjeno_gradiliste_id, master_id, rucni_naziv });
        });

        if (greska) {
            var err = document.getElementById('katalog-err');
            err.textContent = 'Upiši naziv za stavke označene sa "Upiši ručno".';
            err.style.display = 'block';
            return;
        }
        document.getElementById('katalog-modal').style.display = 'none';
    }

    var btn = document.getElementById('mag-btn-sacuvaj');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Čuvam...'; }

    var data = {
        firma:          document.getElementById('prev-firma').value.trim(),
        broj_dokumenta: document.getElementById('prev-broj').value.trim(),
        datum:          document.getElementById('prev-datum').value,
        tip:            document.getElementById('prev-tip').value,
        stavke:         pokupiStavke(),
        _fajl:          _magParsed ? _magParsed._fajl : ''
    };

    var fd = new FormData();
    fd.append('_action', 'magacin_sacuvaj_primku');
    fd.append('id', 0);
    fd.append('data', JSON.stringify(data));
    fd.append('fajl', data._fajl);
    fd.append('lokacija', lokFromSelect(document.getElementById('prev-lokacija')).lokacija);
    fd.append('firma_izbor', _firmaIzbor);
    fd.append('odluke_katalog', JSON.stringify(_katalogOdluke));

    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            window.location.href = '?page=magacin&tab=primke';
        } else {
            if (btn) { btn.disabled = false; btn.textContent = '💾 Sačuvaj'; }
            var err = document.getElementById('mag-err');
            err.textContent = d.err || 'Greška.';
            err.style.display = 'block';
        }
    })
    .catch(() => {
        if (btn) { btn.disabled = false; btn.textContent = '💾 Sačuvaj'; }
        var err = document.getElementById('mag-err');
        err.textContent = 'Mrežna greška.';
        err.style.display = 'block';
    });
}

function pokupiStavke() {
    var stavke = [];
    document.querySelectorAll('#prev-stavke-lista > div').forEach(function(row) {
        var lok = lokFromSelect(row.querySelector('.st-lokacija'));
        stavke.push({
            naziv:         row.querySelector('.st-naziv').value.trim(),
            kolicina:      parseFloat(row.querySelector('.st-kolicina').value) || 0,
            jm:            row.querySelector('.st-jm').value.trim(),
            lokacija:      lok.lokacija,
            gradiliste_id: lok.gradiliste_id,
            namenjeno_gradiliste_id: parseInt(row.querySelector('.st-namenjeno').value) || 0
        });
    });
    return stavke.filter(function(s) { return s.naziv && s.kolicina > 0; });
}

function resetMagacin() {
    _magFajl = null; _magParsed = null; _firmaIzbor = 'nova'; _katalogOdluke = [];
    document.getElementById('upload-zona').style.display = 'block';
    document.getElementById('mag-loading').style.display = 'none';
    document.getElementById('mag-preview').style.display = 'none';
    document.getElementById('mag-file-input').value = '';
}

function togglePrimka(i) {
    var detalj = document.getElementById('primka-detalj-' + i);
    var arrow  = document.getElementById('arrow-pr-' + i);
    var open   = detalj.style.display !== 'none';
    // '' (a ne 'table-row') — na mobilnom je tabela CSS-om pretvorena u kartice,
    // pa red sa stavkama prikazuje media-query (display:block); na desktopu ostaje table-row.
    detalj.style.display = open ? 'none' : '';
    arrow.textContent    = open ? '▼' : '▲';
}

function obrisiPrimku(id) {
    if (!confirm('Obrisati ovaj ulaz sa svim stavkama?')) return;
    var fd = new FormData();
    fd.append('_action', 'magacin_obrisi_primku');
    fd.append('id', id);
    fetch('', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if (d.ok) location.reload(); else alert(d.err); });
}

// ── Stanje: akordeon + pretraga ──────────────────────────────
function toggleLok(i) {
    var body = document.getElementById('lok-body-' + i);
    var chev = document.getElementById('lok-chev-' + i);
    var open = body.style.display !== 'none';
    body.style.display = open ? 'none' : 'block';
    if (chev) chev.textContent = open ? '▶' : '▼';
}

function filtrirajStanje() {
    var q = document.getElementById('srch-stanje').value.trim().toLowerCase();
    document.querySelectorAll('.lok-grupa').forEach(function(grupa, idx) {
        var redovi = grupa.querySelectorAll('.stanje-red');
        var vidljivih = 0;
        redovi.forEach(function(r) {
            var match = !q || (r.dataset.nazivLower || '').indexOf(q) !== -1;
            r.style.display = match ? '' : 'none';
            if (match) vidljivih++;
        });
        grupa.style.display = (q && vidljivih === 0) ? 'none' : '';
        // pri pretrazi automatski otvori grupe sa pogocima
        var body = grupa.querySelector('.lok-body');
        var chev = grupa.querySelector('.lok-chevron');
        if (body) {
            if (q && vidljivih > 0) { body.style.display = 'block'; if (chev) chev.textContent = '▼'; }
            else if (!q) { body.style.display = 'none'; if (chev) chev.textContent = '▶'; }
        }
    });
}

// ── Prenos na drugu lokaciju ─────────────────────────────────
function openPrenos(btn) {
    var r = btn.closest('.stanje-red');
    document.getElementById('prenos-naziv').value   = r.dataset.naziv;
    document.getElementById('prenos-jm').value      = r.dataset.jm;
    document.getElementById('prenos-katalog').value = r.dataset.katalog || 0;
    document.getElementById('prenos-lok-iz').value  = r.dataset.lokacija;
    document.getElementById('prenos-gid-iz').value  = r.dataset.gradiliste || 0;
    document.getElementById('prenos-namenjeno-iz').value = r.dataset.namenjeno || 0;
    // odredište podrazumevano nasleđuje namenu izvora (može se promeniti)
    document.getElementById('prenos-namenjeno').value = (r.dataset.namenjeno && r.dataset.namenjeno !== '0') ? r.dataset.namenjeno : '';
    document.getElementById('prenos-lok-iz-disp').value = r.dataset.lokacija;
    document.getElementById('prenos-naslov').textContent = '↔ ' + r.dataset.naziv;
    document.getElementById('prenos-max').textContent = 'na stanju: ' + r.dataset.stanje + ' ' + r.dataset.jm;
    document.getElementById('prenos-kolicina').value = '';
    document.getElementById('prenos-kolicina').max = r.dataset.stanje;
    document.getElementById('prenos-napomena').value = '';
    document.getElementById('prenos-err').style.display = 'none';
    document.getElementById('prenos-modal').style.display = 'flex';
}

function sacuvajPrenos() {
    var lokDo = lokFromSelect(document.getElementById('prenos-lok-do'));
    var fd = new FormData();
    fd.append('_action', 'magacin_prenos'); fd.append('id', 0);
    fd.append('naziv',        document.getElementById('prenos-naziv').value);
    fd.append('jm',           document.getElementById('prenos-jm').value);
    fd.append('katalog_id',   document.getElementById('prenos-katalog').value);
    fd.append('lokacija_iz',  document.getElementById('prenos-lok-iz').value);
    fd.append('gradiliste_iz', document.getElementById('prenos-gid-iz').value);
    fd.append('lokacija_do',  lokDo.lokacija);
    fd.append('gradiliste_do', lokDo.gradiliste_id);
    fd.append('namenjeno_iz', document.getElementById('prenos-namenjeno-iz').value);
    fd.append('namenjeno_do', document.getElementById('prenos-namenjeno').value);
    fd.append('kolicina',     document.getElementById('prenos-kolicina').value);
    fd.append('datum',        document.getElementById('prenos-datum').value);
    fd.append('napomena',     document.getElementById('prenos-napomena').value);
    fetch('', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
        if (d.ok) { document.getElementById('prenos-modal').style.display = 'none'; location.reload(); }
        else { var e = document.getElementById('prenos-err'); e.textContent = d.err || 'Greška.'; e.style.display = 'block'; }
    });
}

// ── Premesti celu lokaciju (ručno vezivanje za gradilište) ───
function openPremestiLok(btn) {
    var lok = btn.dataset.lok;
    document.getElementById('premesti-lok-iz').value = lok;
    document.getElementById('premesti-iz-disp').textContent = lok;
    document.getElementById('premesti-err').style.display = 'none';
    // ne nudi istu lokaciju kao odredište
    var sel = document.getElementById('premesti-lok-do');
    Array.prototype.forEach.call(sel.options, function (o) {
        o.disabled = (o.text === lok);
    });
    if (sel.options[sel.selectedIndex] && sel.options[sel.selectedIndex].disabled) sel.selectedIndex = 0;
    document.getElementById('premesti-modal').style.display = 'flex';
}

function sacuvajPremestiLok() {
    var lokDo = lokFromSelect(document.getElementById('premesti-lok-do'));
    var fd = new FormData();
    fd.append('_action', 'magacin_premesti_lokaciju'); fd.append('id', 0);
    fd.append('lokacija_iz',   document.getElementById('premesti-lok-iz').value);
    fd.append('lokacija_do',   lokDo.lokacija);
    fd.append('gradiliste_do', lokDo.gradiliste_id);
    fetch('', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
        if (d.ok) { document.getElementById('premesti-modal').style.display = 'none'; location.reload(); }
        else { var e = document.getElementById('premesti-err'); e.textContent = d.err || 'Greška.'; e.style.display = 'block'; }
    });
}

// ── Izmena stavke ulaza (Ulaz robe) ──────────────────────────
var _magUrediRow = null;
var _magUrediBtn = null;
var _magIzmenaRow = null;

// Format broja: 1234.5 -> "1.234,50"
function fmtBroj(n) {
    return Number(n).toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function openUrediStavku(btn) {
    _magUrediBtn = btn;
    _magUrediRow = btn.closest('tr');
    document.getElementById('us-id').value       = btn.dataset.id;
    document.getElementById('us-naziv').value    = btn.dataset.naziv;
    document.getElementById('us-kolicina').value = btn.dataset.kolicina;
    document.getElementById('us-jm').value       = btn.dataset.jm;
    var gid = parseInt(btn.dataset.gradiliste) || 0;
    document.getElementById('us-lokacija').value = gid > 0 ? String(gid) : 'magacin';
    var nam = parseInt(btn.dataset.namenjeno) || 0;
    document.getElementById('us-namenjeno').value = nam > 0 ? String(nam) : '';
    document.getElementById('us-err').style.display = 'none';
    document.getElementById('uredi-stavku-modal').style.display = 'flex';
}

function sacuvajUrediStavku() {
    var lok = lokFromSelect(document.getElementById('us-lokacija'));
    var fd = new FormData();
    fd.append('_action', 'magacin_uredi_stavku'); fd.append('id', 0);
    fd.append('stavka_id',     document.getElementById('us-id').value);
    fd.append('naziv',         document.getElementById('us-naziv').value);
    fd.append('kolicina',      document.getElementById('us-kolicina').value);
    fd.append('jm',            document.getElementById('us-jm').value);
    fd.append('lokacija',      lok.lokacija);
    fd.append('gradiliste_id', lok.gradiliste_id);
    fd.append('namenjeno_gradiliste_id', document.getElementById('us-namenjeno').value);
    fetch('', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
        if (!d.ok) { var e = document.getElementById('us-err'); e.textContent = d.err || 'Greška.'; e.style.display = 'block'; return; }

        // Ažuriraj samo taj red (bez reload-a)
        var naziv = document.getElementById('us-naziv').value;
        var kol   = parseFloat(document.getElementById('us-kolicina').value) || 0;
        var jm    = document.getElementById('us-jm').value;
        var lokSel = document.getElementById('us-lokacija');
        var lokText = lokSel.options[lokSel.selectedIndex] ? lokSel.options[lokSel.selectedIndex].text : '';
        var namSel = document.getElementById('us-namenjeno');
        var namId  = namSel.value;
        var namNaziv = (namId && namSel.options[namSel.selectedIndex]) ? namSel.options[namSel.selectedIndex].text : '';

        var r = _magUrediRow;
        if (r) {
            var tds = r.querySelectorAll('td'); // [1]naziv [2]kolicina [3]jm [4]lokacija [5]namenjeno
            if (tds[1]) tds[1].textContent = naziv;
            if (tds[2]) tds[2].textContent = fmtBroj(kol);
            if (tds[3]) tds[3].textContent = jm;
            if (tds[4]) tds[4].innerHTML = '<span style="background:#fff;border:1px solid var(--light2);border-radius:4px;padding:1px 7px;">' + esc(lokText) + '</span>';
            if (tds[5]) tds[5].innerHTML = namNaziv ? '<span class="st-namenjeno-chip">🎯 ' + esc(namNaziv) + '</span>' : '<span style="color:var(--muted);">—</span>';
        }
        if (_magUrediBtn) {
            _magUrediBtn.dataset.naziv      = naziv;
            _magUrediBtn.dataset.kolicina   = kol;
            _magUrediBtn.dataset.jm         = jm;
            _magUrediBtn.dataset.lokacija   = lokText;
            _magUrediBtn.dataset.gradiliste = lok.gradiliste_id || 0;
            _magUrediBtn.dataset.namenjeno  = namId || 0;
        }
        document.getElementById('uredi-stavku-modal').style.display = 'none';
    });
}

// ── Izmena stavke (pun edit + log) ───────────────────────────
function openIzmena(btn) {
    var r = btn.closest('.stanje-red');
    _magIzmenaRow = r;
    document.getElementById('izmena-stari-naziv').value = r.dataset.naziv;
    document.getElementById('izmena-stari-jm').value    = r.dataset.jm;
    document.getElementById('izmena-lokacija').value    = r.dataset.lokacija;
    document.getElementById('izmena-gid').value         = r.dataset.gradiliste || 0;
    document.getElementById('izmena-katalog').value     = r.dataset.katalog || 0;
    document.getElementById('izmena-namenjeno-staro').value = r.dataset.namenjeno || 0;
    document.getElementById('izmena-namenjeno').value   = (r.dataset.namenjeno && r.dataset.namenjeno !== '0') ? r.dataset.namenjeno : '';
    document.getElementById('izmena-lok-disp').value    = r.dataset.lokacija;
    document.getElementById('izmena-naziv').value       = r.dataset.naziv;
    document.getElementById('izmena-jm').value          = r.dataset.jm;
    document.getElementById('izmena-kolicina').value    = r.dataset.stanje;
    document.getElementById('izmena-err').style.display = 'none';
    document.getElementById('izmena-modal').style.display = 'flex';
}

function sacuvajIzmena() {
    var fd = new FormData();
    fd.append('_action', 'magacin_izmeni_stanje'); fd.append('id', 0);
    fd.append('stari_naziv',  document.getElementById('izmena-stari-naziv').value);
    fd.append('stari_jm',     document.getElementById('izmena-stari-jm').value);
    fd.append('lokacija',     document.getElementById('izmena-lokacija').value);
    fd.append('gradiliste_id', document.getElementById('izmena-gid').value);
    fd.append('katalog_id',   document.getElementById('izmena-katalog').value);
    fd.append('novi_naziv',   document.getElementById('izmena-naziv').value);
    fd.append('novi_jm',      document.getElementById('izmena-jm').value);
    fd.append('nova_kolicina', document.getElementById('izmena-kolicina').value);
    fd.append('namenjeno_staro', document.getElementById('izmena-namenjeno-staro').value);
    fd.append('namenjeno_gradiliste_id', document.getElementById('izmena-namenjeno').value);
    fetch('', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
        if (!d.ok) { var e = document.getElementById('izmena-err'); e.textContent = d.err || 'Greška.'; e.style.display = 'block'; return; }

        // Strukturna izmena (preimenovanje / spajanje namene) dira i druge redove → pun reload
        if (d.reload) { location.reload(); return; }

        // Inače ažuriraj samo taj red (bez reload-a)
        var naziv = document.getElementById('izmena-naziv').value;
        var jm    = document.getElementById('izmena-jm').value;
        var kol   = parseFloat(document.getElementById('izmena-kolicina').value) || 0;
        var namSel = document.getElementById('izmena-namenjeno');
        var namId  = namSel.value;
        var namNaziv = (namId && namSel.options[namSel.selectedIndex]) ? namSel.options[namSel.selectedIndex].text : '';

        var r = _magIzmenaRow;
        if (r) {
            r.dataset.naziv          = naziv;
            r.dataset.nazivLower     = naziv.toLowerCase();
            r.dataset.jm             = jm;
            r.dataset.namenjeno      = namId || 0;
            r.dataset.namenjenoNaziv = namNaziv;
            r.dataset.stanje         = kol;

            var ime = r.querySelector('.st-ime');
            if (ime) ime.innerHTML = esc(naziv) + (namNaziv ? ' <span class="st-namenjeno-chip">🎯 ' + esc(namNaziv) + '</span>' : '');
            var jmEl = r.querySelector('.st-jm');
            if (jmEl) jmEl.textContent = jm;
            var kolEl = r.querySelector('.st-kol');
            if (kolEl) { kolEl.textContent = fmtBroj(kol); kolEl.style.color = kol >= 0 ? '#059669' : '#dc2626'; }
        }
        document.getElementById('izmena-modal').style.display = 'none';
    });
}

function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
