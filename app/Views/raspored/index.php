<?php
$dani_puni = ['Ponedeljak', 'Utorak', 'Sreda', 'Četvrtak', 'Petak', 'Subota'];
$je_prosla = strtotime($ponedeljak) < strtotime('monday this week');
$elektricari_json = json_encode($elektricari);

function gradiliste_boja(string $naziv): string {
    $boje = ['#fef3c7','#dbeafe','#dcfce7','#fce7f3','#ede9fe','#ffedd5','#cffafe','#f0fdf4'];
    return $boje[abs(crc32($naziv)) % count($boje)];
}
function gradiliste_boja_text(string $naziv): string {
    $boje = ['#92400e','#1e40af','#166534','#9d174d','#5b21b6','#9a3412','#155e75','#14532d'];
    return $boje[abs(crc32($naziv)) % count($boje)];
}
?>

<?php
$je_buduca = $nedelja && strtotime($ponedeljak) > strtotime('monday this week');
?>
<div class="topbar-admin" style="flex-wrap:wrap;gap:10px;">
    <div class="page-title">Raspored rada</div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <a href="?page=raspored&datum=<?= $prethodna_nedelja ?>" class="btn-sm">←</a>
        <div style="position:relative;">
            <button type="button" onclick="toggleNedeljeDropdown()"
                style="background:#fff;border:1.5px solid var(--light2);border-radius:8px;padding:6px 14px;font-size:14px;font-weight:700;color:var(--blue);cursor:pointer;display:flex;align-items:center;gap:8px;">
                <?= date('d.m', strtotime($ponedeljak)) ?> — <?= date('d.m.Y', strtotime($subota)) ?>
                <span style="font-size:10px;color:var(--muted);">▼</span>
            </button>
            <div id="nedelje-dropdown" style="display:none;position:absolute;top:calc(100% + 6px);left:0;background:#fff;border:1.5px solid var(--light2);border-radius:10px;box-shadow:0 8px 24px #0002;z-index:999;min-width:240px;max-height:320px;overflow-y:auto;">
                <?php foreach ($sve_nedelje as $rn): ?>
                <?php $aktivan = ($rn['datum_od'] === $ponedeljak) ? 'background:#eff6ff;font-weight:700;' : ''; ?>
                <a href="?page=raspored&datum=<?= $rn['datum_od'] ?>"
                   style="display:block;padding:10px 16px;font-size:13px;color:var(--blue);text-decoration:none;border-bottom:1px solid var(--light2);<?= $aktivan ?>">
                    <?= date('d.m', strtotime($rn['datum_od'])) ?> — <?= date('d.m.Y', strtotime($rn['datum_do'])) ?>
                    <?php if ($rn['datum_od'] === $ponedeljak): ?>
                    <span style="font-size:10px;background:#2563eb;color:#fff;border-radius:4px;padding:1px 6px;margin-left:4px;">Trenutna</span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
                <?php if (empty($sve_nedelje)): ?>
                <div style="padding:12px 16px;font-size:13px;color:var(--muted);">Nema kreiranih nedelja</div>
                <?php endif; ?>
            </div>
        </div>
        <a href="?page=raspored&datum=<?= $sledeca_nedelja ?>" class="btn-sm">→</a>
        <a href="?page=raspored&datum=<?= date('Y-m-d') ?>" class="btn-sm mark">Ova nedelja</a>
    </div>
    <?php if ($nedelja): ?>
    <div style="display:flex;gap:8px;margin-left:auto;">
        <button type="button" class="btn-sm" onclick="kopirajSledecu(<?= $nedelja['id'] ?>)">📋 Kopiraj → Sledeća nedelja</button>
        <?php if ($je_buduca): ?>
        <button type="button" class="btn-sm del" onclick="obrisiNedelju(<?= $nedelja['id'] ?>)">🗑 Obriši nedelju</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($nacrti)): ?>
