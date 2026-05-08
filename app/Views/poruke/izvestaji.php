<?php
$tabs = [
    'inbox'      => ['label' => '📨 Inbox',      'url' => BASE_URL . '/?page=poruke&tab=inbox'],
    'zadaci'     => ['label' => '📋 Zadaci',     'url' => BASE_URL . '/?page=poruke&tab=zadaci'],
    'nabavka'    => ['label' => '🛒 Nabavka',    'url' => BASE_URL . '/?page=poruke&tab=nabavka'],
    'gradilista' => ['label' => '🏗️ Gradilišta', 'url' => BASE_URL . '/?page=poruke&tab=gradilista'],
    'izvestaji'  => ['label' => '📊 Izveštaji',  'url' => BASE_URL . '/?page=poruke&tab=izvestaji'],
];
$current_tab = 'izvestaji';
?>

<div class="poruke-header">
    <h1>Izveštaji</h1>
</div>

<div class="p-tabs">
    <?php foreach ($tabs as $key => $t): ?>
    <a href="<?= $t['url'] ?>" class="p-tab <?= $current_tab === $key ? 'active' : '' ?>">
        <?= $t['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filter po gradilištu -->
<div class="filter-bar" style="margin-bottom:16px;">
    <a href="?page=poruke&tab=izvestaji"
       class="filter-btn <?= !$gradiliste_id ? 'active' : '' ?>">Sva gradilišta</a>
    <?php foreach ($sva_gradilista as $g): ?>
    <a href="?page=poruke&tab=izvestaji&gradiliste_id=<?= $g['id'] ?>"
       class="filter-btn <?= $gradiliste_id === $g['id'] ? 'active' : '' ?>">
        <?= h($g['naziv']) ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($izvestaji)): ?>
    <p class="empty-msg">Nema izveštaja.</p>
<?php else: ?>
<div class="zadaci-lista">
    <?php foreach ($izvestaji as $iz): ?>
    <div class="z-card">
        <div class="z-card__header">
            <?php if ($iz['gradiliste_naziv']): ?>
            <span class="z-card__gradiliste">🏗️ <?= h($iz['gradiliste_naziv']) ?></span>
            <?php endif; ?>
            <span class="z-card__datum"><?= date('d.m.Y H:i', strtotime($iz['created_at'])) ?></span>
        </div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:4px;">
            Zadatak: <a href="<?= BASE_URL ?>/?page=poruke&view=thread&id=<?= $iz['roditelj_id'] ?>"
                style="color:var(--blue);"><?= h($iz['zadatak_naslov']) ?></a>
        </div>
        <div class="z-card__meta">
            <span>👤 <?= h($iz['posiljalac_ime']) ?></span>
        </div>
        <div class="z-card__opis"><?= nl2br(h(mb_substr($iz['sadrzaj'], 0, 200))) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
