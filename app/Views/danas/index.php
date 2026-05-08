
<?php
$dani_nazivi = ['Ned','Pon','Uto','Sri','Čet','Pet','Sub'];
$dani_puni   = ['Nedjelja','Ponedeljak','Utorak','Sreda','Četvrtak','Petak','Subota'];
?>

<div class="topbar-admin">
    <div class="page-title">📅 Moj raspored</div>
</div>

<!-- Navigacija datuma -->
<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
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

<!-- Prikaz 4 dana -->
<div style="display:flex;flex-direction:column;gap:14px;">
<?php foreach ($datumi as $idx => $d):
    $stavke  = $dani[$d] ?? [];
    $jeDanas = ($d === date('Y-m-d'));
    $jeIzabran = ($d === $datum);
    $dow     = (int)date('w', strtotime($d));
    $boja_dana = $jeIzabran ? '#1a3a6e' : ($jeDanas ? '#2563eb' : '#94a3b8');
?>
<div style="border-radius:14px;border:2px solid <?= $boja_dana ?><?= $jeIzabran ? '' : '44' ?>;overflow:hidden;">

    <!-- Header dana -->
    <div style="background:<?= $boja_dana ?><?= $jeIzabran ? '' : '18' ?>;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;">
        <div>
            <span style="font-size:14px;font-weight:700;color:<?= $jeIzabran ? '#fff' : $boja_dana ?>;">
                <?= $dani_puni[$dow] ?>
            </span>
            <span style="font-size:12px;color:<?= $jeIzabran ? '#ffffffaa' : '#64748b' ?>;margin-left:8px;">
                <?= date('d.m.Y', strtotime($d)) ?>
            </span>
        </div>
        <?php if ($jeDanas): ?>
        <span style="background:#fff;color:#2563eb;border-radius:20px;font-size:10px;font-weight:700;padding:2px 10px;">DANAS</span>
        <?php endif; ?>
    </div>

    <!-- Sadržaj dana -->
    <div style="padding:10px 12px;background:#fff;display:flex;flex-direction:column;gap:8px;">
    <?php if (empty($stavke)): ?>
        <p style="color:var(--muted);font-size:13px;font-style:italic;padding:4px 0;margin:0;">Nema zadataka</p>
    <?php else: ?>
        <?php foreach ($stavke as $s): ?>
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
                    <div style="font-size:14px;font-weight:700;color:var(--blue);">
                        🏗️ <?= h($s['gradiliste_naziv']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-sm" id="dp-btn-<?= $s['id'] ?>"
                    onclick="openDanasThread(<?= $s['id'] ?>, '<?= h(addslashes($s['gradiliste_naziv'] ?? 'Stavka')) ?>')"
                    style="flex-shrink:0;position:relative;">
                    💬<?php if ($s['poruka_count'] > 0): ?><span style="background:#6b7280;color:#fff;border-radius:99px;font-size:10px;padding:1px 5px;margin-left:3px;font-weight:700;"><?= $s['poruka_count'] ?></span><?php endif; ?><?php if (!empty($s['nova_poruka'])): ?><span style="background:#e53935;color:#fff;border-radius:99px;font-size:10px;padding:1px 5px;margin-left:2px;font-weight:700;"><?= $s['nove_poruke_count'] ?></span><?php endif; ?>
                </button>
            </div>
            <?php if ($s['opis']): ?>
            <div style="font-size:13px;color:#333;line-height:1.5;background:#f8faff;border-radius:6px;padding:8px 10px;">
                <?= nl2br(h($s['opis'])) ?>
            </div>
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
        <button onclick="document.getElementById('danas-thread').style.display='none'; clearInterval(_danasPorukeInterval);" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
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

<style>
.danas-stavka {
    background: #fff;
    border-radius: 10px;
    border: 1.5px solid var(--light2);
    padding: 10px 12px;
    transition: box-shadow .15s;
}
</style>

<script>
var _danasStavkaId = 0;
var _danasPorukeInterval = null;

function openDanasThread(stavkaId, naslov) {
    _danasStavkaId = stavkaId;
    document.getElementById('danas-thread-naslov').textContent = naslov;
    document.getElementById('danas-thread-input').value = '';
    document.getElementById('danas-thread').style.display = 'flex';
    ucitajDanasPoruke(stavkaId);
    var fdv = new FormData();
    fdv.append('_action', 'raspored_oznaci_vidjeno');
    fdv.append('stavka_id', stavkaId);
    fdv.append('id', 0);
    fetch('', { method: 'POST', body: fdv });
    var btn = document.getElementById('dp-btn-' + stavkaId);
    if (btn) {
        var spans = btn.querySelectorAll('span');
        if (spans.length > 1) spans[1].style.display = 'none';
    }
    clearInterval(_danasPorukeInterval);
    _danasPorukeInterval = setInterval(function() {
        ucitajDanasPoruke(stavkaId);
    }, 10000);
}

function ucitajDanasPoruke(stavkaId) {
    var fd = new FormData();
    fd.append('_action', 'danas_poruke_get');
    fd.append('stavka_id', stavkaId);
    fd.append('id', 0);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        var wrap = document.getElementById('danas-thread-poruke');
        wrap.innerHTML = '';
        if (!d.poruke || d.poruke.length === 0) {
            wrap.innerHTML = '<p style="color:var(--muted);font-size:13px;text-align:center;padding:20px 0;">Nema poruka. Postavi pitanje ili komentar.</p>';
            return;
        }
        d.poruke.forEach(function(p) {
            var div = document.createElement('div');
            div.style.cssText = 'padding:9px 13px;border-radius:10px;font-size:14px;max-width:85%;line-height:1.5;' +
                (p.moja ? 'background:var(--blue);color:#fff;margin-left:auto;' : 'background:var(--light);color:var(--text);');
            div.innerHTML = '<strong style="font-size:11px;opacity:.75;display:block;margin-bottom:2px;">' + p.autor + '</strong>' +
                            p.sadrzaj.replace(/\n/g,'<br>');
            wrap.appendChild(div);
        });
        wrap.scrollTop = wrap.scrollHeight;
    });
}

function posaljiDanasPoruku() {
    var sadrzaj = document.getElementById('danas-thread-input').value.trim();
    if (!sadrzaj) return;
    var fd = new FormData();
    fd.append('_action', 'danas_poruka_add');
    fd.append('stavka_id', _danasStavkaId);
    fd.append('id', 0);
    fd.append('sadrzaj', sadrzaj);
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            document.getElementById('danas-thread-input').value = '';
            ucitajDanasPoruke(_danasStavkaId);
        }
    });
}

// Auto-refresh stranice svakih 60s da se vide novi zadaci
setInterval(function() {
    if (!document.getElementById('danas-thread').style.display || 
        document.getElementById('danas-thread').style.display === 'none') {
        location.reload();
    }
}, 25000);
</script>
