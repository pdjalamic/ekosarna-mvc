<style>
.hr-karton-grid {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 20px;
    align-items: start;
    max-width: 100%;
}
@media (max-width: 768px) {
    .hr-karton-grid {
        grid-template-columns: 1fr !important;
    }
    .main { overflow-x: hidden; }
    body { overflow-x: hidden; }
}
</style>

<?php
$je_admin = \Core\Auth::isAdmin() || \Core\Auth::uloga() === 'Operater';
$tip_ugovora_label = ['neodredjeno'=>'Na neodređeno','odredjeno'=>'Na određeno','ucenicka'=>'Učenička praksa','probni'=>'Probni rad'];
$tip_odsustva = ['godisnji'=>'Godišnji odmor','bolovanje'=>'Bolovanje','neplaceno'=>'Neplaćeno','porodiljsko'=>'Porodiljsko','placeno'=>'Plaćeno','praznik'=>'Državni praznik','ostalo'=>'Ostalo'];
$tip_boja = ['godisnji'=>'#dcfce7','bolovanje'=>'#fee2e2','neplaceno'=>'#fef3c7','porodiljsko'=>'#fce7f3','placeno'=>'#dbeafe','praznik'=>'#ede9fe','ostalo'=>'#f1f5f9'];
$tip_text = ['godisnji'=>'#166534','bolovanje'=>'#991b1b','neplaceno'=>'#92400e','porodiljsko'=>'#9d174d','placeno'=>'#1e40af','praznik'=>'#5b21b6','ostalo'=>'#475569'];
$tekuca_godina = date('Y');
$godisnji_map = [];
foreach ($godisnji as $g) $godisnji_map[$g['godina']] = $g;
$ukupno_tekuca     = ($godisnji_map[$tekuca_godina]['ukupno_dana'] ?? 20) + ($godisnji_map[$tekuca_godina]['preneseno'] ?? 0);
$iskoristio_tekuca = $iskoristio_map[$tekuca_godina] ?? 0;
$ostatak_tekuca    = $ukupno_tekuca - $iskoristio_tekuca;
?>

<div class="topbar-admin" style="flex-wrap:wrap;gap:10px;">
    <div style="display:flex;align-items:center;gap:10px;">
        <?php if ($je_admin): ?>
        <a href="?page=hr" class="btn-sm">&#8592; Nazad</a>
        <?php endif; ?>
        <div class="page-title">&#128100; <?= h($zaposleni['ime']) ?></div>
    </div>
    <?php if ($je_admin): ?>
    <div style="display:flex;gap:8px;margin-left:auto;">
        <button type="button" class="btn-sm <?= $zaposleni['aktivan'] ? 'del' : 'mark' ?>" onclick="toggleAktivan(<?= $id ?>)">
            <?= $zaposleni['aktivan'] ? 'Deaktiviraj' : 'Aktiviraj' ?>
        </button>
        <button type="button" class="btn-primary" onclick="openHrEditModal()">&#9998; Izmeni</button>
    </div>
    <?php endif; ?>
</div>

<div class="hr-karton-grid">
<div style="display:flex;flex-direction:column;gap:16px;">

<div style="background:#fff;border-radius:14px;border:1.5px solid var(--light2);padding:24px;text-align:center;">
    <div style="width:110px;height:110px;border-radius:50%;overflow:hidden;margin:0 auto 14px;background:var(--light);border:3px solid var(--light2);display:flex;align-items:center;justify-content:center;">
        <?php if (!empty($zaposleni['slika_putanja'])): ?>
        <img src="<?= h($zaposleni['slika_putanja']) ?>" class="hr-slika-img" style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
        <span style="font-size:44px;" class="hr-slika-placeholder">&#128100;</span>
        <?php endif; ?>
    </div>
    <?php if ($je_admin): ?>
    <label style="cursor:pointer;font-size:12px;color:var(--blue);font-weight:600;display:block;margin-bottom:10px;">
        &#128247; Promeni sliku
        <input type="file" id="hr-slika-input" accept="image/*" style="display:none;" onchange="uploadSliku(<?= $id ?>)">
    </label>
    <?php endif; ?>
    <div style="font-size:17px;font-weight:700;color:var(--blue);"><?= h($zaposleni['ime']) ?></div>
    <div style="font-size:13px;color:var(--muted);margin-top:2px;"><?= h($zaposleni['pozicija'] ?? '') ?></div>
