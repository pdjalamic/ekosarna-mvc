<?php
namespace Models;

use Core\Database as DB;

class Firma
{
    // ── Firme ──────────────────────────────────────────

    public static function count(string $search = ''): int
    {
        if ($search) {
            $like = '%' . $search . '%';
            $stmt = DB::prepare(
                "SELECT COUNT(DISTINCT f.id) FROM imenik_firme f
                 LEFT JOIN imenik_kontakti k ON k.firma_id=f.id
                 WHERE f.naziv LIKE ? OR f.adresa LIKE ? OR k.ime LIKE ? OR k.email LIKE ? OR k.telefon LIKE ?"
            );
            $stmt->execute([$like, $like, $like, $like, $like]);
        } else {
            $stmt = DB::get()->query("SELECT COUNT(*) FROM imenik_firme");
        }
        return (int)$stmt->fetchColumn();
    }

    public static function getPage(int $page, int $perPage, string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;
        if ($search) {
            $like = '%' . $search . '%';
            $stmt = DB::prepare(
                "SELECT DISTINCT f.id,f.naziv,f.adresa,f.drzava,f.komentar
                 FROM imenik_firme f
                 LEFT JOIN imenik_kontakti k ON k.firma_id=f.id
                 WHERE f.naziv LIKE ? OR f.adresa LIKE ? OR k.ime LIKE ? OR k.email LIKE ? OR k.telefon LIKE ?
                 ORDER BY f.naziv ASC LIMIT $perPage OFFSET $offset"
            );
            $stmt->execute([$like,$like,$like,$like,$like]);
        } else {
            $stmt = DB::get()->query(
                "SELECT id,naziv,adresa,drzava,komentar FROM imenik_firme
                 ORDER BY naziv ASC LIMIT $perPage OFFSET $offset"
            );
        }
        $firme = $stmt->fetchAll();

        // Učitaj kontakte za svaku firmu
        foreach ($firme as &$firma) {
            $firma['kontakti'] = self::getKontakti((int)$firma['id']);
        }
        return $firme;
    }

    public static function create(array $data): int
    {
        DB::prepare("INSERT INTO imenik_firme (naziv,adresa,drzava,komentar) VALUES (?,?,?,?)")
          ->execute([$data['naziv'], $data['adresa'], $data['drzava'] ?? 'Srbija', $data['komentar'] ?? '']);
        return (int)DB::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        DB::prepare("UPDATE imenik_firme SET naziv=?,adresa=?,drzava=?,komentar=? WHERE id=?")
          ->execute([$data['naziv'], $data['adresa'], $data['drzava'] ?? 'Srbija', $data['komentar'] ?? '', $id]);
    }

    public static function delete(int $id): void
    {
        DB::prepare("DELETE FROM imenik_firme WHERE id=?")->execute([$id]);
    }

    // ── Kontakti ───────────────────────────────────────

    public static function getKontakti(int $firmaId): array
    {
        $stmt = DB::prepare("SELECT * FROM imenik_kontakti WHERE firma_id=? ORDER BY id ASC");
        $stmt->execute([$firmaId]);
        return $stmt->fetchAll();
    }

    public static function addKontakt(array $data): int
    {
        DB::prepare("INSERT INTO imenik_kontakti (firma_id,ime,email,telefon,komentar) VALUES (?,?,?,?,?)")
          ->execute([$data['firma_id'], $data['ime'], $data['email'], $data['telefon'], $data['komentar']]);
        return (int)DB::lastInsertId();
    }

    public static function updateKontakt(int $id, array $data): void
    {
        DB::prepare("UPDATE imenik_kontakti SET ime=?,email=?,telefon=?,komentar=? WHERE id=?")
          ->execute([$data['ime'], $data['email'], $data['telefon'], $data['komentar'], $id]);
    }

    public static function deleteKontakt(int $id): void
    {
        DB::prepare("DELETE FROM imenik_kontakti WHERE id=?")->execute([$id]);
    }
}
