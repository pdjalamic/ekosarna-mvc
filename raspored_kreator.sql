-- Raspored — ko je napravio stavku (kreator)
-- Potrebno da: (a) kreator zadatka dobija notifikaciju za poruke na svom zadatku,
-- (b) nacrt bude vidljiv samo kreatoru (#1, kasnije).
--
-- Idempotentno (MariaDB: ADD COLUMN IF NOT EXISTS).
-- Pokrenuti JEDNOM na produkciji PRE deploya koda.

ALTER TABLE raspored_stavke ADD COLUMN IF NOT EXISTS kreator_id INT UNSIGNED NULL AFTER status;