</div>

<div style="background:#fff;border-radius:14px;border:1.5px solid var(--light2);padding:18px;">
    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--blue);margin-bottom:12px;">Godisnji <?= $tekuca_godina ?></div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;text-align:center;margin-bottom:10px;">
        <div style="background:#f8faff;border-radius:8px;padding:8px;">
            <div style="font-size:20px;font-weight:700;color:var(--blue);"><?= $ukupno_tekuca ?></div>
            <div style="font-size:10px;color:var(--muted);">Ukupno</div>
        </div>
        <div style="background:#fee2e2;border-radius:8px;padding:8px;">
            <div style="font-size:20px;font-weight:700;color:#991b1b;"><?= $iskoristio_tekuca ?></div>
            <div style="font-size:10px;color:var(--muted);">Iskori&scaron;ceno</div>
        </div>
        <div style="background:#dcfce7;border-radius:8px;padding:8px;">
            <div style="font-size:20px;font-weight:700;color:#166534;"><?= $ostatak_tekuca ?></div>
            <div style="font-size:10px;color:var(--muted);">Ostalo</div>
        </div>
    </div>
    <?php $pct = $ukupno_tekuca > 0 ? min(100, round($iskoristio_tekuca / $ukupno_tekuca * 100)) : 0; ?>
    <div style="background:var(--light);border-radius:99px;height:6px;overflow:hidden;">
        <div style="background:#2563eb;height:100%;width:<?= $pct ?>%;border-radius:99px;"></div>
    </div>
    <?php if ($je_admin): ?>
    <button type="button" class="btn-sm mark" style="margin-top:10px;width:100%;" onclick="openGodisnjModal(<?= $id ?>)">Postavi dane</button>
    <?php endif; ?>
</div>

<div style="background:#fff;border-radius:14px;border:1.5px solid var(--light2);padding:18px;">
    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--blue);margin-bottom:10px;">Kontakt</div>
    <div style="font-size:13px;display:flex;flex-direction:column;gap:5px;">
        <?php if (!empty($zaposleni['telefon'])): ?><div><?= h($zaposleni['telefon']) ?></div><?php endif; ?>
        <?php if (!empty($zaposleni['email'])): ?><div><?= h($zaposleni['email']) ?></div><?php endif; ?>
        <?php if (!empty($zaposleni['adresa'])): ?><div><?= h($zaposleni['adresa']) ?><?= !empty($zaposleni['grad']) ? ', ' . h($zaposleni['grad']) : '' ?></div><?php endif; ?>
    </div>
</div>

</div>
<div style="display:flex;flex-direction:column;gap:16px;">

