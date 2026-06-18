<?php /** @var array $linije  log linije, najnovije prve */ ?>
<style>
.pl-wrap { max-width:900px; }
.pl-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; }
.pl-head h1 { font-size:20px; color:#1a3a6e; margin:0; }
.pl-list { background:#fff; border:1.5px solid var(--light2); border-radius:12px; overflow:hidden; }
.pl-row { padding:10px 14px; border-bottom:1px solid var(--light2); font-size:13px; color:#1f2a44; font-family:ui-monospace,Menlo,Consolas,monospace; white-space:pre-wrap; word-break:break-word; }
.pl-row:last-child { border-bottom:none; }
.pl-row:nth-child(even) { background:#f8faff; }
.pl-empty { padding:40px; text-align:center; color:var(--muted); background:#fff; border:1.5px solid var(--light2); border-radius:12px; }
</style>

<div class="pl-wrap">
  <div class="pl-head">
    <h1>📨 Push log</h1>
    <span style="font-size:12px;color:var(--muted);"><?= count($linije) ?> zapisa (najnoviji gore)</span>
  </div>

  <?php if (empty($linije)): ?>
    <div class="pl-empty">Još uvek nema poslatih push poruka.</div>
  <?php else: ?>
    <div class="pl-list">
      <?php foreach ($linije as $l): ?>
        <div class="pl-row"><?= h($l) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
