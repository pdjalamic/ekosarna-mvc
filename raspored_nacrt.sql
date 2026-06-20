-- ============================================================
-- Ekošarna MVC — Raspored: nacrt (draft) vs objavljeno
-- Pokrenuti JEDNOM na produkciji PRE deploya koda.
-- Idempotentno (IF NOT EXISTS, MariaDB).
-- ============================================================
--
-- Status stavke: 'nacrt' (sačuvano privremeno, ekipa NIJE obaveštena)
-- ili 'objavljeno' (obaveštenja poslata). Default 'objavljeno' da sve
-- postojeće stavke ostanu nepromenjene.

ALTER TABLE raspored_stavke
    ADD COLUMN IF NOT EXISTS status ENUM('nacrt','objavljeno')
        NOT NULL DEFAULT 'objavljeno';
