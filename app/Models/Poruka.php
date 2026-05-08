<?php
namespace App\Models;
use App\Core\Database;

class Poruka {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function posalji(int $posiljalac_id, ?int $primalac_id, string $naslov, string $sadrzaj): bool {
        $stmt = $this->db->prepare("
            INSERT INTO poruke (posiljalac_id, primalac_id, naslov, sadrzaj)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$posiljalac_id, $primalac_id, $naslov, $sadrzaj]);
    }

    public function reply(int $posiljalac_id, int $roditelj_id, string $sadrzaj): bool {
        $root = $this->getById($roditelj_id);
        $primalac_id = ($root['posiljalac_id'] == $posiljalac_id)
            ? $root['primalac_id']
            : $root['posiljalac_id'];
        $naslov = 'Re: ' . $root['naslov'];
        $stmt = $this->db->prepare("
            INSERT INTO poruke (posiljalac_id, primalac_id, roditelj_id, naslov, sadrzaj)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$posiljalac_id, $primalac_id, $roditelj_id, $naslov, $sadrzaj]);
    }

    public function getById(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM poruke WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }

    public function inbox(int $korisnik_id): array {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   pos.ime AS posiljalac_ime,
                   prim.ime AS primalac_ime
            FROM poruke p
            JOIN admin_korisnici pos ON p.posiljalac_id = pos.id
            LEFT JOIN admin_korisnici prim ON p.primalac_id = prim.id
            WHERE (p.primalac_id = ? OR p.primalac_id IS NULL)
              AND p.posiljalac_id != ?
              AND p.roditelj_id IS NULL
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$korisnik_id, $korisnik_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function poslato(int $korisnik_id): array {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   pos.ime AS posiljalac_ime,
                   prim.ime AS primalac_ime
            FROM poruke p
            JOIN admin_korisnici pos ON p.posiljalac_id = pos.id
            LEFT JOIN admin_korisnici prim ON p.primalac_id = prim.id
            WHERE p.posiljalac_id = ?
              AND p.roditelj_id IS NULL
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$korisnik_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getThread(int $roditelj_id): array {
        $stmt = $this->db->prepare("
            SELECT p.*,
                   pos.ime AS posiljalac_ime,
                   prim.ime AS primalac_ime
            FROM poruke p
            JOIN admin_korisnici pos ON p.posiljalac_id = pos.id
            LEFT JOIN admin_korisnici prim ON p.primalac_id = prim.id
            WHERE p.id = ? OR p.roditelj_id = ?
            ORDER BY p.created_at ASC
        ");
        $stmt->execute([$roditelj_id, $roditelj_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function oznaci_procitano(int $poruka_id): void {
        $stmt = $this->db->prepare("UPDATE poruke SET procitano = 1 WHERE id = ? OR roditelj_id = ?");
        $stmt->execute([$poruka_id, $poruka_id]);
    }

    public function neprocitane(int $korisnik_id): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM poruke
            WHERE (primalac_id = ? OR primalac_id IS NULL)
              AND posiljalac_id != ?
              AND procitano = 0
        ");
        $stmt->execute([$korisnik_id, $korisnik_id]);
        return (int)$stmt->fetchColumn();
    }

    public function sviKorisnici(int $iskljuci_id): array {
        $stmt = $this->db->prepare("
            SELECT id, ime, uloga FROM admin_korisnici
            WHERE aktivan = 1 AND id != ?
            ORDER BY ime
        ");
        $stmt->execute([$iskljuci_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
