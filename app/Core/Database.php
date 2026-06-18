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
        ];
        foreach ($alters as $sql) {
            try { $db->exec($sql); } catch (\PDOException $e) { /* kolona već postoji */ }
        }

        // Seed: prvi administrator
        $cnt = (int)$db->query("SELECT COUNT(*) FROM admin_korisnici")->fetchColumn();
        if ($cnt === 0) {
            $hash = password_hash('Ek0s@rna2024!', PASSWORD_BCRYPT);
            $db->prepare("INSERT INTO admin_korisnici (ime,email,username,password_hash,uloga) VALUES (?,?,?,?,?)")
               ->execute(['Administrator', 'info@ekosarna.com', 'ekosarna', $hash, 'Administrator']);
        }
    }
}