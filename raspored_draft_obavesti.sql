-- Raspored — nacrt pamti izbor obaveštenja (varijanta B)
-- Draft („Snimi privremeno") sada pamti ŠTA je izabrano za obaveštenje
-- (odmah / zakazano + vreme / ne), ali NIŠTA ne šalje dok se ručno ne klikne „Objavi".
-- Te kolone čuvaju taj izbor po stavci (i za nacrt i za objavljeno — za round-trip u modalu).
--
-- Idempotentno (MariaDB: ADD COLUMN IF NOT EXISTS).
-- Pokrenuti JEDNOM na produkciji PRE deploya koda.

ALTER TABLE raspored_stavke ADD COLUMN IF NOT EXISTS obavesti_tip ENUM('odmah','zakazano','ne') NULL AFTER status;
ALTER TABLE raspored_stavke ADD COLUMN IF NOT EXISTS obavesti_at  DATETIME NULL AFTER obavesti_tip;
