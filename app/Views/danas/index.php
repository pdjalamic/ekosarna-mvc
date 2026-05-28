<?php
$dani_nazivi = ['Ned','Pon','Uto','Sri','Čet','Pet','Sub'];
$dani_puni   = ['Nedjelja','Ponedeljak','Utorak','Sreda','Četvrtak','Petak','Subota'];
$gradilista_json = json_encode($gradilista ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?: '[]';
?>

<div class="topbar-admin">
    <div class="page-title">📅 Moj raspored</div>
</div>

<div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
    <a href="?page=danas&datum=<?= date('Y-m-d', strtotime($datum . ' -1 day')) ?>" class="btn-sm">←</a>
    <form method="GET" style="display:flex;align-items:center;gap:6px;">
        <input type="hidden" name="page" value="danas">
        <input type="date" name="datum" value="<?= h($datum) ?>"
               onchange="this.form.submit()"
               style="border:1.5px solid var(--light2);border-radius:8px;padding:6px 10px;font-size:13px;outline:none;background:#fff;">
    </form>
    <a href="?page=danas&datum=<?= date('Y-m-d', strtotime($datum . ' +1 day')) ?>" class="btn-sm">→</a>
    <?php if ($datum !== date('Y-m-d')): ?>
    <a href="?page=danas" class="btn-sm mark">Danas</a>
    <?php endif; ?>
</div>

<!-- Slobodan unos -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
    <button onclick="openSlobodanUnos('vreme')" class="btn-sm"
        style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;font-size:12px;">
        🕐 Slobodan unos vremena
    </button>
    <button onclick="openSlobodanUnos('materijal')" class="btn-sm"
        style="background:#fffbeb;color:#b45309;border-color:#fde68a;font-size:12px;">
        📦 Slobodan unos materijala
    </button>
</div>

<div style="display:flex;flex-direction:column;gap:14px;">
<?php foreach ($datumi as $idx => $d):
    $stavke    = $dani[$d] ?? [];
    $jeDanas   = ($d === date('Y-m-d'));
    $jeIzabran = ($d === $datum);
    $dow       = (int)date('w', strtotime($d));
    $boja_dana = $jeIzabran ? '#1a3a6e' : ($jeDanas ? '#2563eb' : '#94a3b8');
?>
<div style="border-radius:14px;border:2px solid <?= $boja_dana ?><?= $jeIzabran ? '' : '44' ?>;overflow:hidden;">
    <div style="background:<?= $boja_dana ?><?= $jeIzabran ? '' : '18' ?>;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;">
        <div>
            <span style="font-size:14px;font-weight:700;color:<?= $jeIzabran ? '#fff' : $boja_dana ?>;"><?= $dani_puni[$dow] ?></span>
            <span style="font-size:12px;color:<?= $jeIzabran ? '#ffffffaa' : '#64748b' ?>;margin-left:8px;"><?= date('d.m.Y', strtotime($d)) ?></span>
        </div>
        <?php if ($jeDanas): ?>
        <span style="background:#fff;color:#2563eb;border-radius:20px;font-size:10px;font-weight:700;padding:2px 10px;">DANAS</span>
        <?php endif; ?>
    </div>
    <div style="padding:10px 12px;background:#fff;display:flex;flex-direction:column;gap:8px;">
    <?php if (empty($stavke)): ?>
        <p style="color:var(--muted);font-size:13px;font-style:italic;padding:4px 0;margin:0;">Nema zadataka</p>
    <?php else: ?>
        <?php foreach ($stavke as $s):
            $jeOdgovoran = ((int)($s['odgovoran_id'] ?? 0) === (int)$uid);
        ?>
        <div class="danas-stavka" style="border-left:4px solid <?= h($s['boja']) ?>;margin:0;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <div>
                    <?php if ($s['vreme_od'] || $s['vreme_do']): ?>
                    <div style="font-size:12px;font-weight:700;color:var(--blue);margin-bottom:2px;">
                        🕐 <?= $s['vreme_od'] ? substr($s['vreme_od'],0,5) : '' ?>
                        <?= $s['vreme_od'] && $s['vreme_do'] ? ' – ' : '' ?>
                        <?= $s['vreme_do'] ? substr($s['vreme_do'],0,5) : '' ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($s['gradiliste_naziv']): ?>
                    <div style="font-size:14px;font-weight:700;color:var(--blue);">🏗️ <?= h($s['gradiliste_naziv']) ?></div>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:5px;flex-shrink:0;">
                    <button type="button" class="btn-sm" id="dp-btn-<?= $s['id'] ?>"
                        onclick="openDanasThread(<?= $s['id'] ?>, '<?= h(addslashes($s['gradiliste_naziv'] ?? 'Stavka')) ?>')"
                        title="Poruke" style="position:relative;">
                        💬<?php if ($s['poruka_count'] > 0): ?><span style="background:#6b7280;color:#fff;border-radius:99px;font-size:10px;padding:1px 5px;margin-left:3px;font-weight:700;"><?= $s['poruka_count'] ?></span><?php endif; ?><?php if (!empty($s['nova_poruka'])): ?><span style="background:#e53935;color:#fff;border-radius:99px;font-size:10px;padding:1px 5px;margin-left:2px;font-weight:700;"><?= $s['nove_poruke_count'] ?></span><?php endif; ?>
                    </button>
                    <button type="button" class="btn-sm" title="Upiši vreme"
                        onclick="openDanasVreme(<?= $s['id'] ?>, '<?= h(addslashes($s['gradiliste_naziv'] ?? 'Stavka')) ?>')"
                        style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">🕐</button>
                    <?php if ($jeOdgovoran): ?>
                    <button type="button" class="btn-sm" title="Upiši materijal"
                        onclick="openDanasMaterijal(<?= $s['id'] ?>, '<?= h(addslashes($s['gradiliste_naziv'] ?? 'Stavka')) ?>')"
                        style="background:#fffbeb;color:#b45309;border-color:#fde68a;">📦</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($s['opis']): ?>
            <div style="font-size:13px;color:#333;line-height:1.5;background:#f8faff;border-radius:6px;padding:8px 10px;"><?= nl2br(h($s['opis'])) ?></div>
            <?php endif; ?>
            <?php if ($jeOdgovoran): ?>
            <div style="margin-top:6px;font-size:11px;color:#b45309;background:#fffbeb;border-radius:5px;padding:3px 9px;display:inline-block;">📦 Ti si odgovoran za unos materijala</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- MODAL: Thread poruka -->
<div id="danas-thread" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:flex-end;justify-content:center;">
    <div style="background:#fff;border-radius:16px 16px 0 0;padding:20px;width:100%;max-width:600px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 -8px 40px #0005;position:relative;">
        <button onclick="zatvoriSveModale()" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 id="danas-thread-naslov" style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:14px;padding-right:32px;"></h3>
        <div id="danas-thread-poruke" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:8px;margin-bottom:12px;min-height:80px;"></div>
        <div style="display:flex;gap:8px;">
            <input type="text" id="danas-thread-input" placeholder="Napiši poruku ili pitanje..."
                style="flex:1;border:1.5px solid var(--light2);border-radius:8px;padding:9px 12px;font-size:14px;outline:none;background:var(--light);"
                onkeydown="if(event.key==='Enter')posaljiDanasPoruku()">
            <button type="button" class="btn-primary" onclick="posaljiDanasPoruku()" style="padding:9px 16px;font-size:18px;">→</button>
        </div>
    </div>
</div>

<!-- MODAL: Upiši vreme -->
<div id="danas-vreme-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:flex-end;justify-content:center;">
    <div style="background:#fff;border-radius:16px 16px 0 0;padding:20px;width:100%;max-width:600px;max-height:85vh;overflow-y:auto;box-shadow:0 -8px 40px #0005;position:relative;">
        <button onclick="zatvoriSveModale()" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 id="danas-vreme-naslov" style="font-size:15px;font-weight:700;color:#1d4ed8;margin-bottom:4px;padding-right:32px;">🕐 Upiši vreme</h3>
        <p style="font-size:12px;color:var(--muted);margin:0 0 12px;">Napiši slobodnim tekstom koliko si radio i šta.</p>

        <!-- Gradilište (samo za slobodan unos) -->
        <div id="vreme-gradiliste-wrap" style="display:none;margin-bottom:12px;">
            <label style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:6px;">Gradilište</label>
            <select id="vreme-gradiliste-sel" onchange="gradilisteSelectChange('vreme')"
                style="width:100%;border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);margin-bottom:6px;">
                <option value="">— Izaberi gradilište —</option>
                <?php foreach ($gradilista as $g): ?>
                <option value="<?= $g['id'] ?>" data-naziv="<?= h($g['naziv']) ?>"><?= h($g['naziv']) ?></option>
                <?php endforeach; ?>
                <option value="rucno">— Upiši ručno —</option>
            </select>
            <input type="text" id="vreme-gradiliste-rucno" placeholder="Naziv gradilišta..."
                style="display:none;width:100%;border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);box-sizing:border-box;">
        </div>

        <div id="danas-vreme-input-wrap">
            <textarea id="danas-vreme-tekst" rows="4"
                placeholder="npr. od 7 do 15h, kabliranje rasvete na spratu"
                style="border:1.5px solid var(--light2);border-radius:8px;padding:9px 13px;font-size:14px;font-family:inherit;outline:none;background:var(--light);width:100%;resize:vertical;box-sizing:border-box;"></textarea>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;">
                <button onclick="zatvoriSveModale()" class="mail-cancel-btn">Odustani</button>
                <button onclick="danasAiAnaliziraj('vreme')" class="btn-primary" id="btn-vreme-analiziraj">🤖 Analiziraj</button>
            </div>
        </div>
        <div id="danas-vreme-preview" style="display:none;"></div>
    </div>
</div>

<!-- MODAL: Upiši materijal -->
<div id="danas-mat-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:flex-end;justify-content:center;">
    <div style="background:#fff;border-radius:16px 16px 0 0;padding:20px;width:100%;max-width:600px;max-height:85vh;overflow-y:auto;box-shadow:0 -8px 40px #0005;position:relative;">
        <button onclick="zatvoriSveModale()" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 id="danas-mat-naslov" style="font-size:15px;font-weight:700;color:#b45309;margin-bottom:4px;padding-right:32px;">📦 Upiši materijal</h3>
        <p style="font-size:12px;color:var(--muted);margin:0 0 12px;">Napiši slobodnim tekstom šta je potrošeno.</p>

        <!-- Gradilište (samo za slobodan unos) -->
        <div id="mat-gradiliste-wrap" style="display:none;margin-bottom:12px;">
            <label style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:6px;">Gradilište</label>
            <select id="mat-gradiliste-sel" onchange="gradilisteSelectChange('mat')"
                style="width:100%;border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);margin-bottom:6px;">
                <option value="">— Izaberi gradilište —</option>
                <?php foreach ($gradilista as $g): ?>
                <option value="<?= $g['id'] ?>" data-naziv="<?= h($g['naziv']) ?>"><?= h($g['naziv']) ?></option>
                <?php endforeach; ?>
                <option value="rucno">— Upiši ručno —</option>
            </select>
            <input type="text" id="mat-gradiliste-rucno" placeholder="Naziv gradilišta..."
                style="display:none;width:100%;border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);box-sizing:border-box;">
        </div>

        <div id="danas-mat-input-wrap">
            <textarea id="danas-mat-tekst" rows="5"
                placeholder="npr. kabal 3x1.5 n2xh 69m, kabal 5x1.5 n2xh 125m, buzir hf f16 130m"
                style="border:1.5px solid var(--light2);border-radius:8px;padding:9px 13px;font-size:14px;font-family:inherit;outline:none;background:var(--light);width:100%;resize:vertical;box-sizing:border-box;"></textarea>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;">
                <button onclick="zatvoriSveModale()" class="mail-cancel-btn">Odustani</button>
                <button onclick="danasAiAnaliziraj('materijal')" class="btn-primary" id="btn-mat-analiziraj">🤖 Analiziraj</button>
            </div>
        </div>
        <div id="danas-mat-preview" style="display:none;"></div>
    </div>
</div>

<style>
.danas-stavka { background:#fff;border-radius:10px;border:1.5px solid var(--light2);padding:10px 12px; }
.danas-ai-preview { background:#fff;border:2px solid #2563eb;border-radius:10px;padding:14px 16px; }
.danas-ai-row { display:flex;align-items:flex-start;gap:10px;padding:6px 0;border-top:1px solid var(--light2);font-size:13px; }
.danas-ai-row:first-child { border-top:none; }
.danas-ai-col { flex-direction:column;gap:2px; }
.danas-ai-label { min-width:75px;font-weight:700;font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em; }
.danas-ai-tag { background:var(--light);border:1px solid var(--light2);border-radius:5px;padding:2px 8px;font-size:12px;display:inline-block;margin:2px 3px 2px 0; }
.danas-ai-akcije { display:flex;justify-content:flex-end;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid var(--light2); }
</style>

<script>
var _danasStavkaId        = 0;
var _danasPorukeInterval  = null;
var _danasAktivniStavkaId = 0;
var _slobodanUnos         = false; // true = slobodan unos, false = vezan za stavku
var _danasAiData = null;
var _gradilista           = <?= $gradilista_json ?>;

// ── Slobodan unos ────────────────────────────────────────────
function openSlobodanUnos(tip) {
    _slobodanUnos         = true;
    _danasAktivniStavkaId = 0;

    var wrapId   = tip === 'vreme' ? 'vreme-gradiliste-wrap' : 'mat-gradiliste-wrap';
    var modalId  = tip === 'vreme' ? 'danas-vreme-modal'     : 'danas-mat-modal';
    var naslovId = tip === 'vreme' ? 'danas-vreme-naslov'     : 'danas-mat-naslov';
    var tekstId  = tip === 'vreme' ? 'danas-vreme-tekst'      : 'danas-mat-tekst';
    var prevId   = tip === 'vreme' ? 'danas-vreme-preview'    : 'danas-mat-preview';
    var wrapInpId= tip === 'vreme' ? 'danas-vreme-input-wrap' : 'danas-mat-input-wrap';

    document.getElementById(wrapId).style.display   = 'block';
    document.getElementById(naslovId).textContent   = tip === 'vreme' ? '🕐 Slobodan unos vremena' : '📦 Slobodan unos materijala';
    document.getElementById(tekstId).value          = '';
    document.getElementById(prevId).style.display   = 'none';
    document.getElementById(prevId).innerHTML       = '';
    document.getElementById(wrapInpId).style.display = 'block';
    document.getElementById(modalId).style.display  = 'flex';

    // Učitaj poslednje gradilište
    ucitajGradilistePreferencu(tip);
}

function ucitajGradilistePreferencu(tip) {
    var fd = new FormData();
    fd.append('_action', 'danas_ucitaj_preferencu');
    fd.append('kljuc', 'slobodan_gradiliste_id');
    fd.append('id', 0);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.ok && d.vrednost) {
            var selId = tip === 'vreme' ? 'vreme-gradiliste-sel' : 'mat-gradiliste-sel';
            var sel = document.getElementById(selId);
            if (sel) sel.value = d.vrednost;
        }
    }).catch(function(){});
}