<div class="rs-nacrti">
    <div class="rs-nacrti-head">📝 Nacrti — još nisu objavljeni (ekipa nije obaveštena)</div>
    <?php foreach ($nacrti as $n): $di = $n['dan_index']; $brn = count($n['stavke']); ?>
    <div class="rs-nacrti-dan">
        <div class="rs-nacrti-dan-head">
            <span><?= $dani_puni[$di] ?? '' ?> · <?= date('d.m.Y', strtotime($n['datum'])) ?></span>
            <button type="button" class="rs-objavi-btn"
                onclick="objaviDan(<?= $n['dan_id'] ?>, <?= $brn ?>)">
                📢 Objavi <?= $brn ?> <?= $brn == 1 ? 'nacrt' : 'nacrta' ?>
            </button>
        </div>
        <?php foreach ($n['stavke'] as $s):
            $gn  = $s['gradiliste_naziv'] ?? '';
            $gb  = $gn ? gradiliste_boja($gn) : '';
            $gbt = $gn ? gradiliste_boja_text($gn) : '';
        ?>
        <div class="rs-nacrt-red">
            <div class="rs-nacrt-info">
                <?php if ($gn): ?>
                <span class="rs-grad-chip" style="background:<?= $gb ?>;color:<?= $gbt ?>;"><?= h($gn) ?></span>
                <?php endif; ?>
                <span class="rs-nacrt-opis"><?= $s['opis'] ? h(mb_substr($s['opis'], 0, 80)) . (mb_strlen($s['opis']) > 80 ? '…' : '') : '—' ?></span>
                <span class="rs-nacrt-ekipa">
                    <?php foreach ($s['radnici'] as $r): $jeOdg = ($s['odgovoran_id'] && $s['odgovoran_id'] == $r['radnik_id']); ?>
                    <span><?= $jeOdg ? '📦' : '👷' ?> <?= h($r['ime']) ?><?php if ($r['vreme_od']): ?> <span style="opacity:.7;">(<?= substr($r['vreme_od'],0,5) ?>–<?= substr($r['vreme_do'],0,5) ?>)</span><?php endif; ?></span>
                    <?php endforeach; ?>
                </span>
            </div>
            <div class="rs-nacrt-akcije">
                <button type="button" class="rs-objavi-btn" onclick="objaviStavku(<?= $s['id'] ?>)">Objavi</button>
                <button type="button" class="btn-sm" onclick="openIzmeniStavku(<?= $s['id'] ?>, <?= $di ?>)" title="Izmeni">✏️</button>
                <button type="button" class="btn-sm del" onclick="obrisiStavku(<?= $s['id'] ?>)" title="Obriši">🗑</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($dani) && !$nedelja): ?>
<div class="empty">
    <big>📅</big>
    Nema rasporeda za ovu nedelju.<br>
    <?php if (!$je_prosla): ?>
    <button type="button" class="btn-primary" style="margin-top:16px;" onclick="initNedelja()">Kreiraj raspored</button>
    <?php else: ?>
    <p style="color:var(--muted);font-size:13px;margin-top:12px;">Prošle nedelje se ne mogu menjati.</p>
    <?php endif; ?>
</div>
<?php else: ?>

