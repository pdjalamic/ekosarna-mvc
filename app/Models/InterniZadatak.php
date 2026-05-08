<?php
namespace Models;

use Core\Database as DB;

class InterniZadatak
{
    public static function migrate(): void
    {
        DB::exec("CREATE TABLE IF NOT EXISTS interni_zadaci (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tekst         TEXT NOT NULL,
            kategorija    VARCHAR(100) NOT NULL DEFAULT '',
            status        ENUM('otvoreno','u_toku','zavrseno') NOT NULL DEFAULT 'otvoreno',
            rok           DATE NULL,
            kreirao_id    INT UNSIGNED NOT NULL,
            dodeljeno_id  INT UNSIGNED NULL,
            datum_kreiranja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            datum_izmene    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_kreirao (kreirao_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function getAll(array $filters = []): array
    {
        $where = []; $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'z.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(z.tekst LIKE ? OR z.kategorija LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like; $params[] = $like;
        }
        if (!empty($filters['kategorija'])) {
            $where[] = 'z.kategorija = ?';
            $params[] = $filters['kategorija'];
        }

        $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT z.*, 
                       k.ime AS kreirao_ime,
                       d.ime AS dodeljeno_ime
                FROM interni_zadaci z
                LEFT JOIN admin_korisnici k ON k.id = z.kreirao_id
                LEFT JOIN admin_korisnici d ON d.id = z.dodeljeno_id
                $w
                ORDER BY 
                  CASE z.status WHEN 'zavrseno' THEN 1 ELSE 0 END ASC,
                  CASE WHEN z.rok IS NULL THEN 1 ELSE 0 END ASC,
                  z.rok ASC,
                  z.datum_kreiranja DESC";
        $stmt = DB::prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getKategorije(): array
    {
        $stmt = DB::prepare(
            "SELECT DISTINCT kategorija FROM interni_zadaci 
             WHERE kategorija != '' ORDER BY kategorija ASC"
        );
        $stmt->execute();
        return array_column($stmt->fetchAll(), 'kategorija');
    }

    public static function create(array $data): int
    {
        DB::prepare(
            "INSERT INTO interni_zadaci (tekst, kategorija, status, rok, kreirao_id, dodeljeno_id)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([
            $data['tekst'],
            $data['kategorija'] ?? '',
            $data['status'] ?? 'otvoreno',
            $data['rok'] ?: null,
            $data['kreirao_id'],
            $data['dodeljeno_id'] ?: null,
        ]);
        return (int)DB::lastInsertId();
    }

    public static function updateStatus(int $id, string $status): void
    {
        DB::prepare("UPDATE interni_zadaci SET status=? WHERE id=?")
          ->execute([$status, $id]);
    }

    public static function update(int $id, array $data): void
    {
        DB::prepare(
            "UPDATE interni_zadaci SET tekst=?, kategorija=?, status=?, rok=?, dodeljeno_id=? WHERE id=?"
        )->execute([
            $data['tekst'],
            $data['kategorija'] ?? '',
            $data['status'] ?? 'otvoreno',
            $data['rok'] ?: null,
            $data['dodeljeno_id'] ?: null,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        DB::prepare("DELETE FROM interni_zadaci WHERE id=?")->execute([$id]);
    }
}
