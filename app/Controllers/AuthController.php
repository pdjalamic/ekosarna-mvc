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
                    $redirect = \Core\Auth::isElektricar() ? 'danas' : 'raspored';
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
}