<div class="rs-tabela-wrap">
    <table class="rs-tabela">
        <thead>
            <tr>
                <th style="width:130px;">Dan</th>
                <th style="width:170px;">Gradilište</th>
                <th>Zadatak</th>
                <th style="width:280px;">Ekipa</th>
                <th style="width:120px;text-align:right;"></th>
            </tr>
        </thead>
        <tbody>
        <?php for ($i = 0; $i < 6; $i++):
            $datum_dana = date('Y-m-d', strtotime($ponedeljak . " +$i days"));
            $dan = null;
            foreach ($dani as $d) { if ($d['datum'] === $datum_dana) { $dan = $d; break; } }
            $stavke = $dan['stavke'] ?? [];
            $boja_dana = $boje[$i];
            $broj_stavki = count($stavke);

            $gradiliste_boje_mapa = [];
            foreach ($stavke as $s) {
                $gn = $s['gradiliste_naziv'] ?? '';
                if ($gn && !isset($gradiliste_boje_mapa[$gn])) {
                    $gradiliste_boje_mapa[$gn] = [
                        'bg'   => gradiliste_boja($gn),
                        'text' => gradiliste_boja_text($gn),
                    ];
                }
            }
        ?>
        <?php if (empty($stavke)): ?>
        <tr>
            <td style="background:<?= $boja_dana ?>18;border-left:4px solid <?= $boja_dana ?>;font-weight:700;color:<?= $boja_dana ?>;">
                <div style="font-size:13px;"><?= $dani_puni[$i] ?></div>
                <div style="font-size:11px;font-weight:400;color:var(--muted);"><?= date('d.m.Y', strtotime($datum_dana)) ?></div>
            </td>
            <td colspan="3" style="color:var(--muted);font-size:13px;font-style:italic;">Nema zadataka</td>
            <td style="text-align:right;">
                <?php if (!$je_prosla): ?>
                <button type="button" class="btn-sm mark"
                    onclick="openDodajStavku('<?= $datum_dana ?>', <?= $dan ? $dan['id'] : 'null' ?>, <?= $i ?>)"
                    style="font-size:11px;">+ Nova stavka</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php else: ?>
        <?php foreach ($stavke as $si => $s):
            $gn = $s['gradiliste_naziv'] ?? '';
            $gboja = $gn ? $gradiliste_boje_mapa[$gn] : null;
        ?>
        <tr class="rs-red">
            <?php if ($si === 0): ?>
            <td rowspan="<?= $broj_stavki ?>"
                style="background:<?= $boja_dana ?>18;border-left:4px solid <?= $boja_dana ?>;vertical-align:top;font-weight:700;color:<?= $boja_dana ?>;">
                <div style="font-size:13px;"><?= $dani_puni[$i] ?></div>
                <div style="font-size:11px;font-weight:400;color:var(--muted);"><?= date('d.m.Y', strtotime($datum_dana)) ?></div>
            </td>
            <?php endif; ?>
            <td style="vertical-align:middle;">
                <?php if ($gn && $gboja): ?>
                <span style="background:<?= $gboja['bg'] ?>;color:<?= $gboja['text'] ?>;border-radius:6px;padding:3px 10px;font-size:12px;font-weight:700;white-space:nowrap;">
                    <?= h($gn) ?>
                </span>
                <?php else: ?>
                <span style="color:var(--muted);">—</span>
                <?php endif; ?>
            </td>
            <td style="font-size:13px;vertical-align:middle;">
                <?= $s['opis'] ? h(mb_substr($s['opis'], 0, 100)) . (mb_strlen($s['opis']) > 100 ? '...' : '') : '—' ?>
            </td>
            <td style="font-size:12px;vertical-align:middle;">
                <?php foreach ($s['radnici'] as $r): ?>
                <div class="rs-el-radnik-red">
                    <?php $jeOdg = (isset($s['odgovoran_id']) && $s['odgovoran_id'] == $r['radnik_id']); ?>
                    <span class="rs-el-ime-txt" <?= $jeOdg ? 'style="background:#fef3c7;border-radius:4px;padding:1px 6px;color:#92400e;font-weight:700;"' : '' ?>>
                    <?= $jeOdg ? '📦' : '👷' ?>
                    <?= h($r['ime']) ?>
                    </span>
                    <?php if ($r['vreme_od'] || $r['vreme_do']): ?>
                    <span class="rs-el-vreme-txt">
                        <?= $r['vreme_od'] ? substr($r['vreme_od'],0,5) : '' ?>
                        <?= $r['vreme_od'] && $r['vreme_do'] ? '–' : '' ?>
                        <?= $r['vreme_do'] ? substr($r['vreme_do'],0,5) : '' ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </td>
            <td style="text-align:right;white-space:nowrap;vertical-align:middle;">
                <?php if (!$je_prosla): ?>
                <button type="button" class="btn-sm" onclick="openIzmeniStavku(<?= $s['id'] ?>, <?= $i ?>)" title="Izmeni">✏️</button>
                <?php endif; ?>
                <button type="button" class="btn-sm rs-poruke-btn" id="rs-pb-<?= $s['id'] ?>"
                    onclick="openPorukeStavke(<?= $s['id'] ?>, '<?= h(addslashes($gn ?: 'Stavka')) ?>')" title="Poruke">
                    💬<?php if (!empty($s['poruka_count'])): ?><span style="background:#6b7280;color:#fff;border-radius:99px;font-size:10px;padding:1px 5px;margin-left:3px;font-weight:700;"><?= $s['poruka_count'] ?></span><?php endif; ?><?php if (!empty($s['nova_poruka'])): ?><span style="background:#e53935;color:#fff;border-radius:99px;font-size:10px;padding:1px 5px;margin-left:2px;font-weight:700;"><?= $s['nove_poruke_count'] ?></span><?php endif; ?>
                </button>
                <?php if (!$je_prosla): ?>
                <button type="button" class="btn-sm del" onclick="obrisiStavku(<?= $s['id'] ?>)" title="Obriši">🗑</button>
                <?php if ($si === $broj_stavki - 1): ?>
                <button type="button" class="btn-sm mark"
                    onclick="openDodajStavku('<?= $datum_dana ?>', <?= $dan['id'] ?>, <?= $i ?>)"
                    style="font-size:11px;" title="Nova stavka">+</button>
                <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php endfor; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- MODAL: Dodaj/Izmeni stavku -->