<div style="background:#fff;border-radius:14px;border:1.5px solid var(--light2);padding:20px;">
    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--blue);margin-bottom:14px;">Podaci o zaposlenju</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
        <?php if (!empty($zaposleni['datum_rodjenja'])): ?>
        <div><span style="color:var(--muted);">Datum rodjenja:</span><br><strong><?= date('d.m.Y', strtotime($zaposleni['datum_rodjenja'])) ?></strong></div>
        <?php endif; ?>
        <?php if (!empty($zaposleni['jmbg'])): ?>
        <div><span style="color:var(--muted);">JMBG:</span><br><strong><?= h($zaposleni['jmbg']) ?></strong></div>
        <?php endif; ?>
        <?php if (!empty($zaposleni['datum_zaposlenja'])): ?>
        <div><span style="color:var(--muted);">Zaposlen od:</span><br><strong><?= date('d.m.Y', strtotime($zaposleni['datum_zaposlenja'])) ?></strong></div>
        <?php endif; ?>
        <div><span style="color:var(--muted);">Tip ugovora:</span><br><strong><?= $tip_ugovora_label[$zaposleni['tip_ugovora'] ?? 'neodredjeno'] ?></strong></div>
        <?php if (!empty($zaposleni['datum_isteka'])): ?>
        <div><span style="color:var(--muted);">Istek ugovora:</span><br>
            <strong style="color:<?= strtotime($zaposleni['datum_isteka']) < strtotime('+30 days') ? '#dc2626' : 'inherit' ?>">
                <?= date('d.m.Y', strtotime($zaposleni['datum_isteka'])) ?>
            </strong>
        </div>
        <?php endif; ?>
        <?php if (!empty($zaposleni['pol'])): ?>
        <div><span style="color:var(--muted);">Pol:</span><br><strong><?= $zaposleni['pol'] === 'M' ? 'Muski' : 'Zenski' ?></strong></div>
        <?php endif; ?>
    </div>
    <?php if (!empty($zaposleni['napomena'])): ?>
    <div style="margin-top:12px;padding:10px;background:var(--light);border-radius:8px;font-size:13px;color:var(--muted);"><?= nl2br(h($zaposleni['napomena'])) ?></div>
    <?php endif; ?>
</div>

<div style="background:#fff;border-radius:14px;border:1.5px solid var(--light2);padding:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--blue);">Evidencija odsustva</div>
        <?php if ($je_admin): ?>
        <button type="button" class="btn-sm mark" onclick="openOdsustvoModal(<?= $id ?>)">+ Dodaj</button>
        <?php endif; ?>
    </div>
    <?php if (empty($odsustva)): ?>
    <p style="color:var(--muted);font-size:13px;">Nema evidentirane odsutnosti.</p>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:6px;">
        <?php foreach ($odsustva as $o): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;background:var(--light);border-radius:8px;flex-wrap:wrap;">
            <span style="background:<?= $tip_boja[$o['tip']] ?? '#f1f5f9' ?>;color:<?= $tip_text[$o['tip']] ?? '#475569' ?>;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:700;white-space:nowrap;"><?= $tip_odsustva[$o['tip']] ?? $o['tip'] ?></span>
            <span style="font-size:13px;"><?= date('d.m.Y', strtotime($o['datum_od'])) ?> — <?= date('d.m.Y', strtotime($o['datum_do'])) ?></span>
            <span style="font-size:12px;color:var(--muted);"><?= $o['broj_dana'] ?> dana</span>
            <?php if (!empty($o['napomena'])): ?><span style="font-size:12px;color:var(--muted);font-style:italic;flex:1;"><?= h($o['napomena']) ?></span><?php endif; ?>
            <?php if ($je_admin): ?><button type="button" class="btn-sm del" onclick="obrisiOdsustvo(<?= $o['id'] ?>)" style="margin-left:auto;">&#128465;</button><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div style="background:#fff;border-radius:14px;border:1.5px solid var(--light2);padding:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--blue);">Dokumenta</div>
        <?php if ($je_admin): ?>
        <button type="button" class="btn-sm mark" onclick="openDokModal(<?= $id ?>)">+ Upload</button>
        <?php endif; ?>
    </div>
    <?php if (empty($dokumenta)): ?>
    <p style="color:var(--muted);font-size:13px;">Nema uploadovanih dokumenata.</p>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:6px;">
        <?php foreach ($dokumenta as $dok): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;background:var(--light);border-radius:8px;">
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;color:var(--blue);"><?= h($dok['naziv']) ?></div>
                <div style="font-size:11px;color:var(--muted);"><?= ucfirst($dok['tip']) ?> &middot; <?= date('d.m.Y', strtotime($dok['created_at'])) ?></div>
            </div>
            <a href="<?= h($dok['putanja']) ?>" target="_blank" class="btn-sm">&#8659;</a>
            <?php if ($je_admin): ?><button type="button" class="btn-sm del" onclick="obrisiDokument(<?= $dok['id'] ?>)">&#128465;</button><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</div>
