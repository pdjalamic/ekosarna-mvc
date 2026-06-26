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

// Veličina fajla (bajtovi -> čitljivo)
function fmtVelicina(int $b): string {
    if ($b >= 1048576) return number_format($b / 1048576, 1, ',', '.') . ' MB';
    if ($b >= 1024)    return number_format($b / 1024, 0, ',', '.') . ' KB';
    return $b . ' B';
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
    <?php if ($mozeZadati): ?>
    <button onclick="document.getElementById('z-novi-modal').style.display='flex'"
        style="background:#1d4ed8;color:#fff;border:none;border-radius:9px;padding:10px 18px;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap;flex-shrink:0;">
        + Novi zadatak
    </button>
    <?php endif; ?>
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
        <option value="" <?= $filters['dodeljeno']==='svi'?'selected':'' ?>>Prihvatio: Svi</option>
        <?php foreach ($korisnici as $u): ?>
        <option value="<?= $u['id'] ?>" <?= (is_int($filters['dodeljeno']) && $filters['dodeljeno']===(int)$u['id'])?'selected':'' ?>><?= h($u['ime']) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="z2-sel" onchange="setUrlParam('zsort', this.value)">
        <option value="" <?= $zsort==='default'?'selected':'' ?>>Sortiraj: Podrazumevano</option>
        <option value="rok_asc" <?= $zsort==='rok_asc'?'selected':'' ?>>Rok: najpre najbliži</option>
        <option value="rok_desc" <?= $zsort==='rok_desc'?'selected':'' ?>>Rok: najpre najdalji</option>
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
    $clanovi        = $z['clanovi'] ?? [];
    $jaClan = false; $jaPrihvatio = false;
    foreach ($clanovi as $c) {
        if ((int)$c['korisnik_id'] === (int)$uid) { $jaClan = true; $jaPrihvatio = (bool)$c['prihvatio']; }
    }
    $moguPrihvatiti = $jaClan && !$jaPrihvatio && $z['status'] !== 'zavrseno';
    $katBoje        = katBoja($z['kategorija'] ?? '');
    $rb             = ($stranica-1)*$po_stranici + $i + 1;
    $tekst_pun      = $z['tekst'];
    $tekst_kratki   = mb_strlen($tekst_pun) > 100 ? mb_substr($tekst_pun, 0, 100).'…' : $tekst_pun;
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
                <?php foreach ($clanovi as $c): ?>
                    <?php if ($c['prihvatio']): ?>
                    <span style="font-size:11px;background:#dcfce7;color:#15803d;border-radius:20px;padding:2px 8px;font-weight:700;" title="Prihvatio<?= $c['prihvatio_at'] ? ' · '.date('d.m H:i', strtotime($c['prihvatio_at'])) : '' ?>">✓ <?= h($c['korisnik_ime']) ?></span>
                    <?php else: ?>
                    <span style="font-size:11px;background:#fef3c7;color:#d97706;border-radius:20px;padding:2px 8px;" title="Čeka prihvatanje">⏳ <?= h($c['korisnik_ime']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
                <span style="font-size:10px;color:var(--muted);margin-left:auto;"><?= h($z['kreirao_ime']) ?> · <?= date('d.m.', strtotime($z['datum_kreiranja'])) ?></span>
            </div>
        </div>
        <!-- Akcije -->
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0;">
            <div style="display:flex;gap:4px;flex-wrap:wrap;justify-content:flex-end;align-items:center;">
                <?php if ($moguPrihvatiti): ?>
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
                <?php $moguPozvati = $jaClan || (int)$z['kreirao_id'] === (int)$uid || $jeDirektor; ?>
                <?php if ($moguPozvati): ?>
                <button class="btn-sm" onclick="otvoriPozovi(<?= $z['id'] ?>)" title="Pozovi još ljudi na zadatak"
                    style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;font-size:11px;white-space:nowrap;">➕ Pozovi</button>
                <?php endif; ?>
                <?php $brFajlova = count($z['fajlovi'] ?? []); ?>
                <button class="btn-sm" onclick="otvoriFajlove(<?= $z['id'] ?>)" title="Fajlovi na zadatku"
                    style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;font-size:11px;white-space:nowrap;">📎 Fajlovi<span id="zfajl-cnt-<?= $z['id'] ?>"><?= $brFajlova ? ' ('.$brFajlova.')' : '' ?></span></button>
                <button class="btn-sm" onclick="otvoriAlarm(<?= $z['id'] ?>)" title="Podsetnik / alarm"
                    style="background:#fff7ed;color:#c2410c;border-color:#fed7aa;font-size:11px;white-space:nowrap;">⏰</button>
                <?php if ($mozeZadati): ?>
                <button class="btn-sm" onclick="otvoriEditZadatak(<?= $z['id'] ?>,'<?= h(addslashes($z['tekst'])) ?>','<?= h(addslashes($z['kategorija'])) ?>','<?= $z['status'] ?>','<?= $z['rok']??'' ?>','<?= $z['dodeljeno_id']??'' ?>')" title="Izmeni">✎</button>
                <button class="btn-sm del" onclick="obrisiZadatak(<?= $z['id'] ?>)" title="Obriši">🗑</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Toggle: proširi + komentari -->
    <div style="margin-top:10px;border-top:1px solid var(--light2);padding-top:8px;">
        <button onclick="toggleZKom(<?= $z['id'] ?>)"
            style="background:none;border:none;cursor:pointer;font-size:12px;color:var(--blue);font-weight:600;padding:0;display:flex;align-items:center;gap:6px;"
            id="zbtn-kom-<?= $z['id'] ?>">
            <span id="zikona-<?= $z['id'] ?>">▶</span>
            <span id="zkom-count-<?= $z['id'] ?>" data-n="<?= (int)$z['komentar_count'] ?>"><?php
                if ($z['komentar_count'] > 0) {
                    echo '💬 ' . (int)$z['komentar_count'] . ' ' . ($z['komentar_count']===1?'poruka':($z['komentar_count']<5?'poruke':'poruka'));
                } else {
                    echo '💬 Dodaj komentar';
                }
            ?></span>
            <?php if (mb_strlen($tekst_pun) > 100): ?><span style="color:var(--muted);"> · prikaži ceo tekst</span><?php endif; ?>
        </button>

        <!-- Prošireni sadržaj -->
        <div id="zkom-wrap-<?= $z['id'] ?>"
            data-last-ts="<?= $lastTs ?>"
            style="display:none;margin-top:10px;">

            <!-- Fajlovi -->
            <?php
                $fajlovi      = $z['fajlovi'] ?? [];
                $jeKreatorZad = (int)$z['kreirao_id'] === (int)$uid;
                $moguKaciti   = $jaClan || $jeKreatorZad || $jeDirektor;
                $smemSkinuti  = $jaPrihvatio || $jeKreatorZad || $jeDirektor;
            ?>
            <div id="zfajl-sekcija-<?= $z['id'] ?>" style="margin-bottom:12px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                    <span style="font-size:12px;font-weight:700;color:var(--muted);">📎 Fajlovi</span>
                    <?php if ($moguKaciti): ?>
                    <input type="file" id="zfajl-input-<?= $z['id'] ?>" multiple style="display:none;" onchange="uploadFajl(<?= $z['id'] ?>, this)">
                    <button class="btn-sm" onclick="document.getElementById('zfajl-input-<?= $z['id'] ?>').click()"
                        style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;font-size:11px;">+ Dodaj fajl(ove)</button>
                    <span id="zfajl-status-<?= $z['id'] ?>" style="font-size:11px;color:var(--muted);"></span>
                    <?php endif; ?>
                </div>
                <div id="zfajl-lista-<?= $z['id'] ?>" style="display:flex;flex-direction:column;gap:4px;max-width:700px;">
                    <?php foreach ($fajlovi as $f):
                        $fext  = strtolower(pathinfo($f['naziv'], PATHINFO_EXTENSION));
                        $isImg = in_array($fext, ['jpg','jpeg','png','gif','webp'], true);
                        $isPdf = ($fext === 'pdf');
                        $furl  = BASE_URL . '/?page=zadaci&dl=' . (int)$f['id'];
                        $smem  = $smemSkinuti || (int)$f['dodao_id'] === (int)$uid;
                        $moguObrisati = (int)$f['dodao_id'] === (int)$uid || $jeKreatorZad || $jeDirektor;
                    ?>
                    <div id="zfajl-row-<?= (int)$f['id'] ?>" style="display:flex;align-items:center;gap:8px;background:var(--light);border:1px solid var(--light2);border-radius:8px;padding:6px 10px;font-size:12px;">
                        <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            📄 <?= h($f['naziv']) ?>
                            <span style="color:var(--muted);"> · <?= fmtVelicina((int)$f['velicina']) ?><?= $f['dodao_ime'] ? ' · '.h($f['dodao_ime']) : '' ?></span>
                        </span>
                        <?php if ($smem): ?>
                            <?php if ($isImg): ?>
                            <button class="btn-sm" onclick="openModal('<?= $furl ?>','img')" title="Pregledaj">👁</button>
                            <?php elseif ($isPdf): ?>
                            <button class="btn-sm" onclick="openModal('<?= $furl ?>','pdf')" title="Pregledaj">👁</button>
                            <?php endif; ?>
                            <a class="btn-sm" href="<?= $furl ?>&download=1" title="Preuzmi" style="text-decoration:none;">⬇</a>
                        <?php else: ?>
                            <span style="color:var(--muted);font-size:11px;white-space:nowrap;">🔒 nakon prihvatanja</span>
                        <?php endif; ?>
                        <?php if ($moguObrisati): ?>
                        <button class="btn-sm del" onclick="obrisiFajl(<?= (int)$f['id'] ?>, <?= $z['id'] ?>)" title="Obriši">🗑</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

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
        <button onclick="zatvoriZModal('z-novi-modal')"
            style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:16px;">+ Novi zadatak</h3>
        <div style="position:relative;margin-bottom:12px;">
            <textarea id="z-tekst" placeholder="Upiši novi zadatak..."
                style="width:100%;border:1.5px solid var(--light2);border-radius:8px;padding:10px 46px 10px 12px;font-size:14px;font-family:inherit;resize:vertical;outline:none;background:var(--light);box-sizing:border-box;min-height:80px;"></textarea>
            <button type="button" onclick="startMic('z-tekst', this)" id="mic-z-novi"
                style="position:absolute;top:8px;right:8px;background:#f1f5f9;color:#475569;border:1.5px solid var(--light2);border-radius:8px;padding:6px 10px;font-size:15px;cursor:pointer;line-height:1;" title="Glasovni unos">🎤</button>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
            <input type="text" id="z-kat" placeholder="Kategorija" list="kat-list" autocomplete="off"
                style="flex:1;min-width:140px;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;">
            <datalist id="kat-list">
                <?php foreach ($kategorije as $k): ?><option value="<?= h($k) ?>"><?php endforeach; ?>
            </datalist>
            <input type="date" id="z-rok" min="<?= $today ?>"
                style="flex:1;min-width:140px;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;">
        </div>
        <div style="margin-bottom:12px;">
            <div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:6px;">Dodeli osobama <span style="font-weight:400;">(jednoj ili više):</span></div>
            <div id="z-osobe" style="max-height:170px;overflow-y:auto;border:1.5px solid var(--light2);border-radius:8px;padding:6px 10px;background:var(--light);display:flex;flex-direction:column;gap:2px;">
                <?php foreach ($primaoci as $u): ?>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;padding:4px 2px;">
                    <input type="checkbox" class="z-osoba-ck" value="<?= $u['id'] ?>" style="width:16px;height:16px;cursor:pointer;flex-shrink:0;">
                    <?= h($u['ime']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div id="z-add-err" style="color:var(--red);font-size:12px;margin-bottom:8px;display:none;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button class="mail-cancel-btn" onclick="zatvoriZModal('z-novi-modal')">Odustani</button>
            <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="dodajZadatak()">+ Dodaj</button>
        </div>
    </div>
</div>

<!-- MODAL: Izmena zadatka -->
<div id="z-edit-modal"
    style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:500px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="zatvoriZModal('z-edit-modal')"
            style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:16px;">✎ Izmeni zadatak</h3>
        <input type="hidden" id="z-edit-id">
        <div style="position:relative;margin-bottom:10px;">
            <textarea id="z-edit-tekst" rows="3"
                style="width:100%;border:1.5px solid var(--light2);border-radius:8px;padding:9px 46px 9px 12px;font-size:14px;font-family:inherit;resize:vertical;outline:none;background:var(--light);box-sizing:border-box;"></textarea>
            <button type="button" onclick="startMic('z-edit-tekst', this)" id="mic-z-edit"
                style="position:absolute;top:8px;right:8px;background:#f1f5f9;color:#475569;border:1.5px solid var(--light2);border-radius:8px;padding:6px 10px;font-size:15px;cursor:pointer;line-height:1;" title="Glasovni unos">🎤</button>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
            <input type="text" id="z-edit-kat" placeholder="Kategorija" list="kat-list-edit"
                style="flex:1;min-width:120px;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;">
            <datalist id="kat-list-edit">
                <?php foreach ($kategorije as $k): ?><option value="<?= h($k) ?>"><?php endforeach; ?>
            </datalist>
            <input type="date" id="z-edit-rok" min="<?= $today ?>"
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
                <?php foreach ($primaoci as $u): ?><option value="<?= $u['id'] ?>"><?= h($u['ime']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div id="z-edit-err" style="color:var(--red);font-size:12px;margin-bottom:8px;display:none;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button class="mail-cancel-btn" onclick="zatvoriZModal('z-edit-modal')">Odustani</button>
            <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="sacuvajZadatak()">Sačuvaj</button>
        </div>
    </div>
</div>

<!-- MODAL: Pozovi još ljudi -->
<div id="z-pozovi-modal"
    style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:460px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="zatvoriZModal('z-pozovi-modal')"
            style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:6px;">➕ Pozovi još ljudi</h3>
        <div style="font-size:12px;color:var(--muted);margin-bottom:14px;">Pozvane osobe dobijaju notifikaciju i mogu da prihvate zadatak.</div>
        <input type="hidden" id="z-pozovi-id">
        <div id="z-pozovi-osobe" style="max-height:260px;overflow-y:auto;border:1.5px solid var(--light2);border-radius:8px;padding:6px 10px;background:var(--light);display:flex;flex-direction:column;gap:2px;"></div>
        <div id="z-pozovi-err" style="color:var(--red);font-size:12px;margin-top:8px;display:none;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px;">
            <button class="mail-cancel-btn" onclick="zatvoriZModal('z-pozovi-modal')">Odustani</button>
            <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="posaljiPoziv()">Pozovi</button>
        </div>
    </div>
</div>

<!-- MODAL: Podsetnik / alarm -->
<div id="z-alarm-modal"
    style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:460px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="zatvoriZModal('z-alarm-modal')"
            style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:6px;">⏰ Podsetnik za zadatak</h3>
        <div style="font-size:12px;color:var(--muted);margin-bottom:14px;">Stiže kao push/Telegram poruka u izabrano vreme.</div>
        <input type="hidden" id="z-alarm-id">

        <div id="z-alarm-lista" style="display:flex;flex-direction:column;gap:6px;margin-bottom:14px;"></div>

        <div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:6px;">Novi podsetnik</div>
        <input type="datetime-local" id="z-alarm-kada"
            style="width:100%;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;box-sizing:border-box;">
        <div id="z-alarm-presets" style="display:flex;gap:6px;flex-wrap:wrap;margin:8px 0;"></div>
        <input type="text" id="z-alarm-poruka" maxlength="255" placeholder="Napomena (opciono)"
            style="width:100%;border:1.5px solid var(--light2);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--light);outline:none;box-sizing:border-box;margin-bottom:10px;">
        <div id="z-alarm-tip-wrap" style="display:flex;gap:16px;align-items:center;font-size:13px;margin-bottom:6px;">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;"><input type="radio" name="z-alarm-tip" value="licni" checked> Samo mene</label>
            <label id="z-alarm-tim-lbl" style="display:flex;align-items:center;gap:6px;cursor:pointer;"><input type="radio" name="z-alarm-tip" value="tim"> Ceo tim</label>
        </div>
        <div id="z-alarm-err" style="color:var(--red);font-size:12px;margin-top:6px;display:none;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px;">
            <button class="mail-cancel-btn" onclick="zatvoriZModal('z-alarm-modal')">Odustani</button>
            <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="postaviAlarm()">Postavi podsetnik</button>
        </div>
    </div>
</div>

<script>
var _zUid = <?= (int)$uid ?>;
var _zOtvoreni = {}; // zadatak_id -> last_ts
// Za modal „Pozovi": lista svih primaoca + članovi po zadatku (da ne nudimo već dodate)
var _zPrimaoci = <?= json_encode(array_map(fn($u) => ['id' => (int)$u['id'], 'ime' => $u['ime']], $primaoci), JSON_UNESCAPED_UNICODE) ?>;
var _zClanovi = <?php
    $m = [];
    foreach ($zadaci as $zz) {
        $m[(int)$zz['id']] = array_map(fn($c) => (int)$c['korisnik_id'], $zz['clanovi'] ?? []);
    }
    echo json_encode($m);
?>;
// Alarmi: dozvola za „ceo tim", kreator/rok po zadatku, i vidljivi zakazani podsetnici
var _zMozeZadati = <?= $mozeZadati ? 'true' : 'false' ?>;
var _zKreator = <?php
    $mk = [];
    foreach ($zadaci as $zz) { $mk[(int)$zz['id']] = (int)$zz['kreirao_id']; }
    echo json_encode($mk);
?>;
var _zRok = <?php
    $mr = [];
    foreach ($zadaci as $zz) { $mr[(int)$zz['id']] = $zz['rok'] ?: ''; }
    echo json_encode($mr, JSON_UNESCAPED_UNICODE);
?>;
var _zAlarmi = <?php
    $ma = [];
    foreach ($zadaci as $zz) {
        $vis = [];
        foreach ($zz['alarmi'] ?? [] as $al) {
            $tim = empty($al['korisnik_id']);
            $moj = ((int)$al['postavio_id'] === (int)$uid) || ((int)$al['korisnik_id'] === (int)$uid);
            if ($tim || $moj || $jeDirektor) {
                $vis[] = [
                    'id'      => (int)$al['id'],
                    'tim'     => $tim,
                    'send_at' => $al['send_at'],
                    'poruka'  => $al['poruka'],
                ];
            }
        }
        $ma[(int)$zz['id']] = $vis;
    }
    echo json_encode($ma, JSON_UNESCAPED_UNICODE);
?>;

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
            d.komentari.forEach(function(k) { renderKomBubble(lista, k); });
            // Ažuriraj poll-kursor
            _zOtvoreni[id] = d.komentari[d.komentari.length-1].created_at;
            var wrap = document.getElementById('zkom-wrap-'+id);
            if (wrap) wrap.dataset.lastTs = _zOtvoreni[id];
            // Ažuriraj brojač poruka (na namenskom span-u, ne na strelici)
            bumpKomCount(id, d.komentari.length);
            var btn = document.getElementById('zbtn-kom-'+id);
            if (btn) { btn.style.background='#eff6ff'; btn.style.color='#1d4ed8'; btn.style.borderColor='#bfdbfe'; }
            lista.lastChild.scrollIntoView({behavior:'smooth',block:'nearest'});
        }).catch(function(){});
    });
}, 5000);

