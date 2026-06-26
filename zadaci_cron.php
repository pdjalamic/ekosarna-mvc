<?php
/**
 * Ekošarna — Podsetnici (alarmi) za zadatke (Faza 3)
 * Šalje dospele podsetnike postavljene na zadacima (push/Telegram).
 *
 * cPanel Cron Jobs → Add New Cron Job (npr. svakih 5 minuta):
 *   Minute: *​/5  Hour: *  Day: *  Month: *  Weekday: *
 *   Command: /usr/local/bin/php /home/CPANEL_USER/public_html/mvc/zadaci_cron.php
 *
 * Bez ovog cron-a podsetnici se ne šalju — niko ne povuče okidač u zakazano vreme.
 */

define('ROOT', __DIR__);
define('APP',  ROOT . '/app');

// Autoloader (kao u push_cron.php / raspored_cron.php)
spl_autoload_register(function (string $class): void {
    $file = APP . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once APP . '/Core/Config.php';
require_once APP . '/helpers.php';

use Controllers\ZadaciController;

$log = '[' . date('Y-m-d H:i:s') . '] Zadaci podsetnici cron start' . PHP_EOL;

try {
    $res = (new ZadaciController())->posaljiAlarme();
    $log .= '  Poslato: ' . $res['poslato'] . PHP_EOL;
    foreach ($res['log'] as $l) {
        $log .= '  ' . $l . PHP_EOL;
    }
} catch (\Throwable $e) {
    $log .= '  GREŠKA: ' . $e->getMessage() . PHP_EOL;
}

$log .= '[' . date('Y-m-d H:i:s') . '] Zadaci podsetnici cron end' . PHP_EOL . PHP_EOL;

$logFile = ROOT . '/logs/zadaci_cron.log';
if (!is_dir(dirname($logFile))) mkdir(dirname($logFile), 0755, true);
file_put_contents($logFile, $log, FILE_APPEND);

echo $log;
