<?php
/**
 * Ekošarna — Zakazane notifikacije rasporeda (#4)
 * Šalje poruke koje su u rasporedu zakazane preko "Zakaži obaveštenje".
 *
 * cPanel Cron Jobs → Add New Cron Job (npr. svakih 5 minuta):
 *   Minute: *​/5  Hour: *  Day: *  Month: *  Weekday: *
 *   Command: /usr/local/bin/php /home/CPANEL_USER/public_html/mvc/raspored_cron.php
 *
 * Bez ovog cron-a "Zakaži obaveštenje" ne radi — niko ne povuče okidač u zakazano vreme.
 */

define('ROOT', __DIR__);
define('APP',  ROOT . '/app');

// Autoloader (kao u push_cron.php)
spl_autoload_register(function (string $class): void {
    $file = APP . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once APP . '/Core/Config.php';
require_once APP . '/helpers.php';

use Controllers\RasporedController;

$log = '[' . date('Y-m-d H:i:s') . '] Raspored zakazano cron start' . PHP_EOL;

try {
    $res = (new RasporedController())->posaljiZakazane();
    $log .= '  Poslato: ' . $res['poslato'] . PHP_EOL;
    foreach ($res['log'] as $l) {
        $log .= '  ' . $l . PHP_EOL;
    }
} catch (\Throwable $e) {
    $log .= '  GREŠKA: ' . $e->getMessage() . PHP_EOL;
}

$log .= '[' . date('Y-m-d H:i:s') . '] Raspored zakazano cron end' . PHP_EOL . PHP_EOL;

$logFile = ROOT . '/logs/raspored_cron.log';
if (!is_dir(dirname($logFile))) mkdir(dirname($logFile), 0755, true);
file_put_contents($logFile, $log, FILE_APPEND);

echo $log;
