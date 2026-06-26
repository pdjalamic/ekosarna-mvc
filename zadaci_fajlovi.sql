-- zadaci_fajlovi.sql — Faza 4: fajlovi na zadatku
-- Pokrenuti JEDNOM na produkciji PRE deploya koda. Idempotentno.

CREATE TABLE IF NOT EXISTS zadaci_fajlovi (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zadatak_id  INT UNSIGNED NOT NULL,
    naziv       VARCHAR(255) NOT NULL,                 -- originalno ime fajla
    putanja     VARCHAR(255) NOT NULL,                 -- ime na disku (nasumično)
    tip         VARCHAR(100) NOT NULL DEFAULT '',       -- MIME tip
    velicina    INT UNSIGNED NOT NULL DEFAULT 0,        -- bajtovi
    dodao_id    INT UNSIGNED NOT NULL,
    dodat_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_zadatak (zadatak_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