<div id="rs-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:580px;box-shadow:0 24px 80px #000a;position:relative;margin:auto;">
        <button onclick="zatvoriRsModal()" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 id="rs-modal-title" style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:18px;">Nova stavka</h3>
        <input type="hidden" id="rs-stavka-id" value="0">
        <input type="hidden" id="rs-dan-id" value="">
        <input type="hidden" id="rs-dan-index" value="0">

        <div class="tim-form-group">
            <label>Gradilište</label>
            <select id="rs-gradiliste">
                <option value="">— Bez gradilišta —</option>
                <?php foreach ($gradilista as $g): ?>
                <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tim-form-group">
            <label>Ekipa i vreme rada</label>
            <select id="rs-dodaj-radnika" onchange="if(this.value){dodajRadnika(this.value);this.value='';}"
                style="border:1.5px solid var(--light2);border-radius:7px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;width:100%;margin:6px 0;cursor:pointer;font-family:inherit;">
                <option value="">+ Dodaj radnika</option>
                <?php foreach ($elektricari as $e): ?>
                <option value="<?= $e['id'] ?>">👷 <?= h($e['ime']) ?></option>
                <?php endforeach; ?>
            </select>
            <div id="rs-ekipa-lista" style="display:flex;flex-direction:column;gap:8px;"></div>
        </div>
        <div class="tim-form-group">
            <label>Opis zadatka</label>
            <div style="position:relative;">
                <textarea id="rs-opis" rows="3"
                    style="border:1.5px solid var(--light2);border-radius:7px;padding:8px 46px 8px 12px;font-size:13px;font-family:inherit;outline:none;background:var(--light);width:100%;resize:vertical;box-sizing:border-box;"
                    placeholder="Šta treba uraditi..."></textarea>
                <button type="button" onclick="startMic('rs-opis', this)" id="mic-rs-opis"
                    style="position:absolute;top:8px;right:8px;background:#f1f5f9;color:#475569;border:1.5px solid var(--light2);border-radius:8px;padding:6px 10px;font-size:15px;cursor:pointer;line-height:1;" title="Glasovni unos">🎤</button>
            </div>
        </div>

        <!-- NOVO: Odgovoran za materijal -->
        <div class="tim-form-group" id="rs-odgovoran-wrap" style="display:none;">
            <label style="font-size:12px;font-weight:700;color:#b45309;">📦 Odgovoran za unos materijala</label>
            <select id="rs-odgovoran-id"
                style="border:1.5px solid #fde68a;border-radius:7px;padding:7px 10px;font-size:13px;background:#fffbeb;outline:none;width:100%;margin-top:4px;">
                <option value="">— Niko nije određen —</option>
            </select>
        </div>

        <div style="border-top:1px solid var(--light2);padding-top:14px;margin-top:8px;">
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--blue);display:block;margin-bottom:8px;">Obaveštenje ekipi</label>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;">
                    <input type="radio" name="rs_obavesti" value="odmah" checked> Obavesti odmah
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;">
                    <input type="radio" name="rs_obavesti" value="zakazano"> Zakaži obaveštenje
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;">
                    <input type="radio" name="rs_obavesti" value="ne"> Ne obaveštavaj
                </label>
            </div>
            <div id="rs-obavesti-dt" style="display:none;margin-top:8px;">
                <input type="datetime-local" id="rs-obavesti-datetime"
                    style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 12px;font-size:13px;font-family:inherit;outline:none;background:var(--light);width:100%;">
            </div>
        </div>

        <div id="rs-err" style="color:#dc2626;font-size:12px;margin-top:8px;display:none;"></div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px;flex-wrap:wrap;">
            <button class="mail-cancel-btn" onclick="zatvoriRsModal()">Odustani</button>
            <button class="tim-add-btn" style="width:auto;padding:10px 16px;background:#64748b;" onclick="sacuvajStavku('nacrt')">💾 Snimi privremeno</button>
            <button class="tim-add-btn" style="width:auto;padding:10px 16px;" onclick="sacuvajStavku('objavljeno')">📢 Objavi</button>
        </div>
    </div>
</div>

<!-- MODAL: Preklapanje -->
<div id="rs-overlap-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:420px;box-shadow:0 24px 80px #000a;">
        <h3 style="font-size:15px;font-weight:700;color:#b45309;margin-bottom:12px;">⚠️ Preklapanje vremena</h3>
        <div id="rs-overlap-text" style="font-size:13px;color:var(--text);line-height:1.6;margin-bottom:20px;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button class="mail-cancel-btn" onclick="document.getElementById('rs-overlap-modal').style.display='none'">Odustani</button>
            <button class="tim-add-btn" style="width:auto;padding:10px 22px;background:#b45309;" onclick="potvrdiSacuvaj()">Ipak sačuvaj</button>
        </div>
    </div>
</div>

<!-- MODAL: Poruke -->
<div id="rs-poruke-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:500px;max-height:80vh;overflow-y:auto;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('rs-poruke-modal').style.display='none'; clearInterval(_rsPorukeInterval);" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 id="rs-poruke-naslov" style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:14px;padding-right:32px;"></h3>
        <div id="rs-poruke-lista" style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px;min-height:60px;"></div>
        <div style="display:flex;gap:8px;">
            <input type="text" id="rs-poruka-input" placeholder="Napiši poruku..."
                style="flex:1;border:1.5px solid var(--light2);border-radius:8px;padding:8px 12px;font-size:13px;outline:none;background:var(--light);"
                onkeydown="if(event.key==='Enter')posaljiRasporedPoruku()">
            <button type="button" class="btn-primary" onclick="posaljiRasporedPoruku()" style="padding:8px 16px;">→</button>
        </div>
    </div>
</div>

