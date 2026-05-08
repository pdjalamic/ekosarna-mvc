<?php
$tabs = [
    'inbox'      => ['label' => '📨 Inbox',      'url' => BASE_URL . '/?page=poruke&tab=inbox'],
    'zadaci'     => ['label' => '📋 Zadaci',     'url' => BASE_URL . '/?page=poruke&tab=zadaci'],
    'nabavka'    => ['label' => '🛒 Nabavka',    'url' => BASE_URL . '/?page=poruke&tab=nabavka'],
    'gradilista' => ['label' => '🏗️ Gradilišta', 'url' => BASE_URL . '/?page=poruke&tab=gradilista'],
    'izvestaji'  => ['label' => '📊 Izveštaji',  'url' => BASE_URL . '/?page=poruke&tab=izvestaji'],
];
$current_tab = 'inbox';
?>

<div class="poruke-header">
    <h1>Poruke</h1>
    <a href="<?= BASE_URL ?>/?page=poruke&view=nova" class="btn-primary">+ Nova poruka</a>
</div>

<!-- Tab navigacija -->
<div class="p-tabs">
    <?php foreach ($tabs as $key => $t): ?>
    <a href="<?= $t['url'] ?>" class="p-tab <?= $current_tab === $key ? 'active' : '' ?>">
        <?= $t['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Sub-tabs: Primljene / Poslato -->
<div class="poruke-tabs" style="margin-bottom:16px;">
    <a href="?page=poruke&tab=inbox&subtab=primljene"
       class="poruke-tab <?= $subtab === 'primljene' ? 'active' : '' ?>">Primljene</a>
    <a href="?page=poruke&tab=inbox&subtab=poslato"
       class="poruke-tab <?= $subtab === 'poslato' ? 'active' : '' ?>">Poslato</a>
</div>

<?php $lista = $subtab === 'poslato' ? $poslato : $inbox; ?>

<?php if (empty($lista)): ?>
    <p class="empty-msg">Nema poruka.</p>
<?php else: ?>
<div class="poruke-table table-wrap">
    <table>
        <thead>
            <tr>
                <th style="width:20px"></th>
                <th><?= $subtab === 'poslato' ? 'Prima' : 'Šalje' ?></th>
                <th>Naslov</th>
                <th>Datum</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lista as $p): ?>
            <tr class="<?= (!$p['procitano'] && $subtab === 'primljene') ? 'poruka-unread' : '' ?>">
                <td><?= (!$p['procitano'] && $subtab === 'primljene') ? '🔵' : '' ?></td>
                <td>
                    <?php if ($subtab === 'poslato'): ?>
                        <?= $p['primalac_id'] === null ? '<strong>Svi</strong>' : h($p['primalac_ime']) ?>
                    <?php else: ?>
                        <?= h($p['posiljalac_ime']) ?>
                        <?= $p['primalac_id'] === null ? '<span class="badge-broadcast">broadcast</span>' : '' ?>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= BASE_URL ?>/?page=poruke&view=thread&id=<?= $p['id'] ?>">
                        <?= h($p['naslov']) ?>
                    </a>
                </td>
                <td class="datum"><?= date('d.m.Y H:i', strtotime($p['created_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