</div>

<?php if ($je_admin): ?>

<div id="hr-edit-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto;">
<div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:600px;box-shadow:0 24px 80px #000a;position:relative;margin:auto;">
    <button onclick="document.getElementById('hr-edit-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">&#10005;</button>
    <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:18px;">Izmeni karton</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="tim-form-group" style="grid-column:1/-1;"><label>Ime i prezime</label><input type="text" id="hr-ime" value="<?= h($zaposleni['ime']) ?>"></div>
        <div class="tim-form-group"><label>Pozicija</label><input type="text" id="hr-pozicija" value="<?= h($zaposleni['pozicija'] ?? '') ?>"></div>
        <div class="tim-form-group"><label>JMBG</label><input type="text" id="hr-jmbg" value="<?= h($zaposleni['jmbg'] ?? '') ?>" maxlength="13"></div>
        <div class="tim-form-group"><label>Email</label><input type="email" id="hr-email" value="<?= h($zaposleni['email'] ?? '') ?>"></div>
        <div class="tim-form-group"><label>Telefon</label><input type="tel" id="hr-telefon" value="<?= h($zaposleni['telefon'] ?? '') ?>"></div>
        <div class="tim-form-group"><label>Datum rodjenja</label><input type="date" id="hr-datum-rodj" value="<?= h($zaposleni['datum_rodjenja'] ?? '') ?>"></div>
        <div class="tim-form-group"><label>Pol</label>
            <select id="hr-pol">
                <option value="">—</option>
                <option value="M" <?= ($zaposleni['pol'] ?? '') === 'M' ? 'selected' : '' ?>>Muski</option>
                <option value="Z" <?= ($zaposleni['pol'] ?? '') === 'Z' ? 'selected' : '' ?>>Zenski</option>
            </select>
        </div>
        <div class="tim-form-group"><label>Adresa</label><input type="text" id="hr-adresa" value="<?= h($zaposleni['adresa'] ?? '') ?>"></div>
        <div class="tim-form-group"><label>Grad</label><input type="text" id="hr-grad" value="<?= h($zaposleni['grad'] ?? '') ?>"></div>
        <div class="tim-form-group"><label>Datum zaposlenja</label><input type="date" id="hr-datum-zaposl" value="<?= h($zaposleni['datum_zaposlenja'] ?? '') ?>"></div>
        <div class="tim-form-group"><label>Tip ugovora</label>
            <select id="hr-tip-ugovora">
                <option value="neodredjeno" <?= ($zaposleni['tip_ugovora'] ?? '') === 'neodredjeno' ? 'selected' : '' ?>>Na neodredjeno</option>
                <option value="odredjeno"   <?= ($zaposleni['tip_ugovora'] ?? '') === 'odredjeno'   ? 'selected' : '' ?>>Na odredjeno</option>
                <option value="ucenicka"    <?= ($zaposleni['tip_ugovora'] ?? '') === 'ucenicka'    ? 'selected' : '' ?>>Ucenicka praksa</option>
                <option value="probni"      <?= ($zaposleni['tip_ugovora'] ?? '') === 'probni'      ? 'selected' : '' ?>>Probni rad</option>
            </select>
        </div>
        <div class="tim-form-group" style="grid-column:1/-1;"><label>Datum isteka ugovora</label><input type="date" id="hr-datum-isteka" value="<?= h($zaposleni['datum_isteka'] ?? '') ?>"></div>
        <div class="tim-form-group" style="grid-column:1/-1;"><label>Poveži sa nalogom u aplikaciji</label>
            <select id="hr-korisnik">
                <option value="">— Bez naloga —</option>
                <?php if (!empty($zaposleni['korisnik_id'])): ?>
                <option value="<?= $zaposleni['korisnik_id'] ?>" selected>Trenutni nalog</option>
                <?php endif; ?>
                <?php foreach ($korisnici_slobodni as $k): ?>
                <option value="<?= $k['id'] ?>"><?= h($k['ime']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tim-form-group" style="grid-column:1/-1;"><label>Napomena</label>
            <textarea id="hr-napomena" rows="2" style="border:1.5px solid var(--light2);border-radius:7px;padding:8px 12px;font-size:13px;font-family:inherit;outline:none;background:var(--light);width:100%;resize:vertical;"><?= h($zaposleni['napomena'] ?? '') ?></textarea>
        </div>
    </div>
    <div id="hr-edit-err" style="color:#dc2626;font-size:12px;margin-bottom:8px;display:none;"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px;">
        <button class="mail-cancel-btn" onclick="document.getElementById('hr-edit-modal').style.display='none'">Odustani</button>
        <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="sacuvajKarton()">Sacuvaj</button>
    </div>
</div>
</div>

<div id="hr-godisnji-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:center;justify-content:center;">
<div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:360px;box-shadow:0 24px 80px #000a;position:relative;">
    <button onclick="document.getElementById('hr-godisnji-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">&#10005;</button>
    <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:18px;">Godisnji odmor</h3>
    <input type="hidden" id="hr-g-zid">
    <div class="tim-form-group"><label>Godina</label><input type="number" id="hr-g-godina" value="<?= date('Y') ?>" min="2020" max="2035"></div>
    <div class="tim-form-group"><label>Ukupno dana</label><input type="number" id="hr-g-ukupno" value="20" min="1" max="365"></div>
    <div class="tim-form-group"><label>Preneseno iz prethodne godine</label><input type="number" id="hr-g-preneseno" value="0" min="0" max="365"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px;">
        <button class="mail-cancel-btn" onclick="document.getElementById('hr-godisnji-modal').style.display='none'">Odustani</button>
        <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="sacuvajGodisnji()">Sacuvaj</button>
    </div>
</div>
</div>

<div id="hr-odsustvo-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:center;justify-content:center;">
<div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:400px;box-shadow:0 24px 80px #000a;position:relative;">
    <button onclick="document.getElementById('hr-odsustvo-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">&#10005;</button>
    <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:18px;">Dodaj odsustvo</h3>
    <input type="hidden" id="hr-o-zid">
    <div class="tim-form-group"><label>Tip</label>
        <select id="hr-o-tip">
            <option value="godisnji">Godisnji odmor</option>
            <option value="bolovanje">Bolovanje</option>
            <option value="neplaceno">Neplaceno odsustvo</option>
            <option value="porodiljsko">Porodiljsko</option>
            <option value="placeno">Placeno odsustvo</option>
            <option value="praznik">Drzavni praznik</option>
            <option value="ostalo">Ostalo</option>
        </select>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="tim-form-group"><label>Od</label><input type="date" id="hr-o-od"></div>
        <div class="tim-form-group"><label>Do</label><input type="date" id="hr-o-do"></div>
    </div>
    <div class="tim-form-group"><label>Napomena</label><input type="text" id="hr-o-napomena" placeholder="Opciono..."></div>
    <div id="hr-o-err" style="color:#dc2626;font-size:12px;margin-bottom:8px;display:none;"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px;">
        <button class="mail-cancel-btn" onclick="document.getElementById('hr-odsustvo-modal').style.display='none'">Odustani</button>
        <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="sacuvajOdsustvo()">Dodaj</button>
    </div>
</div>
</div>

<div id="hr-dok-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:center;justify-content:center;">
<div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:400px;box-shadow:0 24px 80px #000a;position:relative;">
    <button onclick="document.getElementById('hr-dok-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">&#10005;</button>
    <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:18px;">Upload dokumenta</h3>
    <input type="hidden" id="hr-d-zid">
    <div class="tim-form-group"><label>Naziv</label><input type="text" id="hr-d-naziv" placeholder="npr. Ugovor o radu 2025"></div>
    <div class="tim-form-group"><label>Tip</label>
        <select id="hr-d-tip">
            <option value="ugovor">Ugovor</option>
            <option value="diploma">Diploma/Sertifikat</option>
            <option value="licenca">Licenca</option>
            <option value="ostalo">Ostalo</option>
        </select>
    </div>
    <div class="tim-form-group"><label>Fajl</label><input type="file" id="hr-d-fajl"></div>
    <div id="hr-d-err" style="color:#dc2626;font-size:12px;margin-bottom:8px;display:none;"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px;">
        <button class="mail-cancel-btn" onclick="document.getElementById('hr-dok-modal').style.display='none'">Odustani</button>
        <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="uploadDokument()">Upload</button>
    </div>
</div>
</div>

<script>
var _hrZid = <?= $id ?>;

function openHrEditModal() {
    document.getElementById('hr-edit-err').style.display = 'none';
    document.getElementById('hr-edit-modal').style.display = 'flex';
}

function sacuvajKarton() {
    var err = document.getElementById('hr-edit-err');
    err.style.display = 'none';
    var fd = new FormData();
    fd.append('_action',          'hr_sacuvaj_karton');
    fd.append('id',               0);
    fd.append('zaposleni_id',     _hrZid);
    fd.append('ime',              document.getElementById('hr-ime').value);
    fd.append('pozicija',         document.getElementById('hr-pozicija').value);
    fd.append('jmbg',             document.getElementById('hr-jmbg').value);
    fd.append('email',            document.getElementById('hr-email').value);
    fd.append('telefon',          document.getElementById('hr-telefon').value);
    fd.append('pol',              document.getElementById('hr-pol').value);
    fd.append('datum_rodjenja',   document.getElementById('hr-datum-rodj').value);
    fd.append('adresa',           document.getElementById('hr-adresa').value);
    fd.append('grad',             document.getElementById('hr-grad').value);
    fd.append('datum_zaposlenja', document.getElementById('hr-datum-zaposl').value);
    fd.append('tip_ugovora',      document.getElementById('hr-tip-ugovora').value);
    fd.append('datum_isteka',     document.getElementById('hr-datum-isteka').value);
    fd.append('korisnik_id',      document.getElementById('hr-korisnik').value);
    fd.append('napomena',         document.getElementById('hr-napomena').value);
    fetch('/mvc/?page=hr', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.ok) {
            document.getElementById('hr-edit-modal').style.display = 'none';
            location.reload();
        } else {
            err.textContent = d.err || 'Greška.';
            err.style.display = 'block';
        }
    })
    .catch(function(e) {
        err.textContent = 'Greška pri čuvanju.';
        err.style.display = 'block';
    });
}

