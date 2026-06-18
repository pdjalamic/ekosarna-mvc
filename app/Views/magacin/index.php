<?php
$tabs = [
    'stanje' => '📦 Stanje zaliha',
    'primke' => '📋 Ulaz robe',
    'nova'   => '➕ Novi ulaz',
];
$aktivan_tab = $_GET['tab'] ?? 'stanje';
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
  .mag-primke > tbody > tr[id^="primka-detalj-"] > td { padding:0 !important; }
  .mag-primke > tbody > tr[id^="primka-detalj-"] > td::before { display:none; }
  .mag-primke > tbody > tr[id^="primka-detalj-"] table { min-width:0 !important; width:100%; font-size:11px; }
}
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
<div style="margin-bottom:12px;">
    <input type="text" id="srch-stanje" placeholder="Pretraži artikal..."
        oninput="filtrirajStanje()"
        style="border:1.5px solid var(--light2);border-radius:8px;padding:8px 12px;font-size:13px;outline:none;background:#fff;width:280px;">
</div>
<div class="rs-tabela-wrap">
<?php if (empty($stanje)): ?>
    <div style="padding:40px;text-align:center;color:var(--muted);">Nema artikala na stanju.</div>
<?php else: ?>
    <table class="rs-tabela mag-card mag-stanje">
        <thead>
            <tr>
                <th style="width:36px;text-align:center;">#</th>
                <th>Artikal</th>
                <th style="width:60px;text-align:center;">JM</th>
                <th style="width:90px;text-align:center;">Primljeno</th>
                <th style="width:90px;text-align:center;">Izdato</th>
                <th style="width:90px;text-align:center;">Stanje</th>
                <th style="width:120px;">Lokacija</th>
                <th style="width:60px;text-align:right;"></th>
            </tr>
        </thead>
        <tbody id="stanje-tbody">
        <?php foreach ($stanje as $idx => $s): ?>
        <tr class="stanje-red rs-red" data-naziv="<?= htmlspecialchars(strtolower($s['naziv']), ENT_QUOTES) ?>">
            <td style="text-align:center;font-size:11px;color:var(--muted);"><?= $idx + 1 ?></td>
            <td style="font-size:13px;">
                <?= h($s['naziv']) ?>
                <div style="font-size:11px;color:var(--muted);"><?= h($s['firma_naziv']) ?> · <?= $s['datum_prijema'] ? date('d.m.Y', strtotime($s['datum_prijema'])) : '' ?></div>
            </td>
            <td style="text-align:center;font-size:12px;color:var(--muted);"><?= h($s['jm']) ?></td>
            <td style="text-align:center;font-size:13px;"><?= number_format($s['primljeno'], 2, ',', '.') ?></td>
            <td style="text-align:center;font-size:13px;color:#dc2626;"><?= number_format($s['izdato'], 2, ',', '.') ?></td>
            <td style="text-align:center;font-weight:700;font-size:13px;color:<?= $s['stanje'] > 0 ? '#059669' : '#dc2626' ?>;">
                <?= number_format($s['stanje'], 2, ',', '.') ?>
            </td>
            <td style="font-size:12px;">
                <span style="background:var(--light);border:1px solid var(--light2);border-radius:5px;padding:2px 8px;"><?= h($s['lokacija']) ?></span>
            </td>
            <td style="text-align:right;">
                <button class="btn-sm" onclick="openPokret(<?= $s['id'] ?>, '<?= h(addslashes($s['naziv'])) ?>', <?= $s['stanje'] ?>, '<?= h(addslashes($s['lokacija'])) ?>')" title="Pokret">↗</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

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
                <?php if ($pr['pdf_putanja']): ?>
                <a href="<?= BASE_URL ?>/uploads/magacin/<?= h($pr['pdf_putanja']) ?>" target="_blank"
                   class="btn-sm" title="Prikaži dokument"
                   onclick="event.stopPropagation()"
                   style="margin-right:6px;">📄</a>
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
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pr['stavke'] as $si => $s): ?>
                        <tr style="border-top:1px solid var(--light2);">
                            <td style="text-align:center;padding:6px 8px;color:var(--muted);"><?= $si + 1 ?></td>
                            <td style="padding:6px 8px;"><?= h($s['naziv']) ?></td>
                            <td style="text-align:center;padding:6px 8px;font-weight:600;"><?= number_format($s['kolicina'], 2, ',', '.') ?></td>
                            <td style="text-align:center;padding:6px 8px;color:var(--muted);"><?= h($s['jm']) ?></td>
                            <td style="padding:6px 8px;"><span style="background:#fff;border:1px solid var(--light2);border-radius:4px;padding:1px 7px;"><?= h($s['lokacija']) ?></span></td>
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
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:8px;">
                Stavke (<span id="prev-stavke-count">0</span>)
            </div>
            <div style="display:grid;grid-template-columns:24px 1fr 70px 48px 130px 30px;gap:6px;padding:4px 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);">
                <span>#</span><span>Naziv</span><span style="text-align:center;">Količina</span><span style="text-align:center;">JM</span><span>Lokacija</span><span></span>
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

