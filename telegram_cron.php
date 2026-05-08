<?php
/**
 * Ekošarna — Telegram Podsetnici
 * Cron Job: svaki dan u 08:00
 *
 * cPanel → Cron Jobs → Add New:
 * 0 8 * * * /usr/local/bin/php /home/ekosarna/public_html/mvc/telegram_cron.php
 */

define('ROOT', __DIR__);
define('APP',  ROOT . '/app');

spl_autoload_register(function (string $class): void {
    $file = APP . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once APP . '/Core/Config.php';
require_once APP . '/helpers.php';

use Core\Database as DB;

$log = '[' . date('Y-m-d H:i:s') . '] Telegram cron start' . PHP_EOL;

function tgSend(string $token, int $chatId, string $text): bool {
    $url  = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML'];
    $ch   = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $body = json_decode($res, true);
    return $code === 200 && ($body['ok'] ?? false);
}

try {
    // Nađi sve zadatke koji ističu sutra
    $stmt = DB::prepare(
        "SELECT z.id, z.tekst, z.rok,
                ts.chat_id, ts.ime,
                k.ime as korisnik_ime
         FROM interni_zadaci z
         JOIN admin_korisnici k ON (k.id = z.kreirao_id OR k.id = z.dodeljeno_id)
         JOIN telegram_subscriptions ts ON ts.korisnik_id = k.id AND ts.aktivan = 1
         WHERE z.rok = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
           AND z.status != 'zavrseno'
         GROUP BY z.id, ts.chat_id"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $sent = 0;
    foreach ($rows as $row) {
        $rok    = date('d.m.Y.', strtotime($row['rok']));
        $tekst  = mb_substr($row['tekst'], 0, 200);
        $poruka = "⏰ <b>Podsetnik — zadatak ističe sutra!</b>\n\n"
                . "📋 {$tekst}\n\n"
                . "📅 Rok: <b>{$rok}</b>\n\n"
                . "Otvori admin panel: https://ekosarna.com/mvc/?page=zadaci";

        $ok = tgSend(TELEGRAM_BOT_TOKEN, (int)$row['chat_id'], $poruka);

        if ($ok) {
            $sent++;
            $log .= "  ✓ Poslato → {$row['ime']} (zadatak: {$tekst})\n";
        } else {
            $log .= "  ✗ Greška → {$row['ime']}\n";
        }
    }

    $log .= "Ukupno poslato: {$sent}" . PHP_EOL;

} catch (\Exception $e) {
    $log .= '  GREŠKA: ' . $e->getMessage() . PHP_EOL;
}

$log .= '[' . date('Y-m-d H:i:s') . '] Telegram cron end' . PHP_EOL . PHP_EOL;

// Čuvaj log
$logFile = ROOT . '/logs/telegram_cron.log';
if (!is_dir(dirname($logFile))) mkdir(dirname($logFile), 0755, true);
file_put_contents($logFile, $log, FILE_APPEND);

echo $log;
