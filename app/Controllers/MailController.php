<?php
namespace Controllers;

use Core\Auth;

class MailController extends \Core\Controller
{
    public function send(): void
    {
        $to_email     = trim($_POST['to_email']   ?? '');
        $subject      = trim($_POST['subject']    ?? '');
        $msg_body     = trim($_POST['msg_body']   ?? '');
        $from_context = trim($_POST['from_context'] ?? 'forme');

        $user_ime     = Auth::ime();
        $user_telefon = Auth::telefon();
        $user_email   = Auth::email();
        $user_mpass   = Auth::mailPass();

        // Određi SMTP kredencijale po kontekstu
        if ($from_context === 'imenik' && $user_email && $user_mpass) {
            $smtp_user = $user_email;
            $smtp_pass = $user_mpass;
            $from_name = $user_ime;
        } else {
            // Kontakt forme — uvek info@ekosarna.com
            $smtp_user    = SMTP_USER;
            $smtp_pass    = SMTP_PASS;
            $from_name    = SMTP_FROM_NAME;
            $user_ime     = SMTP_FROM_NAME;
            $user_telefon = '';
        }

        if (!$to_email || !$subject || !$msg_body) {
            $this->json(['ok' => false, 'err' => 'Sva polja su obavezna.']);
        }
        if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['ok' => false, 'err' => 'Email adresa nije ispravna.']);
        }

        require_once PHPMAILER_DIR . 'Exception.php';
        require_once PHPMAILER_DIR . 'PHPMailer.php';
        require_once PHPMAILER_DIR . 'SMTP.php';

        $logo_cid = 'logo_ekosarna';
        $tel_html = $user_telefon
            ? '<p style="margin:0 0 16px;font-size:13px;color:#5d7a96;">' . htmlspecialchars($user_telefon, ENT_QUOTES, 'UTF-8') . '</p>'
            : '';

        $html_body = '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;font-size:14px;color:#1a2d42;max-width:600px;margin:0 auto;">'
            . '<div style="padding:24px;">'
            . nl2br(htmlspecialchars($msg_body, ENT_QUOTES, 'UTF-8'))
            . '<br><br><hr style="border:none;border-top:1px solid #dce8f6;margin:24px 0;">'
            . '<p style="margin:0 0 6px;font-size:13px;color:#5d7a96;">Srdačan pozdrav,</p>'
            . '<p style="margin:0 0 4px;font-weight:700;font-size:14px;color:#1a3a6e;">' . htmlspecialchars($user_ime, ENT_QUOTES, 'UTF-8') . '</p>'
            . $tel_html
            . '<img src="cid:' . $logo_cid . '" alt="Ekošarna" style="height:44px;width:auto;">'
            . '</div></body></html>';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_user;
            $mail->Password   = $smtp_pass;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($smtp_user, $from_name);
            $mail->addAddress($to_email);
            $mail->addReplyTo($smtp_user, $from_name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html_body;
            $mail->AltBody = $msg_body . "\n\n--\nSrdačan pozdrav,\n" . $user_ime
                           . ($user_telefon ? "\n" . $user_telefon : '') . "\nEkošarna D.O.O.";

            if (file_exists(LOGO_PATH)) {
                $mail->addEmbeddedImage(LOGO_PATH, $logo_cid, 'mika_logo_1.png', 'base64', 'image/png');
            }

            // Višestruki prilozi (samo imenik)
            if ($from_context === 'imenik' && !empty($_FILES['mail_fajlovi']['name'])) {
                $files  = $_FILES['mail_fajlovi'];
                $names  = (array)$files['name'];
                $tmps   = (array)$files['tmp_name'];
                $errs   = (array)$files['error'];
                $sizes  = (array)$files['size'];
                foreach ($names as $i => $name) {
                    if ($errs[$i] === UPLOAD_ERR_OK
                        && !empty($tmps[$i])
                        && $sizes[$i] <= 50 * 1024 * 1024
                        && is_uploaded_file($tmps[$i])
                    ) {
                        $mail->addAttachment($tmps[$i], basename($name));
                    }
                }
            }

            $mail->send();

            // Sačuvaj u Sent folder
            if (extension_loaded('imap')) {
                try {
                    $imap_conn = '{' . IMAP_HOST . ':' . IMAP_PORT . '/imap/ssl/novalidate-cert}';
                    $imap = @imap_open($imap_conn . 'INBOX', $smtp_user, $smtp_pass, 0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
                    if ($imap) {
                        @imap_append($imap, $imap_conn . IMAP_SENT, $mail->getSentMIMEMessage(), '\Seen');
                        @imap_close($imap);
                    }
                } catch (\Exception $e) {
                    error_log('[Ekošarna IMAP] ' . $e->getMessage());
                }
            }

            $this->json(['ok' => true]);

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $this->json(['ok' => false, 'err' => 'Mail greška: ' . $mail->ErrorInfo]);
        }
    }
}