function escH(str) {
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// "YYYY-MM-DD HH:MM:SS" (server) -> "DD.MM HH:MM"
function formatKomTs(s) {
    var m = String(s||'').match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
    return m ? (m[3]+'.'+m[2]+' '+m[4]+':'+m[5]) : '';
}
function pluralPoruka(n) { return n===1?'poruka':(n<5?'poruke':'poruka'); }

// Jedinstven render mehurića (koriste ga i polling i ručno slanje) — pošiljalac desno, primalac levo
function renderKomBubble(lista, k) {
    var moj = (k.autor_id == _zUid);
    var ts  = formatKomTs(k.created_at);
    var div = document.createElement('div');
    div.style.cssText = 'display:flex;flex-direction:column;align-items:'+(moj?'flex-end':'flex-start')+';';
    div.innerHTML =
        '<div style="font-size:11px;color:var(--muted);margin-bottom:2px;'+(moj?'text-align:right;':'')+'">'+
        (moj?'':'<strong>'+escH(k.autor_ime)+'</strong> · ')+ts+'</div>'+
        '<div style="max-width:85%;background:'+(moj?'#1d4ed8':'#f1f5f9')+';color:'+(moj?'#fff':'var(--text)')+
        ';border-radius:'+(moj?'14px 14px 4px 14px':'14px 14px 14px 4px')+';padding:8px 13px;font-size:13px;line-height:1.4;">'+
        escH(k.tekst)+'</div>';
    lista.appendChild(div);
    return div;
}
// Brojač poruka na dugmetu — uvek na #zkom-count-<id>, nikad na strelici
function setKomCount(id, n) {
    var sp = document.getElementById('zkom-count-'+id);
    if (!sp) return;
    sp.dataset.n = n;
    sp.textContent = n>0 ? ('💬 '+n+' '+pluralPoruka(n)) : '💬 Dodaj komentar';
}
function bumpKomCount(id, delta) {
    var sp = document.getElementById('zkom-count-'+id);
    var cur = sp ? (parseInt(sp.dataset.n||'0',10)||0) : 0;
    setKomCount(id, cur + delta);
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
    var osobe = Array.prototype.map.call(
        document.querySelectorAll('.z-osoba-ck:checked'), function(ck){ return ck.value; });
    var err   = document.getElementById('z-add-err');
    err.style.display = 'none';
    if (!tekst) { err.textContent='Upiši tekst.'; err.style.display='block'; return; }
    if (!osobe.length) { err.textContent='Izaberi bar jednu osobu kojoj dodeljuješ zadatak.'; err.style.display='block'; return; }
    var rok = document.getElementById('z-rok').value;
    if (rok && rok < '<?= $today ?>') {
        err.textContent='Rok izvršenja ne može biti raniji od današnjeg datuma. Izaberite današnji ili neki budući datum.';
        err.style.display='block'; return;
    }
    // dodeljeno_id[] -> ručno preko FormData (post() ne ume niz)
    var fd = new FormData();
    fd.append('_action', 'zadatak_add');
    fd.append('tekst', tekst);
    fd.append('kategorija', document.getElementById('z-kat').value.trim());
    fd.append('rok', rok);
    osobe.forEach(function(idv){ fd.append('dodeljeno_id[]', idv); });
    fetch(window.location.pathname, { method:'POST', body:fd })
      .then(function(r){ return r.json(); })
      .then(function(d) {
        if (d.ok) location.reload();
        else { err.textContent = d.err||'Greška.'; err.style.display='block'; }
      })
      .catch(function(){ err.textContent='Greška u komunikaciji.'; err.style.display='block'; });
}

function prihvatiZadatak(id) {
    if (!confirm('Prihvatiti ovaj zadatak?')) return;
    post({ _action:'zadatak_prihvati', id:id }).then(function(d) {
        if (d.ok) location.reload(); else alert(d.err||'Greška.');
    });
}

function otvoriPozovi(id) {
    document.getElementById('z-pozovi-id').value = id;
    document.getElementById('z-pozovi-err').style.display = 'none';
    var postoje = _zClanovi[id] || [];
    var slobodni = _zPrimaoci.filter(function(u){ return postoje.indexOf(u.id) === -1; });
    var box = document.getElementById('z-pozovi-osobe');
    if (!slobodni.length) {
        box.innerHTML = '<div style="font-size:13px;color:var(--muted);padding:8px;">Sve osobe su već na zadatku.</div>';
    } else {
        box.innerHTML = slobodni.map(function(u){
            return '<label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;padding:4px 2px;">'+
                '<input type="checkbox" class="z-poz-ck" value="'+u.id+'" style="width:16px;height:16px;cursor:pointer;flex-shrink:0;">'+
                escH(u.ime)+'</label>';
        }).join('');
    }
    document.getElementById('z-pozovi-modal').style.display = 'flex';
}

function posaljiPoziv() {
    var id = document.getElementById('z-pozovi-id').value;
    var osobe = Array.prototype.map.call(
        document.querySelectorAll('.z-poz-ck:checked'), function(ck){ return ck.value; });
    var err = document.getElementById('z-pozovi-err');
    err.style.display = 'none';
    if (!osobe.length) { err.textContent='Izaberi bar jednu osobu.'; err.style.display='block'; return; }
    var fd = new FormData();
    fd.append('_action', 'zadatak_pozovi');
    fd.append('id', id);
    osobe.forEach(function(idv){ fd.append('osobe[]', idv); });
    fetch(window.location.pathname, { method:'POST', body:fd })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.ok) location.reload();
        else { err.textContent=d.err||'Greška.'; err.style.display='block'; }
      })
      .catch(function(){ err.textContent='Greška u komunikaciji.'; err.style.display='block'; });
}

