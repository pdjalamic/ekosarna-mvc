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

    // Uloge koje su UVEK ponuđene u padajućem meniju „odgovoran za unos materijala"
    // na rasporedu (vođe/rukovodioci), nezavisno od toga ko je dodat na zadatak.
    public const ULOGE_ODGOVORAN_MAT = ['Inženjer na gradilištu', 'Rukovodilac operative', 'Monter poslovođa', 'Zamenik montera poslovođe'];

    // "Zapamti me" — trajni token (selector:validator) u kolačiću; u bazi se čuva
    // samo hash validatora. Omogućava tihu ponovnu prijavu na telefonu/PWA.
    public const REMEMBER_COOKIE = 'ek_remember';
    public const REMEMBER_DAYS   = 90;

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

        // Sesija neaktivna >2h — očisti je, ali zadrži "remember" token kako bi se
        // korisnik tiho ponovo prijavio (bez ručnog logovanja na telefonu).
        if (isset($_SESSION['ek_admin']) && (time() - ($_SESSION['ek_time'] ?? 0)) > 7200) {
            $_SESSION = [];
        }

        // Nema aktivne sesije? Pokušaj tihu prijavu preko "zapamti me" tokena.
        if (empty($_SESSION['ek_admin'])) {
            self::attemptRememberLogin();
        }

        if (!empty($_SESSION['ek_admin'])) {
            $_SESSION['ek_time'] = time();
            return true;
        }

        return false;
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

    public static function requireMagacin(): void
    {
        self::requireLogin();
        if (!self::canMagacin()) {
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
        self::start();
        // Obriši "remember" token samo ovog uređaja (po selektoru iz kolačića)
        $raw = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if ($raw !== '' && strpos($raw, ':') !== false) {
            [$selector] = explode(':', $raw, 2);
            try {
                Database::prepare("DELETE FROM remember_tokens WHERE selektor=?")->execute([$selector]);
            } catch (\PDOException $e) { /* tabela možda još ne postoji */ }
        }
        self::clearRememberCookie();
        session_destroy();
    }

    /** Odjava sa svih uređaja — poništava sve "remember" tokene korisnika. */
    public static function logoutAllDevices(): void
    {
        self::start();
        $uid = self::id();
        if ($uid > 0) {
            try {
                Database::prepare("DELETE FROM remember_tokens WHERE korisnik_id=?")->execute([$uid]);
            } catch (\PDOException $e) { /* tabela možda još ne postoji */ }
        }
        self::clearRememberCookie();
        session_destroy();
    }

    // ── "Zapamti me" token ─────────────────────────────────────────────

    /** Kreira nov trajni token za korisnika i postavlja kolačić (poziva se pri prijavi). */
    public static function issueRememberToken(int $userId): void
    {
        try {
            $selector  = bin2hex(random_bytes(8));   // 16 hex znakova
            $validator = bin2hex(random_bytes(32));  // 64 hex znakova (tajna)
            $expires   = time() + self::REMEMBER_DAYS * 86400;
            Database::prepare(
                "INSERT INTO remember_tokens (korisnik_id, selektor, validator_hash, expires_at)
                 VALUES (?, ?, ?, ?)"
            )->execute([$userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $expires)]);
            self::setRememberCookie($selector . ':' . $validator, $expires);
        } catch (\PDOException $e) { /* tabela možda još ne postoji */ }
    }

    /** Pokušava prijavu preko kolačića; rotira validator pri svakoj upotrebi. */
    private static function attemptRememberLogin(): bool
    {
        $raw = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if ($raw === '' || strpos($raw, ':') === false) return false;
        [$selector, $validator] = explode(':', $raw, 2);
        if ($selector === '' || $validator === '') return false;

        try {
            $stmt = Database::prepare("SELECT * FROM remember_tokens WHERE selektor=? LIMIT 1");
            $stmt->execute([$selector]);
            $row = $stmt->fetch();

            if (!$row) { self::clearRememberCookie(); return false; }

            // Istekao token
            if (strtotime($row['expires_at']) < time()) {
                Database::prepare("DELETE FROM remember_tokens WHERE id=?")->execute([$row['id']]);
                self::clearRememberCookie();
                return false;
            }

            // Pogrešan validator — moguća krađa/falsifikat; poništi token
            if (!hash_equals($row['validator_hash'], hash('sha256', $validator))) {
                Database::prepare("DELETE FROM remember_tokens WHERE id=?")->execute([$row['id']]);
                self::clearRememberCookie();
                return false;
            }

            $us = Database::prepare("SELECT * FROM admin_korisnici WHERE id=? AND aktivan=1");
            $us->execute([(int)$row['korisnik_id']]);
            $user = $us->fetch();
            if (!$user) {
                Database::prepare("DELETE FROM remember_tokens WHERE id=?")->execute([$row['id']]);
                self::clearRememberCookie();
                return false;
            }

            self::login($user);                                 // regeneriše sesiju
            self::rotateRememberToken((int)$row['id'], $selector); // nov validator
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /** Zameni validator postojećeg tokena (rotacija) i osveži kolačić. */
    private static function rotateRememberToken(int $rowId, string $selector): void
    {
        try {
            $validator = bin2hex(random_bytes(32));
            $expires   = time() + self::REMEMBER_DAYS * 86400;
            Database::prepare(
                "UPDATE remember_tokens SET validator_hash=?, expires_at=? WHERE id=?"
            )->execute([hash('sha256', $validator), date('Y-m-d H:i:s', $expires), $rowId]);
            self::setRememberCookie($selector . ':' . $validator, $expires);
        } catch (\PDOException $e) { /* ignoriši — sesija je već postavljena */ }
    }

    private static function cookiePath(): string
    {
        $p = parse_url(defined('BASE_URL') ? BASE_URL : '', PHP_URL_PATH);
        return (is_string($p) && $p !== '') ? $p : '/';
    }

    private static function cookieSecure(): bool
    {
        return defined('BASE_URL') && str_starts_with(BASE_URL, 'https');
    }

    private static function setRememberCookie(string $value, int $expires): void
    {
        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires'  => $expires,
            'path'     => self::cookiePath(),
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => self::cookieSecure(),
        ]);
        $_COOKIE[self::REMEMBER_COOKIE] = $value;
    }

    private static function clearRememberCookie(): void
    {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => self::cookiePath(),
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => self::cookieSecure(),
        ]);
        unset($_COOKIE[self::REMEMBER_COOKIE]);
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

    // Magacin: admini uvek; ostali po per-korisnik flag-u (vidi_magacin) — čita se uživo iz baze
    public static function canMagacin(): bool
    {
        if (self::isAdmin()) return true;
        try {
            $r = Database::prepare("SELECT vidi_magacin FROM admin_korisnici WHERE id=?");
            $r->execute([self::id()]);
            return !empty($r->fetch()['vidi_magacin']);
        } catch (\PDOException $e) { return false; }
    }

    public static function imaHrProfil(): bool
    {
        try {
            $r = Database::prepare("SELECT 1 FROM hr_zaposleni WHERE korisnik_id=? LIMIT 1");
            $r->execute([self::id()]);
            return (bool)$r->fetchColumn();
        } catch (\PDOException $e) { return false; }
    }

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
