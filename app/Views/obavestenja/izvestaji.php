<div class="topbar-admin" style="flex-wrap:wrap;gap:10px;">
    <div class="page-title">📊 Izveštaji — poslata obaveštenja</div>
    <a href="<?= BASE_URL ?>/?page=obavestenja" class="btn-secondary">← Email iz imenika</a>
</div>

<!-- Pretraga -->
<div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;">
    <input type="text" id="srch-naslov" placeholder="Pretraži po naslovu ili tekstu..."
        oninput="filtriraj()"
        style="flex:1;min-width:200px;border:1.5px solid var(--light2);border-radius:8px;padding:8px 12px;font-size:13px;outline:none;background:#fff;">
    <input type="text" id="srch-primalac" placeholder="Pretraži po primaocu ili firmi..."
        oninput="filtriraj()"
        style="flex:1;min-width:200px;border:1.5px solid var(--light2);border-radius:8px;padding:8px 12px;font-size:13px;outline:none;background:#fff;">
</div>

<div class="rs-tabela-wrap">
<?php if (empty($logovi)): ?>
    <div style="padding:40px;text-align:center;color:var(--muted);font-size:14px;">
        Nema poslatih obaveštenja.
    </div>
<?php else: ?>
    <table class="rs-tabela" style="min-width:500px;">
        <thead>
            <tr>
                <th style="width:150px;">Datum</th>
                <th>Naslov</th>
                <th style="width:110px;text-align:center;">Primaoci</th>
                <th style="width:40px;"></th>
            </tr>
        </thead>
        <tbody id="izv-tbody">
        <?php foreach ($logovi as $i => $l):
            $primaoci_str = implode(' ', array_map(fn($p) => strtolower($p['ime'] . ' ' . $p['email'] . ' ' . $p['firma']), $l['primaoci']));
        ?>
        <tr class="izv-red rs-red"
            data-naslov="<?= htmlspecialchars(strtolower($l['naslov'] . ' ' . $l['tekst']), ENT_QUOTES) ?>"
            data-primaoci="<?= htmlspecialchars(strtolower($primaoci_str), ENT_QUOTES) ?>"
            onclick="toggleDetalj(<?= $i ?>)"
            style="cursor:pointer;">
            <td style="font-size:12px;color:var(--muted);">
                <?= date('d.m.Y H:i', strtotime($l['created_at'])) ?>
            </td>
            <td style="font-weight:600;font-size:13px;">
                <?= h($l['naslov']) ?>
                <div style="font-size:11px;color:var(--muted);font-weight:400;margin-top:2px;">
                    Poslao: <?= h($l['posiljac_ime']) ?>
                    <?php if (!empty($l['attachmenti'])): ?>
                    · <span style="color:var(--blue);">📎 <?= count($l['attachmenti']) ?> <?= count($l['attachmenti']) === 1 ? 'prilog' : (count($l['attachmenti']) < 5 ? 'priloga' : 'priloga') ?></span>
                    <?php endif; ?>
                </div>
            </td>
            <td style="text-align:center;">
                <span style="background:var(--light);border:1px solid var(--light2);border-radius:99px;font-size:12px;padding:2px 10px;">
                    <?= $l['poslato'] ?> / <?= $l['ukupno'] ?>
                </span>
            </td>
            <td style="text-align:center;color:var(--muted);font-size:13px;" id="arrow-<?= $i ?>">▼</td>
        </tr>
        <tr class="izv-detalj" id="detalj-<?= $i ?>" style="display:none;"
            data-naslov="<?= htmlspecialchars(strtolower($l['naslov'] . ' ' . $l['tekst']), ENT_QUOTES) ?>"
            data-primaoci="<?= htmlspecialchars(strtolower($primaoci_str), ENT_QUOTES) ?>">
            <td colspan="4" style="padding:0;">
                <div style="background:var(--light);border-top:1px solid var(--light2);border-bottom:1px solid var(--light2);padding:16px 18px;">

                    <!-- Tekst poruke -->
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:8px;">Tekst poruke</div>
                    <div style="background:#fff;border:1.5px solid var(--light2);border-radius:8px;padding:12px 14px;font-size:13px;line-height:1.6;white-space:pre-wrap;margin-bottom:16px;"><?= h($l['tekst']) ?></div>

                    <!-- Attachmenti -->
                    <?php if (!empty($l['attachmenti'])): ?>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:8px;">Priloženi fajlovi</div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
                        <?php foreach ($l['attachmenti'] as $att):
                            $ext = strtolower(pathinfo($att['naziv'], PATHINFO_EXTENSION));
                            $ikone = ['pdf'=>'📄','doc'=>'📝','docx'=>'📝','xls'=>'📊','xlsx'=>'📊','png'=>'🖼️','jpg'=>'🖼️','jpeg'=>'🖼️','zip'=>'🗜️','rar'=>'🗜️'];
                            $ikona = $ikone[$ext] ?? '📎';
                            $vel = $att['velicina'] < 1024*1024
                                ? round($att['velicina']/1024, 1) . ' KB'
                                : round($att['velicina']/(1024*1024), 1) . ' MB';
                            $url = BASE_URL . '/uploads/obavestenja/' . h($att['putanja']);
                        ?>
                        <a href="<?= $url ?>" target="_blank"
                           style="display:flex;align-items:center;gap:8px;background:#fff;border:1.5px solid var(--light2);border-radius:8px;padding:8px 12px;text-decoration:none;color:var(--text);transition:border-color .15s;"
                           onmouseover="this.style.borderColor='var(--blue)'" onmouseout="this.style.borderColor='var(--light2)'">
                            <span style="font-size:18px;"><?= $ikona ?></span>
                            <div>
                                <div style="font-size:12px;font-weight:600;"><?= h($att['naziv']) ?></div>
                                <div style="font-size:11px;color:var(--muted);"><?= $vel ?></div>
                            </div>
                            <span style="font-size:11px;color:var(--blue);margin-left:4px;">↓</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Primaoci -->
                    <?php
                        // Grupiši primaoce po firmi (da se firma ne ponavlja na svakom redu)
                        $grupe = [];
                        foreach ($l['primaoci'] as $p) {
                            $f = trim($p['firma'] ?? '');
                            $kljuc = $f !== '' ? $f : '— bez firme —';
                            $grupe[$kljuc][] = $p;
                        }
                        uksort($grupe, function($a, $b) {
                            if ($a === '— bez firme —') return 1;   // bez firme uvek na kraj
                            if ($b === '— bez firme —') return -1;
                            return strcasecmp($a, $b);
                        });
                    ?>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:8px;">
                        Primaoci (<?= count($l['primaoci']) ?>) · <?= count($grupe) ?> <?= count($grupe) === 1 ? 'firma' : 'firmi' ?>
                    </div>
                    <div style="background:#fff;border:1.5px solid var(--light2);border-radius:8px;max-height:340px;overflow-y:auto;">
                        <?php foreach ($grupe as $firma => $kontakti): ?>
                        <div style="display:flex;align-items:baseline;gap:8px;padding:7px 14px;background:var(--light);border-bottom:1px solid var(--light2);position:sticky;top:0;z-index:1;">
                            <span style="font-weight:700;font-size:12px;color:var(--blue);"><?= h($firma) ?></span>
                            <span style="font-size:11px;color:var(--muted);">(<?= count($kontakti) ?>)</span>
                        </div>
                        <?php foreach ($kontakti as $p): ?>
                        <div style="display:flex;flex-wrap:wrap;gap:2px 12px;padding:6px 14px 6px 22px;border-bottom:1px solid var(--light);font-size:12px;">
                            <?php if ($p['ime']): ?><span style="font-weight:600;min-width:140px;"><?= h($p['ime']) ?></span><?php endif; ?>
                            <span style="color:var(--muted);"><?= h($p['email']) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

