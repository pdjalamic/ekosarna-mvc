<div class="poruke-header">
    <h1>Nova poruka</h1>
    <a href="<?= BASE_URL ?>/?page=poruke" class="btn-secondary">← Nazad</a>
</div>

<?php if (!empty($greska)): ?>
    <div class="error-msg"><?= h($greska) ?></div>
<?php endif; ?>

<div class="poruke-form-card">
    <form method="POST" action="<?= BASE_URL ?>/?page=poruke&view=nova">
        <div class="tim-form-group">
            <label>Primalac</label>
            <select name="primalac_id">
                <option value="">— Svi (broadcast) —</option>
                <?php foreach ($korisnici as $k): ?>
                    <option value="<?= $k['id'] ?>"><?= h($k['ime']) ?> (<?= h($k['uloga']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tim-form-group">
            <label>Naslov</label>
            <input type="text" name="naslov" required maxlength="255" placeholder="Naslov poruke...">
        </div>
        <div class="tim-form-group">
            <label>Poruka</label>
            <textarea name="sadrzaj" rows="6" required placeholder="Tekst poruke..."></textarea>
        </div>
        <button type="submit" class="btn-primary">Pošalji</button>
    </form>
</div>
