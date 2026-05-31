<?php
$statusLabele = [
    'novo'       => ['tekst'=>'Novo',       'bg'=>'#fee2e2','color'=>'#dc2626','border'=>'#fca5a5','ikona'=>'🔴'],
    'u_obradi'   => ['tekst'=>'U obradi',   'bg'=>'#dbeafe','color'=>'#1d4ed8','border'=>'#93c5fd','ikona'=>'🔵'],
    'poruceno'   => ['tekst'=>'Poručeno',   'bg'=>'#fef3c7','color'=>'#d97706','border'=>'#fde68a','ikona'=>'🟡'],
    'isporuceno' => ['tekst'=>'Isporučeno', 'bg'=>'#dcfce7','color'=>'#16a34a','border'=>'#bbf7d0','ikona'=>'🟢'],
];
?>
<div class="topbar-admin" style="flex-wrap:wrap;gap:10px;">
    <div class="page-title">🛒 Nabavka</div>
    <button onclick="openNovaNabavka()"
        style="background:#15803d;color:#fff;border:none;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;">
        + Nova nabavka
    </button>
</div>

<!-- Filteri -->
<div style="max-width:fit-content; min-width:min(100%, 600px);">
<form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px;align-items:flex-end;">
    <input type="hidden" name="page" value="nabavka">
    <div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:4px;">Od</div>
        <input type="date" name="od" value="<?= h($filter_od) ?>" style="border:1.5px solid var(--light2);border-radius:7px;padding:6px 10px;font-size:13px;outline:none;background:#fff;">
    </div>
    <div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:4px;">Do</div>
        <input type="date" name="do" value="<?= h($filter_do) ?>" style="border:1.5px solid var(--light2);border-radius:7px;padding:6px 10px;font-size:13px;outline:none;background:#fff;">
    </div>
    <div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:4px;">Status</div>
        <select name="status" style="border:1.5px solid var(--light2);border-radius:7px;padding:6px 10px;font-size:13px;outline:none;background:#fff;">
            <option value="">— Svi —</option>
            <?php foreach ($statusLabele as $k => $v): ?>
            <option value="<?= $k ?>" <?= $filter_status === $k ? 'selected' : '' ?>><?= $v['ikona'] ?> <?= $v['tekst'] ?></option>
            <?php endforeach; ?>
        </select>
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
    <?php if ($je_kancelarija): ?>
    <div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:4px;">Preuzeo</div>
        <select name="obradjuje" style="border:1.5px solid var(--light2);border-radius:7px;padding:6px 10px;font-size:13px;outline:none;background:#fff;">
            <option value="0">— Svi —</option>
            <?php foreach ($radnici as $r): ?>
            <option value="<?= $r['id'] ?>" <?= ($filter_obradjuje ?? 0) == $r['id'] ? 'selected' : '' ?>><?= h($r['ime']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <button type="submit" class="btn-primary" style="padding:8px 18px;">Filtriraj</button>
    <a href="?page=nabavka" class="btn-secondary">Resetuj</a>
</form>

<?php if (empty($zahtevi)): ?>
<div style="padding:60px 20px;text-align:center;color:var(--muted);">
    <div style="font-size:40px;margin-bottom:12px;">🛒</div>
    <div style="font-size:15px;">Nema zahteva za nabavku.</div>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:14px;min-width:100%;">
<?php foreach ($zahtevi as $z):
    $st = $statusLabele[$z['status']] ?? $statusLabele['novo'];
    $jeHitno = ($z['prioritet'] === 'hitno');
    $borderColor = $jeHitno ? '#f97316' : 'var(--light2)';
?>
<div style="background:#fff;border-radius:12px;border:2px solid <?= $borderColor ?>;padding:16px 18px;">

    <!-- Header -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <?php if ($jeHitno): ?>
            <span style="background:#fff7ed;color:#ea580c;border:1px solid #fed7aa;border-radius:20px;font-size:11px;font-weight:700;padding:2px 9px;">🔥 HITNO</span>
            <?php endif; ?>
            <span style="font-size:14px;font-weight:700;color:#1a3a6e;"><?= h($z['radnik_ime']) ?></span>
            <?php if ($z['gradiliste_naziv']): ?>
            <span style="font-size:12px;color:var(--blue);">🏗️ <?= h($z['gradiliste_naziv']) ?></span>
            <?php endif; ?>
            <span style="font-size:11px;color:var(--muted);"><?= date('d.m.Y H:i', strtotime($z['created_at'])) ?></span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;flex-wrap:wrap;">
            <!-- Status badge -->
            <span style="font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;
                background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;border:1px solid <?= $st['border'] ?>;">
                <?= $st['ikona'] ?> <?= $st['tekst'] ?>
            </span>
            <?php if ($z['obradjuje_ime']): ?>
            <span style="font-size:11px;color:var(--muted);background:var(--light);border-radius:20px;padding:2px 8px;">
                👤 <?= h($z['obradjuje_ime']) ?>
            </span>
            <?php endif; ?>
            <?php if ($je_kancelarija && !$z['obradjuje_id']): ?>
            <button onclick="preuzmiZahtev(<?= $z['id'] ?>)" class="btn-sm"
                style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;font-size:11px;">
                Preuzmi
            </button>
            <?php endif; ?>
            <?php if ($je_kancelarija): ?>
            <button onclick="openStatusModal(<?= $z['id'] ?>, '<?= h($z['status']) ?>', '<?= h(addslashes($z['napomena_admin'] ?? '')) ?>', <?= (int)$z['obradjuje_id'] ?>)"
                class="btn-sm" title="Promeni status">⚙️</button>
            <?php endif; ?>
            <?php if ($is_admin): ?>
            <?php $mozeBrisati = ($z['status'] === 'novo' && !$z['obradjuje_id']); ?>
            <?php if ($mozeBrisati): ?>
            <button onclick="obrisiZahtev(<?= $z['id'] ?>)" class="btn-sm del" title="Obriši">🗑</button>
            <?php else: ?>
            <span title="Ne može se obrisati — zahtev je preuzet ili u obradi"
                style="background:var(--light);border-radius:6px;padding:3px 8px;font-size:14px;opacity:.35;cursor:not-allowed;">🗑</span>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Originalni tekst -->
    <div style="font-size:13px;color:var(--muted);font-style:italic;background:#f8faff;border-radius:6px;padding:8px 12px;margin-bottom:10px;">
        📝 <?= h($z['tekst_original']) ?>
    </div>

    <!-- AI stavke -->
    <?php if (!empty($z['stavke_parsed'])): ?>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;">
        <?php foreach ($z['stavke_parsed'] as $s): ?>
        <span style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:6px;padding:4px 10px;font-size:13px;font-weight:600;color:#15803d;">
            <?= h($s['naziv'] ?? '') ?>
            <?php if (!empty($s['kolicina'])): ?>
            <span style="font-weight:400;color:#166534;"> — <?= $s['kolicina'] ?> <?= h($s['jm'] ?? '') ?></span>
            <?php endif; ?>
            <?php if (!empty($s['napomena'])): ?>
            <span style="font-weight:400;font-style:italic;color:var(--muted);font-size:11px;"> (<?= h($s['napomena']) ?>)</span>
            <?php endif; ?>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Napomena admina -->
    <?php if ($z['napomena_admin']): ?>
    <div style="font-size:12px;background:#fef3c7;border-radius:6px;padding:6px 12px;color:#92400e;border-left:3px solid #fbbf24;margin-bottom:10px;">
        💬 <?= h($z['napomena_admin']) ?>
    </div>
    <?php endif; ?>

    <!-- Komentari — collapse/expand -->
    <?php $brKomentara = count($z['komentari']); ?>
    <div style="margin-top:8px;">
        <button onclick="toggleKomentari(<?= $z['id'] ?>)"
            style="background:none;border:none;cursor:pointer;font-size:12px;color:var(--blue);font-weight:600;padding:4px 0;display:flex;align-items:center;gap:6px;">
            <span id="ikona-kom-<?= $z['id'] ?>">▶</span>
            <?php if ($brKomentara > 0): ?>
                💬 <?= $brKomentara ?> <?= $brKomentara === 1 ? 'poruka' : ($brKomentara < 5 ? 'poruke' : 'poruka') ?>
            <?php else: ?>
                💬 Dodaj komentar
            <?php endif; ?>
        </button>

        <div id="komentari-<?= $z['id'] ?>" style="display:none;margin-top:8px;border-top:1px solid var(--light2);padding-top:10px;">
            <?php if (!empty($z['komentari'])): ?>
            <div style="display:flex;flex-direction:column;gap:8px;max-width:700px;margin-bottom:12px;">
                <?php foreach ($z['komentari'] as $k):
                    $moj = ((int)$k['autor_id'] === (int)$uid);
                ?>
                <div style="display:flex;flex-direction:column;align-items:<?= $moj ? 'flex-end' : 'flex-start' ?>;">
                    <div style="font-size:11px;color:var(--muted);margin-bottom:2px;<?= $moj ? 'text-align:right;' : '' ?>">
                        <?= $moj ? '' : '<strong>'.h($k['autor_ime']).'</strong> · ' ?>
                        <?= date('d.m H:i', strtotime($k['created_at'])) ?>
                    </div>
                    <div style="max-width:85%;background:<?= $moj ? '#1d4ed8' : '#f1f5f9' ?>;color:<?= $moj ? '#fff' : 'var(--text)' ?>;border-radius:<?= $moj ? '14px 14px 4px 14px' : '14px 14px 14px 4px' ?>;padding:8px 13px;font-size:13px;line-height:1.4;"><?= h($k['tekst']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div id="komentar-row-<?= $z['id'] ?>" style="display:flex;gap:8px;margin-top:2px;">
                <input type="text" class="komentar-input" data-id="<?= $z['id'] ?>"
                    placeholder="Napiši komentar..."
                    style="flex:1;border:1.5px solid var(--light2);border-radius:7px;padding:6px 10px;font-size:13px;outline:none;background:var(--light);"
                    onkeydown="if(event.key==='Enter')posaljiKomentar(this)"
                    onfocus="var row=document.getElementById('komentar-row-<?= $z['id'] ?>');setTimeout(function(){row.scrollIntoView({behavior:'smooth',block:'nearest'});},400)">
                <button onclick="posaljiKomentar(this.previousElementSibling)" class="btn-sm"
                    style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">→</button>
            </div>
        </div>
    </div>

</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div><!-- /wrapper -->

<!-- MODAL: Promeni status -->
<div id="modal-status" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:440px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('modal-status').style.display='none'"
            style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:16px;">⚙️ Promeni status zahteva</h3>
        <input type="hidden" id="modal-status-id">
        <div class="tim-form-group">
            <label>Status</label>
            <select id="modal-status-val" style="border:1.5px solid var(--light2);border-radius:7px;padding:8px 10px;font-size:13px;outline:none;background:var(--light);width:100%;">
                <option value="novo">🔴 Novo</option>
                <option value="u_obradi">🔵 U obradi</option>
                <option value="poruceno">🟡 Poručeno</option>
                <option value="isporuceno">🟢 Isporučeno</option>
            </select>
        </div>
        <div class="tim-form-group">
            <label>Ko obrađuje</label>
            <select id="modal-obradjuje" style="border:1.5px solid var(--light2);border-radius:7px;padding:8px 10px;font-size:13px;outline:none;background:var(--light);width:100%;">
                <option value="0">— Niko —</option>
                <?php foreach ($radnici as $r): ?>
                <option value="<?= $r['id'] ?>"><?= h($r['ime']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tim-form-group">
            <label>Napomena</label>
            <textarea id="modal-status-napomena" rows="3"
                placeholder="Npr. naručeno kod dobavljača, stiže u petak..."
                style="border:1.5px solid var(--light2);border-radius:7px;padding:8px 10px;font-size:13px;outline:none;background:var(--light);width:100%;resize:vertical;box-sizing:border-box;font-family:inherit;"></textarea>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('modal-status').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="sacuvajStatus()" class="btn-primary">Sačuvaj</button>
        </div>
    </div>
</div>

<script>
function toggleKomentari(id) {
    var div   = document.getElementById('komentari-' + id);
    var ikona = document.getElementById('ikona-kom-' + id);
    var otvoren = div.style.display !== 'none';
    div.style.display   = otvoren ? 'none' : 'block';
    ikona.textContent   = otvoren ? '▶' : '▼';
}

function openStatusModal(id, status, napomena, obradjuje_id) {
    document.getElementById('modal-status-id').value       = id;
    document.getElementById('modal-status-val').value      = status;
    document.getElementById('modal-status-napomena').value = napomena;
    document.getElementById('modal-obradjuje').value       = obradjuje_id || 0;
    document.getElementById('modal-status').style.display  = 'flex';
}

function sacuvajStatus() {
    var id = document.getElementById('modal-status-id').value;
    var fd = new FormData();
    fd.append('_action', 'nabavka_promeni_status');
    fd.append('id', id);
    fd.append('status',         document.getElementById('modal-status-val').value);
    fd.append('napomena_admin', document.getElementById('modal-status-napomena').value);
    fd.append('obradjuje_id',   document.getElementById('modal-obradjuje').value);
    fetch('', { method:'POST', body:fd })
    .then(r=>r.json())
    .then(d => {
        if (d.ok) { document.getElementById('modal-status').style.display='none'; location.reload(); }
        else alert(d.err||'Greška.');
    });
}

function preuzmiZahtev(id) {
    var fd = new FormData();
    fd.append('_action', 'nabavka_preuzmi');
    fd.append('id', id);
    fetch('', { method:'POST', body:fd })
    .then(r=>r.json())
    .then(d => { if (d.ok) location.reload(); else alert(d.err||'Greška.'); });
}

function posaljiKomentar(input) {
    var tekst = input.value.trim();
    if (!tekst) return;
    var id = input.dataset.id;
    var fd = new FormData();
    fd.append('_action', 'nabavka_komentar');
    fd.append('id', id);
    fd.append('tekst', tekst);
    fetch('', { method:'POST', body:fd })
    .then(r=>r.json())
    .then(d => {
        if (!d.ok) { alert(d.err||'Greška.'); return; }
        // Dodaj poruku direktno u listu bez reload-a
        var row = document.getElementById('komentar-row-' + id);
        var lista = row.previousElementSibling;
        if (!lista || !lista.querySelector) {
            // Napravi listu ako ne postoji
            lista = document.createElement('div');
            lista.style.cssText = 'border-top:1px solid var(--light2);padding-top:10px;margin-top:4px;display:flex;flex-direction:column;gap:8px;max-width:700px;';
            row.parentElement.insertBefore(lista, row);
        }
        var sada = new Date();
        var vreme = (sada.getDate()+'.'+(sada.getMonth()+1)+' '+
                    String(sada.getHours()).padStart(2,'0')+':'+String(sada.getMinutes()).padStart(2,'0'));
        var div = document.createElement('div');
        div.style.cssText = 'display:flex;flex-direction:column;align-items:flex-end;';
        div.innerHTML = '<div style="font-size:11px;color:var(--muted);margin-bottom:2px;text-align:right;">'+vreme+'</div>' +
            '<div style="max-width:85%;background:#1d4ed8;color:#fff;border-radius:14px 14px 4px 14px;padding:8px 13px;font-size:13px;line-height:1.4;">'+
            tekst.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')+'</div>';
        lista.appendChild(div);
        input.value = '';
        div.scrollIntoView({behavior:'smooth', block:'nearest'});
    });
}

function obrisiZahtev(id) {
    if (!confirm('Obrisati ovaj zahtev za nabavku?')) return;
    var fd = new FormData();
    fd.append('_action', 'nabavka_obrisi');
    fd.append('id', id);
    fetch('', { method:'POST', body:fd })
    .then(r=>r.json())
    .then(d => { if (d.ok) location.reload(); else alert(d.err||'Greška.'); });
}

// ── Nova nabavka ─────────────────────────────────────────────
var _novaNabavkaAiData = null;

function openNovaNabavka() {
    document.getElementById('modal-nova-nabavka').style.display = 'flex';
    document.getElementById('nova-nabavka-input-wrap').style.display = 'block';
    document.getElementById('nova-nabavka-preview').style.display = 'none';
    document.getElementById('nova-nabavka-preview').innerHTML = '';
    document.getElementById('nova-nabavka-tekst').value = '';
    document.getElementById('nova-nabavka-gradiliste').value = '';
    document.getElementById('nova-nabavka-hitno').checked = false;
    _novaNabavkaAiData = null;
}

function novaNabavkaAnaliziraj() {
    var tekst = document.getElementById('nova-nabavka-tekst').value.trim();
    if (!tekst) { alert('Unesi tekst pre analize.'); return; }
    var btn = document.getElementById('btn-nova-nabavka-analiziraj');
    btn.disabled = true; btn.textContent = '⏳ Analiziram...';

    var fd = new FormData();
    fd.append('_action', 'danas_ai_parse');
    fd.append('tip', 'nabavka');
    fd.append('tekst', tekst);
    fd.append('stavka_id', 0);
    fd.append('id', 0);

    fetch('', { method:'POST', body:fd })
    .then(r=>r.json())
    .then(d => {
        btn.disabled = false; btn.textContent = '🤖 Analiziraj';
        if (!d.ok) { alert('Greška: '+(d.err||'Nepoznata')); return; }
        _novaNabavkaAiData = d.data;
        novaNabavkaPrikaziPreview(d.data);
    })
    .catch(()=>{ btn.disabled=false; btn.textContent='🤖 Analiziraj'; alert('Mrežna greška.'); });
}

function novaNabavkaPrikaziPreview(data) {
    document.getElementById('nova-nabavka-input-wrap').style.display = 'none';
    var wrap = document.getElementById('nova-nabavka-preview');
    wrap.style.display = 'block';

    var html = '<div style="border:2px solid #15803d;border-radius:10px;padding:14px 16px;background:#fff;">';
    html += '<div style="font-weight:700;font-size:14px;margin-bottom:10px;color:#15803d;">🛒 Proveri zahtev</div>';

    if (data.stavke && data.stavke.length) {
        html += '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;">';
        data.stavke.forEach(function(s) {
            html += '<span style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:6px;padding:4px 10px;font-size:13px;font-weight:600;color:#15803d;">';
            html += esc(s.naziv||'');
            if (s.kolicina) html += ' <span style="font-weight:400;color:#166534;">— '+s.kolicina+' '+esc(s.jm||'')+'</span>';
            if (s.napomena) html += ' <em style="font-size:11px;color:#6b7280;">('+esc(s.napomena)+')</em>';
            html += '</span>';
        });
        html += '</div>';
    } else {
        html += '<p style="color:var(--muted);font-size:13px;">AI nije prepoznao stavke. Pokušaj ponovo.</p>';
    }

    html += '<div style="display:flex;gap:8px;justify-content:flex-end;">';
    html += '<button onclick="novaNabavkaIzmeni()" class="mail-cancel-btn">✏️ Izmeni</button>';
    html += '<button onclick="novaNabavkaSacuvaj()" class="btn-primary">✅ Pošalji zahtev</button>';
    html += '</div></div>';
    wrap.innerHTML = html;
}

function novaNabavkaIzmeni() {
    document.getElementById('nova-nabavka-preview').style.display = 'none';
    document.getElementById('nova-nabavka-input-wrap').style.display = 'block';
}

function novaNabavkaSacuvaj() {
    if (!_novaNabavkaAiData) { alert('Nema podataka.'); return; }
    var btn = event.target;
    btn.disabled = true; btn.textContent = '⏳ Čuvam...';

    var gradSel = document.getElementById('nova-nabavka-gradiliste');
    var gradId  = gradSel.value;
    var gradNaziv = gradSel.options[gradSel.selectedIndex] ? gradSel.options[gradSel.selectedIndex].text : '';
    if (gradId === '0' || !gradId) { gradId = 0; gradNaziv = ''; }
    var hitno   = document.getElementById('nova-nabavka-hitno').checked ? 'hitno' : 'normalno';
    var tekst   = document.getElementById('nova-nabavka-tekst').value.trim();

    var fd = new FormData();
    fd.append('_action', 'danas_upisi_nabavku');
    fd.append('stavka_id', 0);
    fd.append('meta', JSON.stringify(_novaNabavkaAiData));
    fd.append('tekst_original', tekst);
    fd.append('gradiliste_id', gradId);
    fd.append('gradiliste_naziv', gradNaziv);
    fd.append('prioritet', hitno);
    fd.append('id', 0);

    fetch('', { method:'POST', body:fd })
    .then(r=>r.json())
    .then(d => {
        if (d.ok) {
            document.getElementById('modal-nova-nabavka').style.display = 'none';
            location.reload();
        } else {
            btn.disabled=false; btn.textContent='✅ Pošalji zahtev';
            alert('Greška: '+(d.err||'Nepoznata'));
        }
    })
    .catch(()=>{ btn.disabled=false; btn.textContent='✅ Pošalji zahtev'; alert('Mrežna greška.'); });
}

function esc(str) {
    if (!str && str!==0) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<!-- MODAL: Nova nabavka -->
<div id="modal-nova-nabavka" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:520px;box-shadow:0 24px 80px #000a;position:relative;max-height:90vh;overflow-y:auto;">
        <button onclick="document.getElementById('modal-nova-nabavka').style.display='none'"
            style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:15px;font-weight:700;color:#15803d;margin-bottom:16px;">🛒 Nova nabavka</h3>

        <!-- Gradilište -->
        <div class="tim-form-group">
            <label>Gradilište</label>
            <select id="nova-nabavka-gradiliste"
                style="border:1.5px solid var(--light2);border-radius:7px;padding:8px 10px;font-size:13px;outline:none;background:var(--light);width:100%;">
                <option value="0">— Izaberi gradilište —</option>
                <?php foreach ($gradilista as $g): ?>
                <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Hitno -->
        <label style="display:flex;align-items:center;gap:8px;margin-bottom:14px;cursor:pointer;font-size:14px;font-weight:600;color:#ea580c;">
            <input type="checkbox" id="nova-nabavka-hitno" style="width:16px;height:16px;cursor:pointer;">
            🔥 Hitno — potrebno što pre
        </label>

        <div id="nova-nabavka-input-wrap">
            <div class="tim-form-group">
                <label>Šta treba nabaviti</label>
                <textarea id="nova-nabavka-tekst" rows="4"
                    placeholder="npr. 50m kabla n2xh 3x1.5, 10 doza, izolir traka..."
                    style="border:1.5px solid var(--light2);border-radius:7px;padding:8px 10px;font-size:13px;outline:none;background:var(--light);width:100%;resize:vertical;box-sizing:border-box;font-family:inherit;"></textarea>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button onclick="document.getElementById('modal-nova-nabavka').style.display='none'" class="mail-cancel-btn">Odustani</button>
                <button onclick="novaNabavkaAnaliziraj()" class="btn-primary" id="btn-nova-nabavka-analiziraj">🤖 Analiziraj</button>
            </div>
        </div>
        <div id="nova-nabavka-preview" style="display:none;"></div>
    </div>
</div>
