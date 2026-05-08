<?php
$tabs = [
    'inbox'      => ['label' => '📨 Inbox',      'url' => BASE_URL . '/?page=poruke&tab=inbox'],
    'zadaci'     => ['label' => '📋 Zadaci',     'url' => BASE_URL . '/?page=poruke&tab=zadaci'],
    'nabavka'    => ['label' => '🛒 Nabavka',    'url' => BASE_URL . '/?page=poruke&tab=nabavka'],
    'gradilista' => ['label' => '🏗️ Gradilišta', 'url' => BASE_URL . '/?page=poruke&tab=gradilista'],
    'izvestaji'  => ['label' => '📊 Izveštaji',  'url' => BASE_URL . '/?page=poruke&tab=izvestaji'],
];
$current_tab = 'nabavka';

$statusLabele = [
    'ceka'      => ['label' => 'Čeka odobrenje', 'class' => 'badge-new'],
    'odobreno'  => ['label' => 'Odobreno',       'class' => 'badge-operator'],
    'naruceno'  => ['label' => 'Naručeno',        'class' => 'badge-admin'],
    'isporuceno'=> ['label' => 'Isporučeno',      'class' => 'badge-ok'],
];

$tipLabele = [
    'lokalna'  => '🏪 Lokalna',
    'udaljena' => '🚚 Udaljena',
    'usputna'  => '🔄 Usputna',
];
?>

<div class="poruke-header">
    <h1>Nabavka</h1>
    <a href="<?= BASE_URL ?>/?page=poruke&view=nova_nabavka" class="btn-primary">+ Nova nabavka</a>
</div>

<!-- Tab navigacija -->
<div class="p-tabs">
    <?php foreach ($tabs as $key => $t): ?>
    <a href="<?= $t['url'] ?>" class="p-tab <?= $current_tab === $key ? 'active' : '' ?>">
        <?= $t['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="filter-bar" style="margin-bottom:16px;">
    <?php
    $filteri = ['sve' => 'Sve', 'ceka' => 'Čeka', 'odobreno' => 'Odobreno', 'naruceno' => 'Naručeno', 'isporuceno' => 'Isporučeno'];
    foreach ($filteri as $val => $lab):
    ?>
    <a href="?page=poruke&tab=nabavka&filter=<?= $val ?>"
       class="filter-btn <?= $filter === $val ? 'active' : '' ?>"><?= $lab ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($nabavke)): ?>
    <p class="empty-msg">Nema zahteva za nabavku.</p>
<?php else: ?>
<div class="zadaci-lista">
    <?php foreach ($nabavke as $n): ?>
    <?php
    $sl  = $statusLabele[$n['status']] ?? ['label' => $n['status'], 'class' => 'badge-operator'];
    $tip = $tipLabele[$n['meta']['tip_nabavke'] ?? ''] ?? '';
    $stavke = $n['meta']['stavke'] ?? [];
    ?>
    <div class="z-card">
        <div class="z-card__header">
            <span class="<?= $sl['class'] ?>"><?= $sl['label'] ?></span>
            <?php if ($tip): ?><span class="z-card__gradiliste"><?= $tip ?></span><?php endif; ?>
            <?php if ($n['gradiliste_naziv']): ?>
            <span class="z-card__gradiliste">🏗️ <?= h($n['gradiliste_naziv']) ?></span>
            <?php endif; ?>
            <span class="z-card__datum"><?= date('d.m.Y', strtotime($n['created_at'])) ?></span>
        </div>
        <div class="z-card__naslov">
            <a href="<?= BASE_URL ?>/?page=poruke&view=thread&id=<?= $n['id'] ?>">
                <?= h($n['naslov']) ?>
            </a>
        </div>
        <div class="z-card__meta">
            <span>👤 <?= h($n['posiljalac_ime']) ?></span>
            <span>📦 <?= count($stavke) ?> stavki</span>
        </div>
        <?php if (!empty($stavke)): ?>
        <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">
            <?php foreach (array_slice($stavke, 0, 4) as $s): ?>
            <span style="background:var(--light);border:1px solid var(--light2);border-radius:6px;padding:2px 8px;font-size:12px;">
                <?= h($s['naziv']) ?> — <?= $s['kolicina'] ?> <?= h($s['jedinica']) ?>
            </span>
            <?php endforeach; ?>
            <?php if (count($stavke) > 4): ?>
            <span style="font-size:12px;color:var(--muted);">+<?= count($stavke) - 4 ?> više</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
