-- Raspored — zakazane notifikacije (#4)
-- Tabela raspored_obavestenja je do sada čuvala SAMO nedelja_id+send_at, pa
-- zakazana poruka nije imala ni primaoca ni tekst, a nijedan cron je nije slao.
-- Ove kolone omogućavaju da raspored_cron.php pošalje tačnu poruku tačnom radniku.
--
-- Idempotentno (MariaDB: ADD COLUMN/INDEX IF NOT EXISTS).
-- Pokrenuti JEDNOM na produkciji PRE deploya koda.

ALTER TABLE raspored_obavestenja ADD COLUMN IF NOT EXISTS stavka_id INT UNSIGNED NULL AFTER nedelja_id;
ALTER TABLE raspored_obavestenja ADD COLUMN IF NOT EXISTS radnik_id INT UNSIGNED NULL AFTER stavka_id;
ALTER TABLE raspored_obavestenja ADD COLUMN IF NOT EXISTS poruka    TEXT        NULL AFTER radnik_id;
ALTER TABLE raspored_obavestenja ADD COLUMN IF NOT EXISTS datum     VARCHAR(20) NULL AFTER poruka;

-- Brzo nalaženje dospelih za slanje (cron: poslato=0 AND send_at<=NOW())
ALTER TABLE raspored_obavestenja ADD INDEX IF NOT EXISTS ix_dospelo (poslato, send_at);
