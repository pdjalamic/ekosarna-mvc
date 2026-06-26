-- zadaci_clanovi.sql — Faza 1: više članova po zadatku
-- Pokrenuti JEDNOM na produkciji PRE deploya koda. Idempotentno.

CREATE TABLE IF NOT EXISTS zadaci_clanovi (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zadatak_id    INT UNSIGNED NOT NULL,
    korisnik_id   INT UNSIGNED NOT NULL,
    prihvatio     TINYINT(1) NOT NULL DEFAULT 0,
    prihvatio_at  DATETIME NULL,
    pozvao_id     INT UNSIGNED NULL,          -- ko ga je pozvao (NULL = prvobitna dodela)
    dodat_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_zad_kor (zadatak_id, korisnik_id),
    INDEX idx_zadatak (zadatak_id),
    INDEX idx_korisnik (korisnik_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed iz postojećih zadataka (idempotentno):
-- 1) Dodeljeni postaju članovi.
INSERT IGNORE INTO zadaci_clanovi (zadatak_id, korisnik_id, prihvatio)
SELECT id, dodeljeno_id, 0 FROM interni_zadaci WHERE dodeljeno_id IS NOT NULL;

-- 2) Prihvatioci postaju članovi i odmah označeni kao prihvaćeni.
INSERT IGNORE INTO zadaci_clanovi (zadatak_id, korisnik_id, prihvatio, prihvatio_at)
SELECT id, prihvaceno_id, 1, prihvaceno_at FROM interni_zadaci WHERE prihvaceno_id IS NOT NULL;

-- 3) Ako je prihvatilac već unet kao dodeljeni red, označi ga kao prihvaćenog.
UPDATE zadaci_clanovi zc
JOIN interni_zadaci z ON z.id = zc.zadatak_id
SET zc.prihvatio = 1, zc.prihvatio_at = z.prihvaceno_at
WHERE z.prihvaceno_id = zc.korisnik_id;
