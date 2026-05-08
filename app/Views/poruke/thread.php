<?php
$uid = \Core\Auth::id();
$tip = $root['tip'] ?? 'poruka';

$statusLabele = [
    'otvoreno'   => ['label' => 'Otvoreno',       'class' => 'badge-new'],
    'u_toku'     => ['label' => 'U toku',          'class' => 'badge-operator'],
    'zavrseno'   => ['label' => 'Završeno',        'class' => 'badge-ok'],
    'ceka'       => ['label' => 'Čeka odobrenje',  'class' => 'badge-new'],
    'odobreno'   => ['label' => 'Odobreno',        'class' => 'badge-operator'],
    'naruceno'   => ['label' => 'Naručeno',         'class' => 'badge-admin'],
    'isporuceno' => ['label' => 'Isporučeno',       'class' => 'badge-ok'],
];

$backUrl = match($tip) {
    'zadatak' => BASE_URL . '/?page=poruke&tab=zadaci',
    'nabavka' => BASE_URL . '/?page=poruke&tab=nabavka',
    default   => BASE_URL . '/?page=poruke',
};

$mozeMenjaStatus = \Core\Auth::isAdmin() || $root['posiljalac_id'] == $uid;
?>

<div class="poruke-header">
    <h1><?= h($root['naslov']) ?></h1>
    <a href="<?= $backUrl ?>" class="btn-secondary">← Nazad</a>
</div>

<!-- Meta header za zadatak/nabavku -->
<?php if ($tip !== 'poruka'): ?>
<div class="thread-meta-box">
    <?php if ($root['status']): ?>
    <?php $sl = $statusLabele[$root['status']] ?? ['label' => $root['status'], 'class' => 'badge-operator']; ?>
    <span class="<?= $sl['class'] ?>"><?= $sl['label'] ?></span>
    <?php endif; ?>

    <?php if ($gradiliste): ?>
    <span>🏗️ <?= h($gradiliste['naziv']) ?></span>
    <?php endif; ?>

    <?php if ($tip === 'zadatak' && !empty($root['meta']['datum'])): ?>
    <span>📅 Rok: <?= date('d.m.Y', strtotime($root['meta']['datum'])) ?></span>
    <?php endif; ?>

    <?php if ($tip === 'nabavka' && !empty($root['meta']['tip_nabavke'])): ?>
    <span>📦 <?= ucfirst($root['meta']['tip_nabavke']) ?> nabavka</span>
    <?php endif; ?>

    <!-- Promeni status -->
    <?php if ($mozeMenjaStatus && $root['status']): ?>
    <div style="margin-left:auto;">
        <?php if ($tip === 'zadatak'): ?>
        <form method="POST" style="display:inline;">
            <select name="novi_status" onchange="this.form.submit()"
                style="border:1.5px solid var(--light2);border-radius:6px;padding:4px 8px;font-size:12px;background:var(--light);cursor:pointer;">
                <option value="otvoreno"  <?= $root['status']==='otvoreno'  ? 'selected':'' ?>>Otvoreno</option>
                <option value="u_toku"    <?= $root['status']==='u_toku'    ? 'selected':'' ?>>U toku</option>
                <option value="zavrseno"  <?= $root['status']==='zavrseno'  ? 'selected':'' ?>>Završeno</option>
            </select>
        </form>
        <?php elseif ($tip === 'nabavka'): ?>
        <form method="POST" style="display:inline;">
            <select name="novi_status" onchange="this.form.submit()"
                style="border:1.5px solid var(--light2);border-radius:6px;padding:4px 8px;font-size:12px;background:var(--light);cursor:pointer;">
                <option value="ceka"       <?= $root['status']==='ceka'       ? 'selected':'' ?>>Čeka odobrenje</option>
                <option value="odobreno"   <?= $root['status']==='odobreno'   ? 'selected':'' ?>>Odobreno</option>
                <option value="naruceno"   <?= $root['status']==='naruceno'   ? 'selected':'' ?>>Naručeno</option>
                <option value="isporuceno" <?= $root['status']==='isporuceno' ? 'selected':'' ?>>Isporučeno</option>
            </select>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Stavke za nabavku -->
<?php if ($tip === 'nabavka' && !empty($root['meta']['stavke'])): ?>
<div class="thread-stavke">
    <strong style="font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;">Stavke:</strong>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;">
        <?php foreach ($root['meta']['stavke'] as $s): ?>
        <span style="background:var(--light);border:1px solid var(--light2);border-radius:6px;padding:3px 10px;font-size:12px;">
            <?= h($s['naziv']) ?> — <?= $s['kolicina'] ?> <?= h($s['jedinica']) ?>
        </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Thread poruke -->
<div class="thread-wrap">
    <?php foreach ($poruke as $p): ?>
    <?php $moja = ($p['posiljalac_id'] == $uid); ?>
    <div class="thread-msg <?= $moja ? 'thread-msg--moja' : 'thread-msg--tuda' ?>">
        <div class="thread-msg__header">
            <strong><?= h($p['posiljalac_ime']) ?></strong>
            <span><?= date('d.m.Y H:i', strtotime($p['created_at'])) ?></span>
        </div>
        <div class="thread-msg__body"><?= nl2br(h($p['sadrzaj'])) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Reply forma -->
<div class="poruke-form-card" style="margin-top:1.5rem;">
    <form method="POST" action="<?= BASE_URL ?>/?page=poruke&view=thread&id=<?= $roditelj_id ?>">
        <div class="tim-form-group">
            <label><?= $tip === 'zadatak' ? 'Izveštaj / Komentar' : 'Odgovor' ?></label>
            <textarea name="sadrzaj" rows="3" required
                placeholder="<?= $tip === 'zadatak' ? 'Šta je urađeno? Napiši izveštaj...' : 'Napiši odgovor...' ?>"
                style="border:1.5px solid var(--light2);border-radius:8px;padding:9px 13px;font-size:14px;font-family:inherit;outline:none;background:var(--light);width:100%;resize:vertical;"></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end;">
            <button type="submit" class="btn-primary">Pošalji</button>
        </div>
    </form>
</div>

<style>
.thread-meta-box {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    background: var(--light); border-radius: 10px; padding: 10px 14px;
    margin-bottom: 16px; font-size: 13px; color: var(--muted);
}
.thread-stavke {
    background: #fff; border: 1.5px solid var(--light2);
    border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;
}
</style>
