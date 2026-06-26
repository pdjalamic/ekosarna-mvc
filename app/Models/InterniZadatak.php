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

        // Faza 1: više članova po zadatku (svako prihvata zasebno).
        // Seed iz postojećih dodeljeno_id/prihvaceno_id se radi jednom kroz zadaci_clanovi.sql.
        DB::exec("CREATE TABLE IF NOT EXISTS zadaci_clanovi (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            zadatak_id    INT UNSIGNED NOT NULL,
            korisnik_id   INT UNSIGNED NOT NULL,
            prihvatio     TINYINT(1) NOT NULL DEFAULT 0,
            prihvatio_at  DATETIME NULL,
            pozvao_id     INT UNSIGNED NULL,
            dodat_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_zad_kor (zadatak_id, korisnik_id),
            INDEX idx_zadatak (zadatak_id),
            INDEX idx_korisnik (korisnik_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Faza 3: podsetnici (alarmi) za zadatak — lični (korisnik_id) ili ceo tim (NULL).
        DB::exec("CREATE TABLE IF NOT EXISTS zadaci_alarmi (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            zadatak_id   INT UNSIGNED NOT NULL,
            korisnik_id  INT UNSIGNED NULL,
            postavio_id  INT UNSIGNED NOT NULL,
            send_at      DATETIME NOT NULL,
            poruka       VARCHAR(255) NOT NULL DEFAULT '',
            poslato      TINYINT(1) NOT NULL DEFAULT 0,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_due (poslato, send_at),
            INDEX idx_zadatak (zadatak_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Faza 4: fajlovi na zadatku.
        DB::exec("CREATE TABLE IF NOT EXISTS zadaci_fajlovi (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            zadatak_id  INT UNSIGNED NOT NULL,
            naziv       VARCHAR(255) NOT NULL,
            putanja     VARCHAR(255) NOT NULL,
            tip         VARCHAR(100) NOT NULL DEFAULT '',
            velicina    INT UNSIGNED NOT NULL DEFAULT 0,
            dodao_id    INT UNSIGNED NOT NULL,
            dodat_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_zadatak (zadatak_id)
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
        // Obriši fajlove sa diska pre brisanja redova.
        $st = DB::prepare("SELECT putanja FROM zadaci_fajlovi WHERE zadatak_id=?");
        $st->execute([$id]);
        foreach ($st->fetchAll(\PDO::FETCH_COLUMN) as $putanja) {
            $p = UPLOAD_DIR . 'zadaci/' . $putanja;
            if (is_file($p)) @unlink($p);
        }

        DB::prepare("DELETE FROM interni_zadaci WHERE id=?")->execute([$id]);
        DB::prepare("DELETE FROM zadaci_clanovi WHERE zadatak_id=?")->execute([$id]);
        DB::prepare("DELETE FROM zadaci_alarmi WHERE zadatak_id=?")->execute([$id]);
        DB::prepare("DELETE FROM zadaci_fajlovi WHERE zadatak_id=?")->execute([$id]);
    }

    // ----- Faza 3: podsetnici (alarmi) -----

    public static function dodajAlarm(int $zadatakId, ?int $korisnikId, int $postavioId, string $sendAt, string $poruka = ''): int
    {
        DB::prepare(
            "INSERT INTO zadaci_alarmi (zadatak_id, korisnik_id, postavio_id, send_at, poruka) VALUES (?,?,?,?,?)"
        )->execute([$zadatakId, $korisnikId ?: null, $postavioId, $sendAt, $poruka]);
        return (int)DB::lastInsertId();
    }

    public static function obrisiAlarm(int $id): void
    {
        DB::prepare("DELETE FROM zadaci_alarmi WHERE id=?")->execute([$id]);
    }

    // ----- Faza 1: članovi zadatka -----

    /** Dodaje članove (idempotentno). $pozvaoId = ko ih je pozvao (NULL = prvobitna dodela). */
    public static function dodajClanove(int $zadatakId, array $korisnikIds, ?int $pozvaoId = null): void
    {
        $stmt = DB::prepare(
            "INSERT IGNORE INTO zadaci_clanovi (zadatak_id, korisnik_id, pozvao_id) VALUES (?, ?, ?)"
        );
        foreach ($korisnikIds as $kid) {
            $kid = (int)$kid;
            if ($kid > 0) {
                $stmt->execute([$zadatakId, $kid, $pozvaoId ?: null]);
            }
        }
    }

    /** Članovi sa imenom i statusom prihvatanja, redosled: prvo prihvaćeni, pa po imenu. */
    public static function getClanovi(int $zadatakId): array
    {
        $stmt = DB::prepare(
            "SELECT zc.korisnik_id, zc.prihvatio, zc.prihvatio_at, zc.pozvao_id, zc.dodat_at,
                    k.ime AS korisnik_ime
             FROM zadaci_clanovi zc
             JOIN admin_korisnici k ON k.id = zc.korisnik_id
             WHERE zc.zadatak_id = ?
             ORDER BY zc.prihvatio DESC, k.ime ASC"
        );
        $stmt->execute([$zadatakId]);
        return $stmt->fetchAll();
    }

    /** Lista korisnik_id koji su članovi (za vidljivost/notifikacije). */
    public static function clanIds(int $zadatakId): array
    {
        $stmt = DB::prepare("SELECT korisnik_id FROM zadaci_clanovi WHERE zadatak_id=?");
        $stmt->execute([$zadatakId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'korisnik_id'));
    }

    public static function jeClan(int $zadatakId, int $korisnikId): bool
    {
        $stmt = DB::prepare("SELECT 1 FROM zadaci_clanovi WHERE zadatak_id=? AND korisnik_id=?");
        $stmt->execute([$zadatakId, $korisnikId]);
        return (bool)$stmt->fetchColumn();
    }

    /** Označi da je član prihvatio. Vraća false ako osoba nije član zadatka. */
    public static function prihvati(int $zadatakId, int $korisnikId): bool
    {
        $stmt = DB::prepare(
            "UPDATE zadaci_clanovi SET prihvatio=1, prihvatio_at=NOW()
             WHERE zadatak_id=? AND korisnik_id=? AND prihvatio=0"
        );
        $stmt->execute([$zadatakId, $korisnikId]);
        // rowCount 0 može značiti: nije član ILI je već prihvatio — razlikuj preko jeClan()
        if ($stmt->rowCount() > 0) return true;
        return self::jeClan($zadatakId, $korisnikId);
    }

    /** Lista korisnik_id koji su PRIHVATILI zadatak (za notifikacije). */
    public static function prihvatiliIds(int $zadatakId): array
    {
        $stmt = DB::prepare("SELECT korisnik_id FROM zadaci_clanovi WHERE zadatak_id=? AND prihvatio=1");
        $stmt->execute([$zadatakId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'korisnik_id'));
    }

    // ----- Faza 4: fajlovi -----

    public static function dodajFajl(int $zadatakId, string $naziv, string $putanja, string $tip, int $velicina, int $dodaoId): int
    {
        DB::prepare(
            "INSERT INTO zadaci_fajlovi (zadatak_id, naziv, putanja, tip, velicina, dodao_id) VALUES (?,?,?,?,?,?)"
        )->execute([$zadatakId, $naziv, $putanja, $tip, $velicina, $dodaoId]);
        return (int)DB::lastInsertId();
    }

    public static function getFajlovi(int $zadatakId): array
    {
        $stmt = DB::prepare(
            "SELECT zf.*, k.ime AS dodao_ime
             FROM zadaci_fajlovi zf
             LEFT JOIN admin_korisnici k ON k.id = zf.dodao_id
             WHERE zf.zadatak_id = ?
             ORDER BY zf.dodat_at ASC"
        );
        $stmt->execute([$zadatakId]);
        return $stmt->fetchAll();
    }

    /** Jedan fajl + kreator zadatka (za proveru pristupa pri preuzimanju). */
    public static function getFajl(int $id): ?array
    {
        $stmt = DB::prepare(
            "SELECT zf.*, z.kreirao_id
             FROM zadaci_fajlovi zf
             JOIN interni_zadaci z ON z.id = zf.zadatak_id
             WHERE zf.id = ?"
        );
        $stmt->execute([$id]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function obrisiFajl(int $id): void
    {
        $stmt = DB::prepare("SELECT putanja FROM zadaci_fajlovi WHERE id=?");
        $stmt->execute([$id]);
        $putanja = $stmt->fetchColumn();
        if ($putanja) {
            $p = UPLOAD_DIR . 'zadaci/' . $putanja;
            if (is_file($p)) @unlink($p);
        }
        DB::prepare("DELETE FROM zadaci_fajlovi WHERE id=?")->execute([$id]);
    }
}
