<?php
namespace Controllers;

use Core\Auth;
use Core\Database;
use Models\Korisnik;

class AuthController extends \Core\Controller
{
    public function login(): void
    {
        $error = '';

        if (isset($_GET['timeout'])) {
            $error = 'Sesija je istekla. Prijavite se ponovo.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_login'])) {
            try {
                Database::migrate();
                $user = Korisnik::findByUsername($_POST['username'] ?? '');
                if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
                    Auth::login($user);
                    // "Zapamti me" je uvek uključeno — ostani prijavljen ~90 dana
                    Auth::issueRememberToken((int)$user['id']);
                    // Telefon: vodi na home screen (mreža ikonica); desktop kao pre
                    $isMobile = (bool) preg_match(
                        '/Mobile|Android|iPhone|iPad|iPod|Opera Mini|IEMobile|BlackBerry/i',
                        $_SERVER['HTTP_USER_AGENT'] ?? ''
                    );
                    $redirect = $isMobile ? 'home' : (\Core\Auth::isElektricar() ? 'danas' : 'raspored');
                    header('Location: ' . BASE_URL . '/?page=' . $redirect);
                    exit;
                }
                $error = 'Pogrešno korisničko ime ili lozinka.';
            } catch (\PDOException $e) {
                $error = 'Greška baze: ' . $e->getMessage();
            }
        }

        require_once APP . '/Views/auth/login.php';
    }

    /** AJAX: odjava sa svih uređaja — poništava sve "remember" tokene. */
    public function logoutAll(): void
    {
        Auth::requireLogin();
        Auth::logoutAllDevices();
        echo json_encode(['ok' => true]);
    }
}
