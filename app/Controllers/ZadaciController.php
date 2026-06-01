<?php
namespace Controllers;

use Core\Auth;
use Models\InterniZadatak;
use Models\Korisnik;

class ZadaciController extends \Core\Controller
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::get();
    }

    public function index(): void
    {
        Auth::requireLogin();
        InterniZadatak::migrate();

        $uid = Auth::id();

        $filters = [
            'status'     => $_GET['zstatus']    ?? '',
            'q'          => trim($_GET['zq']    ?? ''),
            'kategorija' => trim($_GET['zkat']  ?? ''),
            'dodeljeno'  => (int)($_GET['zdod'] ?? 0) ?: null,
        ];

        $zsort = in_array($_GET['zsort'] ?? '', ['rok_asc','rok_desc','default'])
                 ? ($_GET['zsort'] ?? 'default')
                 : 'default';

        $svi_zadaci = InterniZadatak::getAll($filters);

        // Dohvati dodatne podatke
        foreach ($svi_zadaci as &$z) {
            if ($z['prihvaceno_id'] ?? null) {
                $s = $this->db->prepare("SELECT ime FROM admin_korisnici WHERE id=?");
                $s->execute([$z['prihvaceno_id']]);
                $z['prihvaceno_ime'] = $s->fetchColumn() ?: null;
            } else {
                $z['prihvaceno_ime'] = null;
            }
            $s = $this->db->prepare("SELECT COUNT(*) FROM zadaci_komentari WHERE zadatak_id=?");
            $s->execute([$z['id']]);
            $z['komentar_count'] = (int)$s->fetchColumn();
            $s = $this->db->prepare("
                SELECT zk.*, k.ime AS autor_ime FROM zadaci_komentari zk
                JOIN admin_korisnici k ON zk.autor_id=k.id
                WHERE zk.zadatak_id=? ORDER BY zk.created_at ASC
            ");
            $s->execute([$z['id']]);
            $z['komentari'] = $s->fetchAll(\PDO::FETCH_ASSOC);
        }
        unset($z);

        // Sakrij završene po defaultu
if ($filters['status'] === '') {
    $svi_zadaci = array_values(array_filter($svi_zadaci, fn($z) => $z['status'] !== 'zavrseno'));
}

// Filter po tome ko je PRIHVATIO (ne ko je dodeljen)
if (!empty($filters['dodeljeno'])) {
    $svi_zadaci = array_values(array_filter($svi_zadaci,
        fn($z) => (int)($z['prihvaceno_id'] ?? 0) === (int)$filters['dodeljeno']
    ));
}

        // Sortiranje
        usort($svi_zadaci, function($a, $b) use ($zsort) {
            if ($zsort === 'rok_asc') {
                if (!$a['rok'] && !$b['rok']) return $b['id'] - $a['id'];
                if (!$a['rok']) return 1;
                if (!$b['rok']) return -1;
                return strcmp($a['rok'], $b['rok']);
            }
            if ($zsort === 'rok_desc') {
                if (!$a['rok'] && !$b['rok']) return $b['id'] - $a['id'];
                if (!$a['rok']) return 1;
                if (!$b['rok']) return -1;
                return strcmp($b['rok'], $a['rok']);
            }
            // Default: neprihvaćeni prvi, pa po id DESC
            $aNep = empty($a['prihvaceno_id']) && $a['status'] !== 'zavrseno';
            $bNep = empty($b['prihvaceno_id']) && $b['status'] !== 'zavrseno';
            if ($aNep && !$bNep) return -1;
            if (!$aNep && $bNep) return 1;
            return $b['id'] - $a['id'];
        });

        $ukupno     = count($svi_zadaci);
        $stranica   = max(1, (int)($_GET['str'] ?? 1));
        $po_stranici = 15;
        $stranica   = min($stranica, max(1, (int)ceil($ukupno / $po_stranici)));
        $zadaci     = array_slice($svi_zadaci, ($stranica-1)*$po_stranici, $po_stranici);

        $kategorije = InterniZadatak::getKategorije();
        $korisnici  = Korisnik::getAll();

        $svi      = count(InterniZadatak::getAll());
        $otvoreno = count(InterniZadatak::getAll(['status' => 'otvoreno']));
        $u_toku   = count(InterniZadatak::getAll(['status' => 'u_toku']));
        $zavrseno = count(InterniZadatak::getAll(['status' => 'zavrseno']));

        $this->view('zadaci/index', compact(
            'zadaci', 'kategorije', 'korisnici', 'filters',
            'svi', 'otvoreno', 'u_toku', 'zavrseno', 'uid',
            'stranica', 'po_stranici', 'ukupno', 'zsort'
        ));
    }

    public function ajax(string $action, int $id): void
    {
        InterniZadatak::migrate();
        $uid = Auth::id();

        switch ($action) {
            case 'zadatak_add':
                $tekst = trim($_POST['tekst'] ?? '');
                if (!$tekst) $this->json(['ok' => false, 'err' => 'Tekst je obavezan.']);
                $newId = InterniZadatak::create([
                    'tekst'        => mb_substr($tekst, 0, 2000),
                    'kategorija'   => mb_substr(trim($_POST['kategorija'] ?? ''), 0, 100),
                    'status'       => 'otvoreno',
                    'rok'          => $_POST['rok'] ?? '',
                    'kreirao_id'   => $uid,
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

            case 'zadatak_prihvati':
                $stmt = $this->db->prepare("SELECT dodeljeno_id, prihvaceno_id FROM interni_zadaci WHERE id=?");
                $stmt->execute([$id]);
                $z = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$z) $this->json(['ok' => false, 'err' => 'Zadatak nije pronađen.']);
                if ($z['prihvaceno_id']) $this->json(['ok' => false, 'err' => 'Zadatak je već prihvaćen.']);

                $this->db->prepare("
                    UPDATE interni_zadaci
                    SET prihvaceno_id=?, prihvaceno_at=NOW(), status='u_toku'
                    WHERE id=?
                ")->execute([$uid, $id]);
                $this->json(['ok' => true]);
                break;

            case 'zadatak_komentar':
                $tekst = trim($_POST['tekst'] ?? '');
                if (!$tekst) $this->json(['ok' => false, 'err' => 'Komentar je prazan.']);

                // Svako može komentarisati zadatke kojima pripada
                $this->db->prepare("
                    INSERT INTO zadaci_komentari (zadatak_id, autor_id, tekst) VALUES (?,?,?)
                ")->execute([$id, $uid, $tekst]);
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

            case 'zadatak_komentari_novi':
                $after = $_POST['after'] ?? '2000-01-01 00:00:00';
                $stmt  = $this->db->prepare("
                    SELECT zk.*, k.ime AS autor_ime
                    FROM zadaci_komentari zk
                    JOIN admin_korisnici k ON zk.autor_id = k.id
                    WHERE zk.zadatak_id = ? AND zk.created_at > ?
                    ORDER BY zk.created_at ASC
                ");
                $stmt->execute([$id, $after]);
                $novi = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $this->json(['ok' => true, 'komentari' => $novi]);
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
