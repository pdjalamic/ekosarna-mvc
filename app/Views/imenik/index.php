

<div class="topbar-admin">
  <div class="page-title">Imenik</div>
  <?php if ($is_admin): ?>
  <div class="stats-bar">
    <button class="im-add-btn" onclick="openAddFirma()">+ Dodaj firmu</button>
  </div>
  <?php endif; ?>
</div>

<form class="im-search-bar" method="get" style="margin-bottom:20px;">
  <input type="hidden" name="page" value="imenik">
  <input type="hidden" name="ipage" value="1">
  <input type="hidden" name="ipp" value="<?= $per_page ?>">
  <input type="text" name="iq" value="<?= h($search) ?>" placeholder="Pretraži firme, osobe, email, telefon...">
  <button type="submit">Traži</button>
  <?php if ($search): ?>
    <a href="<?= BASE_URL ?>/?page=imenik&ipp=<?= $per_page ?>" class="btn-sm" style="text-decoration:none;">✕ Poništi</a>
  <?php endif; ?>
</form>

<?php if (empty($firme)): ?>
<div style="text-align:center;padding:60px;color:var(--muted);">
  <big style="font-size:40px;display:block;margin-bottom:12px;">📒</big>
  <?= $search ? 'Nema rezultata za "' . h($search) . '"' : 'Imenik je prazan.' ?>
</div>
<?php else: ?>
<div class="im-wrap">
  <?php $fi_num = ($page - 1) * $per_page; foreach ($firme as $fi): $fi_num++; ?>
  <?php $kontakti = $fi['kontakti']; $nk = count($kontakti); $zebra = ($fi_num % 2 === 0) ? 'zebra' : ''; ?>
  <div class="im-firma-blok <?= $zebra ?>" id="ifc-<?= $fi['id'] ?>">

    <?php if ($nk === 0): ?>
    <div class="im-row">
      <div class="im-cell im-cell-num"><?= $fi_num ?></div>
      <div class="im-cell im-cell-firma">
        <span class="im-firma-name"><?= h($fi['naziv']) ?></span>
        <?php if ($is_admin): ?>
        <span class="im-firma-btns">
          <button class="btn-sm" onclick="openEditFirma(<?= $fi['id'] ?>,'<?= h(addslashes($fi['naziv'])) ?>','<?= h(addslashes($fi['adresa'])) ?>','<?= h(addslashes($fi['drzava'] ?? 'Srbija')) ?>','<?= h(addslashes($fi['komentar'])) ?>')">✎</button>
          <button class="btn-sm del" onclick="delFirma(<?= $fi['id'] ?>)">🗑</button>
          <button class="btn-sm mark" onclick="openAddKontakt(<?= $fi['id'] ?>)">+</button>
        </span>
        <?php endif; ?>
      </div>
      <div class="im-cell im-cell-adresa"><?= h($fi['adresa']) ?><?= $fi['adresa'] ? ' ' : '' ?><?= h($fi['drzava'] ?? 'Srbija') ?></div>
      <div class="im-cell" style="color:var(--muted);font-size:12px;font-style:italic;grid-column:span 4;">Nema kontakata</div>
    </div>

    <?php else: ?>
    <?php foreach ($kontakti as $ki => $k): ?>
    <div class="im-row" id="ikr-<?= $k['id'] ?>">
      <div class="im-cell im-cell-num"><?= $ki === 0 ? $fi_num : '' ?></div>

      <?php if ($ki === 0): ?>
      <div class="im-cell im-cell-firma">
        <span class="im-firma-name"><?= h($fi['naziv']) ?></span>
        <?php if ($is_admin): ?>
        <span class="im-firma-btns">
          <button class="btn-sm" onclick="openEditFirma(<?= $fi['id'] ?>,'<?= h(addslashes($fi['naziv'])) ?>','<?= h(addslashes($fi['adresa'])) ?>','<?= h(addslashes($fi['drzava'] ?? 'Srbija')) ?>','<?= h(addslashes($fi['komentar'])) ?>')">✎</button>
          <button class="btn-sm del" onclick="delFirma(<?= $fi['id'] ?>)">🗑</button>
          <button class="btn-sm mark" onclick="openAddKontakt(<?= $fi['id'] ?>)" title="Dodaj kontakt">+</button>
        </span>
        <?php endif; ?>
        <?php if ($fi['komentar']): ?><div class="im-firma-kom-txt" style="margin-top:4px;">💬 <?= h($fi['komentar']) ?></div><?php endif; ?>
      </div>
      <div class="im-cell im-cell-adresa"><?= h($fi['adresa']) ?><?= $fi['adresa'] ? ' ' : '' ?><?= h($fi['drzava'] ?? 'Srbija') ?></div>
      <?php else: ?>
      <div class="im-cell im-cell-firma"></div>
      <div class="im-cell im-cell-adresa"></div>
      <?php endif; ?>

      <div class="im-cell im-cell-osoba">
        <span class="im-osoba-ime"><?= h($k['ime']) ?: '<span style="color:var(--muted)">—</span>' ?></span>
        <?php if ($k['komentar']): ?><span class="im-osoba-kom"><?= h($k['komentar']) ?></span><?php endif; ?>
      </div>

      <div class="im-cell im-cell-email">
        <?php if ($k['email']): ?>
          <a href="mailto:<?= h($k['email']) ?>" class="im-email-link">✉ <?= h($k['email']) ?></a>
          <button class="btn-sm mark" style="margin-top:4px;display:inline-flex;align-items:center;gap:3px;font-size:11px;"
            onclick="openMailModal('<?= h($k['email']) ?>','<?= h(addslashes($k['ime'])) ?>','','imenik')">✉ Pošalji e-mail</button>
        <?php else: ?><span style="color:var(--muted);">—</span><?php endif; ?>
      </div>

      <div class="im-cell im-cell-tel">
        <?php if ($k['telefon']): ?>
          <a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $k['telefon'])) ?>">📞 <?= h($k['telefon']) ?></a>
        <?php else: ?><span style="color:var(--muted);">—</span><?php endif; ?>
      </div>

      <?php if ($is_admin): ?>
      <div class="im-cell im-cell-actions">
        <button class="btn-sm" onclick="openEditKontakt(<?= $k['id'] ?>,<?= $fi['id'] ?>,'<?= h(addslashes($k['ime'])) ?>','<?= h(addslashes($k['email'])) ?>','<?= h(addslashes($k['telefon'])) ?>','<?= h(addslashes($k['komentar'])) ?>')">✎</button>
        <button class="btn-sm del" onclick="delKontakt(<?= $k['id'] ?>)">🗑</button>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

  </div>
  <?php endforeach; ?>
