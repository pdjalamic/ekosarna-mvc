
<?php
$tip_boja = [
    'godisnji'   => ['bg'=>'#dcfce7','text'=>'#166534','label'=>'Godišnji odmor'],
    'bolovanje'  => ['bg'=>'#fee2e2','text'=>'#991b1b','label'=>'Bolovanje'],
    'neplaceno'  => ['bg'=>'#fef3c7','text'=>'#92400e','label'=>'Neplaćeno'],
    'porodiljsko'=> ['bg'=>'#fce7f3','text'=>'#9d174d','label'=>'Porodiljsko'],
    'placeno'    => ['bg'=>'#dbeafe','text'=>'#1e40af','label'=>'Plaćeno odsustvo'],
    'praznik'    => ['bg'=>'#ede9fe','text'=>'#5b21b6','label'=>'Državni praznik'],
    'ostalo'     => ['bg'=>'#f1f5f9','text'=>'#475569','label'=>'Ostalo'],
];
?>

<div class="topbar-admin">
    <div class="page-title">👥 Zaposleni</div>
    <button type="button" class="btn-primary" onclick="openDodajModal()">+ Dodaj zaposlenog</button>
</div>

<!-- Lista zaposlenih -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:28px;">
    <?php foreach ($zaposleni as $z):
        $ostatak = ($z['ukupno_godisnji'] ?? 20) - ($z['iskoristio_godisnji'] ?? 0);
    ?>
    <a href="?page=hr&action=karton&id=<?= $z['id'] ?>"
       style="text-decoration:none;background:<?= $z['korisnik_id'] ? '#eff6ff' : '#fff' ?>;border-radius:14px;border:1.5px solid <?= $z['korisnik_id'] ? '#bfdbfe' : 'var(--light2)' ?>;padding:18px;display:flex;gap:14px;align-items:flex-start;opacity:<?= $z['aktivan'] ? '1' : '.6' ?>;">

        <div style="width:48px;height:48px;border-radius:50%;overflow:hidden;flex-shrink:0;background:var(--light);display:flex;align-items:center;justify-content:center;border:2px solid var(--light2);">
            <?php if ($z['slika_putanja']): ?>
            <img src="<?= h($z['slika_putanja']) ?>" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
            <span style="font-size:20px;">👤</span>
            <?php endif; ?>
        </div>

        <div style="flex:1;min-width:0;">
            <div style="font-size:15px;font-weight:700;color:var(--blue);"><?= h($z['ime']) ?></div>
            <div style="font-size:12px;color:var(--muted);margin-bottom:6px;"><?= h($z['pozicija'] ?? '—') ?></div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <span style="font-size:11px;background:#dcfce7;color:#166534;border-radius:6px;padding:1px 8px;">🏖️ <?= $ostatak ?> dana</span>
                <?php if ($z['korisnik_id']): ?>
                <span style="font-size:11px;background:#dbeafe;color:#1e40af;border-radius:6px;padding:1px 8px;">📱 App</span>
                <?php endif; ?>
                <?php if (!$z['aktivan']): ?>
                <span style="font-size:11px;background:#fee2e2;color:#991b1b;border-radius:6px;padding:1px 8px;">Neaktivan</span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php endforeach; ?>

    <?php if (empty($zaposleni)): ?>
    <div class="empty" style="grid-column:1/-1;">
        <big>👥</big>
        Nema zaposlenih. Klikni "+ Dodaj zaposlenog".
    </div>
    <?php endif; ?>
</div>