<style>
.rs-tabela-wrap { overflow-x:auto;border-radius:12px;border:1.5px solid var(--light2);background:#fff; }
.rs-tabela { width:100%;border-collapse:collapse;min-width:600px; }
.rs-tabela thead th { background:var(--light);padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--blue);border-bottom:1.5px solid var(--light2); }
.rs-tabela td { padding:8px 12px;border-bottom:1px solid var(--light2);vertical-align:middle;font-size:13px; }
.rs-tabela tbody tr:last-child td { border-bottom:none; }
.rs-red:hover td { background:#f8faff !important; }
.rs-el-radnik-red { display:flex;align-items:center;gap:8px;white-space:nowrap;margin-bottom:2px; }
.rs-el-ime-txt { font-size:12px; }
.rs-el-vreme-txt { font-size:11px;color:var(--muted);background:var(--light);border-radius:4px;padding:1px 6px; }
.rs-el-row { display:flex;align-items:center;justify-content:space-between;gap:10px;background:var(--light);border:1.5px solid var(--light2);border-radius:8px;padding:8px 12px;transition:border-color .15s; }
.rs-el-row.active { border-color:var(--bluem);background:#eff6ff; }
.rs-el-check { display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:500;flex:1; }
.rs-el-vreme { display:flex;align-items:center;gap:6px; }
.rs-el-name { flex:1;display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500; }
.rs-el-remove { background:none;border:none;color:#dc2626;cursor:pointer;font-size:15px;line-height:1;padding:4px 7px;border-radius:6px; }
.rs-el-remove:hover { background:#fee2e2; }

/* ── Nacrti (draft) blok ── */
.rs-nacrti { border:1.5px dashed #f59e0b;background:#fffbeb;border-radius:12px;padding:12px 14px;margin-bottom:16px; }
.rs-nacrti-head { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b45309;margin-bottom:10px; }
.rs-nacrti-dan { margin-bottom:12px; }
.rs-nacrti-dan:last-child { margin-bottom:0; }
.rs-nacrti-dan-head { display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:13px;font-weight:700;color:var(--blue);padding-bottom:6px;border-bottom:1px solid #fde68a;margin-bottom:6px; }
.rs-nacrt-red { display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;background:#fff;border:1px solid #fde68a;border-radius:8px;padding:8px 10px;margin-bottom:6px; }
.rs-nacrt-red:last-child { margin-bottom:0; }
.rs-nacrt-info { display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:13px;flex:1;min-width:0; }
.rs-grad-chip { border-radius:6px;padding:3px 10px;font-size:12px;font-weight:700;white-space:nowrap; }
.rs-nacrt-opis { color:var(--text); }
.rs-nacrt-ekipa { font-size:12px;color:var(--muted);display:flex;gap:8px;flex-wrap:wrap; }
.rs-nacrt-akcije { display:flex;gap:4px;align-items:center;white-space:nowrap; }
.rs-objavi-btn { background:#16a34a;color:#fff;border:none;border-radius:7px;padding:5px 12px;font-size:12px;font-weight:700;cursor:pointer; }
.rs-objavi-btn:hover { background:#15803d; }

/* ── Mobilni: nedeljni raspored kao kartice (bez horizontalnog skrola) ── */
@media (max-width:720px) {
  .rs-tabela-wrap { overflow-x:visible; border:none; background:transparent; border-radius:0; }
  .rs-tabela { display:block; width:100%; min-width:0; }
  .rs-tabela > thead { display:none; }
  .rs-tabela > tbody { display:block; }
  .rs-tabela > tbody > tr {
    display:block; background:#fff;
    border:1.5px solid var(--light2); border-radius:12px;
    margin-bottom:10px; padding:10px 14px;
  }
  .rs-tabela > tbody > tr > td {
    display:block; width:auto !important; padding:4px 0 !important;
    border:none !important; white-space:normal !important; vertical-align:top;
  }
  /* "Dan" ćelija (prepoznata po levoj obojenoj traci) — kao naslov kartice.
     Ovo gađa i prvu stavku dana (rowspan) i prazan dan, ali NE i gradilište/opis. */
  .rs-tabela > tbody > tr > td[style*="border-left:4px solid"] {
    margin:-10px -14px 8px !important;
    padding:8px 14px !important;
    border-radius:12px 12px 0 0 !important;
    border-bottom:1px solid var(--light2) !important;
  }
  /* Dugmići: levo poravnati na mobilnom */
  .rs-tabela > tbody > tr > td[style*="text-align:right"] { text-align:left !important; }
  .rs-tabela > tbody > tr > td[colspan] { color:var(--muted); font-style:italic; }
}
</style>

<script>
var _rsElektricari   = <?= $elektricari_json ?>;
var _rsStavkaId      = 0;
var _rsDanId         = null;
var _rsPorukStavkaId = 0;
var _rsPorukeInterval = null;
var _rsPendingData   = null;
var _rsVremena       = {};

function osveziDodajMeni() {
    var sel = document.getElementById('rs-dodaj-radnika');
    var dodati = {};
    document.querySelectorAll('#rs-ekipa-lista .rs-el-row').forEach(function(r) { dodati[r.dataset.id] = true; });
    sel.innerHTML = '<option value="">+ Dodaj radnika</option>';
    _rsElektricari.forEach(function(e) {
        if (!dodati[e.id]) {
            var o = document.createElement('option');
            o.value = e.id;
            o.textContent = '👷 ' + e.ime;
            sel.appendChild(o);
        }
    });
}

function dodajRadnika(eid, vod, vdo) {
    eid = parseInt(eid);
    if (!eid || document.getElementById('rs-el-' + eid)) return;
    var e = _rsElektricari.find(function(x) { return x.id == eid; });
    if (!e) return;
    if (!vod) vod = (_rsVremena[eid] ? _rsVremena[eid].substring(0,5) : '07:00');
    if (!vdo) vdo = '16:00';

    var row = document.createElement('div');
    row.className = 'rs-el-row active';
    row.id = 'rs-el-' + eid;
    row.dataset.id = eid;

    var name = document.createElement('span');
    name.className = 'rs-el-name';
    name.textContent = '👷 ' + e.ime;

    var vreme = document.createElement('div');
    vreme.className = 'rs-el-vreme';
    vreme.innerHTML =
        '<input type="time" id="rs-vod-' + eid + '" value="' + vod + '" style="border:1.5px solid var(--light2);border-radius:6px;padding:4px 8px;font-size:12px;outline:none;background:var(--light);">' +
        '<span style="font-size:12px;color:var(--muted);">–</span>' +
        '<input type="time" id="rs-vdo-' + eid + '" value="' + vdo + '" style="border:1.5px solid var(--light2);border-radius:6px;padding:4px 8px;font-size:12px;outline:none;background:var(--light);">';

    var rm = document.createElement('button');
    rm.type = 'button';
    rm.className = 'rs-el-remove';
    rm.title = 'Ukloni';
    rm.textContent = '✕';
    rm.onclick = function() { ukloniRadnika(eid); };

    row.appendChild(name);
    row.appendChild(vreme);
    row.appendChild(rm);
    document.getElementById('rs-ekipa-lista').appendChild(row);

    osveziDodajMeni();
    azurirajOdgovornaSelect();
}

function ukloniRadnika(eid) {
    var row = document.getElementById('rs-el-' + eid);
    if (row) row.remove();
    osveziDodajMeni();
    azurirajOdgovornaSelect();
}

function azurirajOdgovornaSelect() {
    var wrap = document.getElementById('rs-odgovoran-wrap');
    var sel  = document.getElementById('rs-odgovoran-id');
    var trenutni = sel.value;

    sel.innerHTML = '<option value="">— Niko nije određen —</option>';
    var rows = document.querySelectorAll('#rs-ekipa-lista .rs-el-row');
    rows.forEach(function(r) {
        var e = _rsElektricari.find(function(x) { return x.id == r.dataset.id; });
        if (e) {
            var opt = document.createElement('option');
            opt.value = e.id;
            opt.textContent = '👷 ' + e.ime;
            sel.appendChild(opt);
        }
    });

    if (trenutni && document.getElementById('rs-el-' + trenutni)) {
        sel.value = trenutni;
    }

    wrap.style.display = rows.length > 0 ? 'block' : 'none';
}

function resetModal() {
    document.getElementById('rs-gradiliste').selectedIndex = 0;
    document.getElementById('rs-opis').value = '';
    document.getElementById('rs-err').style.display = 'none';
    document.getElementById('rs-obavesti-dt').style.display = 'none';
    document.querySelector('input[name="rs_obavesti"][value="odmah"]').checked = true;
    document.getElementById('rs-odgovoran-id').value = '';
    document.getElementById('rs-odgovoran-wrap').style.display = 'none';
    _rsVremena = {};
    document.getElementById('rs-ekipa-lista').innerHTML = '';
    osveziDodajMeni();
}

document.querySelectorAll('input[name="rs_obavesti"]').forEach(function(r) {
    r.addEventListener('change', function() {
        document.getElementById('rs-obavesti-dt').style.display = this.value === 'zakazano' ? 'block' : 'none';
    });
});

function openDodajStavku(datum, danId, danIndex) {
    _rsStavkaId = 0;
    _rsDanId    = danId;
    document.getElementById('rs-modal-title').textContent = 'Nova stavka';
    document.getElementById('rs-stavka-id').value = '0';
    document.getElementById('rs-dan-id').value    = danId || '';
    document.getElementById('rs-dan-index').value = danIndex;
    resetModal();

    if (!danId) {
        var fd = new FormData();
        fd.append('_action', 'raspored_init_nedelja');
        fd.append('datum_od', '<?= $ponedeljak ?>');
        fd.append('datum_do', '<?= $subota ?>');
        fd.append('id', 0);
        fetch('', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.ok) location.reload(); });
        return;
    }

    var fd = new FormData();
    fd.append('_action', 'raspored_vreme_elektricara');
    fd.append('dan_id', danId);
    fd.append('id', 0);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.ok && d.vremena) {
            _rsVremena = d.vremena;
        }
    });

    document.getElementById('rs-modal').style.display = 'flex';
}

function openIzmeniStavku(stavkaId, danIndex) {
    _rsStavkaId = stavkaId;
    document.getElementById('rs-modal-title').textContent = 'Izmeni stavku';
    document.getElementById('rs-stavka-id').value  = stavkaId;
    document.getElementById('rs-dan-index').value  = danIndex;
    resetModal();

    var fd = new FormData();
    fd.append('_action', 'raspored_get_stavku');
    fd.append('id', stavkaId);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(s => {
        document.getElementById('rs-gradiliste').value = s.gradiliste_id || '';
        document.getElementById('rs-opis').value      = s.opis || '';
        document.getElementById('rs-dan-id').value    = s.dan_id || '';

        (s.radnici || []).forEach(function(r) {
            dodajRadnika(
                r.radnik_id,
                r.vreme_od ? r.vreme_od.substring(0,5) : '07:00',
                r.vreme_do ? r.vreme_do.substring(0,5) : '16:00'
            );
        });

        // Postavi odgovornog nakon što su radnici dodati
        azurirajOdgovornaSelect();
        if (s.odgovoran_id) {
            document.getElementById('rs-odgovoran-id').value = s.odgovoran_id;
        }

        document.getElementById('rs-modal').style.display = 'flex';
    });
}

function skupiRadnike() {
    var radnici = [];
    document.querySelectorAll('#rs-ekipa-lista .rs-el-row').forEach(function(row) {
        var eid = row.dataset.id;
        radnici.push({
            id:       parseInt(eid),
            vreme_od: (document.getElementById('rs-vod-' + eid) || {}).value || '07:00',
            vreme_do: (document.getElementById('rs-vdo-' + eid) || {}).value || '16:00',
        });
    });
    return radnici;
}

function sacuvajStavku(status) {
    status = (status === 'nacrt') ? 'nacrt' : 'objavljeno';
    var stavkaId = parseInt(document.getElementById('rs-stavka-id').value);
    var danId    = document.getElementById('rs-dan-id').value;
    var err      = document.getElementById('rs-err');
    err.style.display = 'none';

    var radnici = skupiRadnike();
    if (radnici.length === 0) {
        err.textContent = 'Dodajte bar jednog radnika.';
        err.style.display = 'block';
        return;
    }

    var obavesti_tip = document.querySelector('input[name="rs_obavesti"]:checked').value;
    var obavesti_at  = document.getElementById('rs-obavesti-datetime').value;

    var fd = new FormData();
    fd.append('_action', stavkaId ? 'raspored_izmeni_stavku' : 'raspored_dodaj_stavku');
    fd.append('id', stavkaId || 0);
    fd.append('dan_id', danId);
    fd.append('gradiliste_id', document.getElementById('rs-gradiliste').value);
    fd.append('opis', document.getElementById('rs-opis').value);
    fd.append('radnici_json', JSON.stringify(radnici));
    fd.append('obavesti_tip', obavesti_tip);
    fd.append('obavesti_at', obavesti_at);
    fd.append('odgovoran_id', document.getElementById('rs-odgovoran-id').value || 0);
    fd.append('status', status);

    _rsPendingData = fd;

    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) { err.textContent = d.err || 'Greška.'; err.style.display = 'block'; return; }

        if (d.upozorenja && d.upozorenja.length > 0) {
            var html = '';
            d.upozorenja.forEach(function(u) {
                html += '<strong>👷 ' + u.ime + '</strong> već ima zadatak u periodu ' + u.preklapanje + '<br>';
            });
            html += '<br>Da li svejedno želiš da sačuvaš?';
            document.getElementById('rs-overlap-text').innerHTML = html;
            document.getElementById('rs-overlap-modal').style.display = 'flex';
        } else {
            document.getElementById('rs-modal').style.display = 'none';
            location.reload();
        }
    });
}

function potvrdiSacuvaj() {
    document.getElementById('rs-overlap-modal').style.display = 'none';
    document.getElementById('rs-modal').style.display = 'none';
    location.reload();
}

function obrisiStavku(id) {
    if (!confirm('Obrisati ovu stavku? Ekipa će biti obaveštena o otkazu.')) return;
    var fd = new FormData();
    fd.append('_action', 'raspored_obrisi_stavku');
    fd.append('id', id);
    fetch('', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if (d.ok) location.reload(); });
}

