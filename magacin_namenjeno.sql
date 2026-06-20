-- ============================================================
-- Ekošarna — Magacin: "Namenjeno za" (gradilište kome je roba namenjena)
-- Nezavisno od fizičke lokacije (kolona `lokacija`/`gradiliste_id`).
-- Pokrenuti JEDNOM na produkciji. Idempotentno (IF NOT EXISTS — MariaDB).
-- ============================================================

ALTER TABLE magacin_promet
  ADD COLUMN IF NOT EXISTS namenjeno_gradiliste_id INT UNSIGNED NULL AFTER gradiliste_id,
  ADD KEY IF NOT EXISTS idx_mpromet_namenjeno (namenjeno_gradiliste_id);

ALTER TABLE magacin_stavke
  ADD COLUMN IF NOT EXISTS namenjeno_gradiliste_id INT UNSIGNED NULL AFTER gradiliste_id,
  ADD KEY IF NOT EXISTS idx_mstavke_namenjeno (namenjeno_gradiliste_id);