// ── Podsetnik / alarm ────────────────────────────────────────
function _zPad(n){ return (n<10?'0':'')+n; }
function _zToLocal(d){
    return d.getFullYear()+'-'+_zPad(d.getMonth()+1)+'-'+_zPad(d.getDate())+'T'+_zPad(d.getHours())+':'+_zPad(d.getMinutes());
}
function _zFmtAlarm(s){
    var m = String(s||'').match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
    return m ? (m[3]+'.'+m[2]+'.'+m[1]+'. '+m[4]+':'+m[5]) : s;
}
function otvoriAlarm(id){
    document.getElementById('z-alarm-id').value = id;
    document.getElementById('z-alarm-err').style.display='none';
    document.getElementById('z-alarm-kada').value='';
    document.getElementById('z-alarm-poruka').value='';
    var moguTim = _zMozeZadati || (_zKreator[id] == _zUid);
    document.getElementById('z-alarm-tim-lbl').style.display = moguTim ? 'flex' : 'none';
    var licni = document.querySelector('input[name="z-alarm-tip"][value="licni"]');
    if (licni) licni.checked = true;
    renderAlarmPresets(id);
    renderAlarmLista(id);
    document.getElementById('z-alarm-modal').style.display='flex';
}
function renderAlarmPresets(id){
    var box = document.getElementById('z-alarm-presets');
    var now = new Date();
    var za1h  = new Date(now.getTime()+3600000);
    var sutra = new Date(now.getFullYear(), now.getMonth(), now.getDate()+1, 8, 0);
    var html = '<button type="button" class="btn-sm" onclick="document.getElementById(\'z-alarm-kada\').value=\''+_zToLocal(za1h)+'\'">za 1h</button>'+
               '<button type="button" class="btn-sm" onclick="document.getElementById(\'z-alarm-kada\').value=\''+_zToLocal(sutra)+'\'">sutra 08:00</button>';
    var rok = _zRok[id];
    if (rok) {
        var rd = new Date(rok+'T08:00');
        if (!isNaN(rd.getTime())) {
            html += '<button type="button" class="btn-sm" onclick="document.getElementById(\'z-alarm-kada\').value=\''+_zToLocal(rd)+'\'">na dan roka 08:00</button>';
        }
    }
    box.innerHTML = html;
}
function renderAlarmLista(id){
    var box = document.getElementById('z-alarm-lista');
    var lista = _zAlarmi[id] || [];
    if (!lista.length) { box.innerHTML=''; return; }
    box.innerHTML = '<div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:2px;">Zakazani podsetnici</div>' +
        lista.map(function(a){
            return '<div style="display:flex;align-items:center;gap:8px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:6px 10px;font-size:12px;">'+
                '<span style="flex:1;">⏰ '+_zFmtAlarm(a.send_at)+' · '+(a.tim?'<strong>ceo tim</strong>':'samo ja')+
                (a.poruka?(' — '+escH(a.poruka)):'')+'</span>'+
                '<button class="btn-sm del" onclick="obrisiAlarm('+a.id+')" title="Otkaži">🗑</button>'+
            '</div>';
        }).join('');
}
function postaviAlarm(){
    var id   = document.getElementById('z-alarm-id').value;
    var kada = document.getElementById('z-alarm-kada').value;
    var poruka = document.getElementById('z-alarm-poruka').value.trim();
    var tipEl = document.querySelector('input[name="z-alarm-tip"]:checked');
    var tip = tipEl ? tipEl.value : 'licni';
    var err = document.getElementById('z-alarm-err'); err.style.display='none';
    if (!kada) { err.textContent='Izaberi datum i vreme podsetnika.'; err.style.display='block'; return; }
    post({ _action:'zadatak_alarm_add', id:id, kada:kada, poruka:poruka, tip:tip }).then(function(d){
        if (d.ok) location.reload();
        else { err.textContent=d.err||'Greška.'; err.style.display='block'; }
    });
}
function obrisiAlarm(alarmId){
    if (!confirm('Otkazati ovaj podsetnik?')) return;
    post({ _action:'zadatak_alarm_delete', id:alarmId }).then(function(d){
        if (d.ok) location.reload(); else alert(d.err||'Greška.');
    });
}