function gradilisteSelectChange(tip) {
    var selId    = tip === 'vreme' ? 'vreme-gradiliste-sel'   : 'mat-gradiliste-sel';
    var rucnoId  = tip === 'vreme' ? 'vreme-gradiliste-rucno' : 'mat-gradiliste-rucno';
    var sel = document.getElementById(selId);
    document.getElementById(rucnoId).style.display = sel.value === 'rucno' ? 'block' : 'none';
}

function getGradilisteZaUnos(tip) {
    if (!_slobodanUnos) return { id: 0, naziv: '' };
    var selId   = tip === 'vreme' ? 'vreme-gradiliste-sel'   : 'mat-gradiliste-sel';
    var rucnoId = tip === 'vreme' ? 'vreme-gradiliste-rucno' : 'mat-gradiliste-rucno';
    var sel = document.getElementById(selId);
    if (!sel) return { id: 0, naziv: '' };
    if (sel.value === 'rucno') {
        return { id: 0, naziv: document.getElementById(rucnoId).value.trim() };
    }
    if (sel.value) {
        var opt = sel.options[sel.selectedIndex];
        return { id: parseInt(sel.value), naziv: opt.dataset.naziv || opt.textContent };
    }
    return { id: 0, naziv: '' };
}

// ── Poruke ───────────────────────────────────────────────────
function openDanasThread(stavkaId, naslov) {
    _slobodanUnos  = false;
    _danasStavkaId = stavkaId;
    document.getElementById('danas-thread-naslov').textContent = naslov;
    document.getElementById('danas-thread-input').value = '';
    document.getElementById('danas-thread').style.display = 'flex';
    ucitajDanasPoruke(stavkaId);
    var fdv = new FormData(); fdv.append('_action','raspored_oznaci_vidjeno'); fdv.append('stavka_id',stavkaId); fdv.append('id',0);
    fetch('', { method:'POST', body:fdv });
    clearInterval(_danasPorukeInterval);
    _danasPorukeInterval = setInterval(function() { ucitajDanasPoruke(stavkaId); }, 10000);
}