<!-- Odsustva ovog meseca -->
<div style="background:#fff;border-radius:14px;border:1.5px solid var(--light2);padding:20px;">
    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--blue);margin-bottom:14px;">
        📅 Svi vidovi odsustva — <?= date('m.Y') ?>
        <span style="font-size:10px;font-weight:400;color:var(--muted);text-transform:none;margin-left:6px;">(godišnji, bolovanje, neplaćeno...)</span>
    </div>
    <?php if (empty($odsustva_mesec)): ?>
    <p style="color:var(--muted);font-size:13px;">Nema evidentirane odsutnosti za ovaj mesec.</p>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:6px;">
        <?php foreach ($odsustva_mesec as $o):
            $b = $tip_boja[$o['tip']] ?? $tip_boja['ostalo'];
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:8px 12px;background:var(--light);border-radius:8px;flex-wrap:wrap;">
            <span style="background:<?= $b['bg'] ?>;color:<?= $b['text'] ?>;border-radius:6px;padding:2px 10px;font-size:11px;font-weight:700;">
                <?= $b['label'] ?>
            </span>
            <span style="font-size:13px;font-weight:600;color:var(--blue);"><?= h($o['zaposleni_ime']) ?></span>
            <span style="font-size:12px;color:var(--muted);">
                <?= date('d.m', strtotime($o['datum_od'])) ?> — <?= date('d.m.Y', strtotime($o['datum_do'])) ?>
                (<?= $o['broj_dana'] ?> dana)
            </span>
            <?php if ($o['napomena']): ?>
            <span style="font-size:12px;color:var(--muted);font-style:italic;"><?= h($o['napomena']) ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL: Dodaj zaposlenog -->
<div id="hr-add-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9997;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:460px;box-shadow:0 24px 80px #000a;position:relative;">
        <button onclick="document.getElementById('hr-add-modal').style.display='none'" style="position:absolute;top:12px;right:12px;background:var(--light);border:none;color:var(--muted);width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;">✕</button>
        <h3 style="font-size:16px;font-weight:700;color:var(--blue);margin-bottom:18px;">+ Dodaj zaposlenog</h3>
        <div class="tim-form-group"><label>Ime i prezime *</label><input type="text" id="hr-add-ime" placeholder="Ime Prezime"></div>
        <div class="tim-form-group"><label>Pozicija/Zvanje</label><input type="text" id="hr-add-pozicija" placeholder="npr. Elektricar"></div>
        <div class="tim-form-group"><label>Email</label><input type="email" id="hr-add-email" placeholder="email@primer.com"></div>
        <div class="tim-form-group"><label>Telefon</label><input type="tel" id="hr-add-telefon" placeholder="061 xxx xxxx"></div>
        <div class="tim-form-group">
            <label>Poveži sa nalogom u aplikaciji (opciono)</label>
            <select id="hr-add-korisnik">
                <option value="">— Bez naloga —</option>
                <?php foreach ($korisnici_slobodni as $k): ?>
                <option value="<?= $k['id'] ?>"><?= h($k['ime']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="hr-add-err" style="color:#dc2626;font-size:12px;margin-bottom:8px;display:none;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px;">
            <button class="mail-cancel-btn" onclick="document.getElementById('hr-add-modal').style.display='none'">Odustani</button>
            <button class="tim-add-btn" style="width:auto;padding:10px 22px;" onclick="dodajZaposlenog()">Dodaj</button>
        </div>
    </div>
</div>

<script>
function openDodajModal() {
    document.getElementById('hr-add-ime').value = '';
    document.getElementById('hr-add-pozicija').value = '';
    document.getElementById('hr-add-email').value = '';
    document.getElementById('hr-add-telefon').value = '';
    document.getElementById('hr-add-korisnik').selectedIndex = 0;
    document.getElementById('hr-add-err').style.display = 'none';
    document.getElementById('hr-add-modal').style.display = 'flex';
}

function dodajZaposlenog() {
    var ime = document.getElementById('hr-add-ime').value.trim();
    var err = document.getElementById('hr-add-err');
    err.style.display = 'none';
    if (!ime) { err.textContent = 'Ime je obavezno.'; err.style.display = 'block'; return; }

    var fd = new FormData();
    fd.append('_action', 'hr_dodaj_zaposlenog');
    fd.append('id', 0);
    fd.append('ime',         ime);
    fd.append('pozicija',    document.getElementById('hr-add-pozicija').value);
    fd.append('email',       document.getElementById('hr-add-email').value);
    fd.append('telefon',     document.getElementById('hr-add-telefon').value);
    fd.append('korisnik_id', document.getElementById('hr-add-korisnik').value);

    fetch('/mvc/?page=hr', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.ok) window.location.href = '?page=hr&action=karton&id=' + d.id;
        else { err.textContent = d.err || 'Greška.'; err.style.display = 'block'; }
    });
}
</script>
