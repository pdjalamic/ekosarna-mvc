<?php
/**
 * Ekošarna — Push Podsetnici
 * Cron Job: svaki dan u 08:00
 * 
 * cPanel Cron Jobs → Add New Cron Job:
 * Minute: 0  Hour: 8  Day: *  Month: *  Weekday: *
 * Command: /usr/local/bin/php /home/CPANEL_USER/public_html/mvc/push_cron.php
 */

define('ROOT', __DIR__);
define('APP',  ROOT . '/app');

// Autoloader
spl_autoload_register(function (string $class): void {
    $file = APP . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once APP . '/Core/Config.php';
require_once APP . '/helpers.php';

use Models\PushSubscription;
use Controllers\PushController;

$log = '[' . date('Y-m-d H:i:s') . '] Push cron start' . PHP_EOL;

try {
    // ── 1. Push notifikacije za Android ──────────────────────────────────
    $pushRows = PushSubscription::getForTomorrow();
    $pushed   = 0;

    foreach ($pushRows as $row) {
        $payload = [
            'title' => '⏰ Zadatak ističe sutra',
            'body'  => mb_substr($row['zadatak_tekst'], 0, 100) . (mb_strlen($row['zadatak_tekst']) > 100 ? '...' : ''),
            'url'   => BASE_URL . '/?page=zadaci',
            'tag'   => 'zadatak-' . $row['zadatak_id'],
            'icon'  => BASE_URL . '/public/icon-192.png',
        ];

        // Preskoči iOS — šalji email
        if ($row['uredjaj'] === 'ios') continue;

        $ok = PushController::sendToSubscription([
            'endpoint' => $row['endpoint'],
            'p256dh'   => $row['p256dh'],
            'auth_key' => $row['auth_key'],
        ], $payload);

        if ($ok) {
            $pushed++;
            $log .= "  ✓ Push → {$row['ime']} — {$row['zadatak_tekst']}" . PHP_EOL;
        } else {
            $log .= "  ✗ Push FAIL → {$row['ime']}" . PHP_EOL;
            // Ako endpoint ne važi više — pošalji email kao backup
            PushController::sendEmailReminder($row, $row);
        }
    }

    // ── 2. Email podsetnici za iOS i korisnike bez push ───────────────────
    $emailRows = PushSubscription::getEmailForTomorrow();

    // Dodaj i iOS korisnike iz push tabele
    foreach ($pushRows as $row) {
        if ($row['uredjaj'] === 'ios') {
            $emailRows[] = $row;
        }
    }

    $emailed = 0;
    foreach ($emailRows as $row) {
        PushController::sendEmailReminder($row, $row);
        $emailed++;
        $log .= "  ✉ Email → {$row['ime']} ({$row['email']})" . PHP_EOL;
    }

    $log .= "Push: {$pushed}, Email: {$emailed}" . PHP_EOL;

} catch (\Exception $e) {
    $log .= '  GREŠKA: ' . $e->getMessage() . PHP_EOL;
}

$log .= '[' . date('Y-m-d H:i:s') . '] Push cron end' . PHP_EOL . PHP_EOL;

// Čuvaj log
$logFile = ROOT . '/logs/push_cron.log';
if (!is_dir(dirname($logFile))) mkdir(dirname($logFile), 0755, true);
file_put_contents($logFile, $log, FILE_APPEND);

echo $log;
