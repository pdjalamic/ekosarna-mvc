-- zadaci_alarmi.sql — Faza 3: podsetnik (alarm) za zadatak
-- Pokrenuti JEDNOM na produkciji PRE deploya koda. Idempotentno.

CREATE TABLE IF NOT EXISTS zadaci_alarmi (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zadatak_id   INT UNSIGNED NOT NULL,
    korisnik_id  INT UNSIGNED NULL,          -- NULL = ceo tim (svi članovi); inače lični podsetnik
    postavio_id  INT UNSIGNED NOT NULL,       -- ko je postavio podsetnik
    send_at      DATETIME NOT NULL,
    poruka       VARCHAR(255) NOT NULL DEFAULT '',
    poslato      TINYINT(1) NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_due (poslato, send_at),
    INDEX idx_zadatak (zadatak_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
