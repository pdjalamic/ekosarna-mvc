<?php
namespace Controllers;

use Core\Auth;

class ObavestenjaController extends \Core\Controller
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::get();
    }

    public function index(): void
    {
        Auth::requireKancelarija();

        $view = $_GET['view'] ?? 'email-imenik';

        if ($view === 'izvestaji') {
            $this->izvestaji();
            return;
        }

        $stmt = $this->db->query("
            SELECT f.id AS firma_id, f.naziv AS firma_naziv,
                   k.id AS kontakt_id, k.ime AS kontakt_ime, k.email
            FROM imenik_firme f
            JOIN imenik_kontakti k ON k.firma_id = f.id
            WHERE k.email != '' AND f.aktivan = 1
            ORDER BY f.naziv ASC, k.ime ASC
        ");

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $firme = [];
        foreach ($rows as $r) {
            $fid = $r['firma_id'];
            if (!isset($firme[$fid])) {
                $firme[$fid] = [
                    'id'       => $fid,
                    'naziv'    => $r['firma_naziv'],
                    'kontakti' => [],
                ];
            }
            $firme[$fid]['kontakti'][] = [
                'id'    => $r['kontakt_id'],
                'ime'   => $r['kontakt_ime'],
                'email' => $r['email'],
            ];
        }

        $this->view('obavestenja/email-imenik', compact('firme'));
    }

    private function izvestaji(): void
    {
        $stmt = $this->db->query("
            SELECT l.*, k.ime AS posiljac_ime
            FROM obavestenja_log l
            JOIN admin_korisnici k ON l.korisnik_id = k.id
            ORDER BY l.created_at DESC
        ");
        $logovi = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($logovi as &$l) {
            $stmt2 = $this->db->prepare("
                SELECT ime, email, firma FROM obavestenja_primaoci WHERE log_id = ?
            ");
            $stmt2->execute([$l['id']]);
            $l['primaoci'] = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

            $stmt3 = $this->db->prepare("
                SELECT id, naziv, putanja, velicina FROM obavestenja_attachmenti WHERE log_id = ?
            ");
            $stmt3->execute([$l['id']]);
            $l['attachmenti'] = $stmt3->fetchAll(\PDO::FETCH_ASSOC);
        }

        $this->view('obavestenja/izvestaji', compact('logovi'));
    }

    public function ajax(string $action, int $id): void
    {
        Auth::requireKancelarija();

        if ($action === 'obavestenja_posalji_email') {
            $naslov   = trim($_POST['naslov']   ?? '');
            $tekst    = trim($_POST['tekst']    ?? '');
            $primaoci = json_decode($_POST['primaoci'] ?? '[]', true);

            if (!$naslov || !$tekst) {
                $this->json(['ok' => false, 'err' => 'Naslov i tekst su obavezni.']);
            }
            if (empty($primaoci)) {
                $this->json(['ok' => false, 'err' => 'Izaberite bar jednog primaoca.']);
            }

            $placeholders = implode(',', array_fill(0, count($primaoci), '?'));
            $stmt = $this->db->prepare("
                SELECT k.ime, k.email, f.naziv AS firma
                FROM imenik_kontakti k
                JOIN imenik_firme f ON k.firma_id = f.id
                WHERE k.id IN ($placeholders) AND k.email != ''
            ");
            $stmt->execute($primaoci);
            $kontakti = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($kontakti)) {
                $this->json(['ok' => false, 'err' => 'Nema validnih email adresa.']);
            }

            require_once PHPMAILER_DIR . 'Exception.php';
            require_once PHPMAILER_DIR . 'PHPMailer.php';
            require_once PHPMAILER_DIR . 'SMTP.php';

            // Sačuvaj attachmente na server i pripremi za slanje
            $upload_dir = UPLOAD_DIR . 'obavestenja/';
            $attachmenti = [];

            if (!empty($_FILES['attachmenti'])) {
                $files = $_FILES['attachmenti'];
                $count = is_array($files['name']) ? count($files['name']) : 1;
                for ($i = 0; $i < $count; $i++) {
                    $tmp      = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                    $name     = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
                    $err      = is_array($files['error'])    ? $files['error'][$i]    : $files['error'];
                    $size     = is_array($files['size'])     ? $files['size'][$i]     : $files['size'];

                    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) continue;

                    // Jedinstveno ime fajla
                    $ext      = pathinfo($name, PATHINFO_EXTENSION);
                    $safename = date('Ymd_His') . '_' . uniqid() . ($ext ? '.' . $ext : '');
                    $putanja  = $upload_dir . $safename;

                    if (move_uploaded_file($tmp, $putanja)) {
                        $attachmenti[] = [
                            'putanja'  => $putanja,
                            'naziv'    => $name,
                            'safename' => $safename,
                            'velicina' => $size,
                        ];
                    }
                }
            }

            $logo_cid   = 'logo_ekosarna_ob';
            $tekst_html = nl2br(htmlspecialchars($tekst, ENT_QUOTES, 'UTF-8'));

            $html_body = '<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;font-size:14px;color:#1a2d42;max-width:600px;margin:0 auto;">
<div style="padding:24px;">
' . $tekst_html . '
<br><br>
<hr style="border:none;border-top:1px solid #dce8f6;margin:24px 0;">
<p style="margin:0 0 6px;font-size:13px;color:#5d7a96;">Srdačan pozdrav,</p>
<img src="cid:' . $logo_cid . '" alt="Ekošarna" style="height:44px;width:auto;margin-top:8px;display:block;">
<a href="https://www.ekosarna.com" style="font-size:13px;color:#2563eb;text-decoration:none;display:block;margin-top:6px;">www.ekosarna.com</a>
</div>
</body>
</html>';

            $alt_body = $tekst . "\n\n--\nSrdačan pozdrav,\nEkošarna D.O.O.\nwww.ekosarna.com";

            $poslato = 0;
            $greske  = [];

            foreach ($kontakti as $k) {
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
                    $mail->addAddress($k['email'], $k['ime'] ?: $k['firma']);
                    $mail->Subject = $naslov;
                    $mail->isHTML(true);
                    $mail->Body    = $html_body;
                    $mail->AltBody = $alt_body;

                    $logo_transparent = ROOT . '/EkosarnaMailTransparentNOVI.png';
                    if (file_exists($logo_transparent)) {
                        $mail->addEmbeddedImage($logo_transparent, $logo_cid, 'EkosarnaMailTransparentNOVI.png', 'base64', 'image/png');
                    } elseif (file_exists(LOGO_PATH)) {
                        $mail->addEmbeddedImage(LOGO_PATH, $logo_cid, 'mika_logo_1.png', 'base64', 'image/png');
                    }

                    // Priloži fajlove sa servera
                    foreach ($attachmenti as $a) {
                        $mail->addAttachment($a['putanja'], $a['naziv']);
                    }

                    $mail->send();
                    $poslato++;

                    // Sačuvaj u Sent folder
                    if (extension_loaded('imap')) {
                        try {
                            $imap_conn = '{' . IMAP_HOST . ':' . IMAP_PORT . '/imap/ssl/novalidate-cert}';
                            $imap = @imap_open($imap_conn . 'INBOX', SMTP_USER, SMTP_PASS, 0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
                            if ($imap) {
                                @imap_append($imap, $imap_conn . IMAP_SENT, $mail->getSentMIMEMessage(), '\Seen');
                                @imap_close($imap);
                            }
                        } catch (\Exception $e) {
                            error_log('[Ekošarna IMAP Obavestenja] ' . $e->getMessage());
                        }
                    }

                } catch (\PHPMailer\PHPMailer\Exception $e) {
                    $greske[] = $k['email'] . ': ' . $mail->ErrorInfo;
                }
            }

            // Logovaj slanje
            if ($poslato > 0) {
                $logStmt = $this->db->prepare("
                    INSERT INTO obavestenja_log (korisnik_id, naslov, tekst, poslato, ukupno)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $logStmt->execute([Auth::id(), $naslov, $tekst, $poslato, count($kontakti)]);
                $log_id = $this->db->lastInsertId();

                $primStmt = $this->db->prepare("
                    INSERT INTO obavestenja_primaoci (log_id, ime, email, firma) VALUES (?, ?, ?, ?)
                ");
                foreach ($kontakti as $k) {
                    $primStmt->execute([$log_id, $k['ime'], $k['email'], $k['firma']]);
                }

                // Logovaj attachmente
                if (!empty($attachmenti)) {
                    $attStmt = $this->db->prepare("
                        INSERT INTO obavestenja_attachmenti (log_id, naziv, putanja, velicina)
                        VALUES (?, ?, ?, ?)
                    ");
                    foreach ($attachmenti as $a) {
                        $attStmt->execute([$log_id, $a['naziv'], $a['safename'], $a['velicina']]);
                    }
                }
            }

            $this->json([
                'ok'      => $poslato > 0,
                'poslato' => $poslato,
                'greske'  => $greske,
                'ukupno'  => count($kontakti),
            ]);
        }
    }
}