</div>

<!-- Paginacija -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-top:20px;flex-wrap:wrap;gap:12px;">
  <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted);">
    Prikaži:
    <?php foreach ([5,10,50,1000] as $pp): ?>
    <a href="<?= BASE_URL ?>/?page=imenik&ipp=<?= $pp ?>&ipage=1<?= $search ? '&iq='.urlencode($search) : '' ?>"
       style="padding:4px 10px;border-radius:6px;border:1.5px solid <?= $per_page===$pp ? 'var(--blue)':'var(--light2)' ?>;background:<?= $per_page===$pp ? 'var(--blue)':'var(--white)' ?>;color:<?= $per_page===$pp ? '#fff':'var(--muted)' ?>;text-decoration:none;font-size:12px;font-weight:600;">
      <?= $pp === 1000 ? 'Sve' : $pp ?>
    </a>
    <?php endforeach; ?>
    <span style="margin-left:8px;">— <?= $total ?> firmi ukupno</span>
  </div>
  <?php if ($total_pages > 1): ?>
  <div class="pagination" style="margin:0;">
    <?php if ($page > 1): ?>
      <a href="<?= BASE_URL ?>/?page=imenik&ipp=<?= $per_page ?>&ipage=<?= $page-1 ?><?= $search ? '&iq='.urlencode($search) : '' ?>">← Prethodna</a>
    <?php endif; ?>
    <?php for ($p = max(1,$page-2); $p <= min($total_pages,$page+2); $p++): ?>
      <?php if ($p === $page): ?>
        <span class="current"><?= $p ?></span>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/?page=imenik&ipp=<?= $per_page ?>&ipage=<?= $p ?><?= $search ? '&iq='.urlencode($search) : '' ?>"><?= $p ?></a>
      <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?>
      <a href="<?= BASE_URL ?>/?page=imenik&ipp=<?= $per_page ?>&ipage=<?= $page+1 ?><?= $search ? '&iq='.urlencode($search) : '' ?>">Sledeća →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Modal: Dodaj/Izmeni firmu -->
<div id="im-firma-modal"
  style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:480px;box-shadow:0 24px 80px #000a;position:relative;">
    <button onclick="closeImModal('im-firma-modal')" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
    <h3 id="im-firma-title" style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:18px;">Dodaj firmu</h3>
    <input type="hidden" id="im-firma-id" value="">
    <div class="tim-form-group"><label>Naziv firme</label><input type="text" id="im-firma-naziv" placeholder="Naziv firme"></div>
    <div class="tim-form-group"><label>Adresa / Grad</label><input type="text" id="im-firma-adresa" placeholder="Adresa ili grad"></div>
    <div class="tim-form-group"><label>Država</label><input type="text" id="im-firma-drzava" placeholder="Srbija" value="Srbija"></div>
    <div class="tim-form-group"><label>Komentar (interni)</label>
      <textarea id="im-firma-komentar" rows="2" style="border:1.5px solid var(--light2);border-radius:7px;padding:8px 12px;font-size:13px;font-family:inherit;outline:none;background:var(--light);width:100%;resize:vertical;" placeholder="Napomene o firmi..."></textarea>
    </div>
    <div style="color:#dc2626;font-size:12px;margin-bottom:8px;display:none;" id="im-firma-err"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
      <button class="mail-cancel-btn" onclick="closeImModal('im-firma-modal')">Odustani</button>
      <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="saveFirma()">Sačuvaj</button>
    </div>
  </div>
</div>

<!-- Modal: Dodaj/Izmeni kontakt -->
<div id="im-kontakt-modal"
  style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:480px;box-shadow:0 24px 80px #000a;position:relative;">
    <button onclick="closeImModal('im-kontakt-modal')" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
    <h3 id="im-kontakt-title" style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:18px;">Dodaj kontakt</h3>
    <input type="hidden" id="im-kontakt-id" value="">
    <input type="hidden" id="im-kontakt-firma-id" value="">
    <div class="tim-form-group"><label>Ime i prezime</label><input type="text" id="im-kontakt-ime" placeholder="Ime Prezime"></div>
    <div class="tim-form-group"><label>Email</label><input type="email" id="im-kontakt-email" placeholder="email@firma.rs"></div>
    <div class="tim-form-group"><label>Telefon</label><input type="tel" id="im-kontakt-tel" placeholder="06x xxx xxxx"></div>
    <div class="tim-form-group"><label>Komentar</label><input type="text" id="im-kontakt-kom" placeholder="Npr. Direktor prodaje"></div>
    <div style="color:#dc2626;font-size:12px;margin-bottom:8px;display:none;" id="im-kontakt-err"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
      <button class="mail-cancel-btn" onclick="closeImModal('im-kontakt-modal')">Odustani</button>
      <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="saveKontakt()">Sačuvaj</button>
    </div>
  </div>
</div>
