
<div class="topbar-admin">
  <div class="page-title">Tim</div>
  <div class="stats-bar">
    <button class="im-add-btn" onclick="openTimAddModal()">+ Dodaj korisnika</button>
  </div>
</div>

<div class="tim-table-wrap">
  <table>
    <thead>
      <tr>
        <th>#</th><th>Ime</th><th>Email</th><th>Telefon</th>
        <th>Korisničko ime</th><th>Uloga</th><th>Platforma</th><th>Status</th><th>Kreiran</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php
    // Vrednost koja se čuva => naziv koji se prikazuje. Stari nalozi sa
    // vrednošću "Elektricar" prikazuju se kao "(stara)" dok ih ne prebaciš
    // na jedan od novih naziva.
    $uloge_opcije = [
        'Direktor'                  => 'Direktor',
        'AT'                        => 'AT',
        'AF'                        => 'AF',
        'Inženjer na gradilištu'    => 'Inženjer na gradilištu',
        'Rukovodilac operative'     => 'Rukovodilac operative',
        'Monter poslovođa'          => 'Monter poslovođa',
        'Zamenik montera poslovođe' => 'Zamenik montera poslovođe',
        'Monter'                    => 'Monter',
        'Pomoćni radnik'            => 'Pomoćni radnik',
    ];
    $uloga_default = 'Inženjer na gradilištu'; // podrazumevana uloga u formi za dodavanje
    ?>
    <?php foreach ($korisnici as $i => $u): ?>
    <tr id="tim-row-<?= $u['id'] ?>">
      <td class="num"><?= $i+1 ?></td>
      <td style="font-weight:600;color:var(--blue);"><?= h($u['ime']) ?></td>
      <td class="muted" style="font-size:12px;"><?= h($u['email']) ?: '—' ?></td>
      <td class="muted" style="font-size:12px;"><?= h($u['telefon'] ?? '') ?: '—' ?></td>
      <td class="muted" style="font-size:12px;font-family:monospace;"><?= h($u['username']) ?></td>
      <td>
        <select class="tim-uloga-select" data-id="<?= $u['id'] ?>"
          style="border:1.5px solid var(--light2);border-radius:6px;padding:3px 7px;font-size:12px;background:var(--light);font-family:inherit;cursor:pointer;"
          <?= $u['id'] == \Core\Auth::id() ? 'disabled' : '' ?>>
          <?php if (!array_key_exists($u['uloga'], $uloge_opcije)): ?>
          <option value="<?= h($u['uloga']) ?>" selected><?= h($u['uloga']) ?> (stara)</option>
          <?php endif; ?>
          <?php foreach ($uloge_opcije as $val => $lab): ?>
          <option value="<?= h($val) ?>" <?= $u['uloga']===$val ? 'selected':'' ?>><?= h($lab) ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td>
        <?php
        $platIkona = ['android' => '🤖', 'ios' => '🍎', 'web' => '🌐'];
        $plat  = $u['platforma']  ?? 'android';
        $plat2 = $u['platforma2'] ?? '';
        ?>
        <span style="font-size:13px;"><?= $platIkona[$plat] ?? '🤖' ?> <?= ucfirst($plat) ?></span>
        <?php if ($plat2): ?>
        <br><span style="font-size:11px;color:var(--muted);"><?= $platIkona[$plat2] ?? '' ?> <?= ucfirst($plat2) ?></span>
        <?php endif; ?>
      </td>
      <td>
        <span id="tim-badge-<?= $u['id'] ?>" class="<?= $u['aktivan'] ? 'badge-aktivan':'badge-neaktivan' ?>">
          <?= $u['aktivan'] ? 'Aktivan':'Neaktivan' ?>
        </span>
      </td>
      <td class="muted" style="font-size:11px;white-space:nowrap;"><?= date('d.m.Y', strtotime($u['datum_kreiranja'])) ?></td>
      <td style="white-space:nowrap;">
        <button class="btn-sm" style="color:var(--muted);"
          onclick="openTimEdit(<?= $u['id'] ?>,'<?= h(addslashes($u['ime'])) ?>','<?= h(addslashes($u['email'])) ?>','<?= h(addslashes($u['telefon'] ?? '')) ?>','<?= h(addslashes($u['telegram_username'] ?? '')) ?>','<?= h($u['platforma'] ?? 'android') ?>','<?= h($u['platforma2'] ?? '') ?>')">✎ Izmeni</button>
        <?php if ($u['id'] != \Core\Auth::id()): ?>
        <button class="btn-sm <?= $u['aktivan'] ? 'del':'mark' ?>" onclick="timToggle(<?= $u['id'] ?>)" id="tim-toggle-<?= $u['id'] ?>">
          <?= $u['aktivan'] ? 'Deaktiviraj':'Aktiviraj' ?>
        </button>
        <button class="btn-sm" style="color:var(--muted);" onclick="timResetPass(<?= $u['id'] ?>,'<?= h($u['ime']) ?>')">Lozinka</button>
        <button class="btn-sm <?= !empty($u['vidi_imenik']) ? 'mark':'' ?>" onclick="timToggleImenik(<?= $u['id'] ?>)" id="tim-imenik-<?= $u['id'] ?>" style="color:var(--muted);">
          📖 <?= !empty($u['vidi_imenik']) ? 'Imenik: DA':'Imenik: NE' ?>
        </button>
        <button class="btn-sm del" onclick="timDelete(<?= $u['id'] ?>)">🗑</button>
        <?php else: ?>
        <span style="font-size:11px;color:var(--muted);">(vi)</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($korisnici)): ?>
    <tr><td colspan="10" style="text-align:center;padding:30px;color:var(--muted);">Nema korisnika.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Dodaj korisnika -->
