<?php
$today     = date('Y-m-d');
$warn_days = 2;

// Boje za kategorije
function katBoja(string $kat): array {
    $mapa = [
        'podsetnik'    => ['#eff6ff','#1d4ed8'],
        'nabavka'      => ['#fff7ed','#c2410c'],
        'nabavka ostala'=> ['#fff7ed','#c2410c'],
        'it'           => ['#f5f3ff','#7c3aed'],
        'admin'        => ['#f0fdf4','#15803d'],
        'hr'           => ['#fdf4ff','#9333ea'],
        'finansije'    => ['#fffbeb','#d97706'],
    ];
    $key = strtolower(trim($kat));
    return $mapa[$key] ?? ['#f1f5f9','#475569'];
}

// Inicijali za avatar
function inicijali(string $ime): string {
    $delovi = explode(' ', trim($ime));
    if (count($delovi) >= 2) return strtoupper(mb_substr($delovi[0],0,1).mb_substr($delovi[1],0,1));
    return strtoupper(mb_substr($ime,0,2));
}

// Boja avatara iz imena
function avatarBoja(string $ime): string {
    $boje = ['#1d4ed8','#15803d','#c2410c','#7c3aed','#0891b2','#d97706','#db2777'];
    return $boje[abs(crc32($ime)) % count($boje)];
}

$ukupno_strana = max(1, (int)ceil($ukupno / $po_stranici));
?>

