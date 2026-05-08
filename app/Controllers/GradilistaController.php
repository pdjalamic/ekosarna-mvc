<?php
namespace Controllers;

use Core\Auth;

class GradilistaController extends \Core\Controller
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::get();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $status = $_GET['status'] ?? 'sve';
        $search = trim($_GET['q'] ?? '');

        $where  = [];
        $params = [];

        if ($status !== 'sve') {
            $where[]  = 'g.status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $where[]  = '(g.naziv LIKE ? OR g.adresa LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql = "SELECT g.*, 
                (SELECT putanja FROM gradilista_slike WHERE gradiliste_id = g.id ORDER BY redosled ASC, id ASC LIMIT 1) AS prva_slika
                FROM gradilista g";
        if ($where) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY FIELD(g.status,'aktivno','pauza','zavrseno'), g.naziv";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $gradilista = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $brojevi = $this->db->query("
            SELECT status, COUNT(*) as broj FROM gradilista GROUP BY status
        ")->fetchAll(\PDO::FETCH_KEY_PAIR);

        $this->view('gradilista/index', compact('gradilista', 'brojevi', 'status', 'search'));
    }

    public function ajax(string $action, int $id): void
    {
        Auth::requireLogin();

        switch ($action) {
            case 'gradiliste_add':
                if (!Auth::isAdmin()) $this->json(['ok' => false, 'err' => 'Nemate pristup.']);
                $naziv    = mb_substr(trim($_POST['naziv']    ?? ''), 0, 200);
                $adresa   = mb_substr(trim($_POST['adresa']   ?? ''), 0, 300);
                $status   = $_POST['status']   ?? 'aktivno';
                $pocetak  = $_POST['pocetak']  ?? null;
                $kraj     = $_POST['kraj']     ?? null;
                $napomena = mb_substr(trim($_POST['napomena'] ?? ''), 0, 2000);

                if (!$naziv) $this->json(['ok' => false, 'err' => 'Naziv je obavezan.']);

                $stmt = $this->db->prepare("
                    INSERT INTO gradilista (naziv, adresa, status, pocetak, kraj, napomena)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $naziv, $adresa, $status,
                    $pocetak ?: null, $kraj ?: null, $napomena
                ]);
                $newId = $this->db->lastInsertId();
                $this->json(['ok' => true, 'id' => $newId]);
                break;

            case 'gradiliste_edit':
                if (!Auth::isAdmin()) $this->json(['ok' => false, 'err' => 'Nemate pristup.']);
                $naziv    = mb_substr(trim($_POST['naziv']    ?? ''), 0, 200);
                $adresa   = mb_substr(trim($_POST['adresa']   ?? ''), 0, 300);
                $status   = $_POST['status']   ?? 'aktivno';
                $pocetak  = $_POST['pocetak']  ?? null;
                $kraj     = $_POST['kraj']     ?? null;
                $napomena = mb_substr(trim($_POST['napomena'] ?? ''), 0, 2000);

                $stmt = $this->db->prepare("
                    UPDATE gradilista SET naziv=?, adresa=?, status=?, pocetak=?, kraj=?, napomena=?
                    WHERE id=?
                ");
                $stmt->execute([
                    $naziv, $adresa, $status,
                    $pocetak ?: null, $kraj ?: null, $napomena, $id
                ]);
                $this->json(['ok' => true]);
                break;

            case 'gradiliste_del':
                if (!Auth::isAdmin()) $this->json(['ok' => false, 'err' => 'Nemate pristup.']);
                // Obriši slike sa diska
                $slike = $this->db->prepare("SELECT putanja FROM gradilista_slike WHERE gradiliste_id=?");
                $slike->execute([$id]);
                foreach ($slike->fetchAll(\PDO::FETCH_COLUMN) as $putanja) {
                    $full = $_SERVER['DOCUMENT_ROOT'] . $putanja;
                    if (file_exists($full)) unlink($full);
                }
                $stmt = $this->db->prepare("DELETE FROM gradilista WHERE id=?");
                $stmt->execute([$id]);
                $this->json(['ok' => true]);
                break;

            case 'gradiliste_get':
                $stmt = $this->db->prepare("SELECT * FROM gradilista WHERE id=?");
                $stmt->execute([$id]);
                $g = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$g) $this->json(['ok' => false]);
                // Dodaj slike
                $stmt2 = $this->db->prepare("SELECT id, putanja FROM gradilista_slike WHERE gradiliste_id=? ORDER BY redosled ASC, id ASC");
                $stmt2->execute([$id]);
                $g['slike'] = $stmt2->fetchAll(\PDO::FETCH_ASSOC);
                $this->json($g);
                break;

            case 'gradiliste_upload_sliku':
                if (!Auth::isAdmin()) $this->json(['ok' => false, 'err' => 'Nemate pristup.']);
                if (!$id) $this->json(['ok' => false, 'err' => 'Nema ID gradilišta.']);

                // Provjeri broj postojećih slika
                $brSlika = $this->db->prepare("SELECT COUNT(*) FROM gradilista_slike WHERE gradiliste_id=?");
                $brSlika->execute([$id]);
                if ((int)$brSlika->fetchColumn() >= 4) {
                    $this->json(['ok' => false, 'err' => 'Maksimalno 4 slike po gradilištu.']);
                }

                if (empty($_FILES['slika']) || $_FILES['slika']['error'] !== UPLOAD_ERR_OK) {
                    $this->json(['ok' => false, 'err' => 'Greška pri uploadu.']);
                }

                $file    = $_FILES['slika'];
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                $mime    = mime_content_type($file['tmp_name']);

                if (!in_array($mime, $allowed)) {
                    $this->json(['ok' => false, 'err' => 'Dozvoljeni formati: JPG, PNG, WEBP.']);
                }

                $ext     = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
                $folder  = $_SERVER['DOCUMENT_ROOT'] . '/mvc/public/uploads/gradilista/' . $id . '/';
                if (!is_dir($folder)) mkdir($folder, 0755, true);

                $filename = uniqid('g', true) . '.' . $ext;
                $dest     = $folder . $filename;

                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    $this->json(['ok' => false, 'err' => 'Nije moguće sačuvati sliku.']);
                }

                $putanja = '/mvc/public/uploads/gradilista/' . $id . '/' . $filename;
                $stmt = $this->db->prepare("INSERT INTO gradilista_slike (gradiliste_id, putanja, redosled) VALUES (?, ?, 0)");
                $stmt->execute([$id, $putanja]);

                $this->json(['ok' => true, 'putanja' => $putanja, 'slika_id' => $this->db->lastInsertId()]);
                break;

            case 'gradiliste_del_sliku':
                if (!Auth::isAdmin()) $this->json(['ok' => false, 'err' => 'Nemate pristup.']);
                $stmt = $this->db->prepare("SELECT putanja FROM gradilista_slike WHERE id=?");
                $stmt->execute([$id]);
                $putanja = $stmt->fetchColumn();
                if ($putanja) {
                    $full = $_SERVER['DOCUMENT_ROOT'] . $putanja;
                    if (file_exists($full)) unlink($full);
                    $this->db->prepare("DELETE FROM gradilista_slike WHERE id=?")->execute([$id]);
                }
                $this->json(['ok' => true]);
                break;

            default:
                $this->json(['ok' => false, 'err' => 'Nepoznata akcija.']);
        }
    }

    public static function getAll(): array
    {
        $db = \Core\Database::get();
        return $db->query("SELECT id, naziv, status FROM gradilista ORDER BY naziv")
                  ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getAktivna(): array
    {
        $db = \Core\Database::get();
        return $db->query("SELECT id, naziv FROM gradilista WHERE status='aktivno' ORDER BY naziv")
                  ->fetchAll(\PDO::FETCH_ASSOC);
    }
}
