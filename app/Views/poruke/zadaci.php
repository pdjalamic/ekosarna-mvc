<?php
$tabs = [
    'inbox'      => ['label' => '📨 Inbox',      'url' => BASE_URL . '/?page=poruke&tab=inbox'],
    'zadaci'     => ['label' => '📋 Zadaci',     'url' => BASE_URL . '/?page=poruke&tab=zadaci'],
    'nabavka'    => ['label' => '🛒 Nabavka',    'url' => BASE_URL . '/?page=poruke&tab=nabavka'],
    'gradilista' => ['label' => '🏗️ Gradilišta', 'url' => BASE_URL . '/?page=poruke&tab=gradilista'],
    'izvestaji'  => ['label' => '📊 Izveštaji',  'url' => BASE_URL . '/?page=poruke&tab=izvestaji'],
];
$current_tab = 'zadaci';

$statusLabele = [
    'otvoreno' => ['label' => 'Otvoreno',  'class' => 'badge-new'],
    'u_toku'   => ['label' => 'U toku',    'class' => 'badge-operator'],
    'zavrseno' => ['label' => 'Završeno',  'class' => 'badge-ok'],
];
?>

<div class="poruke-header">
    <h1>Zadaci</h1>
    <?php if (\Core\Auth::isAdmin()): ?>
    <a href="<?= BASE_URL ?>/?page=poruke&view=nova_zadatak" class="btn-primary">+ Novi zadatak</a>
    <?php endif; ?>
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
    $filteri = ['sve' => 'Svi', 'otvoreno' => 'Otvoreno', 'u_toku' => 'U toku', 'zavrseno' => 'Završeno'];
    foreach ($filteri as $val => $lab):
    ?>
    <a href="?page=poruke&tab=zadaci&filter=<?= $val ?>"
       class="filter-btn <?= $filter === $val ? 'active' : '' ?>"><?= $lab ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($zadaci)): ?>
    <p class="empty-msg">Nema zadataka.</p>
<?php else: ?>
<div class="zadaci-lista">
    <?php foreach ($zadaci as $z): ?>
    <?php $sl = $statusLabele[$z['status']] ?? ['label' => $z['status'], 'class' => 'badge-operator']; ?>
    <div class="z-card">
        <div class="z-card__header">
            <span class="<?= $sl['class'] ?>"><?= $sl['label'] ?></span>
            <?php if ($z['gradiliste_naziv']): ?>
            <span class="z-card__gradiliste">🏗️ <?= h($z['gradiliste_naziv']) ?></span>
            <?php endif; ?>
            <span class="z-card__datum"><?= date('d.m.Y', strtotime($z['created_at'])) ?></span>
        </div>
        <div class="z-card__naslov">
            <a href="<?= BASE_URL ?>/?page=poruke&view=thread&id=<?= $z['id'] ?>">
                <?= h($z['naslov']) ?>
            </a>
        </div>
        <div class="z-card__meta">
            <span>👤 <?= h($z['posiljalac_ime']) ?></span>
            <?php if ($z['primalac_ime']): ?>
            <span>→ <?= h($z['primalac_ime']) ?></span>
            <?php endif; ?>
            <?php if (!empty($z['meta']['datum'])): ?>
            <span>📅 Rok: <?= date('d.m.Y', strtotime($z['meta']['datum'])) ?></span>
            <?php endif; ?>
        </div>
        <div class="z-card__opis"><?= h(mb_substr($z['sadrzaj'], 0, 120)) ?><?= mb_strlen($z['sadrzaj']) > 120 ? '...' : '' ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.zadaci-lista { display:flex; flex-direction:column; gap:10px; }
.z-card { background:#fff; border-radius:12px; border:1.5px solid var(--light2); padding:14px 16px; transition:border-color .15s,box-shadow .15s; }
.z-card:hover { border-color:#a0bcd8; box-shadow:0 2px 12px #1a3a6e12; }
.z-card__header { display:flex; align-items:center; gap:10px; margin-bottom:8px; flex-wrap:wrap; }
.z-card__gradiliste { font-size:12px; color:var(--muted); }
.z-card__datum { font-size:11px; color:var(--muted); margin-left:auto; }
.z-card__naslov a { font-size:15px; font-weight:700; color:var(--blue); text-decoration:none; }
.z-card__naslov a:hover { text-decoration:underline; }
.z-card__meta { display:flex; gap:12px; font-size:12px; color:var(--muted); margin:6px 0; flex-wrap:wrap; }
.z-card__opis { font-size:13px; color:#444; line-height:1.5; }
</style>
