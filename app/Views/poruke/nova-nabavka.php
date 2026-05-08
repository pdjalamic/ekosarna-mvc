<?php
$tabs = [
    'inbox'      => ['label' => '📨 Inbox',      'url' => BASE_URL . '/?page=poruke&tab=inbox'],
    'zadaci'     => ['label' => '📋 Zadaci',     'url' => BASE_URL . '/?page=poruke&tab=zadaci'],
    'nabavka'    => ['label' => '🛒 Nabavka',    'url' => BASE_URL . '/?page=poruke&tab=nabavka'],
    'gradilista' => ['label' => '🏗️ Gradilišta', 'url' => BASE_URL . '/?page=poruke&tab=gradilista'],
    'izvestaji'  => ['label' => '📊 Izveštaji',  'url' => BASE_URL . '/?page=poruke&tab=izvestaji'],
];
$current_tab = 'nabavka';
?>

<div class="poruke-header">
    <h1>Nova nabavka</h1>
    <a href="<?= BASE_URL ?>/?page=poruke&tab=nabavka" class="btn-secondary">← Nazad</a>
</div>

<div class="p-tabs">
    <?php foreach ($tabs as $key => $t): ?>
    <a href="<?= $t['url'] ?>" class="p-tab <?= $current_tab === $key ? 'active' : '' ?>">
        <?= $t['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($greska): ?>
<div class="error-msg" style="margin-bottom:16px;"><?= h($greska) ?></div>
<?php endif; ?>

<div class="poruke-form-card" style="max-width:680px;margin-top:16px;">
    <form method="POST" action="<?= BASE_URL ?>/?page=poruke&view=nova_nabavka">
        <div class="tim-form-group">
            <label>Naziv zahteva *</label>
            <input type="text" name="naslov" required maxlength="255" placeholder="npr. Materijal za gradilište Centar">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="tim-form-group">
                <label>Tip nabavke</label>
                <select name="tip_nabavke">
                    <option value="lokalna">🏪 Lokalna</option>
                    <option value="udaljena">🚚 Udaljena</option>
                    <option value="usputna">🔄 Usputna</option>
                </select>
            </div>
            <div class="tim-form-group">
                <label>Gradilište</label>
                <select name="gradiliste_id">
                    <option value="">— Opšta nabavka —</option>
                    <?php foreach ($gradilista as $g): ?>
                    <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Stavke -->
        <div class="tim-form-group">
            <label>Stavke *</label>
            <div id="stavke-lista">
                <div class="stavka-row">
                    <input type="text" name="stavka_naziv[]" placeholder="Naziv materijala" required style="flex:2;">
                    <input type="number" name="stavka_kolicina[]" placeholder="Kol." min="0.01" step="0.01" value="1" style="flex:.7;min-width:60px;">
                    <select name="stavka_jedinica[]" style="flex:.8;min-width:70px;">
                        <option>kom</option><option>m</option><option>m²</option>
                        <option>m³</option><option>kg</option><option>l</option>
                        <option>pak</option><option>role</option>
                    </select>
                    <button type="button" onclick="this.closest('.stavka-row').remove()"
                        style="background:none;border:none;color:#dc2626;font-size:18px;cursor:pointer;padding:0 4px;">✕</button>
                </div>
            </div>
            <button type="button" onclick="dodajStavku()"
                style="margin-top:8px;background:none;border:1.5px dashed var(--light2);border-radius:8px;padding:6px 14px;font-size:13px;color:var(--blue);cursor:pointer;width:100%;">
                + Dodaj stavku
            </button>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
            <a href="<?= BASE_URL ?>/?page=poruke&tab=nabavka" class="mail-cancel-btn">Odustani</a>
            <button type="submit" class="btn-primary">Pošalji zahtev</button>
        </div>
    </form>
</div>

<style>
.stavka-row {
    display: flex; gap: 8px; align-items: center; margin-bottom: 8px;
}
.stavka-row input, .stavka-row select {
    border: 1.5px solid var(--light2); border-radius: 8px;
    padding: 8px 10px; font-size: 13px; font-family: inherit;
    outline: none; background: var(--light);
}
.stavka-row input:focus, .stavka-row select:focus {
    border-color: var(--bluem); background: #fff;
}
</style>

<script>
function dodajStavku() {
    var lista = document.getElementById('stavke-lista');
    var row = document.createElement('div');
    row.className = 'stavka-row';
    row.innerHTML = `
        <input type="text" name="stavka_naziv[]" placeholder="Naziv materijala" required style="flex:2;">
        <input type="number" name="stavka_kolicina[]" placeholder="Kol." min="0.01" step="0.01" value="1" style="flex:.7;min-width:60px;">
        <select name="stavka_jedinica[]" style="flex:.8;min-width:70px;">
            <option>kom</option><option>m</option><option>m²</option>
            <option>m³</option><option>kg</option><option>l</option>
            <option>pak</option><option>role</option>
        </select>
        <button type="button" onclick="this.closest('.stavka-row').remove()"
            style="background:none;border:none;color:#dc2626;font-size:18px;cursor:pointer;padding:0 4px;">✕</button>
    `;
    lista.appendChild(row);
    row.querySelector('input').focus();
}
</script>
