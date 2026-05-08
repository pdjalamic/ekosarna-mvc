<?php
namespace Controllers;

use Core\Auth;
use Models\Firma;

class ImenikController extends \Core\Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        if (!Auth::canImenik()) {
            header('Location: /?page=kontakt');
            exit;
        }

        $valid_per_page  = [5, 10, 50, 1000];
        $per_page        = in_array((int)($_GET['ipp'] ?? 10), $valid_per_page) ? (int)($_GET['ipp'] ?? 10) : 10;
        $page            = max(1, (int)($_GET['ipage'] ?? 1));
        $search          = trim($_GET['iq'] ?? '');

        $total       = Firma::count($search);
        $firme       = Firma::getPage($page, $per_page, $search);
        $total_pages = max(1, (int)ceil($total / $per_page));

        $this->view('imenik/index', compact('firme','total','total_pages','page','per_page','search'));
    }

    public function ajax(string $action, int $id): void
    {
        switch ($action) {
            case 'imenik_add_firma':
                $naziv   = mb_substr(trim($_POST['naziv']    ?? ''), 0, 200);
                $adresa  = mb_substr(trim($_POST['adresa']   ?? ''), 0, 300);
                $drzava  = mb_substr(trim($_POST['drzava']   ?? 'Srbija'), 0, 100);
                $komentar= mb_substr(trim($_POST['komentar'] ?? ''), 0, 2000);
                if (!$naziv) $this->json(['ok' => false, 'err' => 'Naziv je obavezan.']);
                $newId = Firma::create(compact('naziv','adresa','drzava','komentar'));
                $this->json(['ok' => true, 'id' => $newId]);
                break;

            case 'imenik_edit_firma':
                $naziv   = mb_substr(trim($_POST['naziv']    ?? ''), 0, 200);
                $adresa  = mb_substr(trim($_POST['adresa']   ?? ''), 0, 300);
                $drzava  = mb_substr(trim($_POST['drzava']   ?? 'Srbija'), 0, 100);
                $komentar= mb_substr(trim($_POST['komentar'] ?? ''), 0, 2000);
                Firma::update($id, compact('naziv','adresa','drzava','komentar'));
                $this->json(['ok' => true]);
                break;

            case 'imenik_del_firma':
                Firma::delete($id);
                $this->json(['ok' => true]);
                break;

            case 'imenik_add_kontakt':
                $firma_id= (int)($_POST['firma_id']  ?? 0);
                $ime     = mb_substr(trim($_POST['ime']       ?? ''), 0, 100);
                $email   = mb_substr(trim($_POST['email_k']   ?? ''), 0, 200);
                $telefon = mb_substr(trim($_POST['telefon_k'] ?? ''), 0, 100);
                $komentar= mb_substr(trim($_POST['komentar']  ?? ''), 0, 500);
                $newId = Firma::addKontakt(compact('firma_id','ime','email','telefon','komentar'));
                $this->json(['ok' => true, 'id' => $newId]);
                break;

            case 'imenik_edit_kontakt':
                $ime     = mb_substr(trim($_POST['ime']       ?? ''), 0, 100);
                $email   = mb_substr(trim($_POST['email_k']   ?? ''), 0, 200);
                $telefon = mb_substr(trim($_POST['telefon_k'] ?? ''), 0, 100);
                $komentar= mb_substr(trim($_POST['komentar']  ?? ''), 0, 500);
                Firma::updateKontakt($id, compact('ime','email','telefon','komentar'));
                $this->json(['ok' => true]);
                break;

            case 'imenik_del_kontakt':
                Firma::deleteKontakt($id);
                $this->json(['ok' => true]);
                break;

            default:
                $this->json(['ok' => false, 'err' => 'Nepoznata akcija.']);
        }
    }
}
