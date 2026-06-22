<style>
@media (max-width: 720px) {
    /* Evidencija: široke tabele -> kartice na telefonu (bez horizontalnog skrola) */
    .rs-tabela-wrap { overflow-x: visible !important; }
    .rs-tabela { display: block; width: 100%; min-width: 0 !important; }
    .rs-tabela thead { display: none; }
    .rs-tabela tbody { display: block; }
    .rs-tabela tr.rs-red {
        display: block;
        border: 1.5px solid var(--light2);
        border-radius: 10px;
        margin-bottom: 12px;
        padding: 6px 14px;
        background: #fff;
    }
    .rs-tabela td {
        display: block;
        width: auto !important;
        text-align: left !important;
        padding: 5px 0 !important;
        border-bottom: 1px solid var(--light);
        white-space: normal !important;
    }
    .rs-tabela tr.rs-red td:last-child { border-bottom: none; }
    .rs-tabela td:nth-child(1) { display: none; } /* redni broj */
    .rs-tabela td::before {
        display: block;
        font-weight: 700;
        color: var(--muted);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 2px;
    }
    .rs-vreme td:nth-child(2)::before { content: "Datum"; }
    .rs-vreme td:nth-child(3)::before { content: "Radnik"; }
    .rs-vreme td:nth-child(4)::before { content: "Gradilište / zadatak"; }
    .rs-vreme td:nth-child(5)::before { content: "Vreme"; }
    .rs-vreme td:nth-child(6)::before { content: "Sati"; }
    .rs-vreme td:nth-child(7)::before { content: "Opis rada"; }
    .rs-mat td:nth-child(2)::before { content: "Datum"; }
    .rs-mat td:nth-child(3)::before { content: "Radnik"; }
    .rs-mat td:nth-child(4)::before { content: "Gradilište / zadatak"; }
    .rs-mat td:nth-child(5)::before { content: "Artikal"; }
    .rs-mat td:nth-child(6)::before { content: "Količina"; }
    .rs-mat td:nth-child(7)::before { content: "JM"; }

    /* Materijal: Artikal + Količina + JM u istom redu unutar kartice */
    .rs-mat tr.rs-red { display: flex; flex-wrap: wrap; align-items: flex-start; }
    .rs-mat td { flex: 0 0 100%; }
    .rs-mat td:nth-child(5) { flex: 1 1 45%; }
    .rs-mat td:nth-child(6),
    .rs-mat td:nth-child(7) {
        flex: 0 0 auto;
        text-align: right;
        padding-left: 16px !important;
        border-bottom: none;
    }
}
</style>

<div class="topbar-admin" style="flex-wrap:wrap;gap:10px;">
    <div class="page-title">📊 Evidencija radova</div>
</div>

