-- Poruke (chat) — „pročitano do" po razgovoru (kao raspored_vidjeno)
-- sagovornik_id = id druge osobe za direktan razgovor, ili 0 za grupu „Ekošarna" (broadcast).
-- Nepročitano u razgovoru = poruke tuđeg autora sa created_at > procitano_do.
--
-- Idempotentno. Pokrenuti JEDNOM na produkciji PRE deploya koda.

CREATE TABLE IF NOT EXISTS poruke_procitano (
  korisnik_id   INT UNSIGNED NOT NULL,
  sagovornik_id INT UNSIGNED NOT NULL,           -- 0 = grupa Ekošarna
  procitano_do  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (korisnik_id, sagovornik_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
