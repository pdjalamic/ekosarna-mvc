<?php
namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_PORT, DB_NAME
            );
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    /** Skraćenica */
    public static function db(): PDO
    {
        return self::get();
    }

    public static function prepare(string $sql): \PDOStatement
    {
        return self::get()->prepare($sql);
    }

    public static function exec(string $sql): int|false
    {
        return self::get()->exec($sql);
    }

    public static function lastInsertId(): string
    {
        return self::get()->lastInsertId();
    }

    /** Migracije — kreira tabele ako ne postoje, dodaje kolone ako nedostaju */
    public static function migrate(): void
    {
        $db = self::get();

        $db->exec("CREATE TABLE IF NOT EXISTS admin_korisnici (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ime             VARCHAR(100) NOT NULL DEFAULT '',
            email           VARCHAR(200) NOT NULL DEFAULT '',
            username        VARCHAR(100) NOT NULL UNIQUE,
            password_hash   VARCHAR(255) NOT NULL,
            uloga           ENUM('Direktor','AT','AF','Inženjer na gradilištu','Rukovodilac operative','Monter poslovođa','Zamenik montera poslovođe','Monter','Pomoćni radnik','Administrator','Operater','Elektricar') NOT NULL DEFAULT 'Operater',
            aktivan         TINYINT(1) NOT NULL DEFAULT 1,
            vidi_imenik     TINYINT(1) NOT NULL DEFAULT 0,
            vidi_magacin    TINYINT(1) NOT NULL DEFAULT 0,
            telefon         VARCHAR(50)  NOT NULL DEFAULT '',
            mail_pass       VARCHAR(255) NOT NULL DEFAULT '',
            datum_kreiranja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS kontakt_forme (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            datum         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ime_prezime   VARCHAR(200) NOT NULL,
            firma         VARCHAR(200) NOT NULL DEFAULT '',
            telefon       VARCHAR(60)  NOT NULL DEFAULT '',
            email         VARCHAR(200) NOT NULL DEFAULT '',
            vrsta_usluge  VARCHAR(200) NOT NULL DEFAULT '',
            opis_projekta TEXT         NOT NULL,
            grad          VARCHAR(100) NOT NULL DEFAULT '',
            komentar      TEXT         NOT NULL,
            procitano     TINYINT(1)   NOT NULL DEFAULT 0,
            ip_adresa     VARCHAR(45)  NOT NULL DEFAULT '',
            fajl_putanja  VARCHAR(500) NOT NULL DEFAULT '',
            INDEX idx_datum     (datum),
            INDEX idx_procitano (procitano)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS imenik_firme (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            naziv           VARCHAR(200) NOT NULL,
            adresa          VARCHAR(300) NOT NULL DEFAULT '',
            drzava          VARCHAR(100) NOT NULL DEFAULT 'Srbija',
            komentar        TEXT         NOT NULL,
            aktivan         TINYINT(1)   NOT NULL DEFAULT 1,
            datum_kreiranja DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_naziv (naziv)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS imenik_kontakti (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            firma_id        INT UNSIGNED NOT NULL,
            ime             VARCHAR(100) NOT NULL DEFAULT '',
            email           VARCHAR(200) NOT NULL DEFAULT '',
            telefon         VARCHAR(100) NOT NULL DEFAULT '',
            komentar        VARCHAR(500) NOT NULL DEFAULT '',
            datum_kreiranja DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (firma_id) REFERENCES imenik_firme(id) ON DELETE CASCADE,
            INDEX idx_firma (firma_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
            id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            korisnik_id    INT UNSIGNED NOT NULL,
            selektor       VARCHAR(32)  NOT NULL UNIQUE,
            validator_hash CHAR(64)     NOT NULL,
            expires_at     DATETIME     NOT NULL,
            created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_korisnik (korisnik_id),
            INDEX idx_expires  (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Magacin — knjiga prometa (stanje = SUM(kolicina) po artiklu × lokaciji)
        $db->exec("CREATE TABLE IF NOT EXISTS magacin_promet (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            katalog_id    INT UNSIGNED  NULL,
            naziv         VARCHAR(300)  NOT NULL,
            jm            VARCHAR(20)   NOT NULL DEFAULT 'm',
            lokacija      VARCHAR(150)  NOT NULL DEFAULT 'Magacin',
            gradiliste_id INT UNSIGNED  NULL,
            kolicina      DECIMAL(12,3) NOT NULL,
            tip           ENUM('ulaz','prenos_iz','prenos_do','potrosnja','korekcija') NOT NULL,
            izvor         ENUM('primka','raspored','rucno','edit') NOT NULL DEFAULT 'rucno',
            ref_id        INT UNSIGNED  NULL,
            datum         DATE          NOT NULL,
            komentar      VARCHAR(500)  NOT NULL DEFAULT '',
            korisnik_id   INT UNSIGNED  NULL,
            created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_katalog   (katalog_id),
            INDEX idx_lokacija  (lokacija),
            INDEX idx_gradiliste(gradiliste_id),
            INDEX idx_ref       (izvor, ref_id),
            INDEX idx_tip       (tip)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Magacin — audit log izmena (ko/kada/staro/novo)
        $db->exec("CREATE TABLE IF NOT EXISTS magacin_log (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tip          VARCHAR(40)  NOT NULL,
            zapis_id     INT UNSIGNED NOT NULL DEFAULT 0,
            akcija       VARCHAR(40)  NOT NULL,
            korisnik_id  INT UNSIGNED NULL,
            staro_stanje MEDIUMTEXT   NULL,
            novo_stanje  MEDIUMTEXT   NULL,
            created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tip   (tip),
            INDEX idx_zapis (zapis_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Jednokratni backfill: prepuni magacin_promet iz starih magacin_stavke/pokreti.
        // Stare tabele ostaju netaknute (rezerva). Guard po praznoj promet tabeli → idempotentno.
        try {
            $cntP = (int)$db->query("SELECT COUNT(*) FROM magacin_promet")->fetchColumn();
            $cntS = (int)$db->query("SELECT COUNT(*) FROM magacin_stavke")->fetchColumn();
            if ($cntP === 0 && $cntS > 0) {
                self::backfillMagacinPromet($db);
            }
        } catch (\PDOException $e) { /* magacin_stavke možda nije importovan */ }

        // Samoispravljanje: veži prepoznate tekstualne lokacije za gradilišta
        self::canonicalizeMagacinLokacije($db);

        // ALTER TABLE fallbacks za postojeće instalacije
        $alters = [
            "ALTER TABLE admin_korisnici ADD COLUMN vidi_imenik TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE admin_korisnici ADD COLUMN vidi_magacin TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE admin_korisnici ADD COLUMN telefon VARCHAR(50) NOT NULL DEFAULT ''",
            "ALTER TABLE admin_korisnici ADD COLUMN mail_pass VARCHAR(255) NOT NULL DEFAULT ''",
            "ALTER TABLE imenik_kontakti ADD COLUMN telefon VARCHAR(100) NOT NULL DEFAULT ''",
            "ALTER TABLE imenik_firme    ADD COLUMN drzava VARCHAR(100) NOT NULL DEFAULT 'Srbija' AFTER adresa",
            "ALTER TABLE kontakt_forme   ADD COLUMN fajl_putanja VARCHAR(500) NOT NULL DEFAULT ''",
            "ALTER TABLE admin_korisnici MODIFY COLUMN uloga ENUM('Direktor','AT','AF','Inženjer na gradilištu','Rukovodilac operative','Monter poslovođa','Zamenik montera poslovođe','Monter','Pomoćni radnik','Administrator','Operater','Elektricar') NOT NULL DEFAULT 'Operater'",
            "ALTER TABLE admin_korisnici ADD COLUMN platforma ENUM('android','ios','web') NOT NULL DEFAULT 'android'",
            "ALTER TABLE admin_korisnici ADD COLUMN platforma2 VARCHAR(20) DEFAULT NULL",
            "ALTER TABLE admin_korisnici ADD COLUMN telegram_username VARCHAR(100) NOT NULL DEFAULT ''",
            "ALTER TABLE magacin_stavke ADD COLUMN gradiliste_id INT UNSIGNED NULL AFTER lokacija",
            "ALTER TABLE raspored_materijal ADD COLUMN komentar VARCHAR(500) NOT NULL DEFAULT ''",
        ];
        foreach ($alters as $sql) {
            try { $db->exec($sql); } catch (\PDOException $e) { /* kolona već postoji */ }
        }

        // Utrošak na jednom mestu: prebaci stare ručne unose iz prometa u raspored_materijal
        self::migrateRucnaPotrosnja($db);

        // Seed: prvi administrator
        $cnt = (int)$db->query("SELECT COUNT(*) FROM admin_korisnici")->fetchColumn();
        if ($cnt === 0) {
            $hash = password_hash('Ek0s@rna2024!', PASSWORD_BCRYPT);
            $db->prepare("INSERT INTO admin_korisnici (ime,email,username,password_hash,uloga) VALUES (?,?,?,?,?)")
               ->execute(['Administrator', 'info@ekosarna.com', 'ekosarna', $hash, 'Administrator']);
        }
    }

    /**
     * Jednokratno (idempotentno) prebacivanje ranije ručno unetih utrošaka iz
     * magacin_promet (izvor='rucno', tip='potrosnja') u raspored_materijal, da
     * utrošak živi na jednom mestu (Evidencija). Promet red se zadržava (stanje
     * ostaje tačno) i samo se preveže na izvor='raspored' + ref_id novog zapisa.
     */
    private static function migrateRucnaPotrosnja(PDO $db): void
    {
        try {
            $rows = $db->query("SELECT * FROM magacin_promet WHERE izvor='rucno' AND tip='potrosnja'")->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) return;

            $insRm = $db->prepare("INSERT INTO raspored_materijal
                (stavka_id, radnik_id, datum, naziv, kolicina, jm, gradiliste_id, gradiliste_naziv, komentar)
                VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)");
            $updPr = $db->prepare("UPDATE magacin_promet SET izvor='raspored', ref_id=? WHERE id=?");

            foreach ($rows as $r) {
                if (empty($r['korisnik_id'])) continue; // raspored_materijal.radnik_id je NOT NULL
                $insRm->execute([
                    $r['korisnik_id'], $r['datum'], $r['naziv'], abs((float)$r['kolicina']),
                    $r['jm'] ?: 'Kom', $r['gradiliste_id'] ?: null, $r['lokacija'], $r['komentar'] ?? '',
                ]);
                $updPr->execute([(int)$db->lastInsertId(), (int)$r['id']]);
            }
        } catch (PDOException $e) { /* raspored_materijal/magacin_promet možda ne postoje */ }
    }

    /** Normalizacija naziva lokacije za poređenje (lower + bez kvačica + bez ne-alfanum.). */
    private static function normLok(?string $s): string
    {
        $s = mb_strtolower(trim((string)$s), 'UTF-8');
        $s = strtr($s, ['š' => 's', 'đ' => 'd', 'ž' => 'z', 'č' => 'c', 'ć' => 'c']);
        return preg_replace('/[^a-z0-9]/u', '', $s) ?? '';
    }

    /**
     * Ponovljivo (idempotentno) vezivanje tekstualnih lokacija u magacin_promet
     * za postojeća gradilišta kada se NORMALIZOVANI naziv poklapa. Ne pravi nove
     * lokacije i ne dira one koje se ne prepoznaju (njih korisnik ručno premešta).
     */
    private static function canonicalizeMagacinLokacije(PDO $db): void
    {
        try {
            $gmap = [];
            foreach ($db->query("SELECT id, naziv FROM gradilista")->fetchAll(PDO::FETCH_ASSOC) as $g) {
                $gmap[self::normLok($g['naziv'])] = ['id' => (int)$g['id'], 'naziv' => trim($g['naziv'])];
            }
            if (!$gmap) return;

            $upd = $db->prepare("UPDATE magacin_promet SET lokacija=?, gradiliste_id=? WHERE lokacija=?");
            $lokacije = $db->query("SELECT DISTINCT lokacija FROM magacin_promet")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($lokacije as $lok) {
                $hit = $gmap[self::normLok($lok)] ?? null;
                if ($hit && $hit['naziv'] !== $lok) {
                    $upd->execute([$hit['naziv'], $hit['id'], $lok]);
                }
            }
        } catch (PDOException $e) { /* magacin_promet/gradilista možda ne postoje */ }
    }

    /**
     * Jednokratno prepunjavanje knjige prometa (magacin_promet) iz starog modela
     * (magacin_stavke + magacin_pokreti). Čuva ukupna stanja, a dodaje svest o lokaciji:
     *   - svaka stavka prijema → red 'ulaz' (+kolicina) na svojoj lokaciji
     *   - stari 'izlaz'  → 'potrosnja' (−) sa bazne lokacije
     *   - stari 'povrat' → 'korekcija' (+) na baznoj lokaciji
     *   - stari 'prenos' → par 'prenos_iz' (−) / 'prenos_do' (+) (neto 0 na ukupno)
     * "Kombi" i prazne lokacije se mapiraju na "Magacin".
     */
    private static function backfillMagacinPromet(PDO $db): void
    {
        // Mapa normalizovan naziv gradilišta → [id, kanonski naziv].
        // Normalizacija ignoriše velika/mala slova, razmake i kvačice, pa
        // "FORTUNA BEKA TOWN" == "Fortuna Bekatown".
        $gmap = [];
        try {
            foreach ($db->query("SELECT id, naziv FROM gradilista")->fetchAll(PDO::FETCH_ASSOC) as $g) {
                $gmap[self::normLok($g['naziv'])] = ['id' => (int)$g['id'], 'naziv' => trim($g['naziv'])];
            }
        } catch (PDOException $e) { /* gradilista možda ne postoji */ }

        $mapLok = function (?string $lok) use ($gmap): array {
            $lok = trim((string)$lok);
            if ($lok === '' || strcasecmp($lok, 'Kombi') === 0) $lok = 'Magacin';
            $hit = $gmap[self::normLok($lok)] ?? null;
            if ($hit) return [$hit['naziv'], $hit['id']]; // kanonski naziv + id
            return [$lok, null];
        };

        $ins = $db->prepare("INSERT INTO magacin_promet
            (katalog_id, naziv, jm, lokacija, gradiliste_id, kolicina, tip, izvor, ref_id, datum, komentar, korisnik_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");

        $db->beginTransaction();
        try {
            // ── ULAZ: po jedan red za svaku stavku prijema ──
            $stavke = $db->query("
                SELECT s.id, s.katalog_id, s.naziv, s.jm, s.lokacija, s.kolicina,
                       COALESCE(p.datum, DATE(p.created_at), CURDATE()) AS datum, p.kreator_id
                FROM magacin_stavke s
                JOIN magacin_primke p ON s.primka_id = p.id
            ")->fetchAll(PDO::FETCH_ASSOC);

            $baseLok = [];
            foreach ($stavke as $s) {
                [$lok, $gid] = $mapLok($s['lokacija'] ?? 'Magacin');
                $baseLok[(int)$s['id']] = [$lok, $gid];
                $ins->execute([
                    $s['katalog_id'] ?: null, $s['naziv'], $s['jm'] ?: 'm', $lok, $gid,
                    $s['kolicina'], 'ulaz', 'primka', (int)$s['id'],
                    $s['datum'], '', $s['kreator_id'] ?: null,
                ]);
            }

            // ── POKRETI: izlaz / povrat / prenos ──
            $pokreti = $db->query("
                SELECT po.*, s.katalog_id, s.naziv AS s_naziv, s.jm AS s_jm, s.lokacija AS s_lok
                FROM magacin_pokreti po
                JOIN magacin_stavke s ON po.stavka_id = s.id
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($pokreti as $po) {
                $sid   = (int)$po['stavka_id'];
                $base  = $baseLok[$sid] ?? $mapLok($po['s_lok'] ?? 'Magacin');
                $kat   = $po['katalog_id'] ?: null;
                $naziv = $po['s_naziv'];
                $jm    = $po['s_jm'] ?: 'm';
                $q     = (float)$po['kolicina'];
                $datum = $po['datum'] ?: date('Y-m-d');
                $uid   = $po['korisnik_id'] ?: null;

                if ($po['tip'] === 'izlaz') {
                    $ins->execute([$kat, $naziv, $jm, $base[0], $base[1], -$q, 'potrosnja', 'primka', $sid, $datum, '(migracija: izlaz)', $uid]);
                } elseif ($po['tip'] === 'povrat') {
                    $ins->execute([$kat, $naziv, $jm, $base[0], $base[1], $q, 'korekcija', 'primka', $sid, $datum, '(migracija: povrat)', $uid]);
                } elseif ($po['tip'] === 'prenos') {
                    [$lokIz, $gidIz] = $mapLok($po['lokacija_iz'] ?: $base[0]);
                    [$lokDo, $gidDo] = $mapLok($po['lokacija_do'] ?: 'Magacin');
                    $ins->execute([$kat, $naziv, $jm, $lokIz, $gidIz, -$q, 'prenos_iz', 'primka', $sid, $datum, '(migracija: prenos)', $uid]);
                    $ins->execute([$kat, $naziv, $jm, $lokDo, $gidDo,  $q, 'prenos_do', 'primka', $sid, $datum, '(migracija: prenos)', $uid]);
                }
            }

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[Ekosarna magacin backfill] ' . $e->getMessage());
        }
    }
}