function ucitajDanasPoruke(stavkaId) {
    var fd = new FormData(); fd.append('_action','danas_poruke_get'); fd.append('stavka_id',stavkaId); fd.append('id',0);
    fetch('', { method:'POST', body:fd }).then(r=>r.json()).then(d => {
        var wrap = document.getElementById('danas-thread-poruke');
        wrap.innerHTML = '';
        if (!d.poruke || !d.poruke.length) { wrap.innerHTML='<p style="color:var(--muted);font-size:13px;text-align:center;padding:20px 0;">Nema poruka.</p>'; return; }
        d.poruke.forEach(function(p) {
            var div = document.createElement('div');
            div.style.cssText='padding:9px 13px;border-radius:10px;font-size:14px;max-width:85%;line-height:1.5;'+(p.moja?'background:var(--blue);color:#fff;margin-left:auto;':'background:var(--light);color:var(--text);');
            div.innerHTML='<strong style="font-size:11px;opacity:.75;display:block;margin-bottom:2px;">'+p.autor+'</strong>'+p.sadrzaj.replace(/\n/g,'<br>');
            wrap.appendChild(div);
        });
        wrap.scrollTop = wrap.scrollHeight;
    });
}

function posaljiDanasPoruku() {
    var sadrzaj = document.getElementById('danas-thread-input').value.trim();
    if (!sadrzaj) return;
    var fd = new FormData(); fd.append('_action','danas_poruka_add'); fd.append('stavka_id',_danasStavkaId); fd.append('id',0); fd.append('sadrzaj',sadrzaj);
    fetch('', { method:'POST', body:fd }).then(r=>r.json()).then(d => { if(d.ok) { document.getElementById('danas-thread-input').value=''; ucitajDanasPoruke(_danasStavkaId); } });
}

