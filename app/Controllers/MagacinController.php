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
        Auth::requireAdmin();

        $tab = $_GET['tab'] ?? 'stanje';

        $stanje = $this->getStanje();

        $primke = $this->db->query("
            SELECT p.*, k.ime AS kreator_ime
            FROM magacin_primke p
            JOIN admin_korisnici k ON p.kreator_id = k.id
            ORDER BY p.datum DESC, p.created_at DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($primke as &$pr) {
            $stmt = $this->db->prepare("SELECT * FROM magacin_stavke WHERE primka_id = ? ORDER BY id ASC");
            $stmt->execute([$pr['id']]);
            $pr['stavke'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $gradilista = $this->db->query("
            SELECT id, naziv FROM gradilista WHERE status='aktivno' ORDER BY naziv
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('magacin/index', compact('stanje', 'primke', 'gradilista', 'tab'));
    }

    private function getStanje(): array
    {
        return $this->db->query("
            SELECT
                s.id, s.naziv, s.jm, s.lokacija,
                s.kolicina AS primljeno,
                COALESCE(SUM(CASE WHEN p.tip = 'izlaz' THEN p.kolicina ELSE 0 END), 0) AS izdato,
                COALESCE(SUM(CASE WHEN p.tip = 'povrat' THEN p.kolicina ELSE 0 END), 0) AS povraceno,
                s.kolicina
                - COALESCE(SUM(CASE WHEN p.tip = 'izlaz' THEN p.kolicina ELSE 0 END), 0)
                + COALESCE(SUM(CASE WHEN p.tip = 'povrat' THEN p.kolicina ELSE 0 END), 0) AS stanje,
                pr.firma_naziv,
                pr.datum AS datum_prijema
            FROM magacin_stavke s
            JOIN magacin_primke pr ON s.primka_id = pr.id
            LEFT JOIN magacin_pokreti p ON p.stavka_id = s.id
            GROUP BY s.id
            HAVING stanje > 0
            ORDER BY s.naziv ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);
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
        Auth::requireAdmin();
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
            $lokacija       = trim($_POST['lokacija'] ?? 'Kombi');
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
                $stavkaLokacija = trim($s['lokacija']  ?? $lokacija);
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
                    INSERT INTO magacin_stavke (primka_id, katalog_id, naziv, kolicina, jm, lokacija)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $sStmt->execute([$primka_id, $katalog_id, $naziv, $kolicina, $jm, $stavkaLokacija]);
            }

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

        // ── Obriši primku ────────────────────────────────────
        if ($action === 'magacin_obrisi_primku') {
            $stmt = $this->db->prepare("SELECT pdf_putanja FROM magacin_primke WHERE id=?");
            $stmt->execute([$id]);
            $putanja = $stmt->fetchColumn();
            if ($putanja && file_exists(UPLOAD_DIR . 'magacin/' . $putanja)) {
                @unlink(UPLOAD_DIR . 'magacin/' . $putanja);
            }
            $this->db->prepare("DELETE FROM magacin_primke WHERE id=?")->execute([$id]);
            $this->json(['ok' => true]);
        }
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