function objaviStavku(id) {
    if (!confirm('Objaviti ovaj nacrt? Ekipa će biti obaveštena.')) return;
    var fd = new FormData();
    fd.append('_action', 'raspored_objavi_stavku');
    fd.append('id', id);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); else alert(d.err || 'Greška.'); });
}

function objaviDan(danId, broj) {
    if (!confirm('Objaviti sve nacrte (' + broj + ') za ovaj dan? Cela ekipa će biti obaveštena.')) return;
    var fd = new FormData();
    fd.append('_action', 'raspored_objavi_dan');
    fd.append('dan_id', danId);
    fd.append('id', 0);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); else alert(d.err || 'Greška.'); });
}

function initNedelja() {
    var fd = new FormData();
    fd.append('_action', 'raspored_init_nedelja');
    fd.append('datum_od', '<?= $ponedeljak ?>');
    fd.append('datum_do', '<?= $subota ?>');
    fd.append('id', 0);
    fetch('', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if (d.ok) location.reload(); });
}

function toggleNedeljeDropdown() {
    var dd = document.getElementById('nedelje-dropdown');
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    var dd = document.getElementById('nedelje-dropdown');
    if (dd && !dd.contains(e.target) && !e.target.closest('[onclick="toggleNedeljeDropdown()"]')) {
        dd.style.display = 'none';
    }
});