// ── Fajlovi ──────────────────────────────────────────────────
// Otvori (proširi) zadatak i skroluj do sekcije fajlova
function otvoriFajlove(id){
    var wrap = document.getElementById('zkom-wrap-'+id);
    if (wrap && wrap.style.display === 'none') toggleZKom(id);
    setTimeout(function(){
        var sec = document.getElementById('zfajl-sekcija-'+id);
        if (sec) sec.scrollIntoView({behavior:'smooth', block:'center'});
    }, 120);
}
function fmtVel(b){
    b = (+b)||0;
    if (b >= 1048576) return (Math.round(b/1048576*10)/10).toString().replace('.',',')+' MB';
    if (b >= 1024)    return Math.round(b/1024)+' KB';
    return b+' B';
}
// Broj fajlova -> indikator na dugmetu „📎 Fajlovi (N)"
function updateFajlCnt(id){
    var lista = document.getElementById('zfajl-lista-'+id);
    var n = lista ? lista.querySelectorAll('[id^="zfajl-row-"]').length : 0;
    var cnt = document.getElementById('zfajl-cnt-'+id);
    if (cnt) cnt.textContent = n ? (' ('+n+')') : '';
}
// Red fajla (klijentski render za upravo dodate — autor uvek sme pregled/preuzimanje/brisanje)
function fajlRowHtml(id, f){
    var ext = String(f.ext||'').toLowerCase();
    var isImg = ['jpg','jpeg','png','gif','webp'].indexOf(ext) !== -1;
    var isPdf = ext === 'pdf';
    var view = isImg ? '<button class="btn-sm" onclick="openModal(\''+f.url+'\',\'img\')" title="Pregledaj">👁</button>'
             : (isPdf ? '<button class="btn-sm" onclick="openModal(\''+f.url+'\',\'pdf\')" title="Pregledaj">👁</button>' : '');
    return '<div id="zfajl-row-'+f.id+'" style="display:flex;align-items:center;gap:8px;background:var(--light);border:1px solid var(--light2);border-radius:8px;padding:6px 10px;font-size:12px;">'+
        '<span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">📄 '+escH(f.naziv)+
        '<span style="color:var(--muted);"> · '+fmtVel(f.velicina)+(f.dodao_ime?(' · '+escH(f.dodao_ime)):'')+'</span></span>'+
        view+
        '<a class="btn-sm" href="'+f.url+'&download=1" title="Preuzmi" style="text-decoration:none;">⬇</a>'+
        '<button class="btn-sm del" onclick="obrisiFajl('+f.id+','+id+')" title="Obriši">🗑</button>'+
    '</div>';
}
function uploadFajl(id, input){
    if (!input.files || !input.files.length) return;
    var files = input.files;
    var prevelik = [];
    for (var i=0;i<files.length;i++){ if (files[i].size > 25*1024*1024) prevelik.push(files[i].name); }
    if (prevelik.length){ alert('Preveliki fajlovi (preko 25 MB):\n'+prevelik.join('\n')+'\n\nUkloni ih iz izbora pa pokušaj ponovo.'); input.value=''; return; }
    var status = document.getElementById('zfajl-status-'+id);
    if (status) status.textContent = 'Uploadujem '+files.length+' fajl'+(files.length===1?'':'(ova)')+'…';
    var fd = new FormData();
    fd.append('_action', 'zadatak_fajl_add');
    fd.append('id', id);
    for (var j=0;j<files.length;j++){ fd.append('fajlovi[]', files[j]); }
    fetch(window.location.pathname, { method:'POST', body:fd })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (status) status.textContent = '';
        input.value = '';
        if (d.ok) {
            var lista = document.getElementById('zfajl-lista-'+id);
            if (lista && d.fajlovi) d.fajlovi.forEach(function(f){ lista.insertAdjacentHTML('beforeend', fajlRowHtml(id, f)); });
            updateFajlCnt(id);
            if (d.greske && d.greske.length) alert('Neki fajlovi nisu dodati:\n'+d.greske.join('\n'));
        } else { alert(d.err||'Greška pri uploadu.'); }
      })
      .catch(function(){ if(status) status.textContent=''; alert('Greška u komunikaciji.'); input.value=''; });
}
function obrisiFajl(fid, taskId){
    if (!confirm('Obrisati ovaj fajl?')) return;
    post({ _action:'zadatak_fajl_delete', id:fid }).then(function(d){
        if (d.ok) {
            var row = document.getElementById('zfajl-row-'+fid);
            if (row) row.remove();
            if (taskId) updateFajlCnt(taskId);
        } else alert(d.err||'Greška.');
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
        input.value = '';
        var lista = document.getElementById('zkom-lista-'+id);
        // Koristi komentar koji je server vratio (sa tačnim created_at i autorom)
        var k = d.komentar || { autor_id:_zUid, autor_ime:'', tekst:tekst, created_at:'' };
        var div = renderKomBubble(lista, k);
        div.scrollIntoView({behavior:'smooth',block:'nearest'});
        bumpKomCount(id, 1);
        // Pomeri poll-kursor na ovaj komentar da ga polling NE bi dodao po drugi put
        if (k.created_at) {
            _zOtvoreni[id] = k.created_at;
            var wrap = document.getElementById('zkom-wrap-'+id);
            if (wrap) wrap.dataset.lastTs = k.created_at;
        }
    });
}