function uploadSliku(zid) {
    var file = document.getElementById('hr-slika-input').files[0];
    if (!file) return;
    var fd = new FormData();
    fd.append('_action',      'hr_upload_sliku');
    fd.append('id',           0);
    fd.append('zaposleni_id', zid);
    fd.append('slika',        file);
    fetch('/mvc/?page=hr', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.ok) { location.reload(); }
        else { alert(d.err || 'Greška pri uploadu slike.'); }
    });
}

function toggleAktivan(zid) {
    if (!confirm('Promeniti status zaposlenog?')) return;
    var fd = new FormData();
    fd.append('_action', 'hr_toggle_aktivan');
    fd.append('id',      zid);
    fetch('/mvc/?page=hr', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) { if (d.ok) location.reload(); });
}

function openGodisnjModal(zid) {
    document.getElementById('hr-g-zid').value = zid;
    document.getElementById('hr-godisnji-modal').style.display = 'flex';
}

function sacuvajGodisnji() {
    var zid = document.getElementById('hr-g-zid').value;
    if (!zid || zid == '0') { alert('Greška: ID zaposlenog nije postavljen.'); return; }
    var fd = new FormData();
    fd.append('_action',      'hr_sacuvaj_godisnji');
    fd.append('id',           0);
    fd.append('zaposleni_id', zid);
    fd.append('godina',       document.getElementById('hr-g-godina').value);
    fd.append('ukupno_dana',  document.getElementById('hr-g-ukupno').value);
    fd.append('preneseno',    document.getElementById('hr-g-preneseno').value);
    fetch('/mvc/?page=hr', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.ok) {
            document.getElementById('hr-godisnji-modal').style.display = 'none';
            location.reload();
        } else {
            alert(d.err || 'Greška pri čuvanju.');
        }
    })
    .catch(function(e) { alert('Greška: ' + e); });
}

