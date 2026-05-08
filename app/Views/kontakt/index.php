<?php
$base_q = $search ? '&q=' . urlencode($search) : '';
$cur_filt = $filter;
?>
<div class="topbar-admin">
  <div class="page-title">Kontakt forme</div>
  <div class="stats-bar">
    <span class="stat-pill">Ukupno: <strong><?= $total ?></strong></span>
    <?php if ($neprocitano > 0): ?>
      <span class="stat-pill neprocitano">Nepročitano: <strong><?= $neprocitano ?></strong></span>
    <?php endif; ?>
  </div>
</div>

<div class="filter-bar">
  <a href="<?= BASE_URL ?>/?page=kontakt&filter=sve<?= $base_q ?>"          class="filter-btn <?= $cur_filt==='sve'        ? 'active':'' ?>">Sve</a>
  <a href="<?= BASE_URL ?>/?page=kontakt&filter=neprocitano<?= $base_q ?>"  class="filter-btn <?= $cur_filt==='neprocitano' ? 'active':'' ?>">Nepročitano</a>
  <a href="<?= BASE_URL ?>/?page=kontakt&filter=procitano<?= $base_q ?>"    class="filter-btn <?= $cur_filt==='procitano'   ? 'active':'' ?>">Pročitano</a>
  <form class="search-wrap" method="get">
    <input type="hidden" name="page" value="kontakt">
    <input type="hidden" name="filter" value="<?= h($cur_filt) ?>">
    <input type="text" name="q" class="search-input" value="<?= h($search) ?>" placeholder="Pretraga po imenu, gradu, tel...">
    <button type="submit" class="search-btn">Traži</button>
  </form>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Datum i vreme</th>
        <th>Ime i prezime</th>
        <th>Firma</th>
        <th>Telefon</th>
        <th>E-mail</th>
        <th>Usluga</th>
        <th>Fajl</th>
        <th>Grad *</th>
        <th>Komentar *</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
      <tr>
        <td colspan="12">
          <div class="empty">
            <big>📭</big>
            <?= $search ? 'Nema rezultata za "' . h($search) . '"' : 'Nema zapisa.' ?>
          </div>
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($rows as $i => $r): ?>
      <?php $rn = ($page - 1) * $per_page + $i + 1; ?>
      <tr class="<?= $r['procitano'] ? '' : 'neprocitano-row' ?>" id="row-<?= $r['id'] ?>">
        <td class="num"><?= $rn ?></td>
        <td class="datum">
          <?= date('d.m.Y', strtotime($r['datum'])) ?><br>
          <small><?= date('H:i', strtotime($r['datum'])) ?></small>
        </td>
        <td class="name"><?= h($r['ime_prezime']) ?></td>
        <td style="min-width:100px;">
          <input type="text" class="inline-edit" data-id="<?= $r['id'] ?>" data-field="firma"
                 value="<?= h($r['firma']) ?>" placeholder="Upiši firmu...">
          <span class="saving" id="sav-firma-<?= $r['id'] ?>">Čuvanje...</span>
        </td>
        <td>
          <a href="tel:<?= h(preg_replace('/\s+/', '', $r['telefon'])) ?>" style="color:var(--blue);text-decoration:none;font-weight:600;">
            <?= h($r['telefon']) ?>
          </a>
        </td>
        <td class="muted">
          <?php if ($r['email']): ?>
            <a href="mailto:<?= h($r['email']) ?>" style="color:var(--bluem);text-decoration:none;"><?= h($r['email']) ?></a>
            <button class="btn-sm mark" style="display:block;margin-top:5px;"
              onclick="openMailModal('<?= h($r['email']) ?>','<?= h(addslashes($r['ime_prezime'])) ?>','<?= h(addslashes($r['vrsta_usluge'])) ?>')">
              ✉ Pošalji e-mail
            </button>
          <?php else: echo '—'; endif; ?>
          <?php if ($r['opis_projekta']): ?>
            <small style="color:var(--muted);font-size:11px;display:block;margin-top:3px;font-style:italic"><?= h($r['opis_projekta']) ?></small>
          <?php endif; ?>
        </td>
        <td class="muted" style="font-size:12px;"><?= h($r['vrsta_usluge']) ?: '—' ?></td>
        <td style="white-space:nowrap;">
          <?php if (!empty($r['fajl_putanja'])): ?>
            <?php
              $ext = strtolower(pathinfo($r['fajl_putanja'], PATHINFO_EXTENSION));
              $img_exts = ['jpg','jpeg','png','gif','webp','bmp','tiff','tif','heic','heif','svg'];
              $is_img = in_array($ext, $img_exts);
              $url = '<?= BASE_URL ?>/uploads/' . h(basename($r['fajl_putanja']));
            ?>
            <?php if ($is_img): ?>
              <img src="<?= $url ?>" onclick="openModal('<?= $url ?>','img')"
                   style="max-height:48px;max-width:80px;border-radius:4px;border:1px solid var(--light2);display:block;cursor:zoom-in;">
            <?php else: ?>
              <a onclick="openModal('<?= $url ?>','pdf')" href="#"
                 style="display:inline-flex;align-items:center;gap:5px;color:var(--bluem);text-decoration:none;font-size:12px;font-weight:600;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                PDF
              </a>
            <?php endif; ?>
          <?php else: ?>
            <span style="color:var(--light2);">—</span>
          <?php endif; ?>
        </td>
        <td style="min-width:110px;">
          <input type="text" class="inline-edit" data-id="<?= $r['id'] ?>" data-field="grad"
                 value="<?= h($r['grad']) ?>" placeholder="Upiši grad...">
          <span class="saving" id="sav-grad-<?= $r['id'] ?>">Čuvanje...</span>
        </td>
        <td style="min-width:150px;">
          <textarea class="inline-edit" data-id="<?= $r['id'] ?>" data-field="komentar"
                    rows="2" placeholder="Upiši komentar..."><?= h($r['komentar']) ?></textarea>
          <span class="saving" id="sav-kom-<?= $r['id'] ?>">Čuvanje...</span>
        </td>
        <td style="text-align:center;">
          <span class="<?= $r['procitano'] ? 'badge-ok' : 'badge-new' ?>" id="badge-<?= $r['id'] ?>">
            <?= $r['procitano'] ? 'Pročitano' : 'Novo' ?>
          </span>
        </td>
        <td style="white-space:nowrap;">
          <button class="btn-sm mark" onclick="toggleRead(<?= $r['id'] ?>)">
            <?= $r['procitano'] ? '↩ Novo' : '✓ Pročitano' ?>
          </button>
          <button class="btn-sm del" onclick="delRow(<?= $r['id'] ?>)">🗑</button>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($total_pages > 1): ?>
<div class="pagination">
  <?php if ($page > 1): ?>
    <a href="<?= BASE_URL ?>/?page=kontakt&filter=<?= h($cur_filt) ?>&page_num=<?= $page-1 ?><?= $base_q ?>">← Prethodna</a>
  <?php endif; ?>
  <?php for ($p = max(1,$page-2); $p <= min($total_pages,$page+2); $p++): ?>
    <?php if ($p === $page): ?>
      <span class="current"><?= $p ?></span>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/?page=kontakt&filter=<?= h($cur_filt) ?>&page_num=<?= $p ?><?= $base_q ?>"><?= $p ?></a>
    <?php endif; ?>
  <?php endfor; ?>
  <?php if ($page < $total_pages): ?>
    <a href="<?= BASE_URL ?>/?page=kontakt&filter=<?= h($cur_filt) ?>&page_num=<?= $page+1 ?><?= $base_q ?>">Sledeća →</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<p style="margin-top:16px;font-size:12px;color:var(--muted);">
  * Grad i Komentar su interni admin podaci — kliknite direktno na polje da biste izmenili.
</p>