function otvoriEditZadatak(id,tekst,kat,status,rok,dodeId) {
    document.getElementById('z-edit-id').value        = id;
    document.getElementById('z-edit-tekst').value     = tekst;
    document.getElementById('z-edit-kat').value       = kat;
    document.getElementById('z-edit-status').value    = status;
    document.getElementById('z-edit-rok').value       = rok;
    window._zEditRokOrig = rok || '';
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
    var rok = document.getElementById('z-edit-rok').value;
    if (rok && rok < '<?= $today ?>' && rok !== (window._zEditRokOrig || '')) {
        err.textContent='Rok izvršenja ne može biti raniji od današnjeg datuma. Izaberite današnji ili neki budući datum.';
        err.style.display='block'; return;
    }
    post({
        _action:'zadatak_edit', id:id, tekst:tekst,
        kategorija: document.getElementById('z-edit-kat').value.trim(),
        status: document.getElementById('z-edit-status').value,
        rok: rok,
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

// ── Glasovni unos ────────────────────────────────────────────
var _micRec = null;

function zatvoriZModal(id) {
    if (_micRec) { try { _micRec.stop(); } catch(e) {} _micRec = null; }
    var el = document.getElementById(id);
    if (el) el.style.display = 'none';
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

// ── Otvori konkretan zadatak iz notifikacije (?openz=<id>) ──
(function() {
    var openz = new URLSearchParams(window.location.search).get('openz');
    if (!openz) return;
    var card = document.getElementById('zcard-'+openz);
    if (!card) return; // zadatak nije na ovoj strani/filteru
    var wrap = document.getElementById('zkom-wrap-'+openz);
    if (wrap && wrap.style.display === 'none') toggleZKom(openz); // proširi tekst + komentare
    setTimeout(function() {
        card.scrollIntoView({behavior:'smooth', block:'center'});
        card.style.transition = 'box-shadow .3s';
        card.style.boxShadow = '0 0 0 3px #93c5fd';
        setTimeout(function(){ card.style.boxShadow = ''; }, 2500);
    }, 300);
})();
</script>