function openOdsustvoModal(zid) {
    document.getElementById('hr-o-zid').value = zid;
    document.getElementById('hr-o-err').style.display = 'none';
    document.getElementById('hr-o-od').value = '';
    document.getElementById('hr-o-do').value = '';
    document.getElementById('hr-o-napomena').value = '';
    document.getElementById('hr-odsustvo-modal').style.display = 'flex';
}

function sacuvajOdsustvo() {
    var err = document.getElementById('hr-o-err');
    err.style.display = 'none';
    var fd = new FormData();
    fd.append('_action',      'hr_dodaj_odsustvo');
    fd.append('id',           0);
    fd.append('zaposleni_id', document.getElementById('hr-o-zid').value);
    fd.append('tip',          document.getElementById('hr-o-tip').value);
    fd.append('datum_od',     document.getElementById('hr-o-od').value);
    fd.append('datum_do',     document.getElementById('hr-o-do').value);
    fd.append('napomena',     document.getElementById('hr-o-napomena').value);
    fetch('/mvc/?page=hr', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.ok) {
            document.getElementById('hr-odsustvo-modal').style.display = 'none';
            location.reload();
        } else {
            err.textContent = d.err || 'Greška.';
            err.style.display = 'block';
        }
    });
}

function obrisiOdsustvo(id) {
    if (!confirm('Obrisati odsustvo?')) return;
    var fd = new FormData();
    fd.append('_action', 'hr_obrisi_odsustvo');
    fd.append('id',      id);
    fetch('/mvc/?page=hr', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) { if (d.ok) location.reload(); });
}

