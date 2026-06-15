<?php
namespace Core;

class Auth
{
    // Grupisanje uloga po nivou pristupa. Više naziva uloga mapira se na isti
    // skup ovlašćenja; stari nazivi (Administrator/Operater/Elektricar) ostaju
    // važeći radi postojećih naloga.
    public const ULOGE_ADMIN      = ['Direktor', 'AT', 'AF', 'Administrator'];
    public const ULOGE_OPERATER   = ['Inženjer na gradilištu', 'Rukovodilac operative', 'Operater'];
    public const ULOGE_ELEKTRICAR = ['Monter poslovođa', 'Zamenik montera poslovođe', 'Monter', 'Pomoćni radnik', 'Elektricar'];

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
            session_start();
        }
    }

    public static function check(): bool
    {
        self::start();

        // Auto-odjava nakon 2h neaktivnosti
        if (isset($_SESSION['ek_admin']) && (time() - ($_SESSION['ek_time'] ?? 0)) > 7200) {
            self::logout();
            header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/?timeout=1');
            exit;
        }
        if (isset($_SESSION['ek_admin'])) {
            $_SESSION['ek_time'] = time();
        }

        return !empty($_SESSION['ek_admin']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/?page=kontakt');
            exit;
        }
    }

    public static function requireKancelarija(): void
    {
        self::requireLogin();
        if (self::isElektricar()) {
            header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/?page=danas');
            exit;
        }
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['ek_admin']         = true;
        $_SESSION['ek_time']          = time();
        $_SESSION['ek_user_id']       = $user['id'];
        $_SESSION['ek_user_ime']      = $user['ime'];
        $_SESSION['ek_user_uloga']    = $user['uloga'];
        $_SESSION['ek_user_email']    = $user['email'];
        $_SESSION['ek_user_telefon']  = $user['telefon'] ?? '';
        $_SESSION['ek_user_mail_pass']= $user['mail_pass'] ?? '';
    }

    public static function logout(): void
    {
        session_destroy();
    }

    public static function refreshSession(array $user): void
    {
        $_SESSION['ek_user_ime']       = $user['ime'];
        $_SESSION['ek_user_email']     = $user['email'];
        $_SESSION['ek_user_telefon']   = $user['telefon'] ?? '';
        $_SESSION['ek_user_mail_pass'] = $user['mail_pass'] ?? '';
    }

    public static function id(): int         { return (int)($_SESSION['ek_user_id']    ?? 0); }
    public static function ime(): string     { return $_SESSION['ek_user_ime']          ?? ''; }
    public static function uloga(): string   { return $_SESSION['ek_user_uloga']        ?? ''; }
    public static function email(): string   { return $_SESSION['ek_user_email']        ?? ''; }
    public static function telefon(): string { return $_SESSION['ek_user_telefon']      ?? ''; }
    public static function mailPass(): string{ return $_SESSION['ek_user_mail_pass']    ?? ''; }

    public static function isAdmin(): bool       { return in_array(self::uloga(), self::ULOGE_ADMIN, true); }
    public static function isOperater(): bool    { return in_array(self::uloga(), self::ULOGE_OPERATER, true); }
    public static function isElektricar(): bool  { return in_array(self::uloga(), self::ULOGE_ELEKTRICAR, true); }
    public static function isKancelarija(): bool { return self::isAdmin() || self::isOperater(); }

    public static function canImenik(): bool
    {
        if (self::isAdmin()) return true;
        if (self::isElektricar()) return false;
        try {
            $r = Database::prepare("SELECT vidi_imenik FROM admin_korisnici WHERE id=?");
            $r->execute([self::id()]);
            return !empty($r->fetch()['vidi_imenik']);
        } catch (\PDOException $e) { return false; }
    }
}