<style>
.z2-topbar { display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap; }
.z2-stat { text-align:center;padding:8px 18px;border-radius:10px;border:1.5px solid var(--light2);background:#fff;cursor:pointer;min-width:80px; }
.z2-stat:hover { border-color:#93c5fd; }
.z2-stat.active { border-color:#1d4ed8;background:#eff6ff; }
.z2-stat .broj { font-size:22px;font-weight:800;color:#1a3a6e;line-height:1; }
.z2-stat .lab  { font-size:11px;color:var(--muted);margin-top:2px; }
.z2-filters { display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center; }
.z2-search { flex:1;min-width:200px;border:1.5px solid var(--light2);border-radius:8px;padding:7px 12px;font-size:13px;outline:none;background:#fff; }
.z2-sel { border:1.5px solid var(--light2);border-radius:8px;padding:7px 12px;font-size:13px;outline:none;background:#fff;cursor:pointer; }
.z2-table { width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;border:1.5px solid var(--light2); }
.z2-table thead th { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);padding:10px 14px;background:#f8faff;border-bottom:1.5px solid var(--light2);white-space:nowrap; }
.z2-table tbody tr { border-bottom:1px solid var(--light2);transition:background .1s; }
.z2-table tbody tr:last-child { border-bottom:none; }
.z2-table tbody tr:hover { background:#f8faff; }
.z2-table td { padding:11px 14px;vertical-align:middle;font-size:13px; }
.z2-tekst { font-size:14px;font-weight:600;color:var(--text);margin-bottom:4px;line-height:1.4; }
.z2-meta { display:flex;align-items:center;gap:8px;flex-wrap:wrap; }
.z2-badge { font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px; }
.z2-avatar { width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0; }
.z2-rok-warn { color:#d97706;font-weight:700; }
.z2-rok-err  { color:#dc2626;font-weight:700; }
.z2-actions { display:flex;align-items:center;gap:4px; }
.z2-pagination { display:flex;align-items:center;gap:4px;justify-content:flex-end;margin-top:16px;flex-wrap:wrap; }
.z2-pgbtn { border:1.5px solid var(--light2);background:#fff;border-radius:7px;padding:5px 10px;font-size:13px;cursor:pointer;color:var(--text); }
.z2-pgbtn:hover { border-color:#93c5fd;color:#1d4ed8; }
.z2-pgbtn.active { background:#1d4ed8;color:#fff;border-color:#1d4ed8; }
.z2-pgbtn:disabled { opacity:.4;cursor:default; }
</style>

<!-- Topbar -->
<div class="z2-topbar">
    <div style="flex:1;">
        <div class="page-title" style="margin-bottom:10px;">Interni zadaci</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <div class="z2-stat <?= $filters['status']==='' ? 'active':'' ?>" onclick="setStatusFilter('')">
                <div class="broj"><?= $svi ?></div><div class="lab">Svi</div>
            </div>
            <div class="z2-stat <?= $filters['status']==='otvoreno' ? 'active':'' ?>" onclick="setStatusFilter('otvoreno')" style="<?= $filters['status']==='otvoreno'?'':'border-color:#fcd34d;' ?>">
                <div class="broj" style="color:#d97706;"><?= $otvoreno ?></div><div class="lab">Otvoreno</div>
            </div>
            <div class="z2-stat <?= $filters['status']==='u_toku' ? 'active':'' ?>" onclick="setStatusFilter('u_toku')">
                <div class="broj" style="color:#1d4ed8;"><?= $u_toku ?></div><div class="lab">U toku</div>
            </div>
            <div class="z2-stat <?= $filters['status']==='zavrseno' ? 'active':'' ?>" onclick="setStatusFilter('zavrseno')" style="<?= $filters['status']==='zavrseno'?'':'border-color:#bbf7d0;' ?>">
                <div class="broj" style="color:#15803d;"><?= $zavrseno ?></div><div class="lab">Završeno</div>
            </div>
        </div>
    </div>
    <button onclick="document.getElementById('z-novi-modal').style.display='flex'"
        style="background:#1d4ed8;color:#fff;border:none;border-radius:9px;padding:10px 18px;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap;flex-shrink:0;">
        + Novi zadatak
    </button>
</div>

<!-- Filteri -->
<div class="z2-filters">
    <input type="text" class="z2-search" id="z-search"
        placeholder="Pretraži zadatke..."
        value="<?= h($filters['q']) ?>" oninput="filterZadaci()">
    <select class="z2-sel" onchange="setUrlParam('zkat', this.value)">
        <option value="">Kategorija: Sve</option>
        <?php foreach ($kategorije as $k): ?>
        <option value="<?= h($k) ?>" <?= $filters['kategorija']===$k?'selected':'' ?>><?= h($k) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="z2-sel" onchange="setUrlParam('zdod', this.value)">
    <option value="0">Prihvatio: Svi</option>
        <?php foreach ($korisnici as $u): ?>
        <option value="<?= $u['id'] ?>" <?= ($filters['dodeljeno']==$u['id'])?'selected':'' ?>><?= h($u['ime']) ?></option>
        <?php endforeach; ?>
    </select>
    <a href="?page=zadaci" class="btn-secondary" style="white-space:nowrap;">↺ Resetuj</a>
</div>

<?php if (empty($zadaci)): ?>
<div style="text-align:center;padding:60px;color:var(--muted);background:#fff;border-radius:12px;border:1.5px solid var(--light2);">
    <div style="font-size:40px;margin-bottom:10px;">✅</div>
    <?= $filters['q'] ? 'Nema rezultata za "'.h($filters['q']).'"' : 'Nema zadataka.' ?>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:12px;">
<?php foreach ($zadaci as $i => $z):
    $rok_klasa = '';
    if ($z['rok'] && $z['status'] !== 'zavrseno') {
        $diff = (strtotime($z['rok']) - strtotime($today)) / 86400;
        if ($diff < 0)           $rok_klasa = 'err';
        elseif ($diff <= $warn_days) $rok_klasa = 'warn';
    }
    $prihvacen      = !empty($z['prihvaceno_id']);
    $moguPrihvatiti = !$prihvacen;
    $katBoje        = katBoja($z['kategorija'] ?? '');
    $rb             = ($stranica-1)*$po_stranici + $i + 1;
    $razlicitePosobe= $prihvacen && $z['dodeljeno_id'] && (int)$z['prihvaceno_id'] !== (int)$z['dodeljeno_id'];
    $tekst_pun      = $z['tekst'];
    $tekst_kratki   = mb_strlen($tekst_pun) > 100 ? mb_substr($tekst_pun, 0, 100).'…' : $tekst_pun;
    $ima_vise       = mb_strlen($tekst_pun) > 100 || $z['komentar_count'] > 0;
    $lastTs         = !empty($z['komentari']) ? end($z['komentari'])['created_at'] : '2000-01-01 00:00:00';
    $borderColor    = ($z['status']==='zavrseno') ? '#e2e8f0' : (($rok_klasa==='err') ? '#fca5a5' : (($rok_klasa==='warn') ? '#fde68a' : 'var(--light2)'));
?>
<div style="background:#fff;border-radius:12px;border:1.5px solid <?= $borderColor ?>;padding:14px 16px;" id="zcard-<?= $z['id'] ?>">

    <!-- Header: RB + tekst + akcije -->
    <div style="display:flex;align-items:flex-start;gap:10px;">
        <!-- RB + status check -->
        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;flex-shrink:0;">
            <span style="font-size:10px;color:var(--muted);font-weight:700;"><?= $rb ?></span>
            <div class="zadatak-check <?= $z['status'] ?>"
                onclick="ciklajStatus(<?= $z['id'] ?>, '<?= $z['status'] ?>')"
                style="cursor:pointer;" title="Promeni status">
                <?php if ($z['status']==='zavrseno'): ?>✓
                <?php elseif ($z['status']==='u_toku'): ?>●
                <?php endif; ?>
            </div>
        </div>
        <!-- Tekst -->
        <div style="flex:1;min-width:0;">
            <div style="font-size:14px;font-weight:600;color:var(--text);line-height:1.5;">
                <span id="ztekst-kratki-<?= $z['id'] ?>"><?= h($tekst_kratki) ?></span>
                <span id="ztekst-pun-<?= $z['id'] ?>" style="display:none;"><?= h($tekst_pun) ?></span>
            </div>
            <!-- Meta -->
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-top:6px;">
                <?php if ($z['kategorija']): ?>
                <span class="z2-badge" style="background:<?= $katBoje[0] ?>;color:<?= $katBoje[1] ?>;"><?= h($z['kategorija']) ?></span>
                <?php endif; ?>
                <?php if ($z['rok']): ?>
                <span style="font-size:12px;<?= $rok_klasa==='err'?'color:#dc2626;font-weight:700;':($rok_klasa==='warn'?'color:#d97706;font-weight:700;':'color:var(--muted);') ?>">
                    📅 <?= date('d.m.Y.', strtotime($z['rok'])) ?>
                    <?php if ($rok_klasa==='err'): ?> — PREKORAČEN<?php elseif ($rok_klasa==='warn'): ?> — USKORO<?php endif; ?>
                </span>
                <?php endif; ?>
                <?php if ($razlicitePosobe): ?>
                <span style="font-size:11px;color:var(--muted);">
                    <span style="text-decoration:line-through;"><?= h(explode(' ',$z['dodeljeno_ime'])[0]) ?></span>
                    → <strong style="color:#15803d;"><?= h(explode(' ',$z['prihvaceno_ime'])[0]) ?></strong>
                </span>
                <?php elseif ($prihvacen): ?>
                <span style="font-size:11px;background:#dcfce7;color:#15803d;border-radius:20px;padding:2px 8px;font-weight:700;">✓ Prihvatio <?= h($z['prihvaceno_ime'] ?? '') ?> · <?= date('d.m H:i', strtotime($z['prihvaceno_at'])) ?></span>
                <?php elseif ($z['dodeljeno_id']): ?>
                <span style="font-size:11px;background:#fef3c7;color:#d97706;border-radius:20px;padding:2px 8px;">⏳ <?= h($z['dodeljeno_ime'] ?? '') ?> — čeka prihvatanje</span>
                <?php endif; ?>
                <span style="font-size:10px;color:var(--muted);margin-left:auto;"><?= h($z['kreirao_ime']) ?> · <?= date('d.m.', strtotime($z['datum_kreiranja'])) ?></span>
            </div>
        </div>
        <!-- Akcije -->
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0;">
            <div style="display:flex;gap:4px;flex-wrap:nowrap;align-items:center;">
                <?php if ($moguPrihvatiti && $z['dodeljeno_id']): ?>
                <button class="btn-sm" onclick="prihvatiZadatak(<?= $z['id'] ?>)"
                    style="background:#dcfce7;color:#15803d;border-color:#bbf7d0;font-size:11px;font-weight:700;white-space:nowrap;">
                    Prihvati
                </button>
                <?php endif; ?>
                <select class="z2-sel" style="font-size:11px;padding:3px 6px;"
                    onchange="promeniStatus(<?= $z['id'] ?>, this.value)">
                    <option value="otvoreno" <?= $z['status']==='otvoreno'?'selected':'' ?>>Otvoreno</option>
                    <option value="u_toku"   <?= $z['status']==='u_toku'  ?'selected':'' ?>>U toku</option>
                    <option value="zavrseno" <?= $z['status']==='zavrseno'?'selected':'' ?>>Završeno</option>
                </select>
                <button class="btn-sm" onclick="otvoriEditZadatak(<?= $z['id'] ?>,'<?= h(addslashes($z['tekst'])) ?>','<?= h(addslashes($z['kategorija'])) ?>','<?= $z['status'] ?>','<?= $z['rok']??'' ?>','<?= $z['dodeljeno_id']??'' ?>')" title="Izmeni">✎</button>
                <button class="btn-sm del" onclick="obrisiZadatak(<?= $z['id'] ?>)" title="Obriši">🗑</button>
            </div>
        </div>
    </div>

    <!-- Toggle: proširi + komentari -->
    <?php if ($ima_vise): ?>
    <div style="margin-top:10px;border-top:1px solid var(--light2);padding-top:8px;">
        <button onclick="toggleZKom(<?= $z['id'] ?>)"
            style="background:none;border:none;cursor:pointer;font-size:12px;color:var(--blue);font-weight:600;padding:0;display:flex;align-items:center;gap:6px;"
            id="zbtn-kom-<?= $z['id'] ?>">
            <span id="zikona-<?= $z['id'] ?>">▶</span>
            <?php if ($z['komentar_count'] > 0): ?>
                💬 <?= $z['komentar_count'] ?> <?= $z['komentar_count']===1?'poruka':($z['komentar_count']<5?'poruke':'poruka') ?>
                <?php if (mb_strlen($tekst_pun) > 100): ?> · prikaži ceo tekst<?php endif; ?>
            <?php else: ?>
                <?php if (mb_strlen($tekst_pun) > 100): ?>prikaži ceo tekst<?php else: ?>💬 Dodaj komentar<?php endif; ?>
            <?php endif; ?>
        </button>

        <!-- Prošireni sadržaj -->
        <div id="zkom-wrap-<?= $z['id'] ?>"
            data-last-ts="<?= $lastTs ?>"
            style="display:none;margin-top:10px;">
            <!-- Komentari -->
            <div id="zkom-lista-<?= $z['id'] ?>" style="display:flex;flex-direction:column;gap:8px;max-width:700px;margin-bottom:10px;">
                <?php foreach ($z['komentari'] as $k):
                    $moj = ((int)$k['autor_id'] === (int)$uid); ?>
                <div style="display:flex;flex-direction:column;align-items:<?= $moj?'flex-end':'flex-start' ?>;">
                    <div style="font-size:11px;color:var(--muted);margin-bottom:2px;<?= $moj?'text-align:right;':'' ?>">
                        <?= $moj?'':'<strong>'.h($k['autor_ime']).'</strong> · ' ?>
                        <?= date('d.m H:i', strtotime($k['created_at'])) ?>
                    </div>
                    <div style="max-width:85%;background:<?= $moj?'#1d4ed8':'#f1f5f9' ?>;color:<?= $moj?'#fff':'var(--text)' ?>;border-radius:<?= $moj?'14px 14px 4px 14px':'14px 14px 14px 4px' ?>;padding:8px 13px;font-size:13px;line-height:1.4;"><?= h($k['tekst']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="zkom-row-<?= $z['id'] ?>" style="display:flex;gap:8px;max-width:700px;">
                <input type="text" class="zkom-input" data-id="<?= $z['id'] ?>"
                    placeholder="Napiši komentar..."
                    style="flex:1;border:1.5px solid var(--light2);border-radius:7px;padding:6px 10px;font-size:13px;outline:none;background:var(--light);"
                    onkeydown="if(event.key==='Enter')posaljiZKomentar(this)"
                    onfocus="var row=document.getElementById('zkom-row-<?= $z['id'] ?>');setTimeout(function(){row.scrollIntoView({behavior:'smooth',block:'nearest'});},400)">
                <button onclick="posaljiZKomentar(this.previousElementSibling)" class="btn-sm"
                    style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">→</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php endforeach; ?>
</div>

<!-- Paginacija -->
<?php if ($ukupno_strana > 1): ?>
<div class="z2-pagination">
    <span style="font-size:12px;color:var(--muted);margin-right:8px;">
        <?= (($stranica-1)*$po_stranici)+1 ?>–<?= min($stranica*$po_stranici, $ukupno) ?> od <?= $ukupno ?>
    </span>
    <?php if ($stranica > 1): ?>
    <button class="z2-pgbtn" onclick="setUrlParam('str', <?= $stranica-1 ?>)">←</button>
    <?php endif; ?>
    <?php for ($p = max(1,$stranica-2); $p <= min($ukupno_strana,$stranica+2); $p++): ?>
    <button class="z2-pgbtn <?= $p==$stranica?'active':'' ?>" onclick="setUrlParam('str', <?= $p ?>)"><?= $p ?></button>
    <?php endfor; ?>
    <?php if ($stranica < $ukupno_strana): ?>
    <button class="z2-pgbtn" onclick="setUrlParam('str', <?= $stranica+1 ?>)">→</button>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- MODAL: Novi zadatak -->
<div id="z-novi-modal"
    style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:560px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('z-novi-modal').style.display='none'"
            style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:16px;">+ Novi zadatak</h3>
        <textarea id="z-tekst" placeholder="Upiši novi zadatak..."
            style="width:100%;border:1.5px solid var(--light2);border-radius:8px;padding:10px 12px;font-size:14px;font-family:inherit;resize:vertical;outline:none;background:var(--light);box-sizing:border-box;min-height:80px;margin-bottom:12px;"></textarea>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
            <input type="text" id="z-kat" placeholder="Kategorija" list="kat-list" autocomplete="off"
                style="flex:1;min-width:140px;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;">
            <datalist id="kat-list">
                <?php foreach ($kategorije as $k): ?><option value="<?= h($k) ?>"><?php endforeach; ?>
            </datalist>
            <input type="date" id="z-rok"
                style="flex:1;min-width:140px;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;">
            <select id="z-dodeljeno"
                style="flex:1;min-width:160px;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;">
                <option value="">— Dodeli osobi —</option>
                <?php foreach ($korisnici as $u): ?><option value="<?= $u['id'] ?>"><?= h($u['ime']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div id="z-add-err" style="color:var(--red);font-size:12px;margin-bottom:8px;display:none;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button class="mail-cancel-btn" onclick="document.getElementById('z-novi-modal').style.display='none'">Odustani</button>
            <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="dodajZadatak()">+ Dodaj</button>
        </div>
    </div>
</div>

<!-- MODAL: Izmena zadatka -->
<div id="z-edit-modal"
    style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:500px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('z-edit-modal').style.display='none'"
            style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:16px;">✎ Izmeni zadatak</h3>
        <input type="hidden" id="z-edit-id">
        <textarea id="z-edit-tekst" rows="3"
            style="width:100%;border:1.5px solid var(--light2);border-radius:8px;padding:9px 12px;font-size:14px;font-family:inherit;resize:vertical;outline:none;background:var(--light);box-sizing:border-box;margin-bottom:10px;"></textarea>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
            <input type="text" id="z-edit-kat" placeholder="Kategorija" list="kat-list-edit"
                style="flex:1;min-width:120px;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;">
            <datalist id="kat-list-edit">
                <?php foreach ($kategorije as $k): ?><option value="<?= h($k) ?>"><?php endforeach; ?>
            </datalist>
            <input type="date" id="z-edit-rok"
                style="flex:1;min-width:120px;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;">
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
            <select id="z-edit-status"
                style="flex:1;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;">
                <option value="otvoreno">Otvoreno</option>
                <option value="u_toku">U toku</option>
                <option value="zavrseno">Završeno</option>
            </select>
            <select id="z-edit-dodeljeno"
                style="flex:1;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;">
                <option value="">— Dodeli osobi</option>
                <?php foreach ($korisnici as $u): ?><option value="<?= $u['id'] ?>"><?= h($u['ime']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div id="z-edit-err" style="color:var(--red);font-size:12px;margin-bottom:8px;display:none;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button class="mail-cancel-btn" onclick="document.getElementById('z-edit-modal').style.display='none'">Odustani</button>
            <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="sacuvajZadatak()">Sačuvaj</button>
        </div>
    </div>
</div>

<script>
var _zUid = <?= (int)$uid ?>;
var _zOtvoreni = {}; // zadatak_id -> last_ts

// Polling za nove komentare
setInterval(function() {
    Object.keys(_zOtvoreni).forEach(function(id) {
        var after = _zOtvoreni[id];
        var fd = new FormData();
        fd.append('_action', 'zadatak_komentari_novi');
        fd.append('id', id);
        fd.append('after', after);
        fetch('', { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => {
            if (!d.ok || !d.komentari || !d.komentari.length) return;
            var lista = document.getElementById('zkom-lista-'+id);
            if (!lista) return;
            d.komentari.forEach(function(k) {
                var moj = k.autor_id == _zUid;
                var div = document.createElement('div');
                div.style.cssText = 'display:flex;flex-direction:column;align-items:'+(moj?'flex-end':'flex-start')+';';
                var ts = k.created_at.substring(5,16).replace('T',' ').replace('-','.');
                div.innerHTML = '<div style="font-size:11px;color:var(--muted);margin-bottom:2px;'+(moj?'text-align:right;':'')+'">'+(moj?'':'<strong>'+escH(k.autor_ime)+'</strong> · ')+ts+'</div>'+
                    '<div style="max-width:85%;background:'+(moj?'#1d4ed8':'#f1f5f9')+';color:'+(moj?'#fff':'var(--text)')+';border-radius:'+(moj?'14px 14px 4px 14px':'14px 14px 14px 4px')+';padding:8px 13px;font-size:13px;line-height:1.4;">'+escH(k.tekst)+'</div>';
                lista.appendChild(div);
            });
            // Ažuriraj last_ts
            _zOtvoreni[id] = d.komentari[d.komentari.length-1].created_at;
            // Ažuriraj badge
            var wrap = document.getElementById('zkom-wrap-'+id);
            if (wrap) wrap.dataset.lastTs = _zOtvoreni[id];
            var btn = document.getElementById('zbtn-kom-'+id);
            if (btn) {
                var sp = btn.querySelector('span');
                var cur = sp ? parseInt(sp.textContent||'0') : 0;
                var nova = d.komentari.length;
                if (sp) sp.textContent = cur + nova;
                else btn.innerHTML += ' <span>'+ nova +'</span>';
                btn.style.background='#eff6ff'; btn.style.color='#1d4ed8'; btn.style.borderColor='#bfdbfe';
            }
            lista.lastChild.scrollIntoView({behavior:'smooth',block:'nearest'});
        }).catch(function(){});
    });
}, 5000);

function escH(str) {
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function setUrlParam(key, val) {
    var url = new URL(window.location.href);
    url.searchParams.set('page','zadaci');
    if (val) url.searchParams.set(key, val);
    else url.searchParams.delete(key);
    if (key !== 'str') url.searchParams.delete('str');
    window.location.href = url.toString();
}

function setStatusFilter(s) { setUrlParam('zstatus', s); }

var _st;
function filterZadaci() {
    clearTimeout(_st);
    _st = setTimeout(function(){
        setUrlParam('zq', document.getElementById('z-search').value.trim());
    }, 400);
}

function dodajZadatak() {
    var tekst = document.getElementById('z-tekst').value.trim();
    var err   = document.getElementById('z-add-err');
    err.style.display = 'none';
    if (!tekst) { err.textContent='Upiši tekst.'; err.style.display='block'; return; }
    post({
        _action:'zadatak_add', tekst:tekst,
        kategorija: document.getElementById('z-kat').value.trim(),
        rok: document.getElementById('z-rok').value,
        dodeljeno_id: document.getElementById('z-dodeljeno').value,
    }).then(function(d) {
        if (d.ok) location.reload();
        else { err.textContent = d.err||'Greška.'; err.style.display='block'; }
    });
}

function prihvatiZadatak(id) {
    if (!confirm('Prihvatiti ovaj zadatak?')) return;
    post({ _action:'zadatak_prihvati', id:id }).then(function(d) {
        if (d.ok) location.reload(); else alert(d.err||'Greška.');
    });
}

function promeniStatus(id, status) {
    post({ _action:'zadatak_status', id:id, status:status }).then(function(d) {
        if (d.ok) location.reload();
    });
}

function ciklajStatus(id, current) {
    var next = current==='otvoreno'?'u_toku':current==='u_toku'?'zavrseno':'otvoreno';
    promeniStatus(id, next);
}

function toggleZKom(id) {
    var wrap  = document.getElementById('zkom-wrap-'+id);
    var ikona = document.getElementById('zikona-'+id);
    var otvoren = wrap.style.display !== 'none';
    wrap.style.display = otvoren ? 'none' : 'block';
    if (ikona) ikona.textContent = otvoren ? '▶' : '▼';
    // Swap teksta
    var kratki = document.getElementById('ztekst-kratki-'+id);
    var pun    = document.getElementById('ztekst-pun-'+id);
    if (kratki && pun) {
        kratki.style.display = otvoren ? '' : 'none';
        pun.style.display    = otvoren ? 'none' : '';
    }
    if (!otvoren) {
        _zOtvoreni[id] = wrap.dataset.lastTs || '2000-01-01 00:00:00';
        setTimeout(function(){
            document.getElementById('zkom-row-'+id).scrollIntoView({behavior:'smooth',block:'nearest'});
        }, 100);
    } else {
        delete _zOtvoreni[id];
    }
}

function posaljiZKomentar(input) {
    var tekst = input.value.trim();
    if (!tekst) return;
    var id = input.dataset.id;
    post({ _action:'zadatak_komentar', id:id, tekst:tekst }).then(function(d) {
        if (!d.ok) { alert(d.err||'Greška.'); return; }
        var lista = document.getElementById('zkom-lista-'+id);
        var sada  = new Date();
        var v = sada.getDate()+'.'+(sada.getMonth()+1)+' '+
                String(sada.getHours()).padStart(2,'0')+':'+String(sada.getMinutes()).padStart(2,'0');
        var div = document.createElement('div');
        div.style.cssText = 'display:flex;flex-direction:column;align-items:flex-end;';
        div.innerHTML = '<div style="font-size:11px;color:var(--muted);margin-bottom:2px;text-align:right;">'+v+'</div>'+
            '<div style="max-width:90%;background:#1d4ed8;color:#fff;border-radius:14px 14px 4px 14px;padding:6px 11px;font-size:13px;line-height:1.4;">'+
            tekst.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')+'</div>';
        lista.appendChild(div);
        input.value = '';
        div.scrollIntoView({behavior:'smooth',block:'nearest'});
        var btn = document.getElementById('zbtn-kom-'+id);
        if (btn) {
            var sp = btn.querySelector('span:not(#zikona-'+id+')');
            // Ažuriraj tekst dugmeta
            var ikona = document.getElementById('zikona-'+id);
            var ikonaHtml = ikona ? ikona.outerHTML : '▼';
            var cur = btn.textContent.match(/\d+/);
            var nova_br = (cur ? parseInt(cur[0]) : 0) + 1;
            btn.innerHTML = ikonaHtml + ' 💬 ' + nova_br + ' ' + (nova_br===1?'poruka':(nova_br<5?'poruke':'poruka'));
        }
    });
}

function otvoriEditZadatak(id,tekst,kat,status,rok,dodeId) {
    document.getElementById('z-edit-id').value        = id;
    document.getElementById('z-edit-tekst').value     = tekst;
    document.getElementById('z-edit-kat').value       = kat;
    document.getElementById('z-edit-status').value    = status;
    document.getElementById('z-edit-rok').value       = rok;
    document.getElementById('z-edit-dodeljeno').value = dodeId||'';
    document.getElementById('z-edit-err').style.display = 'none';
    document.getElementById('z-edit-modal').style.display = 'flex';
}

function sacuvajZadatak() {
    var id    = document.getElementById('z-edit-id').value;
    var tekst = document.getElementById('z-edit-tekst').value.trim();
    var err   = document.getElementById('z-edit-err');
    err.style.display = 'none';
    if (!tekst) { err.textContent='Tekst je obavezan.'; err.style.display='block'; return; }
    post({
        _action:'zadatak_edit', id:id, tekst:tekst,
        kategorija: document.getElementById('z-edit-kat').value.trim(),
        status: document.getElementById('z-edit-status').value,
        rok: document.getElementById('z-edit-rok').value,
        dodeljeno_id: document.getElementById('z-edit-dodeljeno').value,
    }).then(function(d) {
        if (d.ok) { document.getElementById('z-edit-modal').style.display='none'; location.reload(); }
        else { err.textContent=d.err||'Greška.'; err.style.display='block'; }
    });
}

function obrisiZadatak(id) {
    if (!confirm('Obriši zadatak?')) return;
    post({ _action:'zadatak_delete', id:id }).then(function(d) {
        if (d.ok) document.getElementById('zcard-'+id).remove();
    });
}
</script>