function obrisiNedelju(nedeljaId) {
    if (!confirm('Obrisati celu nedelju sa svim zadacima? Ova radnja se ne može poništiti.')) return;
    var fd = new FormData();
    fd.append('_action', 'raspored_obrisi_nedelju');
    fd.append('id', nedeljaId);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.ok) window.location.href = '?page=raspored';
        else alert(d.err || 'Greška.');
    });
}

function kopirajSledecu(nedeljaId) {
    if (!confirm('Kopirati trenutni raspored u sledeću nedelju?')) return;
    var fd = new FormData();
    fd.append('_action', 'raspored_kopiraj');
    fd.append('id', nedeljaId);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.ok) window.location.href = d.redirect;
        else { alert(d.err || 'Greška.'); }
    });
}

function openPorukeStavke(stavkaId, naslov) {
    _rsPorukStavkaId = stavkaId;
    document.getElementById('rs-poruke-naslov').textContent = naslov;
    document.getElementById('rs-poruka-input').value = '';
    document.getElementById('rs-poruke-modal').style.display = 'flex';
    ucitajRasporedPoruke(stavkaId);
    var fdv = new FormData();
    fdv.append('_action', 'raspored_oznaci_vidjeno');
    fdv.append('stavka_id', stavkaId);
    fdv.append('id', 0);
    fetch('', { method: 'POST', body: fdv });
    var btn = document.getElementById('rs-pb-' + stavkaId);
    if (btn) {
        var spans = btn.querySelectorAll('span');
        spans.forEach(function(sp) {
            if (sp.style.background === 'rgb(229, 57, 53)' || sp.style.background === '#e53935') {
                sp.style.display = 'none';
            }
        });
    }
    clearInterval(_rsPorukeInterval);
    _rsPorukeInterval = setInterval(function() { ucitajRasporedPoruke(stavkaId); }, 10000);
}

