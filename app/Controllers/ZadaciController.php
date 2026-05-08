<?php
namespace Controllers;

use Core\Auth;
use Models\InterniZadatak;
use Models\Korisnik;

class ZadaciController extends \Core\Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        InterniZadatak::migrate();

        $filters = [
            'status'    => $_GET['zstatus'] ?? '',
            'q'         => trim($_GET['zq'] ?? ''),
            'kategorija'=> trim($_GET['zkat'] ?? ''),
        ];

        $zadaci     = InterniZadatak::getAll($filters);
        $kategorije = InterniZadatak::getKategorije();
        $korisnici  = Korisnik::getAll();

        // Brojevi po statusu
        $svi        = count(InterniZadatak::getAll());
        $otvoreno   = count(InterniZadatak::getAll(['status' => 'otvoreno']));
        $u_toku     = count(InterniZadatak::getAll(['status' => 'u_toku']));
        $zavrseno   = count(InterniZadatak::getAll(['status' => 'zavrseno']));

        $this->view('zadaci/index', compact(
            'zadaci', 'kategorije', 'korisnici', 'filters',
            'svi', 'otvoreno', 'u_toku', 'zavrseno'
        ));
    }

    public function ajax(string $action, int $id): void
    {
        InterniZadatak::migrate();

        switch ($action) {
            case 'zadatak_add':
                $tekst = trim($_POST['tekst'] ?? '');
                if (!$tekst) $this->json(['ok' => false, 'err' => 'Tekst je obavezan.']);
                $newId = InterniZadatak::create([
                    'tekst'        => mb_substr($tekst, 0, 2000),
                    'kategorija'   => mb_substr(trim($_POST['kategorija'] ?? ''), 0, 100),
                    'status'       => 'otvoreno',
                    'rok'          => $_POST['rok'] ?? '',
                    'kreirao_id'   => Auth::id(),
                    'dodeljeno_id' => (int)($_POST['dodeljeno_id'] ?? 0) ?: null,
                ]);
                $this->json(['ok' => true, 'id' => $newId]);
                break;

            case 'zadatak_status':
                $status = $_POST['status'] ?? '';
                if (!in_array($status, ['otvoreno', 'u_toku', 'zavrseno'])) {
                    $this->json(['ok' => false, 'err' => 'Nevalidan status.']);
                }
                InterniZadatak::updateStatus($id, $status);
                $this->json(['ok' => true]);
                break;

            case 'zadatak_edit':
                $tekst = trim($_POST['tekst'] ?? '');
                if (!$tekst) $this->json(['ok' => false, 'err' => 'Tekst je obavezan.']);
                InterniZadatak::update($id, [
                    'tekst'        => mb_substr($tekst, 0, 2000),
                    'kategorija'   => mb_substr(trim($_POST['kategorija'] ?? ''), 0, 100),
                    'status'       => $_POST['status'] ?? 'otvoreno',
                    'rok'          => $_POST['rok'] ?? '',
                    'dodeljeno_id' => (int)($_POST['dodeljeno_id'] ?? 0) ?: null,
                ]);
                $this->json(['ok' => true]);
                break;

            case 'zadatak_delete':
                InterniZadatak::delete($id);
                $this->json(['ok' => true]);
                break;

            default:
                $this->json(['ok' => false, 'err' => 'Nepoznata akcija.']);
        }
    }
}
