<?php
namespace Controllers;

use Core\Auth;

class NabavkaController extends \Core\Controller
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::get();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $uid            = Auth::id();
        $je_kancelarija = !Auth::isElektricar();
        $is_admin       = Auth::isAdmin();

        $filter_status   = $_GET['status']     ?? '';
        $filter_grad     = (int)($_GET['gradiliste'] ?? 0);
        $filter_radnik   = (int)($_GET['radnik']     ?? 0);
        $filter_od       = $_GET['od']          ?? date('Y-m-01');
        $filter_do       = $_GET['do']          ?? date('Y-m-d');
        $filter_obradjuje = $_GET['obradjuje']  ?? '0';

        $radnici    = [];
        $gradilista = $this->db->query("SELECT id, naziv FROM gradilista ORDER BY naziv ASC")->fetchAll(\PDO::FETCH_ASSOC);

        if ($je_kancelarija) {
            $radnici = $this->db->query("
                SELECT id, ime FROM admin_korisnici
                WHERE aktivan=1 AND uloga IN ('administrator','operater')
                ORDER BY ime ASC
            ")->fetchAll(\PDO::FETCH_ASSOC);
        }

        $where  = "nz.created_at BETWEEN ? AND ?";
        $params = [$filter_od . ' 00:00:00', $filter_do . ' 23:59:59'];

        if (!$je_kancelarija) {
            $where .= " AND nz.radnik_id = ?";
            $params[] = $uid;
        } elseif ($filter_radnik) {
            $where .= " AND nz.radnik_id = ?";
            $params[] = $filter_radnik;
        }

        if ($filter_status) {
            $where .= " AND nz.status = ?";
            $params[] = $filter_status;
        }

        if ($filter_grad) {
            $where .= " AND nz.gradiliste_id = ?";
            $params[] = $filter_grad;
        }

        if ($filter_obradjuje === 'moje') {
            $where .= " AND nz.obradjuje_id = ?";
            $params[] = $uid;
        } elseif ($filter_obradjuje === 'niko') {
            $where .= " AND nz.obradjuje_id IS NULL";
        } elseif ((int)$filter_obradjuje > 0) {
            $where .= " AND nz.obradjuje_id = ?";
            $params[] = (int)$filter_obradjuje;
        }

        $stmt = $this->db->prepare("
            SELECT nz.*,
                   k.ime  AS radnik_ime,
                   ob.ime AS obradjuje_ime
            FROM nabavka_zahtevi nz
            JOIN admin_korisnici k  ON nz.radnik_id = k.id
            LEFT JOIN admin_korisnici ob ON nz.obradjuje_id = ob.id
            WHERE $where
            ORDER BY
                FIELD(nz.status,'novo','u_obradi','poruceno','isporuceno'),
                nz.prioritet DESC,
                nz.created_at DESC
        ");
        $stmt->execute($params);
        $zahtevi = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($zahtevi as &$z) {
            $decoded = !empty($z['stavke']) ? json_decode($z['stavke'], true) : [];
            $z['stavke_parsed'] = isset($decoded['stavke']) ? $decoded['stavke'] : ($decoded ?: []);

            // Komentari
            $kStmt = $this->db->prepare("
                SELECT nk.*, k2.ime AS autor_ime
                FROM nabavka_komentari nk
                JOIN admin_korisnici k2 ON nk.autor_id = k2.id
                WHERE nk.zahtev_id = ?
                ORDER BY nk.created_at ASC
            ");
            $kStmt->execute([$z['id']]);
            $z['komentari'] = $kStmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $novi_count = (int)$this->db->query("SELECT COUNT(*) FROM nabavka_zahtevi WHERE status='novo'")->fetchColumn();

        $this->view('nabavka/index', compact(
            'zahtevi', 'radnici', 'gradilista',
            'filter_status', 'filter_grad', 'filter_radnik', 'filter_od', 'filter_do', 'filter_obradjuje',
            'je_kancelarija', 'is_admin', 'uid', 'novi_count'
        ));
    }

    public function ajax(string $action, int $id): void
    {
        Auth::requireLogin();
        $uid = Auth::id();

        // Promeni status + obradjuje
        if ($action === 'nabavka_promeni_status') {
            if (Auth::isElektricar()) $this->json(['ok' => false, 'err' => 'Nemate pristup.']);
            $status   = $_POST['status'] ?? '';
            $napomena = trim($_POST['napomena_admin'] ?? '');
            $obradjuje_id = (int)($_POST['obradjuje_id'] ?? 0) ?: null;

            if (!in_array($status, ['novo','u_obradi','poruceno','isporuceno'])) {
                $this->json(['ok' => false, 'err' => 'Neispravan status.']);
            }
            $this->db->prepare("
                UPDATE nabavka_zahtevi SET status=?, napomena_admin=?, obradjuje_id=? WHERE id=?
            ")->execute([$status, $napomena ?: null, $obradjuje_id, $id]);
            $this->json(['ok' => true]);
        }

        // Preuzmi zahtev
        if ($action === 'nabavka_preuzmi') {
            if (Auth::isElektricar()) $this->json(['ok' => false, 'err' => 'Nemate pristup.']);
            $this->db->prepare("
                UPDATE nabavka_zahtevi SET obradjuje_id=?, status=IF(status='novo','u_obradi',status) WHERE id=?
            ")->execute([$uid, $id]);
            $this->json(['ok' => true]);
        }

        // Novi komentari (polling)
        if ($action === 'nabavka_komentari_novi') {
            $after = $_POST['after'] ?? '2000-01-01 00:00:00';
            $stmt  = $this->db->prepare("
                SELECT nk.*, k2.ime AS autor_ime
                FROM nabavka_komentari nk
                JOIN admin_korisnici k2 ON nk.autor_id = k2.id
                WHERE nk.zahtev_id = ? AND nk.created_at > ?
                ORDER BY nk.created_at ASC
            ");
            $stmt->execute([$id, $after]);
            $novi = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->json(['ok' => true, 'komentari' => $novi]);
        }

        // Dodaj komentar
        if ($action === 'nabavka_komentar') {
            $tekst = trim($_POST['tekst'] ?? '');
            if (!$tekst) $this->json(['ok' => false, 'err' => 'Komentar je prazan.']);

            // Provjera pristupa — radnik koji je napravio zahtev ili kancelarija
            $check = $this->db->prepare("SELECT radnik_id FROM nabavka_zahtevi WHERE id=?");
            $check->execute([$id]);
            $row = $check->fetch(\PDO::FETCH_ASSOC);
            if (!$row) $this->json(['ok' => false, 'err' => 'Zahtev nije pronađen.']);
            if (Auth::isElektricar() && (int)$row['radnik_id'] !== $uid) {
                $this->json(['ok' => false, 'err' => 'Nemate pristup.']);
            }

            $this->db->prepare("INSERT INTO nabavka_komentari (zahtev_id, autor_id, tekst) VALUES (?,?,?)")
                ->execute([$id, $uid, $tekst]);
            $this->json(['ok' => true]);
        }

        // Obrisi zahtev
        if ($action === 'nabavka_obrisi') {
            Auth::requireAdmin();
            $stmt = $this->db->prepare("SELECT status, obradjuje_id FROM nabavka_zahtevi WHERE id=?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) $this->json(['ok' => false, 'err' => 'Zahtev nije pronađen.']);
            if ($row['status'] !== 'novo' || $row['obradjuje_id']) {
                $this->json(['ok' => false, 'err' => 'Ne može se obrisati — zahtev je preuzet ili u obradi.']);
            }
            $this->db->prepare("DELETE FROM nabavka_zahtevi WHERE id=?")->execute([$id]);
            $this->json(['ok' => true]);
        }
    }
}