// ── Vreme / Materijal (vezan za stavku) ──────────────────────
function openDanasVreme(stavkaId, naslov) {
    _slobodanUnos = false;
    _danasAktivniStavkaId = stavkaId;
    document.getElementById('vreme-gradiliste-wrap').style.display = 'none';
    document.getElementById('danas-vreme-naslov').textContent = '🕐 ' + naslov;
    document.getElementById('danas-vreme-tekst').value = '';
    document.getElementById('danas-vreme-preview').style.display = 'none';
    document.getElementById('danas-vreme-preview').innerHTML = '';
    document.getElementById('danas-vreme-input-wrap').style.display = 'block';
    document.getElementById('danas-vreme-modal').style.display = 'flex';
}

function openDanasMaterijal(stavkaId, naslov) {
    _slobodanUnos = false;
    _danasAktivniStavkaId = stavkaId;
    document.getElementById('mat-gradiliste-wrap').style.display = 'none';
    document.getElementById('danas-mat-naslov').textContent = '📦 ' + naslov;
    document.getElementById('danas-mat-tekst').value = '';
    document.getElementById('danas-mat-preview').style.display = 'none';
    document.getElementById('danas-mat-preview').innerHTML = '';
    document.getElementById('danas-mat-input-wrap').style.display = 'block';
    document.getElementById('danas-mat-modal').style.display = 'flex';
}