<!-- Filteri -->
<form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px;align-items:flex-end;">
    <input type="hidden" name="page" value="evidencija">
    <input type="hidden" name="tab" value="<?= h($tab) ?>">
    <input type="hidden" name="grupa" value="<?= h($grupisanje ?? 'ekipa') ?>">

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
    <?php if ($je_kancelarija): ?>
    <a href="?page=evidencija&tab=sumar&od=<?= h($filter_od) ?>&do=<?= h($filter_do) ?>&radnik=<?= $filter_radnik ?>&gradiliste=<?= $filter_grad ?>&grupa=<?= h($grupisanje) ?>"
       style="padding:9px 18px;font-size:13px;font-weight:600;text-decoration:none;border-radius:8px 8px 0 0;
              <?= $tab === 'sumar' ? 'background:#fff;color:var(--blue);border:2px solid var(--light2);border-bottom:2px solid #fff;margin-bottom:-2px;' : 'color:var(--muted);' ?>">
        📋 Dnevni pregled
    </a>
    <?php endif; ?>
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
<table class="rs-tabela rs-vreme">
    <thead>
        <tr>
            <th style="width:40px;text-align:center;">#</th>
            <th style="width:110px;">Datum</th>
            <th style="width:170px;">Ime i prezime</th>
            <th>Gradilište / Zadatak</th>
            <th style="width:110px;text-align:center;">Vreme</th>
            <th style="width:65px;text-align:center;">Sati</th>
            <th>Opis rada</th>
            <?php if ($is_admin): ?><th style="width:80px;text-align:right;"></th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php
    $redni_broj = 0;
    foreach ($vreme_unosi as $i => $v):
        $segmenti = null;
        if (!empty($v['segmenti'])) {
            $segmenti = is_string($v['segmenti']) ? json_decode($v['segmenti'], true) : $v['segmenti'];
        }

        if ($segmenti && count($segmenti) > 1):
            // Više segmenata — svaki kao poseban red
            foreach ($segmenti as $si => $seg):
                $redni_broj++;
                $isPrepravka = !empty($seg['prepravka']);
                $rowBg = $isPrepravka ? 'background:#fff7ed;' : '';
    ?>
    <tr class="rs-red" style="<?= $rowBg ?>">
        <td style="text-align:center;font-size:13px;color:var(--muted);"><?= $redni_broj ?></td>
        <td style="font-size:14px;color:var(--muted);"><?= date('d.m.Y', strtotime($v['datum'])) ?></td>
        <td style="font-size:14px;font-weight:600;"><?= h($v['radnik_ime']) ?></td>
        <td style="font-size:14px;">
            <?php if ($v['gradiliste_naziv']): ?>
            <span style="color:var(--blue);">🏗️ <?= h($v['gradiliste_naziv']) ?></span>
            <?php endif; ?>
            <?php if ($v['zadatak_opis']): ?>
            <div style="color:var(--muted);font-size:12px;margin-top:2px;"><?= h(mb_substr($v['zadatak_opis'], 0, 60)) ?><?= mb_strlen($v['zadatak_opis']) > 60 ? '...' : '' ?></div>
            <?php endif; ?>
        </td>
        <td style="text-align:center;font-size:14px;">
    <?= $v['vreme_od'] ? substr($v['vreme_od'],0,5) : '?' ?>–<?= $v['vreme_do'] ? substr($v['vreme_do'],0,5) : '?' ?>
</td>
        <td style="text-align:center;font-weight:700;font-size:15px;color:<?= $isPrepravka ? '#c2410c' : 'var(--blue)' ?>;">
            <?= number_format($seg['sati'] ?? 0, 1, ',', '.') ?>
        </td>
        <td style="font-size:14px;">
            <?php if ($isPrepravka): ?>
            <span style="font-size:11px;background:#fed7aa;color:#c2410c;border-radius:3px;padding:1px 6px;margin-right:6px;font-weight:700;">PREPRAVKA</span>
            <strong style="color:#c2410c;"><?= h($seg['opis'] ?? '') ?></strong>
            <?php else: ?>
            <span style="color:#1a3a6e;font-weight:600;"><?= h($seg['opis'] ?? '') ?></span>
            <?php endif; ?>
        </td>
        <?php if ($is_admin): ?>
        <td style="text-align:right;white-space:nowrap;">
            <?php if ($si === 0): ?>
            <button class="btn-sm" onclick="openIzmeniVreme(<?= $v['id'] ?>, '<?= h(addslashes($v['datum'])) ?>', '<?= h(addslashes($v['vreme_od'] ?? '')) ?>', '<?= h(addslashes($v['vreme_do'] ?? '')) ?>', <?= (float)($v['ukupno_sati'] ?? 0) ?>, '', '<?= h(addslashes(is_string($v['segmenti']) ? $v['segmenti'] : json_encode($v['segmenti']))) ?>')" title="Izmeni">✏️</button>
            <button class="btn-sm del" onclick="obrisiVreme(<?= $v['id'] ?>)" title="Obriši" style="margin-left:4px;">🗑</button>
            <?php endif; ?>
        </td>
        <?php endif; ?>
    </tr>
    <?php
            endforeach;
        else:
            // Jedan red — normalan prikaz
            $redni_broj++;
    ?>
    <tr class="rs-red">
        <td style="text-align:center;font-size:13px;color:var(--muted);"><?= $redni_broj ?></td>
        <td style="font-size:14px;color:var(--muted);"><?= date('d.m.Y', strtotime($v['datum'])) ?></td>
        <td style="font-size:14px;font-weight:600;"><?= h($v['radnik_ime']) ?></td>
        <td style="font-size:14px;">
            <?php if ($v['gradiliste_naziv']): ?>
            <span style="color:var(--blue);">🏗️ <?= h($v['gradiliste_naziv']) ?></span>
            <?php endif; ?>
            <?php if ($v['zadatak_opis']): ?>
            <div style="color:var(--muted);font-size:12px;margin-top:2px;"><?= h(mb_substr($v['zadatak_opis'], 0, 60)) ?><?= mb_strlen($v['zadatak_opis']) > 60 ? '...' : '' ?></div>
            <?php endif; ?>
        </td>
        <td style="text-align:center;font-size:14px;">
            <?= $v['vreme_od'] ? substr($v['vreme_od'],0,5) : '?' ?>–<?= $v['vreme_do'] ? substr($v['vreme_do'],0,5) : '?' ?>
        </td>
        <td style="text-align:center;font-weight:700;font-size:15px;color:var(--blue);">
            <?= $v['ukupno_sati'] ? number_format($v['ukupno_sati'], 1, ',', '.') : '—' ?>
        </td>
        <td style="font-size:14px;">
            <?php if ($v['napomena_original'] ?? null): ?>
            <div style="color:var(--muted);font-style:italic;margin-bottom:4px;font-size:13px;">
                📝 <?= h($v['napomena_original']) ?>
            </div>
            <?php endif; ?>
            <?php if ($v['napomena'] ?? null): ?>
            <div style="color:#1a3a6e;font-weight:600;"><?= h($v['napomena']) ?></div>
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
    <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</div>

