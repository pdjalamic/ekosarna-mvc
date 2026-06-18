<?php
/**
 * Ekošarna — Ručni test push notifikacija
 *
 * Otvori u browseru (mora biti ulogovan admin):
 *   https://.../mvc/push_test.php          → šalje push tebi (ulogovanom)
 *   https://.../mvc/push_test.php?uid=5     → šalje push korisniku 5
 *
 * Koristi postojeću logiku (PushController::notifyUsers), pa upisuje i u
 * logs/push_send.log. Namenjen testiranju na produkciji (HTTPS), jer push
 * ne radi sa lokalnog http://localhost.
 */

define('ROOT', __DIR__);
define('APP',  ROOT . '/app');

// Autoloader (isti kao index.php)
spl_autoload_register(function (string $class): void {
    $file = APP . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once APP . '/Core/Config.php';
require_once APP . '/helpers.php';

use Core\Auth;
use Models\PushSubscription;
use Controllers\PushController;

// Samo admin (requireAdmin sam pokreće sesiju i preusmerava ako nema pristupa)
Auth::requireAdmin();

// Cilj: podrazumevano ja (ulogovani), opciono ?uid=N
$uid = (int)($_GET['uid'] ?? 0) ?: Auth::id();

PushSubscription::migrate();
$subs = PushSubscription::getByKorisnik($uid);

$payload = [
    'title' => '🔔 Test notifikacija',
    'body'  => 'Ako vidiš ovo, push radi! ' . date('H:i:s'),
    'url'   => BASE_URL . '/?page=home',
    'tag'   => 'push-test-' . time(),
    'icon'  => BASE_URL . '/public/icon-192.png',
];

$sent = PushController::notifyUsers([$uid], $payload);

// ── Dijagnostika okruženja ────────────────────────────────────────────
$env = [
    'PHP verzija'              => PHP_VERSION,
    'openssl ekstenzija'      => extension_loaded('openssl'),
    'curl ekstenzija'         => extension_loaded('curl'),
    'gmp ekstenzija'          => extension_loaded('gmp'),
    'mbstring ekstenzija'     => extension_loaded('mbstring'),
    'openssl_pkey_derive()'   => function_exists('openssl_pkey_derive'),
    'hash_hkdf()'             => function_exists('hash_hkdf'),
];

// ── Tačan rezultat slanja (Core\PushSender) ───────────────────────────
// Direktno pozovemo samostalni sender i prikažemo HTTP status + razlog.
$libError = '';
if ($subs) {
    $s = $subs[0];
    $r = \Core\PushSender::send($s['endpoint'], $s['p256dh'], $s['auth_key'], $payload);
    $libError = 'Uspeh: ' . ($r['success'] ? 'DA' : 'NE')
              . ' | HTTP status: ' . $r['status']
              . ' | razlog: ' . $r['reason'];
} else {
    $libError = '(nema subscription-a za test)';
}

// ── Kratak HTML izveštaj ──────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Push test</title>
<body style="font-family:system-ui,Arial,sans-serif;max-width:520px;margin:40px auto;padding:0 16px;color:#1a2d42;">
  <h2 style="margin:0 0 4px;">🔔 Push test</h2>
  <p style="color:#64748b;margin:0 0 20px;">Cilj: korisnik #<?= (int)$uid ?><?= $uid === Auth::id() ? ' (ti)' : '' ?></p>

  <div style="background:#f1f5f9;border-radius:10px;padding:16px;line-height:1.7;">
    <div>Pronađeno subscription-a: <strong><?= count($subs) ?></strong></div>
    <div>Uspešno poslato: <strong style="color:<?= $sent > 0 ? '#16a34a' : '#dc2626' ?>;"><?= (int)$sent ?></strong></div>
  </div>

  <?php if (!$subs): ?>
    <p style="margin-top:16px;color:#dc2626;">
      Nema sačuvanih uređaja za ovog korisnika. Otvori aplikaciju na telefonu,
      instaliraj PWA i prihvati „Aktiviraj" baner, pa probaj ponovo.
    </p>
  <?php elseif ($sent === 0): ?>
    <p style="margin-top:16px;color:#dc2626;">
      Subscription postoji ali slanje nije uspelo — proveri VAPID ključeve u
      <code>.env</code> i <code>logs/push_send.log</code>.
    </p>
  <?php else: ?>
    <p style="margin-top:16px;color:#16a34a;">
      Poslato. Notifikacija bi trebalo da stigne na uređaj za nekoliko sekundi.
    </p>
  <?php endif; ?>

  <h3 style="margin:28px 0 8px;">🔧 Okruženje (PHP)</h3>
  <div style="background:#f1f5f9;border-radius:10px;padding:16px;line-height:1.7;font-size:14px;">
    <?php foreach ($env as $naziv => $vr): ?>
      <div><?= h($naziv) ?>:
        <strong style="color:<?= is_bool($vr) ? ($vr ? '#16a34a' : '#dc2626') : '#1a2d42' ?>;">
          <?= is_bool($vr) ? ($vr ? 'DA ✓' : 'NE ✗') : h((string)$vr) ?>
        </strong>
      </div>
    <?php endforeach; ?>
  </div>

  <h3 style="margin:28px 0 8px;">📚 Prava greška biblioteke</h3>
  <pre style="background:#1e293b;color:#e2e8f0;border-radius:10px;padding:16px;
       white-space:pre-wrap;word-break:break-word;font-size:13px;line-height:1.5;"><?= h($libError) ?></pre>

  <p style="margin-top:24px;font-size:13px;color:#94a3b8;">
    Detalji su upisani u <code>logs/push_send.log</code>.
  </p>
</body>
