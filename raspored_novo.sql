-- ============================================================
-- Ekošarna MVC — Raspored: vreme i materijal
-- Pokrenuti jednom u phpMyAdmin
-- ============================================================

-- 1. Ko je odgovoran za unos materijala po stavci
ALTER TABLE raspored_stavke
    ADD COLUMN odgovoran_id INT UNSIGNED NULL,
    ADD CONSTRAINT fk_rs_odgovoran
        FOREIGN KEY (odgovoran_id) REFERENCES admin_korisnici(id) ON DELETE SET NULL;

-- 2. Stvarno odrađeno vreme po elektrčaru po stavci
--    (bez UNIQUE — može više unosa po danu, npr. jutro + popodne)
CREATE TABLE raspored_vreme (
    id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    stavka_id    INT UNSIGNED    NOT NULL,
    radnik_id    INT UNSIGNED    NOT NULL,
    datum        DATE            NOT NULL,
    vreme_od     TIME            NULL,
    vreme_do     TIME            NULL,
    ukupno_sati  DECIMAL(4,2)   NULL,
    napomena     TEXT            NULL,
    meta         LONGTEXT        CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL
                                 CHECK (json_valid(meta)),
    created_at   TIMESTAMP       NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY (stavka_id),
    KEY (radnik_id),
    KEY (datum),
    CONSTRAINT fk_rv_stavka FOREIGN KEY (stavka_id) REFERENCES raspored_stavke(id) ON DELETE CASCADE,
    CONSTRAINT fk_rv_radnik FOREIGN KEY (radnik_id) REFERENCES admin_korisnici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Utrošak materijala po stavci (unosi samo odgovoran_id)
CREATE TABLE raspored_materijal (
    id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    stavka_id    INT UNSIGNED    NOT NULL,
    radnik_id    INT UNSIGNED    NOT NULL,
    datum        DATE            NOT NULL,
    naziv        VARCHAR(255)    NOT NULL,
    kolicina     DECIMAL(10,3)  NOT NULL,
    jm           VARCHAR(20)    NOT NULL DEFAULT 'm',
    created_at   TIMESTAMP       NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY (stavka_id),
    KEY (radnik_id),
    KEY (datum),
    CONSTRAINT fk_rm_stavka FOREIGN KEY (stavka_id) REFERENCES raspored_stavke(id) ON DELETE CASCADE,
    CONSTRAINT fk_rm_radnik FOREIGN KEY (radnik_id) REFERENCES admin_korisnici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