<div id="tim-add-modal"
  style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:flex-start;justify-content:center;padding:40px 20px;overflow-y:auto;">
  <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:480px;box-shadow:0 24px 80px #000a;margin:auto;position:relative;">
    <button onclick="document.getElementById('tim-add-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
    <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:18px;">+ Dodaj korisnika</h3>
    <div class="tim-form-group"><label>Ime i prezime</label><input type="text" id="tim-ime" placeholder="Ime Prezime"></div>
    <div class="tim-form-group"><label>Email adresa (za slanje maila)</label><input type="email" id="tim-email" placeholder="korisnik@ekosarna.com"></div>
    <div class="tim-form-group"><label>Telefon</label><input type="tel" id="tim-telefon" placeholder="061 xxx xxxx"></div>
    <div class="tim-form-group"><label>Lozinka email naloga</label><input type="password" id="tim-mail-pass" placeholder="Lozinka za email nalog" autocomplete="new-password"></div>
    <div class="tim-form-group"><label>Telegram username (bez @)</label><input type="text" id="tim-telegram" placeholder="npr. pedja123"></div>
    <div class="tim-form-group"><label>Korisničko ime (za admin panel)</label><input type="text" id="tim-username" placeholder="korisnickoime"></div>
    <div class="tim-form-group"><label>Lozinka admin panela (min. 6)</label><input type="password" id="tim-pass" placeholder="••••••••"></div>
    <div class="tim-form-group"><label>Uloga</label>
      <select id="tim-uloga">
        <?php foreach ($uloge_opcije as $val => $lab): ?>
        <option value="<?= h($val) ?>" <?= $val === $uloga_default ? 'selected':'' ?>><?= h($lab) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="tim-form-group"><label>Primarni kanal notifikacija</label>
      <select id="tim-platforma">
        <option value="android">🤖 Android (Push notifikacije)</option>
        <option value="ios">🍎 iOS (Telegram notifikacije)</option>
        <option value="web">🌐 Web only</option>
      </select>
    </div>
    <div class="tim-form-group"><label>Drugi kanal notifikacija (opciono)</label>
      <select id="tim-platforma2">
        <option value="">— Bez drugog kanala —</option>
        <option value="android">🤖 Android (Push notifikacije)</option>
        <option value="ios">🍎 iOS (Telegram notifikacije)</option>
        <option value="web">🌐 Web only</option>
      </select>
    </div>
    <div class="tim-error" id="tim-add-err"></div>
    <div class="tim-success" id="tim-add-ok">Korisnik je dodat!</div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
      <button class="mail-cancel-btn" onclick="document.getElementById('tim-add-modal').style.display='none'">Odustani</button>
      <button class="tim-add-btn" style="width:auto;padding:10px 24px;" onclick="timAdd()">Dodaj korisnika</button>
    </div>
  </div>
</div>

<!-- Modal: Izmeni korisnika -->
<div id="tim-edit-modal"
  style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:460px;box-shadow:0 24px 80px #000a;position:relative;">
    <button onclick="document.getElementById('tim-edit-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
    <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:18px;">✎ Izmeni korisnika</h3>
    <input type="hidden" id="tim-edit-id">
    <div class="tim-form-group"><label>Ime i prezime</label><input type="text" id="tim-edit-ime" placeholder="Ime Prezime"></div>
    <div class="tim-form-group"><label>Email adresa</label><input type="email" id="tim-edit-email" placeholder="korisnik@ekosarna.com"></div>
    <div class="tim-form-group"><label>Telefon</label><input type="tel" id="tim-edit-telefon" placeholder="061 xxx xxxx"></div>
    <div class="tim-form-group"><label>Lozinka email naloga (prazno = ne menja)</label><input type="password" id="tim-edit-mpass" placeholder="••••••••" autocomplete="new-password"></div>
    <div class="tim-form-group"><label>Telegram username (bez @)</label><input type="text" id="tim-edit-telegram" placeholder="npr. pedja123"></div>
    <div class="tim-form-group"><label>Primarni kanal notifikacija</label>
      <select id="tim-edit-platforma">
        <option value="android">🤖 Android (Push notifikacije)</option>
        <option value="ios">🍎 iOS (Telegram notifikacije)</option>
        <option value="web">🌐 Web only</option>
      </select>
    </div>
    <div class="tim-form-group"><label>Drugi kanal notifikacija (opciono)</label>
      <select id="tim-edit-platforma2">
        <option value="">— Bez drugog kanala —</option>
        <option value="android">🤖 Android (Push notifikacije)</option>
        <option value="ios">🍎 iOS (Telegram notifikacije)</option>
        <option value="web">🌐 Web only</option>
      </select>
    </div>
    <div style="color:#dc2626;font-size:12px;margin-bottom:8px;display:none;" id="tim-edit-err"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;">
      <button class="mail-cancel-btn" onclick="document.getElementById('tim-edit-modal').style.display='none'">Odustani</button>
      <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="saveTimEdit()">Sačuvaj</button>
    </div>
  </div>
</div>