<?php elseif ($tab === 'materijal'): ?>
<!-- ═══ UTROŠAK MATERIJALA ══════════════════════════════════ -->
<?php if ($is_admin): ?>
<div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
    <button class="btn-primary" onclick="openDodajMat()">➕ Dodaj utrošak</button>
</div>
<?php endif; ?>
<div class="rs-tabela-wrap">
<?php if (empty($mat_unosi)): ?>
    <div style="padding:40px;text-align:center;color:var(--muted);">Nema unosa materijala za izabrani period.</div>
<?php else: ?>
<table class="rs-tabela rs-mat">
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
        <td style="font-size:13px;">
            <?= h($m['naziv']) ?>
            <?php if (!empty($m['komentar'])): ?><div style="color:var(--muted);font-size:11px;margin-top:2px;">💬 <?= h($m['komentar']) ?></div><?php endif; ?>
        </td>
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

<?php elseif ($tab === 'sumar'): ?>
<!-- ═══ DNEVNI PREGLED (sumar po danu / timu / gradilištu) ═════ -->
<style>
.dp-card { border:1.5px solid var(--light2);border-radius:12px;background:#fff;margin-bottom:16px;overflow:hidden; }
.dp-head { display:flex;align-items:center;justify-content:space-between;gap:10px;background:#f7f9fd;padding:12px 16px;cursor:pointer;user-select:none; }
.dp-head-title { font-weight:700;color:var(--blue);font-size:15px; }
.dp-chev { color:var(--muted);font-size:14px;transition:transform .15s; }
.dp-card.collapsed .dp-body { display:none; }
.dp-card.collapsed .dp-chev { transform:rotate(180deg); }
.dp-body { padding:14px 16px;border-top:1px solid var(--light2); }
.dp-sec { display:flex;align-items:center;justify-content:space-between;gap:10px;margin:0 0 6px; }
.dp-sec-label { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted); }
.dp-badge { background:#dbe7f7;border:1px solid #c3d4ee;border-radius:99px;padding:3px 12px;font-size:12px;font-weight:700;color:#34548a;white-space:nowrap; }
.dp-tbl { width:100%;border-collapse:collapse;font-size:13px; }
.dp-tbl th { text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);padding:7px 10px;border-bottom:1.5px solid var(--light2); }
.dp-tbl td { padding:9px 10px;border-bottom:1px solid var(--light);vertical-align:top; }
.dp-tbl tr:last-child td { border-bottom:none; }
.dp-mat td { background:#fef6d8; }
.dp-foot { display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;background:#f7f9fd;border:1.5px solid var(--light2);border-radius:12px;padding:12px 18px;margin-top:6px;font-size:13px;color:var(--muted); }
.dp-foot strong { color:var(--text); }
@media (max-width:720px){ .dp-tbl-wrap{ overflow-x:auto; } .dp-tbl{ min-width:520px; } }
</style>
<?php
  $danUkupnoSati = array_sum(array_map(fn($t) => $t['ukupno_sati'], $sumar));
  $fmtSati = fn($h) => number_format((float)$h, 1, ',', '.') . 'h';
  $fmtKol  = fn($k) => rtrim(rtrim(number_format((float)$k, 3, ',', '.'), '0'), ',');

  $renderTim = function($t) use ($fmtSati, $fmtKol) { ?>
    <div class="dp-card">
      <div class="dp-head" onclick="this.parentNode.classList.toggle('collapsed')">
        <span class="dp-head-title">🏗️ <?= h($t['gradiliste_naziv']) ?><?php if ($t['zadatak_opis']): ?> <span style="color:var(--muted);font-weight:600;">— <?= h($t['zadatak_opis']) ?></span><?php endif; ?></span>
        <span class="dp-chev">⌃</span>
      </div>
      <div class="dp-body">
        <div class="dp-sec">
          <span class="dp-sec-label">Radni sati</span>
          <span class="dp-badge">Ukupno: <?= $fmtSati($t['ukupno_sati']) ?></span>
        </div>
        <div class="dp-tbl-wrap">
        <table class="dp-tbl">
          <thead><tr><th>Zaposleni</th><th>Uloga</th><th>Opis aktivnosti</th><th style="text-align:right;">Sati</th></tr></thead>
          <tbody>
          <?php if (empty($t['clanovi'])): ?>
            <tr><td colspan="4" style="color:var(--muted);">Nema unosa radnih sati.</td></tr>
          <?php else: foreach ($t['clanovi'] as $c): ?>
            <tr>
              <td style="font-weight:600;white-space:nowrap;">👷 <?= h($c['ime']) ?></td>
              <td style="color:var(--muted);white-space:nowrap;"><?= h($c['uloga'] ?? '') ?></td>
              <td><?= $c['opis'] !== '' ? nl2br(h($c['opis'])) : '<span style="color:var(--muted);">—</span>' ?></td>
              <td style="text-align:right;font-weight:700;color:var(--blue);white-space:nowrap;"><?= $fmtSati($c['sati']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
        </div>

        <div class="dp-sec" style="margin-top:16px;"><span class="dp-sec-label">Utrošeni materijal</span></div>
        <div class="dp-tbl-wrap">
        <table class="dp-tbl">
          <thead><tr><th>Materijal</th><th>Opis</th><th style="text-align:right;">Količina</th><th>Jedinica</th></tr></thead>
          <tbody>
          <?php if (empty($t['materijal'])): ?>
            <tr><td colspan="4" style="color:var(--muted);">Nema utroška.</td></tr>
          <?php else: foreach ($t['materijal'] as $m): ?>
            <tr class="dp-mat">
              <td style="font-weight:600;"><?= h($m['naziv']) ?></td>
              <td style="color:var(--muted);"><?= !empty($m['opis']) ? h($m['opis']) : '—' ?></td>
              <td style="text-align:right;font-weight:700;"><?= $fmtKol($m['kolicina']) ?></td>
              <td style="color:var(--muted);"><?= h($m['jm']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>
  <?php };
?>

<div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-bottom:16px;">
  <span style="font-size:13px;color:var(--muted);">Dan: <strong style="color:var(--text);"><?= date('d.m.Y', strtotime($sumar_dan)) ?></strong> <span style="font-size:11px;">(menja se poljem „Od" gore)</span></span>
  <span style="font-size:13px;color:var(--muted);">·</span>
  <span style="font-size:13px;color:var(--muted);">Ukupno sati (dan): <strong style="color:var(--blue);"><?= $fmtSati($danUkupnoSati) ?></strong></span>
  <div style="margin-left:auto;display:flex;gap:4px;">
    <?php $base = '?page=evidencija&tab=sumar&od='.h($filter_od).'&do='.h($filter_do).'&radnik='.$filter_radnik.'&gradiliste='.$filter_grad; ?>
    <a href="<?= $base ?>&grupa=ekipa" style="padding:6px 14px;font-size:13px;font-weight:600;text-decoration:none;border-radius:8px;<?= $grupisanje==='ekipa' ? 'background:var(--blue);color:#fff;' : 'background:var(--light);color:var(--text);' ?>">Po ekipi</a>
    <a href="<?= $base ?>&grupa=gradiliste" style="padding:6px 14px;font-size:13px;font-weight:600;text-decoration:none;border-radius:8px;<?= $grupisanje==='gradiliste' ? 'background:var(--blue);color:#fff;' : 'background:var(--light);color:var(--text);' ?>">Po gradilištu</a>
  </div>
</div>

<?php if (empty($sumar)): ?>
  <p style="color:var(--muted);text-align:center;padding:30px 0;">Nema unosa / timova za izabrani dan.</p>
<?php else: ?>
  <?php if ($grupisanje === 'gradiliste'): ?>
    <?php
      $poGrad = [];
      foreach ($sumar as $t) { $poGrad[$t['gradiliste_naziv']][] = $t; }
    ?>
    <?php foreach ($poGrad as $gnaziv => $tmovi): ?>
      <?php $gSati = array_sum(array_map(fn($t) => $t['ukupno_sati'], $tmovi)); ?>
      <div style="margin-bottom:22px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;background:var(--blue);color:#fff;border-radius:10px;padding:9px 14px;margin-bottom:10px;">
          <span style="font-weight:800;font-size:15px;">🏗️ <?= h($gnaziv) ?></span>
          <span style="font-weight:700;">Ukupno: <?= $fmtSati($gSati) ?></span>
        </div>
        <?php foreach ($tmovi as $t) { $renderTim($t); } ?>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <?php foreach ($sumar as $t) { $renderTim($t); } ?>
  <?php endif; ?>

  <?php
    $radniciSet = [];
    foreach ($sumar as $t) { foreach ($t['clanovi'] as $c) { $radniciSet[$c['radnik_id']] = true; } }
  ?>
  <div class="dp-foot">
    <span>📋 Ukupno timova: <strong><?= count($sumar) ?></strong></span>
    <span>Ukupno radnika: <strong><?= count($radniciSet) ?></strong></span>
    <span>Ukupno sati: <span class="dp-badge"><?= $fmtSati($danUkupnoSati) ?></span></span>
  </div>
<?php endif; ?>

<?php endif; ?>

<!-- MODAL: Izmeni radni sat (bez segmenata) -->
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
        <div style="background:#fef3c7;border-radius:6px;padding:8px 12px;font-size:12px;color:#92400e;margin-bottom:12px;">⚠️ Izmena će biti zabeležena u log.</div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('modal-vreme').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="sacuvajIzmenuVreme()" class="btn-primary">Sačuvaj izmenu</button>
        </div>
    </div>
</div>

<!-- MODAL: Izmeni segmente -->
<div id="modal-segmenti" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:20px;overflow-y:auto;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:560px;box-shadow:0 24px 80px #000a;position:relative;margin:auto;">
        <button onclick="document.getElementById('modal-segmenti').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:16px;">✏️ Izmeni radni sat</h3>
        <input type="hidden" id="ev-seg-id">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px;">
            <div class="tim-form-group" style="margin:0;"><label>Datum</label><input type="date" id="ev-seg-datum" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;"></div>
            <div class="tim-form-group" style="margin:0;"><label>Vreme od</label><input type="time" id="ev-seg-od" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;"></div>
            <div class="tim-form-group" style="margin:0;"><label>Vreme do</label><input type="time" id="ev-seg-do" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;"></div>
        </div>
        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:10px;">Segmenti</div>
        <div id="ev-seg-lista" style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;"></div>
        <div style="background:#fef3c7;border-radius:6px;padding:8px 12px;font-size:12px;color:#92400e;margin-bottom:12px;">⚠️ Izmena će biti zabeležena u log.</div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('modal-segmenti').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="sacuvajIzmenuSegmente()" class="btn-primary">Sačuvaj izmenu</button>
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
        <div style="background:#fef3c7;border-radius:6px;padding:8px 12px;font-size:12px;color:#92400e;margin-bottom:12px;">⚠️ Izmena će biti zabeležena u log.</div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('modal-mat').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="sacuvajIzmenuMat()" class="btn-primary">Sačuvaj izmenu</button>
        </div>
    </div>
</div>

<!-- MODAL: Dodaj utrošak (ručni unos potrošnje) -->
<div id="modal-dodaj-mat" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9998;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:14px;padding:24px;width:100%;max-width:440px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('modal-dodaj-mat').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:15px;font-weight:700;color:var(--blue);margin-bottom:4px;padding-right:32px;">➕ Dodaj utrošak</h3>
        <p style="font-size:11px;color:var(--muted);margin:0 0 16px;">Skida količinu sa stanja izabrane lokacije u magacinu.</p>
        <div class="tim-form-group"><label>Lokacija (sa koje se troši)</label>
            <select id="dm-lokacija" onchange="evPopuniArtikle()" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;">
                <option value="magacin">Magacin</option>
                <?php foreach ($gradilista as $g): ?>
                <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="tim-form-group"><label>Artikal</label>
            <select id="dm-naziv" onchange="evArtikalChange()" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;"></select></div>
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;">
            <div class="tim-form-group"><label>Količina <span id="dm-dostupno" style="color:var(--muted);font-size:11px;font-weight:400;"></span></label><input type="number" id="dm-kolicina" min="0.001" step="0.001" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
            <div class="tim-form-group"><label>JM</label><input type="text" id="dm-jm" readonly style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:#f1f5f9;color:#475569;width:100%;box-sizing:border-box;"></div>
        </div>
        <div class="tim-form-group"><label>Datum</label><input type="date" id="dm-datum" value="<?= date('Y-m-d') ?>" style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        <div class="tim-form-group"><label>Komentar</label><input type="text" id="dm-komentar" placeholder="npr. ugrađeno u sali 2..." style="border:1.5px solid var(--light2);border-radius:7px;padding:7px 10px;font-size:13px;outline:none;background:var(--light);width:100%;box-sizing:border-box;"></div>
        <div id="dm-err" style="display:none;color:#dc2626;font-size:12px;margin-bottom:8px;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('modal-dodaj-mat').style.display='none'" class="mail-cancel-btn">Odustani</button>
            <button onclick="sacuvajDodajMat()" class="btn-primary">Sačuvaj</button>
        </div>
    </div>
</div>

<script>
var _evSegmenti = [];
var EV_GRADILISTA = <?= json_encode(array_map(fn($g) => ['id' => (int)$g['id'], 'naziv' => $g['naziv']], $gradilista), JSON_UNESCAPED_UNICODE) ?>;
var EV_STANJE = <?= json_encode($magStanje ?? [], JSON_UNESCAPED_UNICODE) ?>;

// Lokacija <select> → {lokacija (tekst), gradiliste_id}
function evLokFromSelect(selEl) {
    var v = selEl.value;
    if (v === 'magacin') return { lokacija: 'Magacin', gradiliste_id: 0 };
    var opt = selEl.options[selEl.selectedIndex];
    return { lokacija: opt ? opt.text : 'Magacin', gradiliste_id: parseInt(v) || 0 };
}

function openDodajMat() {
    document.getElementById('dm-kolicina').value = '';
    document.getElementById('dm-komentar').value = '';
    document.getElementById('dm-err').style.display = 'none';
    evPopuniArtikle();
    document.getElementById('modal-dodaj-mat').style.display = 'flex';
}

function evPopuniArtikle() {
    var lok = evLokFromSelect(document.getElementById('dm-lokacija')).lokacija;
    var sel = document.getElementById('dm-naziv');
    var arr = EV_STANJE[lok] || [];
    sel.innerHTML = '';
    if (!arr.length) {
        var o = document.createElement('option');
        o.value = ''; o.textContent = '— nema artikala na ovoj lokaciji —';
        sel.appendChild(o);
    } else {
        arr.forEach(function (a) {
            var o = document.createElement('option');
            o.value = a.naziv;
            o.dataset.jm = a.jm;
            o.dataset.stanje = a.stanje;
            o.textContent = a.naziv + ' (' + a.stanje + ' ' + a.jm + ')';
            sel.appendChild(o);
        });
    }
    evArtikalChange();
}

function evArtikalChange() {
    var sel = document.getElementById('dm-naziv');
    var opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('dm-jm').value = opt.dataset.jm || 'Kom';
        document.getElementById('dm-kolicina').max = opt.dataset.stanje || '';
        document.getElementById('dm-dostupno').textContent = 'dostupno: ' + (opt.dataset.stanje || 0) + ' ' + (opt.dataset.jm || '');
    } else {
        document.getElementById('dm-jm').value = '';
        document.getElementById('dm-dostupno').textContent = '';
    }
}

function sacuvajDodajMat() {
    var sel = document.getElementById('dm-naziv');
    var opt = sel.options[sel.selectedIndex];
    var err = document.getElementById('dm-err');
    if (!opt || !opt.value) { err.textContent = 'Izaberi artikal sa ove lokacije.'; err.style.display = 'block'; return; }
    var kol = parseFloat(document.getElementById('dm-kolicina').value) || 0;
    var dostupno = parseFloat(opt.dataset.stanje) || 0;
    if (kol <= 0) { err.textContent = 'Unesi količinu.'; err.style.display = 'block'; return; }
    if (kol > dostupno + 0.0005) { err.textContent = 'Količina je veća od dostupne (' + dostupno + ').'; err.style.display = 'block'; return; }

    var lok = evLokFromSelect(document.getElementById('dm-lokacija'));
    var fd = new FormData();
    fd.append('_action', 'evidencija_dodaj_materijal'); fd.append('id', 0);
    fd.append('naziv',         opt.value);
    fd.append('kolicina',      kol);
    fd.append('jm',            opt.dataset.jm || 'Kom');
    fd.append('lokacija',      lok.lokacija);
    fd.append('gradiliste_id', lok.gradiliste_id);
    fd.append('datum',         document.getElementById('dm-datum').value);
    fd.append('komentar',      document.getElementById('dm-komentar').value);
    fetch('', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
        if (d.ok) { location.reload(); }
        else { err.textContent = d.err || 'Greška.'; err.style.display = 'block'; }
    });
}

function openIzmeniVreme(id, datum, od, do_, sati, napomena, segmentiJson) {
    if (segmentiJson) {
        // Ima segmenata — otvori modal za segmente
        try { _evSegmenti = JSON.parse(segmentiJson); } catch(e) { _evSegmenti = []; }
        document.getElementById('ev-seg-id').value    = id;
        document.getElementById('ev-seg-datum').value = datum;
        document.getElementById('ev-seg-od').value    = od;
        document.getElementById('ev-seg-do').value    = do_;
        renderSegLista();
        document.getElementById('modal-segmenti').style.display = 'flex';
    } else {
        // Bez segmenata — stari modal
        document.getElementById('ev-vreme-id').value   = id;
        document.getElementById('ev-v-datum').value    = datum;
        document.getElementById('ev-v-od').value       = od;
        document.getElementById('ev-v-do').value       = do_;
        document.getElementById('ev-v-sati').value     = sati;
        document.getElementById('ev-v-napomena').value = napomena;
        document.getElementById('modal-vreme').style.display = 'flex';
    }
}

function renderSegLista() {
    var lista = document.getElementById('ev-seg-lista');
    lista.innerHTML = '';
    _evSegmenti.forEach(function(seg, idx) {
        var isPrepravka = !!seg.prepravka;
        var div = document.createElement('div');
        div.style.cssText = 'border:1.5px solid '+(isPrepravka?'#fed7aa':'var(--light2)')+';border-radius:8px;padding:12px;background:'+(isPrepravka?'#fff7ed':'var(--light)')+';';
        div.innerHTML = `
            <div style="display:flex;gap:10px;margin-bottom:8px;align-items:center;">
                <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:#c2410c;cursor:pointer;">
                    <input type="checkbox" ${isPrepravka?'checked':''} onchange="toggleEvPrepravka(${idx}, this.checked)">
                    Prepravka
                </label>
                <span style="font-size:11px;color:var(--muted);margin-left:auto;">Segment ${idx+1}</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 80px;gap:8px;">
                <input type="text" value="${escAttr(seg.opis||'')}" oninput="_evSegmenti[${idx}].opis=this.value"
                    style="border:1.5px solid var(--light2);border-radius:6px;padding:6px 10px;font-size:13px;outline:none;background:#fff;width:100%;box-sizing:border-box;">
                <div style="display:flex;align-items:center;gap:4px;">
                    <input type="number" value="${seg.sati||0}" step="0.5" min="0" oninput="_evSegmenti[${idx}].sati=parseFloat(this.value)||0"
                        style="border:1.5px solid var(--light2);border-radius:6px;padding:6px 8px;font-size:13px;outline:none;background:#fff;width:100%;box-sizing:border-box;">
                    <span style="font-size:12px;color:var(--muted);">h</span>
                </div>
            </div>
        `;
        lista.appendChild(div);
    });
}

function toggleEvPrepravka(idx, val) {
    _evSegmenti[idx].prepravka = val;
    renderSegLista();
}

function sacuvajIzmenuSegmente() {
    var id = document.getElementById('ev-seg-id').value;
    var fd = new FormData();
    fd.append('_action', 'evidencija_izmeni_segmente');
    fd.append('id', id);
    fd.append('datum',    document.getElementById('ev-seg-datum').value);
    fd.append('vreme_od', document.getElementById('ev-seg-od').value);
    fd.append('vreme_do', document.getElementById('ev-seg-do').value);
    fd.append('segmenti', JSON.stringify(_evSegmenti));
    fetch('', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.ok) { document.getElementById('modal-segmenti').style.display = 'none'; location.reload(); }
        else alert(d.err || 'Greška.');
    });
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

function escAttr(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
</script>
