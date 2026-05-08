<?php
$tabs = [
    'inbox'      => ['label' => '📨 Inbox',      'url' => BASE_URL . '/?page=poruke&tab=inbox'],
    'zadaci'     => ['label' => '📋 Zadaci',     'url' => BASE_URL . '/?page=poruke&tab=zadaci'],
    'nabavka'    => ['label' => '🛒 Nabavka',    'url' => BASE_URL . '/?page=poruke&tab=nabavka'],
    'gradilista' => ['label' => '🏗️ Gradilišta', 'url' => BASE_URL . '/?page=poruke&tab=gradilista'],
    'izvestaji'  => ['label' => '📊 Izveštaji',  'url' => BASE_URL . '/?page=poruke&tab=izvestaji'],
];
$current_tab = 'zadaci';
?>

<div class="poruke-header">
    <h1>Novi zadatak</h1>
    <a href="<?= BASE_URL ?>/?page=poruke&tab=zadaci" class="btn-secondary">← Nazad</a>
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

<div class="poruke-form-card" style="max-width:600px;margin-top:16px;">
    <form method="POST" action="<?= BASE_URL ?>/?page=poruke&view=nova_zadatak">
        <div class="tim-form-group">
            <label>Naslov zadatka *</label>
            <input type="text" name="naslov" required maxlength="255" placeholder="npr. Ugradnja razvoda na spratu 2">
        </div>
        <div class="tim-form-group">
            <label>Dodeli kome</label>
            <select name="primalac_id">
                <option value="">— Bez dodele —</option>
                <?php foreach ($korisnici as $k): ?>
                <option value="<?= $k['id'] ?>"><?= h($k['ime']) ?> (<?= h($k['uloga']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="tim-form-group">
                <label>Gradilište</label>
                <select name="gradiliste_id">
                    <option value="">— Bez gradilišta —</option>
                    <?php foreach ($gradilista as $g): ?>
                    <option value="<?= $g['id'] ?>"><?= h($g['naziv']) ?>
                        <?= $g['status'] !== 'aktivno' ? '(' . $g['status'] . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tim-form-group">
                <label>Rok</label>
                <input type="date" name="datum" min="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <div class="tim-form-group">
            <label>Opis zadatka *</label>
            <textarea name="sadrzaj" rows="5" required
                placeholder="Detaljno opišite šta treba uraditi..."
                style="border:1.5px solid var(--light2);border-radius:8px;padding:9px 13px;font-size:14px;font-family:inherit;outline:none;background:var(--light);width:100%;resize:vertical;"></textarea>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <a href="<?= BASE_URL ?>/?page=poruke&tab=zadaci" class="mail-cancel-btn">Odustani</a>
            <button type="submit" class="btn-primary">Dodeli zadatak</button>
        </div>
    </form>
</div>
