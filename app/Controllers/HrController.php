<?php
namespace Controllers;

use Core\Auth;

class HrController extends \Core\Controller
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::get();
    }

    public function index(): void
    {
        Auth::requireLogin();

        if (Auth::isElektricar()) {
            // Električar vidi samo sebe
            $stmt = $this->db->prepare("SELECT id FROM hr_zaposleni WHERE korisnik_id=?");
            $stmt->execute([Auth::id()]);
            $zid = $stmt->fetchColumn();
            if ($zid) {
                header('Location: ' . BASE_URL . '/?page=hr&action=karton&id=' . $zid);
            } else {
                header('Location: ' . BASE_URL . '/?page=hr&action=karton&id=0');
            }
            exit;
        }

        Auth::requireKancelarija();

        $zaposleni = $this->db->query("
            SELECT z.*,
                   k.username,
                   COALESCE(
                       (SELECT SUM(o.broj_dana) FROM hr_odsustva o
                        WHERE o.zaposleni_id = z.id AND o.tip = 'godisnji'
                        AND YEAR(o.datum_od) = YEAR(CURDATE())), 0
                   ) AS iskoristio_godisnji,
                   COALESCE(
                       (SELECT g.ukupno_dana + g.preneseno FROM hr_godisnji g
                        WHERE g.zaposleni_id = z.id AND g.godina = YEAR(CURDATE())), 20
                   ) AS ukupno_godisnji
            FROM hr_zaposleni z
            LEFT JOIN admin_korisnici k ON z.korisnik_id = k.id
            ORDER BY z.aktivan DESC, z.ime ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // Odsustva ovog meseca
        $stmt2 = $this->db->prepare("
            SELECT o.*, z.ime AS zaposleni_ime
            FROM hr_odsustva o
            JOIN hr_zaposleni z ON o.zaposleni_id = z.id
            WHERE o.datum_od <= LAST_DAY(CURDATE())
              AND o.datum_do >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
            ORDER BY o.datum_od ASC
        ");
        $stmt2->execute();
        $odsustva_mesec = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

        // Korisnici koji nisu vezani za zaposlenog (za linkovanje)
        $korisnici_slobodni = $this->db->query("
            SELECT id, ime FROM admin_korisnici
            WHERE id NOT IN (SELECT korisnik_id FROM hr_zaposleni WHERE korisnik_id IS NOT NULL)
            ORDER BY ime
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('hr/index', compact('zaposleni', 'odsustva_mesec', 'korisnici_slobodni'));
    }

    public function karton(): void
    {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);

        // Proveri pristup za električara
        if (Auth::isElektricar()) {
            $stmt = $this->db->prepare("SELECT id FROM hr_zaposleni WHERE korisnik_id=?");
            $stmt->execute([Auth::id()]);
            $moj_id = (int)$stmt->fetchColumn();
            if ($id !== $moj_id) {
                header('Location: ' . BASE_URL . '/?page=hr&action=karton&id=' . $moj_id);
                exit;
            }
        }

        if (!$id) {
            $this->view('hr/karton_prazan', []);
            return;
        }

        $stmt = $this->db->prepare("
            SELECT z.*, k.username, k.uloga AS app_uloga, k.aktivan AS app_aktivan
            FROM hr_zaposleni z
            LEFT JOIN admin_korisnici k ON z.korisnik_id = k.id
            WHERE z.id = ?
        ");
        $stmt->execute([$id]);
        $zaposleni = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$zaposleni) { http_response_code(404); echo 'Nije pronađen.'; exit; }

        $stmt = $this->db->prepare("SELECT * FROM hr_godisnji WHERE zaposleni_id=? ORDER BY godina DESC");
        $stmt->execute([$id]);
        $godisnji = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("SELECT * FROM hr_odsustva WHERE zaposleni_id=? ORDER BY datum_od DESC");
        $stmt->execute([$id]);
        $odsustva = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("SELECT * FROM hr_dokumenta WHERE zaposleni_id=? ORDER BY created_at DESC");
        $stmt->execute([$id]);
        $dokumenta = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("
            SELECT YEAR(datum_od) as godina, SUM(broj_dana) as iskoristio
            FROM hr_odsustva WHERE zaposleni_id=? AND tip='godisnji'
            GROUP BY YEAR(datum_od)
        ");
        $stmt->execute([$id]);
        $iskoristio_map = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $iskoristio_map[$r['godina']] = (int)$r['iskoristio'];
        }

        // Slobodni korisnici za linkovanje
        $korisnici_slobodni = $this->db->query("
            SELECT id, ime FROM admin_korisnici
            WHERE id NOT IN (SELECT korisnik_id FROM hr_zaposleni WHERE korisnik_id IS NOT NULL)
            ORDER BY ime
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('hr/karton', compact('zaposleni', 'godisnji', 'odsustva', 'dokumenta', 'iskoristio_map', 'id', 'korisnici_slobodni'));
    }

    public function ajax(string $action, int $id): void
    {
        Auth::requireLogin();

        switch ($action) {

            case 'hr_dodaj_zaposlenog':
                Auth::requireKancelarija();
                $ime         = mb_substr(trim($_POST['ime'] ?? ''), 0, 100);
                $email       = mb_substr(trim($_POST['email'] ?? ''), 0, 200);
                $telefon     = mb_substr(trim($_POST['telefon'] ?? ''), 0, 50);
                $pozicija    = mb_substr(trim($_POST['pozicija'] ?? ''), 0, 100);
                $korisnik_id = (int)($_POST['korisnik_id'] ?? 0) ?: null;

                if (!$ime) $this->json(['ok' => false, 'err' => 'Ime je obavezno.']);

                $this->db->prepare("
                    INSERT INTO hr_zaposleni (ime, email, telefon, pozicija, korisnik_id)
                    VALUES (?, ?, ?, ?, ?)
                ")->execute([$ime, $email, $telefon, $pozicija, $korisnik_id]);

                $novi_id = $this->db->lastInsertId();
                $this->json(['ok' => true, 'id' => $novi_id]);
                break;

            case 'hr_upload_sliku':
                Auth::requireKancelarija();
                $zid = (int)($_POST['zaposleni_id'] ?? 0);
                if (!$zid) $this->json(['ok' => false, 'err' => 'Nema zaposlenog.']);
                if (empty($_FILES['slika']['tmp_name']) || $_FILES['slika']['error'] !== UPLOAD_ERR_OK) {
                    $this->json(['ok' => false, 'err' => 'Greška pri uploadu: ' . ($_FILES['slika']['error'] ?? 'nema fajla')]);
                }
                $ext = strtolower(pathinfo($_FILES['slika']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                    $this->json(['ok' => false, 'err' => 'Dozvoljeni formati: jpg, png, webp.']);
                }
                $dir = ROOT . '/uploads/hr/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $filename = 'zap_' . $zid . '_' . time() . '.' . $ext;
                if (!move_uploaded_file($_FILES['slika']['tmp_name'], $dir . $filename)) {
                    $this->json(['ok' => false, 'err' => 'Nije moguće sačuvati fajl.']);
                }
                $putanja = BASE_URL . '/uploads/hr/' . $filename;
                $this->db->prepare("UPDATE hr_zaposleni SET slika_putanja=? WHERE id=?")
                    ->execute([$putanja, $zid]);
                $this->json(['ok' => true, 'putanja' => $putanja]);
                break;

            case 'hr_sacuvaj_karton':
                Auth::requireKancelarija();
                $zid           = (int)($_POST['zaposleni_id'] ?? 0);
                $ime           = mb_substr(trim($_POST['ime'] ?? ''), 0, 100);
                $email         = mb_substr(trim($_POST['email'] ?? ''), 0, 200);
                $telefon       = mb_substr(trim($_POST['telefon'] ?? ''), 0, 50);
                $jmbg          = mb_substr(trim($_POST['jmbg'] ?? ''), 0, 13);
                $adresa        = mb_substr(trim($_POST['adresa'] ?? ''), 0, 300);
                $grad          = mb_substr(trim($_POST['grad'] ?? ''), 0, 100);
                $datum_rodj    = $_POST['datum_rodjenja'] ?? null;
                $pol           = in_array($_POST['pol'] ?? '', ['M','Z']) ? $_POST['pol'] : null;
                $datum_zaposl  = $_POST['datum_zaposlenja'] ?? null;
                $tip_ugovora   = in_array($_POST['tip_ugovora'] ?? '', ['neodredjeno','odredjeno','ucenicka','probni'])
                                    ? $_POST['tip_ugovora'] : 'neodredjeno';
                $datum_isteka  = $_POST['datum_isteka'] ?? null;
                $pozicija      = mb_substr(trim($_POST['pozicija'] ?? ''), 0, 100);
                $napomena      = trim($_POST['napomena'] ?? '');
                $korisnik_id   = (int)($_POST['korisnik_id'] ?? 0) ?: null;

                $this->db->prepare("
                    UPDATE hr_zaposleni SET ime=?, email=?, telefon=?, jmbg=?, adresa=?, grad=?,
                    datum_rodjenja=?, pol=?, datum_zaposlenja=?, tip_ugovora=?, datum_isteka=?,
                    pozicija=?, napomena=?, korisnik_id=?
                    WHERE id=?
                ")->execute([
                    $ime, $email, $telefon, $jmbg, $adresa, $grad,
                    $datum_rodj ?: null, $pol, $datum_zaposl ?: null,
                    $tip_ugovora, $datum_isteka ?: null, $pozicija, $napomena, $korisnik_id,
                    $zid
                ]);

                $this->json(['ok' => true]);
                break;

            case 'hr_toggle_aktivan':
                Auth::requireKancelarija();
                $this->db->prepare("UPDATE hr_zaposleni SET aktivan = 1 - aktivan WHERE id=?")->execute([$id]);
                $stmt = $this->db->prepare("SELECT aktivan FROM hr_zaposleni WHERE id=?");
                $stmt->execute([$id]);
                $this->json(['ok' => true, 'aktivan' => (int)$stmt->fetchColumn()]);
                break;

            case 'hr_sacuvaj_godisnji':
                Auth::requireKancelarija();
                $zid       = (int)($_POST['zaposleni_id'] ?? 0);
                $godina    = (int)($_POST['godina'] ?? date('Y'));
                $ukupno    = (int)($_POST['ukupno_dana'] ?? 20);
                $preneseno = (int)($_POST['preneseno'] ?? 0);

                $this->db->prepare("
                    INSERT INTO hr_godisnji (zaposleni_id, godina, ukupno_dana, preneseno)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE ukupno_dana=?, preneseno=?
                ")->execute([$zid, $godina, $ukupno, $preneseno, $ukupno, $preneseno]);

                $this->json(['ok' => true]);
                break;

            case 'hr_dodaj_odsustvo':
                Auth::requireKancelarija();
                $zid      = (int)($_POST['zaposleni_id'] ?? 0);
                $tip      = $_POST['tip'] ?? 'godisnji';
                $datum_od = $_POST['datum_od'] ?? '';
                $datum_do = $_POST['datum_do'] ?? '';
                $napomena = mb_substr(trim($_POST['napomena'] ?? ''), 0, 500);

                if (!$datum_od || !$datum_do) $this->json(['ok' => false, 'err' => 'Datum je obavezan.']);

                $broj_dana = $this->radniDani($datum_od, $datum_do);

                $this->db->prepare("
                    INSERT INTO hr_odsustva (zaposleni_id, tip, datum_od, datum_do, broj_dana, napomena, kreirao_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ")->execute([$zid, $tip, $datum_od, $datum_do, $broj_dana, $napomena, Auth::id()]);

                $this->json(['ok' => true, 'broj_dana' => $broj_dana]);
                break;

            case 'hr_obrisi_odsustvo':
                Auth::requireKancelarija();
                $this->db->prepare("DELETE FROM hr_odsustva WHERE id=?")->execute([$id]);
                $this->json(['ok' => true]);
                break;

            case 'hr_upload_dokument':
                Auth::requireKancelarija();
                $zid     = (int)($_POST['zaposleni_id'] ?? 0);
                $naziv   = mb_substr(trim($_POST['naziv'] ?? ''), 0, 200);
                $tip     = in_array($_POST['tip'] ?? '', ['ugovor','diploma','licenca','ostalo'])
                            ? $_POST['tip'] : 'ostalo';

                if (empty($_FILES['dokument']['tmp_name'])) $this->json(['ok' => false, 'err' => 'Nema fajla.']);

                $ext = strtolower(pathinfo($_FILES['dokument']['name'], PATHINFO_EXTENSION));
                $dir = ROOT . '/uploads/hr/dokumenta/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $filename = 'dok_' . $zid . '_' . time() . '.' . $ext;
                if (!move_uploaded_file($_FILES['dokument']['tmp_name'], $dir . $filename)) {
                    $this->json(['ok' => false, 'err' => 'Greška pri uploadu fajla.']);
                }

                $this->db->prepare("
                    INSERT INTO hr_dokumenta (zaposleni_id, naziv, tip, putanja)
                    VALUES (?, ?, ?, ?)
                ")->execute([$zid, $naziv ?: $_FILES['dokument']['name'], $tip, BASE_URL . '/uploads/hr/dokumenta/' . $filename]);

                $this->json(['ok' => true]);
                break;

            case 'hr_obrisi_dokument':
                Auth::requireKancelarija();
                $stmt = $this->db->prepare("SELECT putanja FROM hr_dokumenta WHERE id=?");
                $stmt->execute([$id]);
                $putanja = $stmt->fetchColumn();
                if ($putanja) @unlink(ROOT . str_replace('/mvc', '', $putanja));
                $this->db->prepare("DELETE FROM hr_dokumenta WHERE id=?")->execute([$id]);
                $this->json(['ok' => true]);
                break;

            default:
                $this->json(['ok' => false, 'err' => 'Nepoznata akcija.']);
        }
    }

    private function radniDani(string $od, string $do): int
    {
        $start = new \DateTime($od);
        $end   = new \DateTime($do);
        $end->modify('+1 day');
        $count = 0;
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
        foreach ($period as $day) {
            if ((int)$day->format('N') < 6) $count++;
        }
        return $count;
    }
}