// ── AI Parse ─────────────────────────────────────────────────
function danasAiAnaliziraj(tip) {
    var tekst = document.getElementById(tip==='vreme'?'danas-vreme-tekst':'danas-mat-tekst').value.trim();
    if (!tekst) { alert('Unesi tekst pre analize.'); return; }

    var btn = document.getElementById(tip==='vreme'?'btn-vreme-analiziraj':'btn-mat-analiziraj');
    btn.disabled = true; btn.textContent = '⏳ Analiziram...';

    var fd = new FormData();
    fd.append('_action', 'danas_ai_parse');
    fd.append('stavka_id', _danasAktivniStavkaId);
    fd.append('tekst', tekst);
    fd.append('tip', tip);
    fd.append('id', 0);

    fetch('', { method:'POST', body:fd })
    .then(r=>r.json())
    .then(d => {
        btn.disabled = false; btn.textContent = '🤖 Analiziraj';
        if (!d.ok) { alert('Greška: '+(d.err||'Nepoznata')); return; }
        danasAiPrikaziPreview(tip, d.data);
    })
    .catch(() => { btn.disabled=false; btn.textContent='🤖 Analiziraj'; alert('Mrežna greška.'); });
}

function danasAiPrikaziPreview(tip, data) {
    document.getElementById(tip==='vreme'?'danas-vreme-input-wrap':'danas-mat-input-wrap').style.display = 'none';
    var wrap = document.getElementById(tip==='vreme'?'danas-vreme-preview':'danas-mat-preview');
    wrap.style.display = 'block';

    var html = '<div class="danas-ai-preview">';
    html += '<div style="font-weight:700;font-size:14px;margin-bottom:10px;">'+(tip==='vreme'?'🕐 Proveri vreme':'📦 Proveri materijal')+'</div>';

    if (tip === 'vreme') {
        if (data.vreme_od || data.ukupno_sati) {
            html += '<div class="danas-ai-row"><span class="danas-ai-label">Vreme</span>';
            html += '<span>'+esc(data.vreme_od||'?')+'–'+esc(data.vreme_do||'?');
            if (data.ukupno_sati) html += ' <strong>('+data.ukupno_sati+'h)</strong>';
            html += '</span></div>';
        }
        if (data.napomena) html += '<div class="danas-ai-row"><span class="danas-ai-label">Napomena</span><span>'+esc(data.napomena)+'</span></div>';
    } else {
        if (data.stavke && data.stavke.length) {
            html += '<div class="danas-ai-row danas-ai-col"><span class="danas-ai-label">Materijal</span><div style="margin-top:4px;">';
            data.stavke.forEach(function(s) {
                html += '<span class="danas-ai-tag">'+esc(s.naziv)+' <strong>'+s.kolicina+' '+esc(s.jm)+'</strong></span>';
            });
            html += '</div></div>';
        }
    }

    html += '<div class="danas-ai-akcije">';
    html += '<button onclick="danasAiIzmeni(\''+tip+'\')" class="mail-cancel-btn">✏️ Izmeni</button>';
    html += '<button onclick="danasAiSacuvaj(\''+tip+'\')" class="btn-primary">✅ Potvrdi</button>';
    html += '</div></div>';
    _danasAiData = data;
    wrap.innerHTML = html;
}

