<?php
namespace Models;

use Core\Database as DB;

class PushSubscription
{
    public static function migrate(): void
    {
        DB::exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            korisnik_id  INT UNSIGNED NOT NULL,
            endpoint     TEXT NOT NULL,
            p256dh       VARCHAR(500) NOT NULL,
            auth_key     VARCHAR(200) NOT NULL,
            uredjaj      VARCHAR(50) NOT NULL DEFAULT 'web',
            datum        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_endpoint (endpoint(255)),
            INDEX idx_korisnik (korisnik_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function save(int $korisnikId, array $sub, string $uredjaj = 'web'): void
    {
        $keys = $sub['keys'] ?? [];
        DB::prepare(
            "INSERT INTO push_subscriptions (korisnik_id, endpoint, p256dh, auth_key, uredjaj)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               korisnik_id = VALUES(korisnik_id),
               p256dh      = VALUES(p256dh),
               auth_key    = VALUES(auth_key),
               uredjaj     = VALUES(uredjaj)"
        )->execute([
            $korisnikId,
            $sub['endpoint'],
            $keys['p256dh']  ?? '',
            $keys['auth']    ?? '',
            $uredjaj,
        ]);
    }

    public static function delete(string $endpoint): void
    {
        DB::prepare("DELETE FROM push_subscriptions WHERE endpoint=?")
          ->execute([$endpoint]);
    }

    /** Svi subscriberi za određenog korisnika */
    public static function getByKorisnik(int $korisnikId): array
    {
        $stmt = DB::prepare(
            "SELECT * FROM push_subscriptions WHERE korisnik_id=?"
        );
        $stmt->execute([$korisnikId]);
        return $stmt->fetchAll();
    }

    /** Svi subscriberi koji imaju zadatke koji ističu sutra */
    public static function getForTomorrow(): array
    {
        $stmt = DB::prepare(
            "SELECT DISTINCT ps.*, k.ime, k.email,
                    z.id as zadatak_id, z.tekst as zadatak_tekst
             FROM push_subscriptions ps
             JOIN admin_korisnici k ON k.id = ps.korisnik_id
             JOIN interni_zadaci z ON (
               z.kreirao_id = ps.korisnik_id
               OR z.dodeljeno_id = ps.korisnik_id
             )
             WHERE z.rok = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
               AND z.status != 'zavrseno'"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Za email — korisnici bez push koji imaju zadatke sutra */
    public static function getEmailForTomorrow(): array
    {
        $stmt = DB::prepare(
            "SELECT k.id, k.ime, k.email,
                    z.id as zadatak_id, z.tekst as zadatak_tekst
             FROM admin_korisnici k
             JOIN interni_zadaci z ON (
               z.kreirao_id = k.id OR z.dodeljeno_id = k.id
             )
             WHERE z.rok = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
               AND z.status != 'zavrseno'
               AND k.aktivan = 1
               AND NOT EXISTS (
                 SELECT 1 FROM push_subscriptions ps
                 WHERE ps.korisnik_id = k.id
               )"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
