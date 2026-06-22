<?php
namespace Controllers;

use Core\Auth;

class EvidencijaController extends \Core\Controller
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::get();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $uid        = Auth::id();
        $is_admin   = Auth::isAdmin();
        $je_kancelarija = !Auth::isElektricar();

        $tab          = $_GET['tab']        ?? 'vreme';
        // Podrazumevano: samo današnji dan (i radni sati i materijal). Filter i dalje radi za druge periode.
        $filter_od    = $_GET['od']         ?? date('Y-m-d');
        $filter_do    = $_GET['do']         ?? date('Y-m-d');
        $filter_radnik= (int)($_GET['radnik']    ?? 0);
        $filter_grad  = (int)($_GET['gradiliste'] ?? 0);

        // Radnici za filter (samo admin/operater)
        $radnici = [];
        if ($je_kancelarija) {
            $radnici = $this->db->query("
                SELECT id, ime FROM admin_korisnici WHERE aktivan=1 ORDER BY ime ASC
            ")->fetchAll(\PDO::FETCH_ASSOC);
        }

        // Gradilišta za filter
        $gradilista = $this->db->query("
            SELECT id, naziv FROM gradilista ORDER BY naziv ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // ── Radni sati ───────────────────────────────────────
        $params_v = [$filter_od, $filter_do];
        $where_v  = "rv.datum BETWEEN ? AND ?";
        if (!$je_kancelarija) { $where_v .= " AND rv.radnik_id = ?"; $params_v[] = $uid; }
        elseif ($filter_radnik) { $where_v .= " AND rv.radnik_id = ?"; $params_v[] = $filter_radnik; }
        if ($filter_grad) { $where_v .= " AND (rv.gradiliste_id = ? OR rs.gradiliste_id = ?)"; $params_v[] = $filter_grad; $params_v[] = $filter_grad; }

        $vreme = $this->db->prepare("
            SELECT  rv.id, rv.datum, rv.vreme_od, rv.vreme_do, rv.ukupno_sati,
                    rv.napomena, rv.napomena_original, rv.segmenti, rv.stavka_id, rv.created_at,
                    k.ime AS radnik_ime,
                    COALESCE(rv.gradiliste_naziv, g.naziv, g2.naziv, '') AS gradiliste_naziv,
                    rs.opis AS zadatak_opis
            FROM raspored_vreme rv
            JOIN admin_korisnici k ON rv.radnik_id = k.id
            LEFT JOIN raspored_stavke rs ON rv.stavka_id = rs.id
            LEFT JOIN gradilista g  ON rv.gradiliste_id = g.id
            LEFT JOIN gradilista g2 ON rs.gradiliste_id = g2.id
            WHERE $where_v
            ORDER BY rv.datum DESC, rv.created_at DESC
        ");
        $vreme->execute($params_v);
        $vreme_unosi = $vreme->fetchAll(\PDO::FETCH_ASSOC);

        $ukupno_sati = array_sum(array_column($vreme_unosi, 'ukupno_sati'));

        // ── Utrošak materijala ───────────────────────────────
        $params_m = [$filter_od, $filter_do];
        $where_m  = "rm.datum BETWEEN ? AND ?";
        if (!$je_kancelarija) { $where_m .= " AND rm.radnik_id = ?"; $params_m[] = $uid; }
        elseif ($filter_radnik) { $where_m .= " AND rm.radnik_id = ?"; $params_m[] = $filter_radnik; }
        if ($filter_grad) { $where_m .= " AND (rm.gradiliste_id = ? OR rs2.gradiliste_id = ?)"; $params_m[] = $filter_grad; $params_m[] = $filter_grad; }

        $mat = $this->db->prepare("
            SELECT rm.id, rm.datum, rm.naziv, rm.kolicina, rm.jm,
                   rm.stavka_id, rm.created_at, COALESCE(rm.komentar,'') AS komentar,
                   k.ime AS radnik_ime,
                   COALESCE(rm.gradiliste_naziv, g.naziv, g2.naziv, '') AS gradiliste_naziv,
                   rs2.opis AS zadatak_opis
            FROM raspored_materijal rm
            JOIN admin_korisnici k ON rm.radnik_id = k.id
            LEFT JOIN raspored_stavke rs2 ON rm.stavka_id = rs2.id
            LEFT JOIN gradilista g  ON rm.gradiliste_id = g.id
            LEFT JOIN gradilista g2 ON rs2.gradiliste_id = g2.id
            WHERE $where_m
            ORDER BY rm.datum DESC, rm.created_at DESC
        ");
        $mat->execute($params_m);
        $mat_unosi = $mat->fetchAll(\PDO::FETCH_ASSOC);

        // Stanje po lokaciji (samo pozitivno) — za izbor artikla pri ručnom unosu utroška
        $magStanje = [];
        try {
            $sr = $this->db->query("
                SELECT lokacija, naziv, jm, ROUND(SUM(kolicina),3) AS stanje
                FROM magacin_promet
                GROUP BY lokacija, naziv, jm
                HAVING stanje > 0
                ORDER BY naziv ASC
            ")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($sr as $r) {
                $magStanje[$r['lokacija']][] = ['naziv' => $r['naziv'], 'jm' => $r['jm'], 'stanje' => (float)$r['stanje']];
            }
        } catch (\PDOException $e) { /* magacin_promet možda ne postoji */ }

        // ── Dnevni pregled (sumar po danu; grupisano po ekipi/zadatku ili po gradilištu) ──
        $grupisanje = (($_GET['grupa'] ?? 'ekipa') === 'gradiliste') ? 'gradiliste' : 'ekipa';
        $sumar_dan  = $filter_od;                 // izabrani dan = polje „Od"
        // Dnevni pregled je menadžerski (svi timovi) — samo kancelarija (ne teren).
        $sumar      = ($tab === 'sumar' && $je_kancelarija) ? $this->dnevniPregled($sumar_dan, $filter_grad) : [];

        $this->view('evidencija/index', compact(
            'tab', 'filter_od', 'filter_do', 'filter_radnik', 'filter_grad',
            'radnici', 'gradilista',
            'vreme_unosi', 'ukupno_sati',
            'mat_unosi', 'magStanje',
            'is_admin', 'je_kancelarija', 'uid',
            'grupisanje', 'sumar_dan', 'sumar'
        ));
    }

    /**
     * Dnevni pregled: za izabrani dan vrati listu TIMOVA (= zadataka iz rasporeda tog dana).
     * Svaki tim: gradilište + zadatak, članovi (ime, sati, opis šta su uneli), ukupno sati,
     * i utrošen materijal za CEO tim (zbirno, ne po članu). + „van rasporeda" za slobodan unos.
     */
    private function dnevniPregled(string $dan, int $filter_grad): array
    {
        // Vodi se iz STVARNIH unosa tog dana (radni sati + materijal), grupiše po zadatku
        // (stavka_id; 0 = van rasporeda). Tako se prikaže sve uneto bez obzira na to kog
        // dana/statusa je sama stavka u rasporedu.
        $vr = $this->db->prepare("
            SELECT rv.radnik_id, k.ime, k.uloga, rv.stavka_id, rv.ukupno_sati, rv.napomena,
                   COALESCE(rs.gradiliste_id, rv.gradiliste_id, 0) AS gradiliste_id,
                   COALESCE(NULLIF(rv.gradiliste_naziv,''), g.naziv, gs.naziv, '(bez gradilišta)') AS gradiliste_naziv,
                   COALESCE(rs.opis, '') AS zadatak_opis
            FROM raspored_vreme rv
            JOIN admin_korisnici k ON k.id = rv.radnik_id
            LEFT JOIN raspored_stavke rs ON rs.id = rv.stavka_id
            LEFT JOIN gradilista g  ON g.id  = rv.gradiliste_id
            LEFT JOIN gradilista gs ON gs.id = rs.gradiliste_id
            WHERE rv.datum = ?
        ");
        $vr->execute([$dan]);
        $vremeRows = $vr->fetchAll(\PDO::FETCH_ASSOC);

        $mt = $this->db->prepare("
            SELECT rm.stavka_id, rm.naziv, rm.kolicina, rm.jm, COALESCE(rm.komentar,'') AS komentar,
                   COALESCE(rs.gradiliste_id, rm.gradiliste_id, 0) AS gradiliste_id,
                   COALESCE(NULLIF(rm.gradiliste_naziv,''), g.naziv, gs.naziv, '(bez gradilišta)') AS gradiliste_naziv,
                   COALESCE(rs.opis, '') AS zadatak_opis
            FROM raspored_materijal rm
            LEFT JOIN raspored_stavke rs ON rs.id = rm.stavka_id
            LEFT JOIN gradilista g  ON g.id  = rm.gradiliste_id
            LEFT JOIN gradilista gs ON gs.id = rs.gradiliste_id
            WHERE rm.datum = ?
        ");
        $mt->execute([$dan]);
        $matRows = $mt->fetchAll(\PDO::FETCH_ASSOC);

        // Ključ grupe: stavka_id ako postoji, inače 'g:'+gradiliste (slobodan unos po gradilištu)
        $kljuc = fn($r) => !empty($r['stavka_id']) ? 'z:'.(int)$r['stavka_id'] : 'g:'.$r['gradiliste_naziv'];
        $grupe = [];
        $init = function(&$grupe, $key, $r) {
            if (!isset($grupe[$key])) {
                $grupe[$key] = [
                    'stavka_id'        => !empty($r['stavka_id']) ? (int)$r['stavka_id'] : 0,
                    'gradiliste_id'    => (int)$r['gradiliste_id'],
                    'gradiliste_naziv' => $r['gradiliste_naziv'],
                    'zadatak_opis'     => $r['zadatak_opis'],
                    'clanovi'          => [],
                    'materijal'        => [],
                ];
            }
        };

        foreach ($vremeRows as $r) {
            $key = $kljuc($r);
            $init($grupe, $key, $r);
            $rid = (int)$r['radnik_id'];
            if (!isset($grupe[$key]['clanovi'][$rid])) {
                $grupe[$key]['clanovi'][$rid] = ['ime' => $r['ime'], 'uloga' => $r['uloga'], 'sati' => 0.0, 'opisi' => []];
            }
            $grupe[$key]['clanovi'][$rid]['sati'] += (float)$r['ukupno_sati'];
            $op = trim((string)$r['napomena']);
            if ($op !== '') $grupe[$key]['clanovi'][$rid]['opisi'][] = $op;
        }
        foreach ($matRows as $r) {
            $key = $kljuc($r);
            $init($grupe, $key, $r);
            $mk = $r['naziv'].'|'.$r['jm'];
            if (!isset($grupe[$key]['materijal'][$mk])) {
                $grupe[$key]['materijal'][$mk] = ['naziv' => $r['naziv'], 'opis' => '', 'kolicina' => 0.0, 'jm' => $r['jm']];
            }
            $grupe[$key]['materijal'][$mk]['kolicina'] += (float)$r['kolicina'];
            if ($grupe[$key]['materijal'][$mk]['opis'] === '' && trim((string)$r['komentar']) !== '') {
                $grupe[$key]['materijal'][$mk]['opis'] = trim($r['komentar']);
            }
        }

        $timovi = [];
        foreach ($grupe as $g) {
            if ($filter_grad && $g['gradiliste_id'] !== $filter_grad) continue;
            $clanovi = [];
            foreach ($g['clanovi'] as $rid => $c) {
                $clanovi[] = ['radnik_id' => $rid, 'ime' => $c['ime'], 'uloga' => $c['uloga'],
                              'sati' => round($c['sati'], 2), 'opis' => implode(' • ', $c['opisi'])];
            }
            usort($clanovi, fn($a, $b) => strcmp($a['ime'], $b['ime']));
            $timovi[] = [
                'stavka_id'        => $g['stavka_id'],
                'gradiliste_id'    => $g['gradiliste_id'],
                'gradiliste_naziv' => $g['gradiliste_naziv'],
                'zadatak_opis'     => $g['stavka_id'] ? $g['zadatak_opis'] : 'Slobodan unos / van rasporeda',
                'clanovi'          => $clanovi,
                'ukupno_sati'      => array_sum(array_map(fn($c) => $c['sati'], $clanovi)),
                'materijal'        => array_values($g['materijal']),
            ];
        }

        usort($timovi, function($a, $b) {
            $c = strcmp($a['gradiliste_naziv'], $b['gradiliste_naziv']);
            return $c !== 0 ? $c : strcmp($a['zadatak_opis'], $b['zadatak_opis']);
        });

        return $timovi;
    }

    public function ajax(string $action, int $id): void
    {
        Auth::requireAdmin();
        $uid = Auth::id();

        // ── Obriši radni sat ─────────────────────────────────
        if ($action === 'evidencija_obrisi_vreme') {
            $stmt = $this->db->prepare("SELECT * FROM raspored_vreme WHERE id=?");
            $stmt->execute([$id]);
            $zapis = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$zapis) $this->json(['ok' => false, 'err' => 'Zapis nije pronađen.']);

            $this->loguj('vreme', $id, 'brisanje', $zapis, null, $uid);
            $this->db->prepare("DELETE FROM raspored_vreme WHERE id=?")->execute([$id]);
            $this->json(['ok' => true]);
        }

        // ── Obriši materijal ─────────────────────────────────
        if ($action === 'evidencija_obrisi_materijal') {
            $stmt = $this->db->prepare("SELECT * FROM raspored_materijal WHERE id=?");
            $stmt->execute([$id]);
            $zapis = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$zapis) $this->json(['ok' => false, 'err' => 'Zapis nije pronađen.']);

            $this->loguj('materijal', $id, 'brisanje', $zapis, null, $uid);
            $this->db->prepare("DELETE FROM raspored_materijal WHERE id=?")->execute([$id]);
            // Skini i pripadajući trag iz knjige prometa magacina (vrati na stanje)
            $this->db->prepare("DELETE FROM magacin_promet WHERE izvor='raspored' AND ref_id=?")->execute([$id]);
            $this->json(['ok' => true]);
        }

        // ── Ručni unos utroška (potrošnja iz magacina) ───────
        if ($action === 'evidencija_dodaj_materijal') {
            $naziv    = trim($_POST['naziv'] ?? '');
            $kolicina = (float)($_POST['kolicina'] ?? 0);
            $jm       = trim($_POST['jm'] ?? 'Kom') ?: 'Kom';
            $lokacija = trim($_POST['lokacija'] ?? '') ?: 'Magacin';
            $gid      = (int)($_POST['gradiliste_id'] ?? 0) ?: null;
            $datum    = $_POST['datum'] ?? date('Y-m-d');
            $komentar = trim($_POST['komentar'] ?? '');

            if (!$naziv || $kolicina <= 0) {
                $this->json(['ok' => false, 'err' => 'Unesi naziv i količinu veću od 0.']);
            }

            // Provera dostupne količine na lokaciji
            $sa = $this->db->prepare("SELECT COALESCE(SUM(kolicina),0) FROM magacin_promet WHERE naziv=? AND jm=? AND lokacija=?");
            $sa->execute([$naziv, $jm, $lokacija]);
            $dostupno = (float)$sa->fetchColumn();
            if ($kolicina > $dostupno + 0.0005) {
                $d = rtrim(rtrim(number_format($dostupno, 3, '.', ''), '0'), '.');
                $this->json(['ok' => false, 'err' => 'Količina je veća od dostupne (' . $d . ') na lokaciji ' . $lokacija . '.']);
            }

            // 1) Zapis u raspored_materijal (jedinstveni izvor utroška)
            $this->db->prepare("INSERT INTO raspored_materijal
                (stavka_id, radnik_id, datum, naziv, kolicina, jm, gradiliste_id, gradiliste_naziv, komentar)
                VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$uid, $datum, $naziv, $kolicina, $jm, $gid, $lokacija, $komentar]);
            $rmId = (int)$this->db->lastInsertId();

            // 2) Odmah skini sa stanja magacina (knjiga prometa), povezano sa rm zapisom
            $this->db->prepare("INSERT INTO magacin_promet
                (katalog_id, naziv, jm, lokacija, gradiliste_id, kolicina, tip, izvor, ref_id, datum, komentar, korisnik_id)
                VALUES (NULL, ?, ?, ?, ?, ?, 'potrosnja', 'raspored', ?, ?, ?, ?)")
                ->execute([$naziv, $jm, $lokacija, $gid, -$kolicina, $rmId, $datum, $komentar, $uid]);

            $this->loguj('materijal', $rmId, 'unos', null,
                ['naziv' => $naziv, 'kolicina' => $kolicina, 'jm' => $jm, 'lokacija' => $lokacija, 'komentar' => $komentar], $uid);

            $this->json(['ok' => true]);
        }

        // ── Izmeni segmente radnog sata ──────────────────────
        if ($action === 'evidencija_izmeni_segmente') {
            $stmt = $this->db->prepare("SELECT * FROM raspored_vreme WHERE id=?");
            $stmt->execute([$id]);
            $staro = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$staro) $this->json(['ok' => false, 'err' => 'Zapis nije pronađen.']);

            $segmenti_json = $_POST['segmenti'] ?? '';
            $vreme_od      = trim($_POST['vreme_od'] ?? '');
            $vreme_do      = trim($_POST['vreme_do'] ?? '');
            $datum         = trim($_POST['datum']    ?? $staro['datum']);

            $segmenti = json_decode($segmenti_json, true);
            if (!$segmenti || !is_array($segmenti)) {
                $this->json(['ok' => false, 'err' => 'Neispravan JSON segmenata.']);
            }

            // Izračunaj ukupno_sati i napomenu iz segmenata
            $ukupno_sati = array_sum(array_column($segmenti, 'sati'));
            $napomena    = implode('; ', array_map(fn($s) => $s['opis'] ?? '', $segmenti));

            $novo = array_merge($staro, [
                'datum'        => $datum,
                'vreme_od'     => $vreme_od ?: null,
                'vreme_do'     => $vreme_do ?: null,
                'ukupno_sati'  => $ukupno_sati,
                'napomena'     => $napomena,
                'segmenti'     => $segmenti_json,
            ]);

            $this->loguj('vreme', $id, 'izmena', $staro, $novo, $uid);

            $this->db->prepare("
                UPDATE raspored_vreme
                SET datum=?, vreme_od=?, vreme_do=?, ukupno_sati=?, napomena=?, segmenti=?
                WHERE id=?
            ")->execute([$datum, $vreme_od ?: null, $vreme_do ?: null, $ukupno_sati, $napomena, $segmenti_json, $id]);

            $this->json(['ok' => true]);
        }

        // ── Izmeni radni sat ─────────────────────────────────
        if ($action === 'evidencija_izmeni_vreme') {
            $stmt = $this->db->prepare("SELECT * FROM raspored_vreme WHERE id=?");
            $stmt->execute([$id]);
            $staro = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$staro) $this->json(['ok' => false, 'err' => 'Zapis nije pronađen.']);

            $vreme_od    = trim($_POST['vreme_od']    ?? '');
            $vreme_do    = trim($_POST['vreme_do']    ?? '');
            $ukupno_sati = (float)($_POST['ukupno_sati'] ?? 0);
            $napomena    = trim($_POST['napomena']    ?? '');
            $datum       = trim($_POST['datum']       ?? $staro['datum']);

            $novo = array_merge($staro, [
                'datum'       => $datum,
                'vreme_od'    => $vreme_od   ?: null,
                'vreme_do'    => $vreme_do   ?: null,
                'ukupno_sati' => $ukupno_sati ?: null,
                'napomena'    => $napomena,
            ]);

            $this->loguj('vreme', $id, 'izmena', $staro, $novo, $uid);

            $this->db->prepare("
                UPDATE raspored_vreme
                SET datum=?, vreme_od=?, vreme_do=?, ukupno_sati=?, napomena=?
                WHERE id=?
            ")->execute([$datum, $vreme_od ?: null, $vreme_do ?: null, $ukupno_sati ?: null, $napomena, $id]);

            $this->json(['ok' => true]);
        }

        // ── Izmeni materijal ─────────────────────────────────
        if ($action === 'evidencija_izmeni_materijal') {
            $stmt = $this->db->prepare("SELECT * FROM raspored_materijal WHERE id=?");
            $stmt->execute([$id]);
            $staro = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$staro) $this->json(['ok' => false, 'err' => 'Zapis nije pronađen.']);

            $naziv    = trim($_POST['naziv']    ?? '');
            $kolicina = (float)($_POST['kolicina'] ?? 0);
            $jm       = trim($_POST['jm']       ?? '');
            $datum    = trim($_POST['datum']     ?? $staro['datum']);

            if (!$naziv || $kolicina <= 0) $this->json(['ok' => false, 'err' => 'Naziv i količina su obavezni.']);

            $novo = array_merge($staro, ['datum' => $datum, 'naziv' => $naziv, 'kolicina' => $kolicina, 'jm' => $jm]);

            $this->loguj('materijal', $id, 'izmena', $staro, $novo, $uid);

            $this->db->prepare("
                UPDATE raspored_materijal SET datum=?, naziv=?, kolicina=?, jm=? WHERE id=?
            ")->execute([$datum, $naziv, $kolicina, $jm, $id]);

            // Uskladi pripadajući trag u knjizi prometa magacina (potrošnja je negativna)
            $this->db->prepare("
                UPDATE magacin_promet SET naziv=?, jm=?, kolicina=?, datum=?
                WHERE izvor='raspored' AND ref_id=?
            ")->execute([$naziv, $jm, -abs($kolicina), $datum, $id]);

            $this->json(['ok' => true]);
        }
    }

    private function loguj(string $tip, int $zapis_id, string $akcija, array $staro, ?array $novo, int $uid): void
    {
        $this->db->prepare("
            INSERT INTO evidencija_log (tip, zapis_id, akcija, korisnik_id, staro_stanje, novo_stanje)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $tip, $zapis_id, $akcija, $uid,
            json_encode($staro, JSON_UNESCAPED_UNICODE),
            $novo ? json_encode($novo, JSON_UNESCAPED_UNICODE) : null
        ]);
    }
}