function danasAiIzmeni(tip) {
    document.getElementById(tip==='vreme'?'danas-vreme-preview':'danas-mat-preview').style.display = 'none';
    document.getElementById(tip==='vreme'?'danas-vreme-input-wrap':'danas-mat-input-wrap').style.display = 'block';
}

function danasAiSacuvaj(tip) {
    var data = _danasAiData;
    if (!data) { alert('Nema podataka za čuvanje.'); return; }
    var btn = event.target;
    btn.disabled = true; btn.textContent = '⏳ Čuvam...';

    var grad = _slobodanUnos ? getGradilisteZaUnos(tip) : { id: 0, naziv: '' };

    var fd = new FormData();
    fd.append('_action', tip==='vreme'?'danas_upisi_vreme':'danas_upisi_materijal');
    fd.append('stavka_id', _danasAktivniStavkaId);
    fd.append('meta', JSON.stringify(data));
    fd.append('gradiliste_id', grad.id);
    fd.append('gradiliste_naziv', grad.naziv);
    fd.append('id', 0);

    fetch('', { method:'POST', body:fd })
    .then(r=>r.json())
    .then(d => {
        if (d.ok) {
            zatvoriSveModale();
            var toast = document.createElement('div');
            toast.textContent = tip==='vreme'?'Vreme upisano ✅':'Materijal upisan ✅';
            toast.style.cssText='position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#059669;color:#fff;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:700;z-index:99999;';
            document.body.appendChild(toast);
            setTimeout(function(){toast.remove();},2500);
        } else {
            btn.disabled=false; btn.textContent='✅ Potvrdi';
            alert('Greška: '+(d.err||'Nepoznata'));
        }
    })
    .catch((e) => {
    btn.disabled=false; btn.textContent='✅ Potvrdi';
    alert('Greška: ' + e.message);
});
}

function zatvoriSveModale() {
    ['danas-thread','danas-vreme-modal','danas-mat-modal'].forEach(function(id){
        document.getElementById(id).style.display='none';
    });
    clearInterval(_danasPorukeInterval);
    _slobodanUnos = false;
}

function esc(str) {
    if (!str && str!==0) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

setInterval(function() {
    var otvoren = ['danas-thread','danas-vreme-modal','danas-mat-modal'].some(function(id){
        var el=document.getElementById(id); return el&&el.style.display!=='none';
    });
    if (!otvoren) location.reload();
}, 25000);
</script>
