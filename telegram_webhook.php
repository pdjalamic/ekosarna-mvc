<?php
/**
 * Ekošarna — Telegram Bot Webhook
 * Postavi na: ekosarna.com/mvc/telegram_webhook.php
 * 
 * Registruj webhook jednom:
 * https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://ekosarna.com/mvc/telegram_webhook.php
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

// Čitaj Telegram update
$input  = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) exit;

$message = $update['message'] ?? null;
if (!$message) exit;

$chat_id  = $message['chat']['id']         ?? null;
$username = $message['from']['username']   ?? '';
$ime      = ($message['from']['first_name'] ?? '') . ' ' . ($message['from']['last_name'] ?? '');
$ime      = trim($ime);
$text     = trim($message['text']          ?? '');

if (!$chat_id) exit;

$token = TELEGRAM_BOT_TOKEN;

// Funkcija za slanje poruke
function tgSend(string $token, int $chatId, string $text): void {
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
    curl_exec($ch);
    curl_close($ch);
}

// Kreiraj tabelu ako ne postoji
try {
    DB::exec("CREATE TABLE IF NOT EXISTS telegram_subscriptions (
        id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        korisnik_id  INT UNSIGNED NULL,
        chat_id      BIGINT NOT NULL UNIQUE,
        ime          VARCHAR(200) NOT NULL DEFAULT '',
        username     VARCHAR(100) NOT NULL DEFAULT '',
        aktivan      TINYINT(1) NOT NULL DEFAULT 1,
        datum        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (\Exception $e) {
    error_log('[Telegram] DB greška: ' . $e->getMessage());
    exit;
}

// Komande
if ($text === '/start') {
    // Pokušaj da nađemo korisnika po telegram_username koloni
    $korisnik_id = null;
    if ($username) {
        try {
            // Dodaj kolonu ako ne postoji
            try { DB::exec("ALTER TABLE admin_korisnici ADD COLUMN telegram_username VARCHAR(100) NOT NULL DEFAULT ''"); } catch (\Exception $e) {}

            $stmt = DB::prepare("SELECT id FROM admin_korisnici WHERE telegram_username = ? AND aktivan = 1");
            $stmt->execute([$username]);
            $row = $stmt->fetch();
            if ($row) $korisnik_id = $row['id'];
        } catch (\Exception $e) {}
    }

    // Sačuvaj subscription
    try {
        DB::prepare(
            "INSERT INTO telegram_subscriptions (chat_id, ime, username, korisnik_id)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE ime=VALUES(ime), username=VALUES(username), aktivan=1"
        )->execute([$chat_id, $ime, $username, $korisnik_id]);
    } catch (\Exception $e) {
        error_log('[Telegram] Save greška: ' . $e->getMessage());
    }

    $poruka = "👋 Zdravo <b>{$ime}</b>!\n\n"
            . "✅ Uspešno si registrovan za podsetnik zadataka iz Ekošarna admin panela.\n\n"
            . "Dan pre isteka roka zadatka dobijaš poruku ovde.\n\n"
            . "📋 Komande:\n"
            . "/start — registracija\n"
            . "/status — tvoji aktivni zadaci\n"
            . "/stop — odjava";

    tgSend($token, $chat_id, $poruka);

} elseif ($text === '/stop') {
    try {
        DB::prepare("UPDATE telegram_subscriptions SET aktivan=0 WHERE chat_id=?")
          ->execute([$chat_id]);
    } catch (\Exception $e) {}
    tgSend($token, $chat_id, '❌ Odjavljen si. Više nećeš dobijati podsetnik. Pošalji /start za ponovnu aktivaciju.');

} elseif ($text === '/status') {
    // Pokušaj da nađeš korisnika
    try {
        $stmt = DB::prepare("SELECT korisnik_id FROM telegram_subscriptions WHERE chat_id=? AND aktivan=1");
        $stmt->execute([$chat_id]);
        $sub = $stmt->fetch();

        if (!$sub || !$sub['korisnik_id']) {
            tgSend($token, $chat_id, '⚠️ Tvoj nalog nije povezan sa admin panelom. Kontaktiraj administratora.');
        } else {
            $kid = $sub['korisnik_id'];
            $stmt2 = DB::prepare(
                "SELECT tekst, rok, status FROM interni_zadaci
                 WHERE (kreirao_id=? OR dodeljeno_id=?) AND status != 'zavrseno'
                 ORDER BY rok ASC LIMIT 10"
            );
            $stmt2->execute([$kid, $kid]);
            $zadaci = $stmt2->fetchAll();

            if (empty($zadaci)) {
                tgSend($token, $chat_id, '✅ Nemaš aktivnih zadataka.');
            } else {
                $poruka = "📋 <b>Tvoji aktivni zadaci:</b>\n\n";
                foreach ($zadaci as $z) {
                    $rok = $z['rok'] ? '📅 ' . date('d.m.Y.', strtotime($z['rok'])) : '(bez roka)';
                    $status = $z['status'] === 'u_toku' ? '🔵' : '⚪';
                    $poruka .= "{$status} " . mb_substr($z['tekst'], 0, 80) . "\n{$rok}\n\n";
                }
                tgSend($token, $chat_id, $poruka);
            }
        }
    } catch (\Exception $e) {
        tgSend($token, $chat_id, '⚠️ Greška pri čitanju zadataka.');
    }

} else {
    tgSend($token, $chat_id, "Nisam razumeo. Dostupne komande:\n/start — registracija\n/status — moji zadaci\n/stop — odjava");
}
