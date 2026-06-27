<?php
namespace Controllers;

use Core\Auth;

class MagacinController extends \Core\Controller
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::get();
    }

    public function index(): void
    {
        Auth::requireMagacin();

        // Kapija za pregled dokumenta ulaza (?dok=<primka_id>) — samo Direktor/AT/AF.
        if (isset($_GET['dok'])) {
            $this->streamDokument((int)$_GET['dok']);
            return;
        }

        $tab     = $_GET['tab'] ?? 'stanje';
        $jeAdmin = Auth::isAdmin();
        if ($tab === 'log' && !$jeAdmin) $tab = 'stanje'; // log vide samo Direktor/AT/AF
        if ($tab === 'kontrola' && !$jeAdmin) $tab = 'stanje'; // kontrolu vide samo Direktor/AT/AF

        // Uvuci potrošnju iz rasporeda (idempotentno) pre računanja stanja
        $this->syncPotrosnjaIzRasporeda();

        $stanjePoLokaciji = $this->getStanjePoLokaciji();

        // Kontrola (reconciliation) — samo admin, samo kad je tab otvoren
        $kontrola = [];
        if ($tab === 'kontrola' && $jeAdmin) {
            $kontrola = $this->getKontrola();
        }

        $primke = $this->db->query("
            SELECT p.*, k.ime AS kreator_ime
            FROM magacin_primke p
            JOIN admin_korisnici k ON p.kreator_id = k.id
            ORDER BY p.datum DESC, p.created_at DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($primke as &$pr) {
            // Povuci i trenutnu (kanonsku) lokaciju iz knjige prometa za svaku stavku
            $stmt = $this->db->prepare("
                SELECT s.*,
                       COALESCE(pr.lokacija, s.lokacija) AS lokacija_cur,
                       pr.gradiliste_id AS gradiliste_cur,
                       COALESCE(pr.namenjeno_gradiliste_id, s.namenjeno_gradiliste_id) AS namenjeno_cur,
                       g.naziv AS namenjeno_naziv
                FROM magacin_stavke s
                LEFT JOIN magacin_promet pr
                       ON pr.izvor = 'primka' AND pr.tip = 'ulaz' AND pr.ref_id = s.id
                LEFT JOIN gradilista g ON g.id = COALESCE(pr.namenjeno_gradiliste_id, s.namenjeno_gradiliste_id)
                WHERE s.primka_id = ?
                ORDER BY s.id ASC
            ");
            $stmt->execute([$pr['id']]);
            $pr['stavke'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        unset($pr);

        $gradilista = $this->db->query("
            SELECT id, naziv FROM gradilista WHERE status='aktivno' ORDER BY naziv
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // Audit log (samo admin, samo kad je tab otvoren)
        $log = [];
        if ($tab === 'log' && $jeAdmin) {
            $log = $this->db->query("
                SELECT l.*, k.ime AS korisnik_ime
                FROM magacin_log l
                LEFT JOIN admin_korisnici k ON k.id = l.korisnik_id
                ORDER BY l.created_at DESC, l.id DESC
                LIMIT 300
            ")->fetchAll(\PDO::FETCH_ASSOC);
        }

        $this->view('magacin/index', compact('stanjePoLokaciji', 'primke', 'gradilista', 'tab', 'jeAdmin', 'log', 'kontrola'));
    }

    /**
     * Kontrola (reconciliation): za svaki artikal (zbir preko SVIH lokacija)
     * proverava da li se račun slaže: Ulaz − Utrošeno − Stanje treba da bude 0.
     * Razlika ≠ 0 znači da je bilo ručnih izmena stanja (korekcija) ili je negde
     * greška u unosu/skidanju. Prenosi između lokacija se globalno poništavaju,
     * pa ne ulaze u jednačinu. Vraća SAMO problematične artikle (|razlika| > 0.001),
     * sortirane po veličini razlike.
     */
    private function getKontrola(): array
    {
        // SQL vraća samo agregate (bez alias-a u HAVING/ORDER BY — MariaDB to ne
        // dozvoljava za grupne funkcije). Razliku, filter i sort radimo u PHP-u.
        $rows = $this->db->query("
            SELECT mp.naziv, mp.jm,
                   ROUND(SUM(CASE WHEN mp.tip='ulaz'      THEN mp.kolicina ELSE 0 END), 3) AS ulaz,
                   ROUND(-SUM(CASE WHEN mp.tip='potrosnja' THEN mp.kolicina ELSE 0 END), 3) AS utroseno,
                   ROUND(SUM(CASE WHEN mp.tip='korekcija' THEN mp.kolicina ELSE 0 END), 3) AS korekcije,
                   ROUND(SUM(mp.kolicina), 3) AS stanje
            FROM magacin_promet mp
            GROUP BY mp.naziv, mp.jm
            ORDER BY mp.naziv ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            // razlika = ulaz − utrošeno − stanje; ≠ 0 znači da račun ne štima
            $razlika = round((float)$r['ulaz'] - (float)$r['utroseno'] - (float)$r['stanje'], 3);
            if (abs($razlika) <= 0.001) continue; // prikaži samo problematične
            $r['razlika'] = $razlika;
            $out[] = $r;
        }

        // Najveća odstupanja prva
        usort($out, fn($a, $b) => abs($b['razlika']) <=> abs($a['razlika']));
        return $out;
    }

    /**
     * Idempotentno uvlači potrošnju iz rasporeda (raspored_materijal) u knjigu
     * prometa kao 'potrosnja' (−) na lokaciji gradilišta. Već uvučeni redovi se
     * preskaču (izvor='raspored', ref_id = raspored_materijal.id).
     */
    private function syncPotrosnjaIzRasporeda(): void
    {
        try {
            $this->db->exec("
                INSERT INTO magacin_promet
                    (katalog_id, naziv, jm, lokacija, gradiliste_id, kolicina, tip, izvor, ref_id, datum, komentar, korisnik_id)
                SELECT NULL, rm.naziv, COALESCE(NULLIF(rm.jm,''),'Kom'),
                       COALESCE(g.naziv, NULLIF(rm.gradiliste_naziv,''), 'Magacin'),
                       rm.gradiliste_id, -ABS(rm.kolicina), 'potrosnja', 'raspored', rm.id, rm.datum,
                       COALESCE(rm.komentar,''), rm.radnik_id
                FROM raspored_materijal rm
                LEFT JOIN gradilista g ON rm.gradiliste_id = g.id
                WHERE rm.kolicina > 0
                  AND NOT EXISTS (
                      SELECT 1 FROM magacin_promet p WHERE p.izvor='raspored' AND p.ref_id = rm.id
                  )
            ");
        } catch (\PDOException $e) { /* raspored_materijal možda ne postoji */ }
    }

    /**
     * Stanje iz knjige prometa, grupisano po lokaciji.
     * Vraća uređenu mapu: 'Magacin' prvi (ako ima stanja), pa gradilišta abecedno.
     * Svaki red: naziv, jm, lokacija, gradiliste_id, katalog_id, stanje.
     */
    private function getStanjePoLokaciji(): array
    {
        $rows = $this->db->query("
            SELECT mp.naziv, mp.jm, mp.lokacija,
                   mp.namenjeno_gradiliste_id,
                   g.naziv AS namenjeno_naziv,
                   MAX(mp.gradiliste_id) AS gradiliste_id,
                   MAX(mp.katalog_id)    AS katalog_id,
                   ROUND(SUM(mp.kolicina), 3) AS stanje
            FROM magacin_promet mp
            LEFT JOIN gradilista g ON g.id = mp.namenjeno_gradiliste_id
            GROUP BY mp.lokacija, mp.naziv, mp.jm, mp.namenjeno_gradiliste_id
            HAVING stanje > 0
            ORDER BY mp.naziv ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['lokacija']][] = $r;
        }

        $ordered = [];
        if (isset($grouped['Magacin'])) {
            $ordered['Magacin'] = $grouped['Magacin'];
            unset($grouped['Magacin']);
        }
        ksort($grouped, SORT_FLAG_CASE | SORT_STRING);
        foreach ($grouped as $lok => $rs) {
            $ordered[$lok] = $rs;
        }
        return $ordered;
    }

    /**
     * Stanje jednog artikla na jednoj lokaciji u TAČNOJ grupi "namenjeno za"
     * (za validaciju). $namenjeno = null znači grupu bez namene (IS NULL).
     */
    private function stanjeArtikla(string $naziv, string $jm, string $lokacija, ?int $namenjeno = null): float
    {
        $sql = "SELECT COALESCE(SUM(kolicina), 0) FROM magacin_promet
                WHERE naziv = ? AND jm = ? AND lokacija = ? AND ";
        $params = [$naziv, $jm, $lokacija];
        if ($namenjeno === null) {
            $sql .= "namenjeno_gradiliste_id IS NULL";
        } else {
            $sql .= "namenjeno_gradiliste_id = ?";
            $params[] = $namenjeno;
        }
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return (float)$st->fetchColumn();
    }

    /** Ime gradilišta po id-u (za čitljiv log). */
    private function gradNaziv(?int $id): ?string
    {
        if (!$id) return null;
        $st = $this->db->prepare("SELECT naziv FROM gradilista WHERE id=?");
        $st->execute([$id]);
        return $st->fetchColumn() ?: null;
    }

    /** Audit log magacina (ko/kada/akcija/staro/novo). */
    private function loguj(string $tip, int $zapis_id, string $akcija, ?array $staro, ?array $novo, int $uid): void
    {
        $this->db->prepare("
            INSERT INTO magacin_log (tip, zapis_id, akcija, korisnik_id, staro_stanje, novo_stanje)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $tip, $zapis_id, $akcija, $uid,
            $staro ? json_encode($staro, JSON_UNESCAPED_UNICODE) : null,
            $novo  ? json_encode($novo,  JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    private function ekstraktujPdfTekst(string $putanja): string
    {
        $autoload = ROOT . '/vendor/autoload.php';
        if (!file_exists($autoload)) return '';
        require_once $autoload;
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($putanja);
            return $pdf->getText() ?: '';
        } catch (\Throwable $e) {
            error_log('[Ekošarna PDFParser] ' . $e->getMessage());
            return '';
        }
    }

    // Pronađi master artikle slične zadatom nazivu
    private function nadjiSlicneMastere(string $naziv, string $dobavljac, float $prag = 60.0): array
    {
        // Tačno poklapanje (isti naziv bez obzira na dobavljača — razmaci i case ignorisani)
        // Prioritizuje istog dobavljača ako postoji
        $stmt = $this->db->prepare("
            SELECT id, naziv, dobavljac, kataloski_broj
            FROM katalog_materijala
            WHERE aktivan=1
              AND REPLACE(LOWER(naziv),' ','') = REPLACE(LOWER(?),' ','')
            ORDER BY (dobavljac = ?) DESC
            LIMIT 1
        ");
        $stmt->execute([$naziv, $dobavljac]);
        $tacno = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($tacno) {
            return [['status' => 'tacno', 'id' => $tacno['id'], 'naziv' => $tacno['naziv'], 'dobavljac' => $tacno['dobavljac'], 'kataloski_broj' => $tacno['kataloski_broj'], 'slicnost' => 100]];
        }

        // Pretraži samo mastere (master_id IS NULL)
        $sve = $this->db->query("
            SELECT id, naziv, dobavljac, kataloski_broj
            FROM katalog_materijala
            WHERE aktivan=1 AND master_id IS NULL
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $slicni = [];
        foreach ($sve as $k) {
            similar_text(strtolower($naziv), strtolower($k['naziv']), $pct);
            if ($pct >= $prag) {
                $slicni[] = [
                    'status'         => 'predlog',
                    'id'             => $k['id'],
                    'naziv'          => $k['naziv'],
                    'dobavljac'      => $k['dobavljac'],
                    'kataloski_broj' => $k['kataloski_broj'],
                    'slicnost'       => round($pct),
                ];
            }
        }

        // Sortiraj po sličnosti
        usort($slicni, fn($a, $b) => $b['slicnost'] - $a['slicnost']);

        return array_slice($slicni, 0, 4);
    }

    public function ajax(string $action, int $id): void
    {
        Auth::requireMagacin();
        $uid = Auth::id();

        // ── Parse dokumenta ─────────────────────────────────
        if ($action === 'magacin_parse_dokument') {
            if (empty($_FILES['dokument'])) {
                $this->json(['ok' => false, 'err' => 'Nije uploadovan fajl.']);
            }

            $tmp  = $_FILES['dokument']['tmp_name'];
            $name = $_FILES['dokument']['name'];
            $size = $_FILES['dokument']['size'];
            $err  = $_FILES['dokument']['error'];

            if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
                $this->json(['ok' => false, 'err' => 'Greška pri uploadu fajla.']);
            }
            if ($size > 10 * 1024 * 1024) {
                $this->json(['ok' => false, 'err' => 'Fajl je prevelik (max 10MB).']);
            }

            $ext        = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $dozvoljeni = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
            if (!isset($dozvoljeni[$ext])) {
                $this->json(['ok' => false, 'err' => 'Dozvoljeni formati: PDF, JPG, PNG.']);
            }

            $apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? '';
            if (!$apiKey) {
                $this->json(['ok' => false, 'err' => 'ANTHROPIC_API_KEY nije postavljen.']);
            }

            $upload_dir = UPLOAD_DIR . 'magacin/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            // Blokiraj direktan HTTP pristup folderu — dokument ide samo kroz ?dok kapiju (admin).
            $hta = $upload_dir . '.htaccess';
            if (!is_file($hta)) {
                @file_put_contents($hta,
                    "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n" .
                    "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
            }
            $safename = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
            $putanja  = $upload_dir . $safename;
            move_uploaded_file($tmp, $putanja);

            if ($ext === 'pdf') {
                $pdfTekst = $this->ekstraktujPdfTekst($putanja);
                if ($pdfTekst) {
                    $systemPrompt = <<<PROMPT
Ti si asistent koji čita poslovne dokumente (otpremnice, profakture, račune) za elektromaterijal.
Dobićeš tekst ekstraktovan iz PDF dokumenta. Izvuci podatke i vrati SAMO validan JSON.

Format:
{
  "firma": "naziv firme dobavljača (ne kupca)",
  "broj_dokumenta": "broj dokumenta",
  "datum": "YYYY-MM-DD",
  "tip": "racun ili otpremnica",
  "stavke": [
    {"naziv": "naziv artikla TAČNO kako piše", "kolicina": 100, "jm": "m"}
  ]
}

Pravila:
- firma: DOBAVLJAČ koji šalje robu, nikad Ekošarna
- Nazive artikala prepiši TAČNO — kataloški kodovi nisu nazivi
- Količina je broj komada/metara, ne cena
- Datum: "11-05-26" → "2026-05-11"
- Odgovaraj SAMO validnim JSON-om
PROMPT;
                    $content = [['type' => 'text', 'text' => $systemPrompt . "\n\nTEKST DOKUMENTA:\n" . $pdfTekst]];
                } else {
                    $fileData = base64_encode(file_get_contents($putanja));
                    $content  = [
                        ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $fileData]],
                        ['type' => 'text', 'text' => $this->getImageSystemPrompt()]
                    ];
                }
            } else {
                $fileData = base64_encode(file_get_contents($putanja));
                $content  = [
                    ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $dozvoljeni[$ext], 'data' => $fileData]],
                    ['type' => 'text', 'text' => $this->getImageSystemPrompt()]
                ];
            }

            $payload = json_encode([
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 2000,
                'messages'   => [['role' => 'user', 'content' => $content]]
            ]);

            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01'],
                CURLOPT_TIMEOUT        => 60,
            ]);

            $response = curl_exec($ch);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr) $this->json(['ok' => false, 'err' => 'cURL greška: ' . $curlErr]);

            $apiData = json_decode($response, true);
            if (!empty($apiData['error'])) {
                $this->json(['ok' => false, 'err' => 'API greška: ' . ($apiData['error']['message'] ?? 'Nepoznata')]);
            }

            $rawText = trim(preg_replace(['/^```json\s*/i', '/```\s*$/i'], '', $apiData['content'][0]['text'] ?? ''));
            $parsed  = json_decode($rawText, true);
            if (!$parsed) $this->json(['ok' => false, 'err' => 'AI nije mogao da pročita dokument.']);

            $this->json(['ok' => true, 'data' => $parsed, 'fajl' => $safename]);
        }

        // ── Proveri katalog za sve stavke ────────────────────
        if ($action === 'magacin_proveri_katalog') {
            $stavke_json = $_POST['stavke'] ?? '[]';
            $dobavljac   = trim($_POST['dobavljac'] ?? '');
            $stavke      = json_decode($stavke_json, true) ?: [];

            $rezultati = [];
            foreach ($stavke as $s) {
                $naziv   = trim($s['naziv']    ?? '');
                $kolicina = (float)($s['kolicina'] ?? 0);
                $jm      = trim($s['jm']       ?? 'Kom');

                if (!$naziv || $kolicina <= 0) continue;

                $slicni = $this->nadjiSlicneMastere($naziv, $dobavljac);

                if (!empty($slicni) && $slicni[0]['status'] === 'tacno') {
                    // Tačno poklapanje — automatski
                    $rezultati[] = [
                        'naziv'    => $naziv,
                        'kolicina' => $kolicina,
                        'jm'       => $jm,
                        'status'   => 'tacno',
                        'master'   => $slicni[0],
                        'predlozi' => [],
                    ];
                } elseif (!empty($slicni)) {
                    // Ima predloga — korisnik odlučuje
                    $rezultati[] = [
                        'naziv'    => $naziv,
                        'kolicina' => $kolicina,
                        'jm'       => $jm,
                        'status'   => 'predlog',
                        'master'   => null,
                        'predlozi' => $slicni,
                    ];
                } else {
                    // Ništa slično — novi master
                    $rezultati[] = [
                        'naziv'    => $naziv,
                        'kolicina' => $kolicina,
                        'jm'       => $jm,
                        'status'   => 'novi',
                        'master'   => null,
                        'predlozi' => [],
                    ];
                }
            }

            $ima_predloga = count(array_filter($rezultati, fn($r) => $r['status'] === 'predlog')) > 0;
            $this->json(['ok' => true, 'rezultati' => $rezultati, 'ima_predloga' => $ima_predloga]);
        }

        // ── Proveri sličnost firme ───────────────────────────
        if ($action === 'magacin_proveri_firmu') {
            $naziv = trim($_POST['naziv'] ?? '');
            if (!$naziv) $this->json(['ok' => true, 'exact' => null, 'firme' => []]);

            $stmt = $this->db->prepare("SELECT id, naziv FROM imenik_firme WHERE naziv = ? AND aktivan=1 LIMIT 1");
            $stmt->execute([$naziv]);
            $exact = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($exact) $this->json(['ok' => true, 'exact' => $exact, 'firme' => []]);

            $sve    = $this->db->query("SELECT id, naziv FROM imenik_firme WHERE aktivan=1")->fetchAll(\PDO::FETCH_ASSOC);
            $slicne = [];
            foreach ($sve as $f) {
                similar_text(strtolower($naziv), strtolower($f['naziv']), $pct);
                if ($pct >= 30) $slicne[] = ['id' => $f['id'], 'naziv' => $f['naziv'], 'slicnost' => round($pct)];
            }
            usort($slicne, fn($a, $b) => $b['slicnost'] - $a['slicnost']);
            $this->json(['ok' => true, 'exact' => null, 'firme' => array_slice($slicne, 0, 5)]);
        }

        // ── Sačuvaj primku ───────────────────────────────────
        if ($action === 'magacin_sacuvaj_primku') {
            $data_json      = $_POST['data']         ?? '{}';
            $fajl           = $_POST['fajl']         ?? '';
            $lokacija       = trim($_POST['lokacija'] ?? 'Magacin');
            $firma_id_izbor = $_POST['firma_izbor']  ?? 'nova';
            // odluke_katalog: JSON niz sa odlukama korisnika po stavci
            // svaka odluka: {naziv, kolicina, jm, lokacija, master_id (null=novi), rucni_naziv (ako rucno)}
            $odluke_json    = $_POST['odluke_katalog'] ?? '';

            $data = json_decode($data_json, true);
            if (!$data) $this->json(['ok' => false, 'err' => 'Neispravan JSON.']);

            $firma_naziv = trim($data['firma'] ?? '');
            $broj_dok    = trim($data['broj_dokumenta'] ?? '');
            $datum       = $data['datum'] ?? null;
            $tip         = in_array($data['tip'] ?? '', ['racun','otpremnica','ostalo']) ? $data['tip'] : 'otpremnica';
            $stavke      = json_decode($odluke_json, true) ?: ($data['stavke'] ?? []);

            if (empty($stavke)) $this->json(['ok' => false, 'err' => 'Nema stavki za upis.']);

            // Firma
            $firma_id = null;
            if ($firma_id_izbor === 'none') {
                $firma_id = null;
            } elseif (str_starts_with($firma_id_izbor, 'existing:')) {
                $firma_id = (int)substr($firma_id_izbor, 9);
            } else {
                if ($firma_naziv) {
                    $fInsert = $this->db->prepare("INSERT INTO imenik_firme (naziv, adresa, komentar) VALUES (?, '', 'Automatski dodata iz magacina')");
                    $fInsert->execute([$firma_naziv]);
                    $firma_id = (int)$this->db->lastInsertId();
                }
            }
            
            // Ako firma postoji u imeniku — koristi njen službeni naziv
            if ($firma_id) {
            $offStmt = $this->db->prepare("SELECT naziv FROM imenik_firme WHERE id=?");
            $offStmt->execute([$firma_id]);
            $oficijalni = $offStmt->fetchColumn();
            if ($oficijalni) $firma_naziv = $oficijalni;
                            }
            // Provera duplikata
            if ($broj_dok && $datum) {
            $dupStmt = $this->db->prepare("
            SELECT id FROM magacin_primke
            WHERE broj_dokumenta = ?
            AND datum = ?
            AND (firma_naziv = ? OR (firma_id IS NOT NULL AND firma_id = ?))
            LIMIT 1
                                        ");
    $dupStmt->execute([$broj_dok, $datum, $firma_naziv, $firma_id ?: 0]);
    if ($dupStmt->fetchColumn()) {
        $this->json(['ok' => false, 'err' => "Dokument broj {$broj_dok} od {$firma_naziv} za datum " . date('d.m.Y', strtotime($datum)) . " je već unet. Proverite duplikat."]);
    }
} 

            // Primka
            $pStmt = $this->db->prepare("
                INSERT INTO magacin_primke (firma_id, firma_naziv, broj_dokumenta, datum, tip, pdf_putanja, kreator_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $pStmt->execute([$firma_id, $firma_naziv, $broj_dok, $datum ?: null, $tip, $fajl, $uid]);
            $primka_id = (int)$this->db->lastInsertId();

            // Stavke
            foreach ($stavke as $s) {
                $naziv          = trim($s['naziv']     ?? '');
                $kolicina       = (float)($s['kolicina'] ?? 0);
                $jm             = trim($s['jm']        ?? 'Kom');
                $stavkaLokacija = trim($s['lokacija']  ?? $lokacija) ?: 'Magacin';
                $stavkaGradId   = (int)($s['gradiliste_id'] ?? 0) ?: null;
                $stavkaNamenjeno = (int)($s['namenjeno_gradiliste_id'] ?? 0) ?: null;
                $master_id_odluka = $s['master_id']   ?? null; // null=novi, broj=veži za ovaj master
                $rucni_naziv    = trim($s['rucni_naziv'] ?? '');

                if (!$naziv || $kolicina <= 0) continue;

                $katalog_id = null;

                if ($master_id_odluka === 'novi') {
                $noviNaziv = $rucni_naziv ?: $naziv;
                $katInsert = $this->db->prepare("INSERT INTO katalog_materijala (kataloski_broj, naziv, jm, dobavljac, master_id) VALUES ('', ?, ?, ?, NULL)");
                $katInsert->execute([$noviNaziv, $jm, $firma_naziv]);
                $katalog_id = (int)$this->db->lastInsertId();
                // Koristi ručni naziv i za stavku u magacinu
                if ($rucni_naziv) $naziv = $rucni_naziv;
            } elseif ($master_id_odluka !== null && (int)$master_id_odluka > 0) {
                    // Veži za postojeći master — kreiraj sinonim
                    $master_id = (int)$master_id_odluka;
                    // Prvo provjeri da li već postoji sinonim za ovog dobavljača sa ovim nazivom
                    $sinStmt = $this->db->prepare("SELECT id FROM katalog_materijala WHERE master_id=? AND dobavljac=? AND REPLACE(LOWER(naziv),' ','')=REPLACE(LOWER(?),' ','') LIMIT 1");
                    $sinStmt->execute([$master_id, $firma_naziv, $naziv]);
                    $existing = $sinStmt->fetchColumn();
                    if ($existing) {
                        $katalog_id = (int)$existing;
                    } else {
                        $sinInsert = $this->db->prepare("INSERT INTO katalog_materijala (kataloski_broj, naziv, jm, dobavljac, master_id) VALUES ('', ?, ?, ?, ?)");
                        $sinInsert->execute([$naziv, $jm, $firma_naziv, $master_id]);
                        $katalog_id = (int)$this->db->lastInsertId();
                    }
                } else {
                    // Tačno poklapanje — koristi postojeći (cross-dobavljač, prioritizuje istog)
                    $katStmt = $this->db->prepare("
                        SELECT id FROM katalog_materijala
                        WHERE aktivan=1
                          AND REPLACE(LOWER(naziv),' ','') = REPLACE(LOWER(?),' ','')
                        ORDER BY (dobavljac = ?) DESC
                        LIMIT 1
                    ");
                    $katStmt->execute([$naziv, $firma_naziv]);
                    $katalog_id = (int)($katStmt->fetchColumn() ?: 0) ?: null;

                    if (!$katalog_id) {
                        // Fallback: dodaj kao novi master
                        $katInsert = $this->db->prepare("INSERT INTO katalog_materijala (kataloski_broj, naziv, jm, dobavljac, master_id) VALUES ('', ?, ?, ?, NULL)");
                        $katInsert->execute([$naziv, $jm, $firma_naziv]);
                        $katalog_id = (int)$this->db->lastInsertId();
                    }
                }

                $sStmt = $this->db->prepare("
                    INSERT INTO magacin_stavke (primka_id, katalog_id, naziv, kolicina, jm, lokacija, gradiliste_id, namenjeno_gradiliste_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $sStmt->execute([$primka_id, $katalog_id, $naziv, $kolicina, $jm, $stavkaLokacija, $stavkaGradId, $stavkaNamenjeno]);
                $stavka_id = (int)$this->db->lastInsertId();

                // Knjiga prometa: ulaz na izabranu lokaciju
                $this->db->prepare("
                    INSERT INTO magacin_promet
                        (katalog_id, naziv, jm, lokacija, gradiliste_id, namenjeno_gradiliste_id, kolicina, tip, izvor, ref_id, datum, komentar, korisnik_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'ulaz', 'primka', ?, ?, '', ?)
                ")->execute([
                    $katalog_id, $naziv, $jm, $stavkaLokacija, $stavkaGradId, $stavkaNamenjeno, $kolicina,
                    $stavka_id, ($datum ?: date('Y-m-d')), $uid,
                ]);
            }

            $this->loguj('primka', $primka_id, 'kreiranje', null,
                ['firma' => $firma_naziv, 'broj_dokumenta' => $broj_dok, 'datum' => $datum, 'broj_stavki' => count($stavke)], $uid);

            $this->json(['ok' => true, 'primka_id' => $primka_id]);
        }

        // ── Pokret ───────────────────────────────────────────
        if ($action === 'magacin_pokret') {
            $stavka_id     = (int)($_POST['stavka_id']     ?? 0);
            $tip           = $_POST['tip']                  ?? 'izlaz';
            $kolicina      = (float)($_POST['kolicina']    ?? 0);
            $gradiliste_id = (int)($_POST['gradiliste_id'] ?? 0) ?: null;
            $lokacija_iz   = trim($_POST['lokacija_iz']    ?? '');
            $lokacija_do   = trim($_POST['lokacija_do']    ?? '');
            $datum         = $_POST['datum']                ?? date('Y-m-d');
            $napomena      = trim($_POST['napomena']        ?? '');

            if (!$stavka_id || $kolicina <= 0) $this->json(['ok' => false, 'err' => 'Nedostaju podaci.']);

            $rStmt = $this->db->prepare("
                SELECT s.kolicina
                    - COALESCE(SUM(CASE WHEN p.tip='izlaz' THEN p.kolicina ELSE 0 END), 0)
                    + COALESCE(SUM(CASE WHEN p.tip='povrat' THEN p.kolicina ELSE 0 END), 0) AS stanje
                FROM magacin_stavke s
                LEFT JOIN magacin_pokreti p ON p.stavka_id = s.id
                WHERE s.id = ?
                GROUP BY s.id
            ");
            $rStmt->execute([$stavka_id]);
            $raspolozivo = (float)($rStmt->fetchColumn() ?? 0);

            if ($tip === 'izlaz' && $kolicina > $raspolozivo) {
                $this->json(['ok' => false, 'err' => "Nedovoljno na stanju. Raspoloživo: {$raspolozivo}"]);
            }

            $this->db->prepare("
                INSERT INTO magacin_pokreti (stavka_id, tip, kolicina, gradiliste_id, lokacija_iz, lokacija_do, datum, napomena, korisnik_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([$stavka_id, $tip, $kolicina, $gradiliste_id, $lokacija_iz, $lokacija_do, $datum, $napomena, $uid]);

            $this->json(['ok' => true]);
        }

        // ── Prenos na drugu lokaciju (deli količinu, ništa se ne gubi) ──
        if ($action === 'magacin_prenos') {
            $naziv      = trim($_POST['naziv'] ?? '');
            $jm         = trim($_POST['jm'] ?? 'Kom');
            $katalog_id = (int)($_POST['katalog_id'] ?? 0) ?: null;
            $lok_iz     = trim($_POST['lokacija_iz'] ?? '');
            $gid_iz     = (int)($_POST['gradiliste_iz'] ?? 0) ?: null;
            $lok_do     = trim($_POST['lokacija_do'] ?? '');
            $gid_do     = (int)($_POST['gradiliste_do'] ?? 0) ?: null;
            $nam_iz     = (int)($_POST['namenjeno_iz'] ?? 0) ?: null; // grupa "namenjeno za" izvora
            $nam_do     = (int)($_POST['namenjeno_do'] ?? 0) ?: null; // izabrana namena za odredište
            $kolicina   = (float)($_POST['kolicina'] ?? 0);
            $datum      = $_POST['datum'] ?? date('Y-m-d');
            $napomena   = trim($_POST['napomena'] ?? '');

            if (!$naziv || $kolicina <= 0 || !$lok_iz || !$lok_do) {
                $this->json(['ok' => false, 'err' => 'Nedostaju podaci.']);
            }
            if ($lok_iz === $lok_do && $nam_iz === $nam_do) {
                $this->json(['ok' => false, 'err' => 'Izvor i odredište su isti (lokacija i namena).']);
            }

            $raspolozivo = $this->stanjeArtikla($naziv, $jm, $lok_iz, $nam_iz);
            if ($kolicina > $raspolozivo + 0.0005) {
                $dostupno = rtrim(rtrim(number_format($raspolozivo, 3, '.', ''), '0'), '.');
                $this->json(['ok' => false, 'err' => 'Nedovoljno na lokaciji ' . $lok_iz . '. Raspoloživo: ' . $dostupno]);
            }

            $ins = $this->db->prepare("INSERT INTO magacin_promet
                (katalog_id, naziv, jm, lokacija, gradiliste_id, namenjeno_gradiliste_id, kolicina, tip, izvor, ref_id, datum, komentar, korisnik_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'rucno', NULL, ?, ?, ?)");
            $ins->execute([$katalog_id, $naziv, $jm, $lok_iz, $gid_iz, $nam_iz, -$kolicina, 'prenos_iz', $datum, $napomena, $uid]);
            $ins->execute([$katalog_id, $naziv, $jm, $lok_do, $gid_do, $nam_do,  $kolicina, 'prenos_do', $datum, $napomena, $uid]);

            $this->loguj('prenos', 0, 'prenos',
                ['naziv' => $naziv, 'jm' => $jm, 'lokacija' => $lok_iz, 'namenjeno' => $this->gradNaziv($nam_iz), 'kolicina' => $kolicina],
                ['lokacija' => $lok_do, 'namenjeno' => $this->gradNaziv($nam_do), 'kolicina' => $kolicina, 'napomena' => $napomena], $uid);

            $this->json(['ok' => true]);
        }

        // ── Uredi stavku ulaza (pun edit) + log; sinhronizuje 'ulaz' u prometu ──
        if ($action === 'magacin_uredi_stavku') {
            $stavka_id = (int)($_POST['stavka_id'] ?? 0);
            $nv_naziv  = trim($_POST['naziv'] ?? '');
            $nv_kol    = (float)($_POST['kolicina'] ?? 0);
            $nv_jm     = trim($_POST['jm'] ?? 'Kom');
            $lokacija  = trim($_POST['lokacija'] ?? 'Magacin') ?: 'Magacin';
            $gid       = (int)($_POST['gradiliste_id'] ?? 0) ?: null;
            $namenjeno = (int)($_POST['namenjeno_gradiliste_id'] ?? 0) ?: null;

            if (!$stavka_id || !$nv_naziv || $nv_kol <= 0) {
                $this->json(['ok' => false, 'err' => 'Nedostaju podaci ili je količina 0.']);
            }

            $st = $this->db->prepare("SELECT * FROM magacin_stavke WHERE id=?");
            $st->execute([$stavka_id]);
            $staro = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$staro) $this->json(['ok' => false, 'err' => 'Stavka nije pronađena.']);

            // 1) Ažuriraj stavku prijema
            $this->db->prepare("
                UPDATE magacin_stavke SET naziv=?, kolicina=?, jm=?, lokacija=?, gradiliste_id=?, namenjeno_gradiliste_id=? WHERE id=?
            ")->execute([$nv_naziv, $nv_kol, $nv_jm, $lokacija, $gid, $namenjeno, $stavka_id]);

            // 2) Sinhronizuj pripadajući 'ulaz' u knjizi prometa (ili ga kreiraj ako fali)
            $updPromet = $this->db->prepare("
                UPDATE magacin_promet
                SET naziv=?, kolicina=?, jm=?, lokacija=?, gradiliste_id=?, namenjeno_gradiliste_id=?
                WHERE izvor='primka' AND tip='ulaz' AND ref_id=?
            ");
            $updPromet->execute([$nv_naziv, $nv_kol, $nv_jm, $lokacija, $gid, $namenjeno, $stavka_id]);
            if ($updPromet->rowCount() === 0) {
                $this->db->prepare("INSERT INTO magacin_promet
                    (katalog_id, naziv, jm, lokacija, gradiliste_id, namenjeno_gradiliste_id, kolicina, tip, izvor, ref_id, datum, komentar, korisnik_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'ulaz', 'primka', ?, ?, '', ?)")
                    ->execute([$staro['katalog_id'] ?: null, $nv_naziv, $nv_jm, $lokacija, $gid, $namenjeno, $nv_kol, $stavka_id, date('Y-m-d'), $uid]);
            }

            $this->loguj('stavka', $stavka_id, 'izmena',
                ['naziv' => $staro['naziv'], 'kolicina' => (float)$staro['kolicina'], 'jm' => $staro['jm'], 'lokacija' => $staro['lokacija'], 'namenjeno' => $this->gradNaziv($staro['namenjeno_gradiliste_id'] !== null ? (int)$staro['namenjeno_gradiliste_id'] : null)],
                ['naziv' => $nv_naziv, 'kolicina' => $nv_kol, 'jm' => $nv_jm, 'lokacija' => $lokacija, 'namenjeno' => $this->gradNaziv($namenjeno)], $uid);

            $this->json(['ok' => true]);
        }

        // ── Premesti SVE sa jedne lokacije na drugu (ručno spajanje/vezivanje) ──
        if ($action === 'magacin_premesti_lokaciju') {
            $lok_iz = trim($_POST['lokacija_iz'] ?? '');
            $lok_do = trim($_POST['lokacija_do'] ?? '');
            $gid_do = (int)($_POST['gradiliste_do'] ?? 0) ?: null;

            if (!$lok_iz || !$lok_do) {
                $this->json(['ok' => false, 'err' => 'Nedostaju podaci.']);
            }
            if ($lok_iz === $lok_do) {
                $this->json(['ok' => false, 'err' => 'Izvor i odredište su iste lokacije.']);
            }

            // Svi artikli sa stanjem != 0 na izvornoj lokaciji (po grupi "namenjeno za")
            $st = $this->db->prepare("
                SELECT naziv, jm, namenjeno_gradiliste_id, MAX(katalog_id) AS katalog_id, ROUND(SUM(kolicina),3) AS stanje
                FROM magacin_promet
                WHERE lokacija = ?
                GROUP BY naziv, jm, namenjeno_gradiliste_id
                HAVING stanje <> 0
            ");
            $st->execute([$lok_iz]);
            $artikli = $st->fetchAll(\PDO::FETCH_ASSOC);

            if (!$artikli) {
                $this->json(['ok' => false, 'err' => 'Na toj lokaciji nema artikala na stanju.']);
            }

            $datum = date('Y-m-d');
            $ins = $this->db->prepare("INSERT INTO magacin_promet
                (katalog_id, naziv, jm, lokacija, gradiliste_id, namenjeno_gradiliste_id, kolicina, tip, izvor, ref_id, datum, komentar, korisnik_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'rucno', NULL, ?, ?, ?)");

            $napomena = "Premešteno sa: {$lok_iz}";
            foreach ($artikli as $a) {
                $kol = (float)$a['stanje'];
                $kat = $a['katalog_id'] ?: null;
                $nam = $a['namenjeno_gradiliste_id'] !== null ? (int)$a['namenjeno_gradiliste_id'] : null; // zadrži namenu
                $ins->execute([$kat, $a['naziv'], $a['jm'], $lok_iz, null,    $nam, -$kol, 'prenos_iz', $datum, "Spajanje na {$lok_do}", $uid]);
                $ins->execute([$kat, $a['naziv'], $a['jm'], $lok_do, $gid_do, $nam,  $kol, 'prenos_do', $datum, $napomena, $uid]);
            }

            $this->loguj('lokacija', 0, 'premesti',
                ['lokacija' => $lok_iz, 'broj_artikala' => count($artikli)],
                ['lokacija' => $lok_do], $uid);

            $this->json(['ok' => true, 'premeseno' => count($artikli)]);
        }

        // ── Izmena stanja (pun edit naziv/JM/količina) + log ──
        if ($action === 'magacin_izmeni_stanje') {
            $st_naziv   = trim($_POST['stari_naziv'] ?? '');
            $st_jm      = trim($_POST['stari_jm'] ?? 'Kom');
            $lokacija   = trim($_POST['lokacija'] ?? '');
            $gid        = (int)($_POST['gradiliste_id'] ?? 0) ?: null;
            $katalog_id = (int)($_POST['katalog_id'] ?? 0) ?: null;
            $nam_staro  = (int)($_POST['namenjeno_staro'] ?? 0) ?: null; // grupa koja se menja
            $nam_novo   = (int)($_POST['namenjeno_gradiliste_id'] ?? 0) ?: null; // izabrana namena
            $nv_naziv   = trim($_POST['novi_naziv'] ?? '');
            $nv_jm      = trim($_POST['novi_jm'] ?? 'Kom');
            $nv_kol     = (float)($_POST['nova_kolicina'] ?? 0);

            if (!$st_naziv || !$lokacija || !$nv_naziv) {
                $this->json(['ok' => false, 'err' => 'Nedostaju podaci.']);
            }

            $staroStanje = $this->stanjeArtikla($st_naziv, $st_jm, $lokacija, $nam_staro);
            $datum = date('Y-m-d');

            // Strukturna izmena = dira i druge redove → frontend mora pun reload.
            // (a) preimenovanje/JM kaskadira na sve lokacije;
            $renamed = ($nv_naziv !== $st_naziv || $nv_jm !== $st_jm);

            // 1) Preimenovanje / JM — na svim lokacijama radi konzistentnosti (uradi prvo)
            if ($renamed) {
                $this->db->prepare("UPDATE magacin_promet SET naziv=?, jm=? WHERE naziv=? AND jm=?")
                    ->execute([$nv_naziv, $nv_jm, $st_naziv, $st_jm]);
            }

            // (b) promena namene u grupu koja na toj lokaciji već ima stanje → spajanje redova.
            $strukturna = $renamed;
            if ($nam_novo !== $nam_staro) {
                $ciljnoPostojece = $this->stanjeArtikla($nv_naziv, $nv_jm, $lokacija, $nam_novo);
                if (abs($ciljnoPostojece) >= 0.0005) $strukturna = true;
            }

            $korekcija = $this->db->prepare("INSERT INTO magacin_promet
                (katalog_id, naziv, jm, lokacija, gradiliste_id, namenjeno_gradiliste_id, kolicina, tip, izvor, ref_id, datum, komentar, korisnik_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'korekcija', 'edit', NULL, ?, 'Izmena stanja', ?)");

            if ($nam_novo === $nam_staro) {
                // 2a) Ista namena: samo korekcija količine na toj grupi
                $delta = round($nv_kol - $staroStanje, 3);
                if (abs($delta) >= 0.0005) {
                    $korekcija->execute([$katalog_id, $nv_naziv, $nv_jm, $lokacija, $gid, $nam_staro, $delta, $datum, $uid]);
                }
            } else {
                // 2b) Promenjena namena: isprazni staru grupu, postavi novu na nv_kol
                if (abs($staroStanje) >= 0.0005) {
                    $korekcija->execute([$katalog_id, $nv_naziv, $nv_jm, $lokacija, $gid, $nam_staro, -$staroStanje, $datum, $uid]);
                }
                if (abs($nv_kol) >= 0.0005) {
                    $korekcija->execute([$katalog_id, $nv_naziv, $nv_jm, $lokacija, $gid, $nam_novo, $nv_kol, $datum, $uid]);
                }
            }

            $this->loguj('stanje', 0, 'izmena',
                ['naziv' => $st_naziv, 'jm' => $st_jm, 'lokacija' => $lokacija, 'namenjeno' => $this->gradNaziv($nam_staro), 'stanje' => $staroStanje],
                ['naziv' => $nv_naziv, 'jm' => $nv_jm, 'lokacija' => $lokacija, 'namenjeno' => $this->gradNaziv($nam_novo), 'stanje' => $nv_kol], $uid);

            $this->json(['ok' => true, 'reload' => $strukturna]);
        }

        // ── Obriši primku ────────────────────────────────────
        if ($action === 'magacin_obrisi_primku') {
            // Brisati ulaz mogu samo Direktor/AT/AF.
            if (!Auth::isAdmin()) {
                $this->json(['ok' => false, 'err' => 'Brisanje ulaza mogu samo Direktor, AT i AF.']);
            }
            $stmt = $this->db->prepare("SELECT firma_naziv, broj_dokumenta, datum, pdf_putanja FROM magacin_primke WHERE id=?");
            $stmt->execute([$id]);
            $primka = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$primka) { $this->json(['ok' => false, 'err' => 'Primka nije pronađena.']); }

            // Šta je ova primka donela na stanje, grupisano po lokaciji/artiklu/JM/nameni
            $ulazi = $this->db->prepare("
                SELECT mp.lokacija, mp.naziv, mp.jm, mp.namenjeno_gradiliste_id,
                       ROUND(SUM(mp.kolicina), 3) AS ulaz
                FROM magacin_promet mp
                JOIN magacin_stavke s ON s.id = mp.ref_id
                WHERE mp.izvor = 'primka' AND mp.tip = 'ulaz' AND s.primka_id = ?
                GROUP BY mp.lokacija, mp.naziv, mp.jm, mp.namenjeno_gradiliste_id
            ");
            $ulazi->execute([$id]);
            $grupe = $ulazi->fetchAll(\PDO::FETCH_ASSOC);

            // Zaštita: ako bi uklanjanje ulaza gurnulo stanje u minus → deo robe je već potrošen/prenet
            $blokirani = [];
            $stChk = $this->db->prepare("
                SELECT COALESCE(SUM(kolicina), 0)
                FROM magacin_promet
                WHERE naziv = ? AND jm = ? AND lokacija = ? AND namenjeno_gradiliste_id <=> ?
            ");
            foreach ($grupe as $g) {
                $stChk->execute([$g['naziv'], $g['jm'], $g['lokacija'], $g['namenjeno_gradiliste_id']]);
                $stanje = (float)$stChk->fetchColumn();
                if ($stanje - (float)$g['ulaz'] < -0.0005) {
                    $blokirani[] = $g['naziv'] . ' (' . $g['lokacija'] . ')';
                }
            }
            if ($blokirani) {
                $this->json(['ok' => false, 'err' =>
                    'Brisanje nije moguće — sa ovog ulaza je deo robe već potrošen ili prenet: '
                    . implode(', ', array_unique($blokirani))
                    . '. Prvo poništi te promete (utrošak/prenos), pa onda obriši ulaz.']);
            }

            // Čisto je → ukloni pripadajuće 'ulaz' redove iz knjige prometa (pre kaskadnog brisanja stavki)
            $this->db->prepare("
                DELETE mp FROM magacin_promet mp
                JOIN magacin_stavke s ON s.id = mp.ref_id
                WHERE mp.izvor = 'primka' AND mp.tip = 'ulaz' AND s.primka_id = ?
            ")->execute([$id]);

            // PDF + sama primka (FK kaskada briše magacin_stavke)
            $putanja = $primka['pdf_putanja'] ?? '';
            if ($putanja && file_exists(UPLOAD_DIR . 'magacin/' . $putanja)) {
                @unlink(UPLOAD_DIR . 'magacin/' . $putanja);
            }
            $this->db->prepare("DELETE FROM magacin_primke WHERE id=?")->execute([$id]);

            $this->loguj('primka', $id, 'brisanje',
                ['firma' => $primka['firma_naziv'], 'broj_dokumenta' => $primka['broj_dokumenta'], 'datum' => $primka['datum']],
                null, $uid);
            $this->json(['ok' => true]);
        }
    }

    /**
     * Strim dokumenta ulaza (otpremnica/račun) uz proveru pristupa.
     * Videti sme SAMO Direktor/AT/AF (Auth::isAdmin()) — niko drugi, ni operativa
     * ni korisnici sa vidi_magacin flagom. Fajl se čita sa diska (readfile), pa
     * .htaccess „Require all denied" nad folderom ne smeta. ?download=1 forsira
     * preuzimanje (attachment), inače inline (za openModal pregled).
     */
    private function streamDokument(int $primka_id): void
    {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo 'Nemate pristup ovom dokumentu. Vide ga samo Direktor, AT i AF.';
            exit;
        }

        $st = $this->db->prepare("SELECT pdf_putanja FROM magacin_primke WHERE id=?");
        $st->execute([$primka_id]);
        $putanja = (string)$st->fetchColumn();
        if ($putanja === '') { http_response_code(404); echo 'Dokument ne postoji.'; exit; }

        $path = UPLOAD_DIR . 'magacin/' . $putanja;
        if (!is_file($path)) { http_response_code(404); echo 'Fajl nije pronađen na disku.'; exit; }

        $ext   = strtolower(pathinfo($putanja, PATHINFO_EXTENSION));
        $dispo = isset($_GET['download']) ? 'attachment' : 'inline';
        $naziv = 'dokument-' . $primka_id . '.' . $ext;
        header('Content-Type: ' . $this->mimeFromExt($ext));
        header("Content-Disposition: $dispo; filename=\"" . $naziv . "\"");
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($path);
        exit;
    }

    /** Grub MIME iz ekstenzije (fallback kad browser ne pošalje tip). */
    private function mimeFromExt(string $ext): string
    {
        $map = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif',  'webp' => 'image/webp', 'bmp' => 'image/bmp',
            'pdf' => 'application/pdf',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }

    private function getImageSystemPrompt(): string
    {
        return <<<PROMPT
Ti si asistent koji čita slike otpremnica, profaktura i računa za elektromaterijal.
Izvuci podatke i vrati SAMO validan JSON bez ikakvog teksta pre ili posle, bez markdown oznaka.

Format:
{
  "firma": "naziv firme dobavljača (ne kupca)",
  "broj_dokumenta": "broj dokumenta",
  "datum": "YYYY-MM-DD",
  "tip": "racun ili otpremnica",
  "stavke": [
    {"naziv": "naziv artikla TAČNO kako piše", "kolicina": 100, "jm": "m"}
  ]
}

Pravila:
- firma: DOBAVLJAČ koji šalje robu, nikad Ekošarna
- Nazive artikala prepiši TAČNO — kataloški kodovi nisu nazivi
- Količina je broj komada/metara, ne cena
- Datum: "11-05-26" → "2026-05-11"
- Odgovaraj SAMO validnim JSON-om
PROMPT;
    }
}
