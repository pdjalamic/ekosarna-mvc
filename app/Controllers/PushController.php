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
     * Šalje push notifikaciju jednom subscriberu
     * Koristi web-push-php biblioteku
     */
    public static function sendToSubscription(array $sub, array $payload): bool
    {
        $webpushDir = ROOT . '/webpush';
        if (!file_exists($webpushDir . '/WebPush.php')) {
            error_log('[Push] webpush biblioteka nije instalirana');
            return false;
        }

        require_once $webpushDir . '/Utils.php';
        require_once $webpushDir . '/Encryption.php';
        require_once $webpushDir . '/VAPID.php';
        require_once $webpushDir . '/Subscription.php';
        require_once $webpushDir . '/WebPush.php';

        try {
            $auth = [
                'VAPID' => [
                    'subject'    => VAPID_SUBJECT,
                    'publicKey'  => VAPID_PUBLIC_KEY,
                    'privateKey' => VAPID_PRIVATE_KEY,
                ],
            ];

            $webPush = new \Minishlink\WebPush\WebPush($auth);
            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $sub['endpoint'],
                'keys'     => [
                    'p256dh' => $sub['p256dh'],
                    'auth'   => $sub['auth_key'],
                ],
            ]);

            $report = $webPush->sendOneNotification(
                $subscription,
                json_encode($payload)
            );

            return $report->isSuccess();
        } catch (\Exception $e) {
            error_log('[Push] Greška: ' . $e->getMessage());
            return false;
        }
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