function ucitajRasporedPoruke(stavkaId) {
    var fd = new FormData();
    fd.append('_action', 'raspored_poruke_get');
    fd.append('stavka_id', stavkaId);
    fd.append('id', 0);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        var lista = document.getElementById('rs-poruke-lista');
        lista.innerHTML = '';
        if (!d.poruke || !d.poruke.length) {
            lista.innerHTML = '<p style="color:var(--muted);font-size:13px;text-align:center;">Nema poruka.</p>';
            return;
        }
        d.poruke.forEach(function(p) {
            var div = document.createElement('div');
            div.style.cssText = 'padding:8px 12px;border-radius:8px;font-size:13px;max-width:85%;' +
                (p.moja ? 'background:var(--blue);color:#fff;margin-left:auto;' : 'background:var(--light);');
            div.innerHTML = '<strong style="font-size:11px;opacity:.8;">' + p.autor + '</strong><br>' + p.sadrzaj.replace(/\n/g,'<br>');
            lista.appendChild(div);
        });
        lista.scrollTop = lista.scrollHeight;
    });
}

function posaljiRasporedPoruku() {
    var sadrzaj = document.getElementById('rs-poruka-input').value.trim();
    if (!sadrzaj) return;
    var fd = new FormData();
    fd.append('_action', 'raspored_poruka_add');
    fd.append('stavka_id', _rsPorukStavkaId);
    fd.append('id', 0);
    fd.append('sadrzaj', sadrzaj);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => { if (d.ok) { document.getElementById('rs-poruka-input').value = ''; ucitajRasporedPoruke(_rsPorukStavkaId); } });
}
// ── Glasovni unos ────────────────────────────────────────────
var _micRec = null;

function zatvoriRsModal() {
    if (_micRec) { try { _micRec.stop(); } catch(e) {} _micRec = null; }
    document.getElementById('rs-modal').style.display = 'none';
}

function startMic(targetId, btn) {
    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        alert('Glasovni unos nije podržan. Koristi Chrome ili Safari.');
        return;
    }
    if (_micRec) { _micRec.stop(); return; }

    var textarea  = document.getElementById(targetId);
    var origStyle = btn.style.cssText;
    var aktivan   = true;

    btn.textContent = '⏹';
    btn.style.background = '#fee2e2';
    btn.style.color = '#dc2626';
    btn.style.borderColor = '#fca5a5';

    var _aktivniRec = null;

    function novaSesija() {
        var rec = new SpeechRecognition();
        rec.lang = 'sr-RS';
        rec.continuous = false;
        rec.interimResults = false;
        _aktivniRec = rec;

        rec.onresult = function(e) {
            var nov = e.results[0][0].transcript.trim();
            var stari = textarea.value.trim();
            textarea.value = stari ? stari + ' ' + nov : nov;
        };

        rec.onerror = function(e) {
            if (e.error === 'no-speech') { if (aktivan) novaSesija(); return; }
            if (e.error !== 'aborted') alert('Greška mikrofona: ' + e.error);
            aktivan = false;
            _micRec = null; _aktivniRec = null;
            resetMicBtn(btn, origStyle);
        };

        rec.onend = function() {
            _aktivniRec = null;
            _micRec = null;
            if (aktivan) novaSesija();
            else resetMicBtn(btn, origStyle);
        };

        try { rec.start(); } catch(e) {}
    }

    _micRec = {
        stop: function() {
            aktivan = false;
            if (_aktivniRec) { try { _aktivniRec.stop(); } catch(e) {} }
            else resetMicBtn(btn, origStyle);
        }
    };
    novaSesija();
}

function resetMicBtn(btn, origStyle) {
    _micRec = null;
    btn.textContent = '🎤';
    btn.style.cssText = origStyle;
}
</script>