<!-- MODAL: Pokret ─────────────────────────────────────────── -->
<div id="pokret-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:440px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('pokret-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 id="pokret-naslov" style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:16px;padding-right:32px;">Pokret robe</h3>
        <input type="hidden" id="pokret-stavka-id">
        <div class="tim-form-group">
            <label>Tip pokreta</label>
            <select id="pokret-tip" onchange="azurirajPokretFormu()" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;">
                <option value="izlaz">↗ Izlaz na gradilište</option>
                <option value="prenos">↔ Prenos na drugu lokaciju</option>
                <option value="povrat">↩ Povrat u magacin</option>
            </select>
        </div>
        <div class="tim-form-group">
            <label>Količina <span id="pokret-max-label" style="color:var(--muted);font-size:11px;"></span></label>
            <input type="number" id="pokret-kolicina" min="0.01" step="0.01" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;">
        </div>
        <div id="pokret-gradiliste-wrap" class="tim-form-group">
            <label>Gradilište</label>
            <select id="pokret-gradiliste" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;">
                <option value="">— Izaberi gradilište —</option>
                <?php foreach ($gradilista as $g): ?>
                <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="tim-form-group"><label>Lokacija iz</label><input type="text" id="pokret-lok-iz" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
            <div class="tim-form-group"><label>Lokacija do</label><input type="text" id="pokret-lok-do" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        </div>
        <div class="tim-form-group"><label>Datum</label><input type="date" id="pokret-datum" value="<?= date('Y-m-d') ?>" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;"></div>
        <div class="tim-form-group"><label>Napomena (opciono)</label><input type="text" id="pokret-napomena" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        <div id="pokret-err" style="display:none;color:#dc2626;font-size:12px;margin-bottom:8px;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('pokret-modal').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="sacuvajPokret()" class="btn-primary">Potvrdi pokret</button>
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
    (data.stavke || []).forEach(function(s, i) {
        var row = document.createElement('div');
        row.style.cssText = 'display:grid;grid-template-columns:24px 1fr 70px 48px 130px 30px;gap:6px;align-items:center;background:var(--light);border-radius:7px;padding:6px 8px;';
        row.innerHTML = `
            <span style="font-size:11px;color:var(--muted);text-align:center;">${i+1}</span>
            <input type="text" value="${esc(s.naziv)}" class="st-naziv" style="border:1.5px solid var(--light2);border-radius:6px;padding:5px 8px;font-size:12px;outline:none;background:#fff;width:100%;box-sizing:border-box;">
            <input type="number" value="${s.kolicina}" class="st-kolicina" min="0" step="0.01" style="border:1.5px solid var(--light2);border-radius:6px;padding:5px 8px;font-size:12px;outline:none;background:#fff;width:100%;box-sizing:border-box;">
            <input type="text" value="${esc(s.jm)}" class="st-jm" style="border:1.5px solid var(--light2);border-radius:6px;padding:5px 8px;font-size:12px;outline:none;background:#fff;width:100%;box-sizing:border-box;">
            <select class="st-lokacija" style="border:1.5px solid var(--light2);border-radius:6px;padding:5px 6px;font-size:12px;outline:none;background:#fff;width:100%;box-sizing:border-box;">${lokOptionsHtml(podrazumevana)}</select>
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

            _katalogOdluke.push({ naziv, kolicina, jm, lokacija, gradiliste_id, master_id, rucni_naziv });
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
            gradiliste_id: lok.gradiliste_id
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
    detalj.style.display = open ? 'none' : 'table-row';
    arrow.textContent    = open ? '▼' : '▲';
}

function obrisiPrimku(id) {
    if (!confirm('Obrisati ovaj ulaz sa svim stavkama?')) return;
    var fd = new FormData();
    fd.append('_action', 'magacin_obrisi_primku');
    fd.append('id', id);
    fetch('', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if (d.ok) location.reload(); else alert(d.err); });
}

function filtrirajStanje() {
    var q = document.getElementById('srch-stanje').value.toLowerCase();
    document.querySelectorAll('.stanje-red').forEach(function(tr) { tr.style.display = tr.dataset.naziv.includes(q) ? '' : 'none'; });
}

function openPokret(stavkaId, naziv, stanje, lokacija) {
    document.getElementById('pokret-stavka-id').value = stavkaId;
    document.getElementById('pokret-naslov').textContent = naziv;
    document.getElementById('pokret-max-label').textContent = 'max: ' + stanje;
    document.getElementById('pokret-kolicina').value = '';
    document.getElementById('pokret-lok-iz').value = lokacija;
    document.getElementById('pokret-lok-do').value = '';
    document.getElementById('pokret-napomena').value = '';
    document.getElementById('pokret-err').style.display = 'none';
    document.getElementById('pokret-tip').value = 'izlaz';
    azurirajPokretFormu();
    document.getElementById('pokret-modal').style.display = 'flex';
}

function azurirajPokretFormu() {
    var tip = document.getElementById('pokret-tip').value;
    document.getElementById('pokret-gradiliste-wrap').style.display = tip === 'izlaz' ? 'block' : 'none';
}

function sacuvajPokret() {
    var fd = new FormData();
    fd.append('_action', 'magacin_pokret'); fd.append('id', 0);
    fd.append('stavka_id', document.getElementById('pokret-stavka-id').value);
    fd.append('tip', document.getElementById('pokret-tip').value);
    fd.append('kolicina', document.getElementById('pokret-kolicina').value);
    fd.append('gradiliste_id', document.getElementById('pokret-gradiliste').value || 0);
    fd.append('lokacija_iz', document.getElementById('pokret-lok-iz').value);
    fd.append('lokacija_do', document.getElementById('pokret-lok-do').value);
    fd.append('datum', document.getElementById('pokret-datum').value);
    fd.append('napomena', document.getElementById('pokret-napomena').value);
    fetch('', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
        if (d.ok) { document.getElementById('pokret-modal').style.display = 'none'; location.reload(); }
        else { var e = document.getElementById('pokret-err'); e.textContent = d.err || 'Greška.'; e.style.display = 'block'; }
    });
}

function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
