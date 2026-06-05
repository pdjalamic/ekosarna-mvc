<?php
namespace Core;

/**
 * Učitava .env i definiše konstante
 */
class Config
{
    public static function load(): void
    {
        // .env je izmešten van web root-a (iznad public_html) radi bezbednosti.
        // Tražimo ga na više lokacija — koristi se PRVA koja postoji.
        $kandidati = [
            dirname(ROOT, 2) . '/.env',  // npr. /home/ekosarna/.env  (van public_html)
            dirname(ROOT) . '/.env',     // npr. /home/ekosarna/public_html/.env
            ROOT . '/.env',              // stara lokacija (fallback)
        ];
        $env_file = '';
        foreach ($kandidati as $putanja) {
            if (is_file($putanja)) { $env_file = $putanja; break; }
        }

        if ($env_file !== '') {
            foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (!str_contains($line, '=')) continue;
                [$key, $val] = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($val);
            }
        }

        defined('DB_HOST')       || define('DB_HOST',       $_ENV['DB_HOST']       ?? 'localhost');
        defined('DB_NAME')       || define('DB_NAME',       $_ENV['DB_NAME']       ?? '');
        defined('DB_USER')       || define('DB_USER',       $_ENV['DB_USER']       ?? '');
        defined('DB_PASS')       || define('DB_PASS',       $_ENV['DB_PASS']       ?? '');
        defined('DB_PORT')       || define('DB_PORT',       $_ENV['DB_PORT']       ?? '3306');
        defined('SMTP_HOST')     || define('SMTP_HOST',     $_ENV['SMTP_HOST']     ?? 'mail.ekosarna.com');
        defined('SMTP_PORT')     || define('SMTP_PORT',     (int)($_ENV['SMTP_PORT'] ?? 465));
        defined('SMTP_USER')     || define('SMTP_USER',     $_ENV['SMTP_USER']     ?? '');
        defined('SMTP_PASS')     || define('SMTP_PASS',     $_ENV['SMTP_PASS']     ?? '');
        defined('SMTP_FROM_NAME')|| define('SMTP_FROM_NAME','Ekošarna D.O.O.');
        defined('IMAP_HOST')     || define('IMAP_HOST',     $_ENV['IMAP_HOST']     ?? 'mail.ekosarna.com');
        defined('IMAP_PORT')     || define('IMAP_PORT',     (int)($_ENV['IMAP_PORT'] ?? 993));
        defined('IMAP_SENT')     || define('IMAP_SENT',     $_ENV['IMAP_SENT']     ?? 'INBOX.Sent');
        defined('UPLOAD_DIR')    || define('UPLOAD_DIR',    ROOT . '/uploads/');
        defined('PHPMAILER_DIR') || define('PHPMAILER_DIR', ROOT . '/phpmailer/');
        defined('LOGO_PATH')     || define('LOGO_PATH',     ROOT . '/mika_logo_1.png');
        defined('VAPID_PUBLIC_KEY')   || define('VAPID_PUBLIC_KEY',   $_ENV['VAPID_PUBLIC_KEY']   ?? '');
        defined('VAPID_PRIVATE_KEY')  || define('VAPID_PRIVATE_KEY',  $_ENV['VAPID_PRIVATE_KEY']  ?? '');
        defined('VAPID_SUBJECT')      || define('VAPID_SUBJECT',      $_ENV['VAPID_SUBJECT']      ?? 'mailto:info@ekosarna.com');
        defined('TELEGRAM_BOT_TOKEN') || define('TELEGRAM_BOT_TOKEN', $_ENV['TELEGRAM_BOT_TOKEN'] ?? '');

        // Base URL — auto-detect
        if (!defined('BASE_URL')) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
            $base   = rtrim(dirname($script), '/');
            define('BASE_URL', $scheme . '://' . $host . $base);
        }
    }
}

// Učitaj odmah
\Core\Config::load();
