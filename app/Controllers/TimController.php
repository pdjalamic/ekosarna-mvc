<?php
namespace Controllers;

use Core\Auth;
use Models\Korisnik;

class TimController extends \Core\Controller
{
    public function index(): void
    {
        Auth::requireAdmin();
        $korisnici = Korisnik::getAll();
        $this->view('tim/index', compact('korisnici'));
    }

    public function ajax(string $action, int $id): void
    {
        switch ($action) {
            case 'tim_add':
                $ime      = mb_substr(trim(strip_tags($_POST['ime']         ?? '')), 0, 100);
                $email    = mb_substr(trim($_POST['email_k']                ?? ''), 0, 200);
                $user     = mb_substr(trim($_POST['username_k']             ?? ''), 0, 100);
                $pass     = $_POST['password_k']  ?? '';
                $uloge_dozvoljene = array_merge(Auth::ULOGE_ADMIN, Auth::ULOGE_OPERATER, Auth::ULOGE_ELEKTRICAR);
                $uloga    = in_array($_POST['uloga'] ?? '', $uloge_dozvoljene, true) ? $_POST['uloga'] : 'Operater';
                $tel      = mb_substr(trim($_POST['telefon_u']   ?? ''), 0, 50);
                $mpass    = mb_substr(trim($_POST['mail_pass_u'] ?? ''), 0, 255);
                $platforma  = in_array($_POST['platforma']  ?? '', ['android','ios','web']) ? $_POST['platforma']  : 'android';
                $platforma2 = in_array($_POST['platforma2'] ?? '', ['android','ios','web','']) ? ($_POST['platforma2'] ?: null) : null;

                if (!$ime || !$user || strlen($pass) < 6) {
                    $this->json(['ok' => false, 'err' => 'Popunite sva polja. Lozinka min. 6 karaktera.']);
                }
                try {
                    Korisnik::create([
                        'ime' => $ime, 'email' => $email, 'username' => $user,
                        'password' => $pass, 'uloga' => $uloga,
                        'telefon' => $tel, 'mail_pass' => $mpass,
                        'platforma' => $platforma, 'platforma2' => $platforma2,
                    ]);
                    $this->json(['ok' => true]);
                } catch (\PDOException $e) {
                    $this->json(['ok' => false, 'err' => 'Korisničko ime već postoji.']);
                }
                break;

            case 'tim_edit_user':
                if (!Auth::isAdmin() && $id !== Auth::id()) {
                    $this->json(['ok' => false, 'err' => 'Nemate pravo da menjate tuđi profil.']);
                }
                $ime       = mb_substr(trim($_POST['ime']         ?? ''), 0, 100);
                $email     = mb_substr(trim($_POST['email_k']     ?? ''), 0, 200);
                $tel       = mb_substr(trim($_POST['telefon_u']   ?? ''), 0, 50);
                $mpass     = mb_substr(trim($_POST['mail_pass_u'] ?? ''), 0, 255);
                $telegram  = mb_substr(trim($_POST['telegram_username'] ?? ''), 0, 100);
                $platforma  = in_array($_POST['platforma']  ?? '', ['android','ios','web']) ? $_POST['platforma']  : 'android';
                $platforma2 = in_array($_POST['platforma2'] ?? '', ['android','ios','web','']) ? ($_POST['platforma2'] ?: null) : null;

                if (!$ime) $this->json(['ok' => false, 'err' => 'Ime je obavezno.']);
                try {
                    Korisnik::update($id, [
                        'ime' => $ime, 'email' => $email,
                        'telefon' => $tel, 'mail_pass' => $mpass,
                        'telegram_username' => $telegram,
                        'platforma' => $platforma, 'platforma2' => $platforma2,
                    ]);
                    if ($id === Auth::id()) {
                        Auth::refreshSession(['ime' => $ime, 'email' => $email, 'telefon' => $tel, 'mail_pass' => $mpass]);
                    }
                    $this->json(['ok' => true]);
                } catch (\PDOException $e) {
                    $this->json(['ok' => false, 'err' => $e->getMessage()]);
                }
                break;

            case 'tim_toggle_aktivan':
                $row = Korisnik::toggleAktivan($id, Auth::id());
                $this->json(['ok' => true, 'aktivan' => (int)$row['aktivan']]);
                break;

            case 'tim_toggle_imenik':
                $row = Korisnik::toggleImenik($id);
                $this->json(['ok' => true, 'vidi_imenik' => (int)$row['vidi_imenik']]);
                break;

            case 'tim_change_uloga':
                $uloge_dozvoljene = array_merge(Auth::ULOGE_ADMIN, Auth::ULOGE_OPERATER, Auth::ULOGE_ELEKTRICAR);
                $uloga = in_array($_POST['uloga'] ?? '', $uloge_dozvoljene, true) ? $_POST['uloga'] : 'Operater';
                Korisnik::changeUloga($id, $uloga);
                $this->json(['ok' => true]);
                break;

            case 'tim_reset_pass':
                $pass = $_POST['password_k'] ?? '';
                if (strlen($pass) < 6) $this->json(['ok' => false, 'err' => 'Lozinka min. 6 karaktera.']);
                Korisnik::resetPassword($id, $pass);
                $this->json(['ok' => true]);
                break;

            case 'tim_delete':
                if ($id === Auth::id()) $this->json(['ok' => false, 'err' => 'Ne možete obrisati sebe.']);
                Korisnik::delete($id);
                $this->json(['ok' => true]);
                break;

            default:
                $this->json(['ok' => false, 'err' => 'Nepoznata akcija.']);
        }
    }
}
