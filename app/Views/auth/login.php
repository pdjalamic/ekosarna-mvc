<!DOCTYPE html>
<html lang="sr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ekošarna — Prijava</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div style="margin-bottom:18px;text-align:center;">
      <img src="<?= BASE_URL ?>/mika_logo_1.png" alt="Ekošarna" style="height:52px;width:auto;display:inline-block;">
    </div>
    <div class="login-logo">Ekošarna<span>.</span></div>
    <div class="login-sub">Admin panel</div>
    <?php if (!empty($error)): ?>
      <div class="error-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="_login" value="1">
      <div class="form-group">
        <label>Korisničko ime</label>
        <input type="text" name="username" autocomplete="username" autofocus>
      </div>
      <div class="form-group">
        <label>Lozinka</label>
        <input type="password" name="password" autocomplete="current-password">
      </div>
      <button type="submit" class="btn">Prijavite se</button>
    </form>
  </div>
</div>
</body>
</html>
