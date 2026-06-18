<?php
namespace Controllers;

use Core\Auth;
use Core\Config;
use Models\PushSubscription;

class PushController extends \Core\Controller
{
    /** Vraća public VAPID key za JS */
    public static function getPublicKey(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['publicKey' => VAPID_PUBLIC_KEY]);
        exit;
    }

    /** Čuva subscription od browsera */
    public static function subscribe(): void
    {
        Auth::requireLogin();
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (empty($data['endpoint'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'err' => 'Nema endpoint-a.']);
            exit;
        }

        PushSubscription::migrate();

        $ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $uredjaj = (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false)
                   ? 'ios' : 'android';

        PushSubscription::save(Auth::id(), $data, $uredjaj);
        echo json_encode(['ok' => true]);
        exit;
    }

    /** Uklanja subscription */
    public static function unsubscribe(): void
    {
        Auth::requireLogin();
        $data = json_decode(file_get_contents('php://input'), true);
        if (!empty($data['endpoint'])) {
            PushSubscription::delete($data['endpoint']);
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    /**
     * Šalje push notifikaciju jednom subscriberu.
     * Koristi samostalni Core\PushSender (samo openssl + curl, bez Composer-a).
     */
    public static function sendToSubscription(array $sub, array $payload): bool
    {
        $r = \Core\PushSender::send(
            $sub['endpoint'] ?? '',
            $sub['p256dh']   ?? '',
            $sub['auth_key'] ?? '',
            $payload
        );

        if (!$r['success']) {
            error_log('[Push] Slanje neuspešno: ' . ($r['reason'] ?? '?'));
            // Istekla/nevažeća pretplata — obriši je da ubuduće ne smeta
            if (\Core\PushSender::isExpiredStatus($r['status']) && !empty($sub['endpoint'])) {
                PushSubscription::delete($sub['endpoint']);
            }
        }
        return $r['success'];
    }

    /**
     * Šalje push notifikaciju listi korisnika (svim njihovim uređajima).
     * Tiha je — nikad ne baca grešku da ne bi srušila akciju koja je pozvala.
     * Vraća broj uspešno poslatih notifikacija.
     */
    public static function notifyUsers(array $userIds, array $payload): int
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (!$userIds) return 0;

        $sent = 0;
        try {
            foreach ($userIds as $uid) {
                foreach (PushSubscription::getByKorisnik($uid) as $sub) {
                    $ok = self::sendToSubscription([
                        'endpoint' => $sub['endpoint'],
                        'p256dh'   => $sub['p256dh'],
                        'auth_key' => $sub['auth_key'],
                    ], $payload);
                    if ($ok) $sent++;
                }
            }
        } catch (\Throwable $e) {
            error_log('[Push notifyUsers] ' . $e->getMessage());
        }
        self::logSend($payload, count($userIds), $sent);
        return $sent;
    }

    /** Upisuje jednu liniju u logs/push_send.log */
    private static function logSend(array $payload, int $primalaca, int $poslato): void
    {
        $naslov = $payload['title'] ?? 'Push';
        $tekst  = mb_substr($payload['body'] ?? '', 0, 60);
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $naslov
              . ' — primalaca: ' . $primalaca . ', poslato: ' . $poslato
              . ($tekst !== '' ? ' — "' . $tekst . '"' : '')
              . PHP_EOL;
        $logFile = ROOT . '/logs/push_send.log';
        if (!is_dir(dirname($logFile))) @mkdir(dirname($logFile), 0755, true);
        @file_put_contents($logFile, $line, FILE_APPEND);
    }

    /** Admin pregled push log-a (?page=push-log) */
    public function logView(): void
    {
        Auth::requireAdmin();
        $logFile = ROOT . '/logs/push_send.log';
        $linije  = [];
        if (is_file($logFile)) {
            $linije = array_reverse(array_values(array_filter(
                explode("\n", trim((string) file_get_contents($logFile)))
            )));
        }
        $this->view('push/log', ['linije' => $linije]);
    }

    /**
     * Šalje email podsetnik (za korisnike bez push)
     */
    public static function sendEmailReminder(array $korisnik, array $zadatak): void
    {
        $to       = $korisnik['email'] ?? '';
        $ime      = $korisnik['ime']   ?? '';
        $tekst    = $zadatak['zadatak_tekst'] ?? '';
        $zadId    = $zadatak['zadatak_id']    ?? 0;

        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) return;

        $subject = 'Podsetnik: zadatak ističe sutra';
        $url     = BASE_URL . '/?page=zadaci';
        $body    = "Zdravo {$ime},\n\n"
                 . "Podsetnik: sledeći zadatak ističe sutra:\n\n"
                 . "\"{$tekst}\"\n\n"
                 . "Otvori zadatke: {$url}\n\n"
                 . "Ekošarna D.O.O.";

        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_USER . ">\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n";

        // Pokušaj slanje PHPMailerom
        $phpmailerDir = PHPMAILER_DIR;
        if (file_exists($phpmailerDir . 'PHPMailer.php')) {
            require_once $phpmailerDir . 'Exception.php';
            require_once $phpmailerDir . 'PHPMailer.php';
            require_once $phpmailerDir . 'SMTP.php';
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = SMTP_PORT;
                $mail->CharSet    = 'UTF-8';
                $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
                $mail->addAddress($to, $ime);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->send();
            } catch (\Exception $e) {
                error_log('[Push Email] ' . $e->getMessage());
            }
        }
    }
}
