<?php
$tabs = [
    'inbox'      => ['label' => '📨 Inbox',      'url' => BASE_URL . '/?page=poruke&tab=inbox'],
    'zadaci'     => ['label' => '📋 Zadaci',     'url' => BASE_URL . '/?page=poruke&tab=zadaci'],
    'nabavka'    => ['label' => '🛒 Nabavka',    'url' => BASE_URL . '/?page=poruke&tab=nabavka'],
    'gradilista' => ['label' => '🏗️ Gradilišta', 'url' => BASE_URL . '/?page=poruke&tab=gradilista'],
    'izvestaji'  => ['label' => '📊 Izveštaji',  'url' => BASE_URL . '/?page=poruke&tab=izvestaji'],
];
$current_tab = 'gradilista';

$statusLabele = [
    'aktivno'  => ['label' => 'Aktivno',  'class' => 'badge-ok'],
    'pauza'    => ['label' => 'Pauza',    'class' => 'badge-new'],
    'zavrseno' => ['label' => 'Završeno', 'class' => 'badge-operator'],
];
?>

<div class="poruke-header">
    <h1>Gradilišta</h1>
    <?php if (\Core\Auth::isAdmin()): ?>
    <a href="<?= BASE_URL ?>/?page=gradilista" class="btn-secondary">Upravljaj →</a>
    <?php endif; ?>
</div>

<div class="p-tabs">
    <?php foreach ($tabs as $key => $t): ?>
    <a href="<?= $t['url'] ?>" class="p-tab <?= $current_tab === $key ? 'active' : '' ?>">
        <?= $t['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($gradilista)): ?>
    <p class="empty-msg">Nema gradilišta.</p>
<?php else: ?>
<div class="gt-lista">
    <?php foreach ($gradilista as $g): ?>
    <?php $sl = $statusLabele[$g['status']] ?? ['label' => $g['status'], 'class' => 'badge-operator']; ?>
    <div class="gt-card">
        <!-- Slika -->
        <div class="gt-card__slika">
            <?php if ($g['prva_slika']): ?>
            <img src="<?= h($g['prva_slika']) ?>" alt="<?= h($g['naziv']) ?>">
            <?php else: ?>
            <div class="gt-card__no-slika">🏗️</div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="gt-card__info">
            <div class="gt-card__top">
                <span class="<?= $sl['class'] ?>"><?= $sl['label'] ?></span>
                <span class="gt-card__naziv"><?= h($g['naziv']) ?></span>
            </div>
            <div class="gt-card__adresa">📍 <?= h($g['adresa']) ?></div>
            <?php if ($g['pocetak'] || $g['kraj']): ?>
            <div class="gt-card__period">
                📅 <?= $g['pocetak'] ? date('d.m.Y', strtotime($g['pocetak'])) : '?' ?>
                → <?= $g['kraj'] ? date('d.m.Y', strtotime($g['kraj'])) : 'u toku' ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Akcije -->
        <div class="gt-card__akcije">
            <a href="<?= BASE_URL ?>/?page=poruke&tab=zadaci&gradiliste_id=<?= $g['id'] ?>"
               class="btn-sm mark" style="text-decoration:none;">📋 Zadaci</a>
            <a href="<?= BASE_URL ?>/?page=poruke&tab=izvestaji&gradiliste_id=<?= $g['id'] ?>"
               class="btn-sm" style="text-decoration:none;">📊 Izveštaji</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.gt-lista {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 16px;
}
.gt-card {
    background: #fff;
    border: 1.5px solid var(--light2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    transition: box-shadow .15s, border-color .15s;
}
.gt-card:hover {
    border-color: #a0bcd8;
    box-shadow: 0 2px 12px #1a3a6e10;
}
.gt-card__slika {
    width: 90px;
    height: 70px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    background: var(--light);
}
.gt-card__slika img {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
}
.gt-card__no-slika {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; color: var(--light2);
}
.gt-card__info {
    flex: 1;
    min-width: 0;
}
.gt-card__top {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}
.gt-card__naziv {
    font-size: 15px;
    font-weight: 700;
    color: var(--blue);
}
.gt-card__adresa {
    font-size: 12px;
    color: var(--muted);
}
.gt-card__period {
    font-size: 12px;
    color: var(--muted);
    margin-top: 2px;
}
.gt-card__akcije {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex-shrink: 0;
}

@media(max-width:600px) {
    .gt-card { flex-wrap: wrap; }
    .gt-card__akcije { flex-direction: row; width: 100%; }
}
</style>