function openDokModal(zid) {
    document.getElementById('hr-d-zid').value = zid;
    document.getElementById('hr-d-err').style.display = 'none';
    document.getElementById('hr-dok-modal').style.display = 'flex';
}

function uploadDokument() {
    var err = document.getElementById('hr-d-err');
    err.style.display = 'none';
    var file = document.getElementById('hr-d-fajl').files[0];
    if (!file) { err.textContent = 'Izaberite fajl.'; err.style.display = 'block'; return; }
    var fd = new FormData();
    fd.append('_action',      'hr_upload_dokument');
    fd.append('id',           0);
    fd.append('zaposleni_id', document.getElementById('hr-d-zid').value);
    fd.append('naziv',        document.getElementById('hr-d-naziv').value);
    fd.append('tip',          document.getElementById('hr-d-tip').value);
    fd.append('dokument',     file);
    fetch('/mvc/?page=hr', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.ok) {
            document.getElementById('hr-dok-modal').style.display = 'none';
            location.reload();
        } else {
            err.textContent = d.err || 'Greška.';
            err.style.display = 'block';
        }
    });
}

function obrisiDokument(id) {
    if (!confirm('Obrisati dokument?')) return;
    var fd = new FormData();
    fd.append('_action', 'hr_obrisi_dokument');
    fd.append('id',      id);
    fetch('/mvc/?page=hr', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) { if (d.ok) location.reload(); });
}
</script>

<?php endif; ?>