<div id="izv-empty" style="display:none;padding:30px;text-align:center;color:var(--muted);font-size:13px;">
    Nema rezultata za unesene termine pretrage.
</div>

<script>
function toggleDetalj(i) {
    var detalj = document.getElementById('detalj-' + i);
    var arrow  = document.getElementById('arrow-' + i);
    var open   = detalj.style.display !== 'none';
    detalj.style.display = open ? 'none' : 'table-row';
    arrow.textContent    = open ? '▼' : '▲';
}

function filtriraj() {
    var sNaslov   = document.getElementById('srch-naslov').value.toLowerCase().trim();
    var sPrimalac = document.getElementById('srch-primalac').value.toLowerCase().trim();
    var redovi    = document.querySelectorAll('.izv-red');
    var vidljivo  = 0;

    redovi.forEach(function(red) {
        var naslov   = red.dataset.naslov   || '';
        var primaoci = red.dataset.primaoci || '';
        var detalj   = red.nextElementSibling;

        var poklapaNaslov   = !sNaslov   || naslov.includes(sNaslov);
        var poklapaPrimalac = !sPrimalac || primaoci.includes(sPrimalac);

        if (poklapaNaslov && poklapaPrimalac) {
            red.style.display = '';
            vidljivo++;
        } else {
            red.style.display = 'none';
            if (detalj && detalj.classList.contains('izv-detalj')) {
                detalj.style.display = 'none';
            }
        }
    });

    document.getElementById('izv-empty').style.display = vidljivo === 0 ? 'block' : 'none';
}
</script>
