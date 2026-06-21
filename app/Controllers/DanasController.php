<?php
namespace Controllers;

use Core\Auth;

class DanasController extends \Core\Controller
{
    // Ko dobija push obaveštenje kada stigne nova nabavka
    const NABAVKA_NOTIFIKACIJE = ['AT', 'AF'];

    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::get();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $uid = Auth::id();

        if (isset($_GET['datum'])) {
            $datum = $_GET['datum'];
        } else {
            $datum = $this->najblizjiDanSaRasporedom($uid);
        }

$datumi = [];
$dodato = 0;
$offset = -1;
while ($dodato < 4) {
    $d = date('Y-m-d', strtotime($datum . " $offset days"));
    if (date('w', strtotime($d)) !== '0') {
        $datumi[] = $d;
        $dodato++;
    }
    $offset++;
}

        $dani = [];
        foreach ($datumi as $d) {
            // Prikaži zadatak ako je korisnik RADNIK na njemu ILI je „odgovoran za unos
            // materijala" (i ako nije radnik). Nacrti se ne prikazuju (samo objavljeno).
            $stmt = $this->db->prepare("
                SELECT rs.*,
                       g.naziv AS gradiliste_naziv,
                       rd.boja,
                       rd.datum AS dan_datum,
                       rr.vreme_od,
                       rr.vreme_do,
                       ko.ime AS odgovoran_ime
                FROM raspored_stavke rs
                JOIN raspored_dani rd    ON rs.dan_id = rd.id
                JOIN radne_nedelje rn    ON rd.nedelja_id = rn.id
                LEFT JOIN raspored_radnici rr ON rr.stavka_id = rs.id AND rr.radnik_id = ?
                LEFT JOIN gradilista g   ON rs.gradiliste_id = g.id
                LEFT JOIN admin_korisnici ko ON rs.odgovoran_id = ko.id
                WHERE rd.datum = ?
                  AND rs.status = 'objavljeno'
                  AND (rr.radnik_id = ? OR rs.odgovoran_id = ?)
                ORDER BY rr.vreme_od ASC, rs.id ASC
            ");
            $stmt->execute([$uid, $d, $uid, $uid]);
            $stavke = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($stavke as &$s) {
                $pc = $this->db->prepare("SELECT COUNT(*) FROM raspored_poruke WHERE stavka_id=?");
                $pc->execute([$s['id']]);
                $s['poruka_count'] = (int)$pc->fetchColumn();

                $moja = $this->db->prepare("SELECT MAX(created_at) FROM raspored_poruke WHERE stavka_id=? AND autor_id=?");
                $moja->execute([$s['id'], $uid]);
                $poslednja_moja = $moja->fetchColumn();

                $vStmt = $this->db->prepare("SELECT vidjeno_do FROM raspored_vidjeno WHERE stavka_id=? AND korisnik_id=?");
                $vStmt->execute([$s['id'], $uid]);
                $vidjeno_do = $vStmt->fetchColumn();

                $referentni = $poslednja_moja;
                if ($vidjeno_do && (!$referentni || $vidjeno_do > $referentni)) {
                    $referentni = $vidjeno_do;
                }

                if ($referentni) {
                    $nova = $this->db->prepare("
                        SELECT COUNT(*) FROM raspored_poruke
                        WHERE stavka_id=? AND autor_id!=? AND created_at > ?
                    ");
                    $nova->execute([$s['id'], $uid, $referentni]);
                    $s['nove_poruke_count'] = (int)$nova->fetchColumn();
                    $s['nova_poruka'] = $s['nove_poruke_count'] > 0;
                } else {
                    $s['nova_poruka'] = false;
                    $s['nove_poruke_count'] = 0;
                }
            }

            $dani[$d] = $stavke;
        }

        // Gradilišta za slobodan unos
        $gradilista = $this->db->query("
            SELECT id, naziv FROM gradilista WHERE status='aktivno' ORDER BY naziv ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('danas/index', compact('dani', 'datum', 'datumi', 'uid', 'gradilista'));
    }

    private function najblizjiDanSaRasporedom(int $uid): string
    {
        $stmt = $this->db->prepare("
            SELECT rd.datum
            FROM raspored_radnici rr
            JOIN raspored_stavke rs ON rr.stavka_id = rs.id
            JOIN raspored_dani rd   ON rs.dan_id = rd.id
            WHERE rr.radnik_id = ?
              AND rd.datum >= ?
            ORDER BY rd.datum ASC
            LIMIT 1
        ");
        $stmt->execute([$uid, date('Y-m-d')]);
        $datum = $stmt->fetchColumn();

        if (!$datum) {
            $stmt = $this->db->prepare("
                SELECT rd.datum
                FROM raspored_radnici rr
                JOIN raspored_stavke rs ON rr.stavka_id = rs.id
                JOIN raspored_dani rd   ON rs.dan_id = rd.id
                WHERE rr.radnik_id = ?
                  AND rd.datum < ?
                ORDER BY rd.datum DESC
                LIMIT 1
            ");
            $stmt->execute([$uid, date('Y-m-d')]);
            $datum = $stmt->fetchColumn();
        }

        return $datum ?: date('Y-m-d');
    }

    public function ajax(string $action, int $id): void
    {
        Auth::requireLogin();
        $uid = Auth::id();

        switch ($action) {

            case 'danas_poruke_get':
                $stavka_id = (int)($_POST['stavka_id'] ?? $id);
                $stmt = $this->db->prepare("
                    SELECT rp.*, k.ime AS autor
                    FROM raspored_poruke rp
                    JOIN admin_korisnici k ON rp.autor_id = k.id
                    WHERE rp.stavka_id = ?
                    ORDER BY rp.created_at ASC
                ");
                $stmt->execute([$stavka_id]);
                $poruke = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($poruke as &$p) { $p['moja'] = ($p['autor_id'] == $uid); }
                $this->json(['ok' => true, 'poruke' => $poruke]);
                break;

            case 'danas_poruka_add':
                $stavka_id = (int)($_POST['stavka_id'] ?? 0);
                $sadrzaj   = trim($_POST['sadrzaj'] ?? '');
                if (!$sadrzaj || !$stavka_id) $this->json(['ok' => false, 'err' => 'Prazna poruka.']);

                $check = $this->db->prepare("SELECT 1 FROM raspored_radnici WHERE stavka_id=? AND radnik_id=?");
                $check->execute([$stavka_id, $uid]);
                if (!$check->fetch()) $this->json(['ok' => false, 'err' => 'Nemate pristup.']);

                $stmt = $this->db->prepare("INSERT INTO raspored_poruke (stavka_id, autor_id, sadrzaj) VALUES (?, ?, ?)");
                $stmt->execute([$stavka_id, $uid, $sadrzaj]);

                $pisaliStmt = $this->db->prepare("
                    SELECT DISTINCT autor_id FROM raspored_poruke
                    WHERE stavka_id=? AND autor_id!=?
                ");
                $pisaliStmt->execute([$stavka_id, $uid]);
                foreach ($pisaliStmt->fetchAll(\PDO::FETCH_COLUMN) as $primalac_id) {
                    $this->notifikuj((int)$primalac_id, Auth::ime(), 'Nova poruka na rasporedu');
                }

                $this->json(['ok' => true]);
                break;

            case 'raspored_oznaci_vidjeno':
                $stavka_id = (int)($_POST['stavka_id'] ?? 0);
                if ($stavka_id) {
                    $this->db->prepare("
                        INSERT INTO raspored_vidjeno (korisnik_id, stavka_id, vidjeno_do)
                        VALUES (?, ?, NOW())
                        ON DUPLICATE KEY UPDATE vidjeno_do = NOW()
                    ")->execute([$uid, $stavka_id]);
                }
                $this->json(['ok' => true]);
                break;

            case 'danas_ai_parse':
                $tekst = trim($_POST['tekst'] ?? '');
                $tip   = in_array($_POST['tip'] ?? '', ['vreme', 'materijal', 'nabavka']) ? $_POST['tip'] : 'vreme';

                if (!$tekst) $this->json(['ok' => false, 'err' => 'Tekst je prazan.']);

                $apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? '';
                if (!$apiKey) $this->json(['ok' => false, 'err' => 'ANTHROPIC_API_KEY nije postavljen.']);

                if ($tip === 'vreme') {
                    $systemPrompt = <<<PROMPT
Ti si asistent koji parsira dnevne izveštaje električara na srpskom jeziku.
Iz slobodnog teksta izvuci vreme rada i opis izvedenih radova.
Vrati ISKLJUČIVO validan JSON bez ikakvog dodatnog teksta, bez markdown oznaka i bez objašnjenja.

Format odgovora kada postoje SEGMENTI (više vrsta posla sa različitim trajanjem):
{"vreme_od":"07:00","vreme_do":"16:00","ukupno_sati":9,"napomena_original":"tačan unos radnika bez izmena","segmenti":[{"opis_original":"Prepravke oko ozvicenja zbog promene projekta","opis":"Prepravke oko ozvučenja usled izmene projekta","sati":2,"prepravka":true},{"opis_original":"pripremanje trasa za kabliranje rasvete i samo kabliranje rasvete","opis":"Priprema kablovskih trasa i kabliranje rasvete","sati":7,"prepravka":false}]}

Format odgovora kada nema segmenata (jedan tip posla):
{"vreme_od":"07:00","vreme_do":"16:00","ukupno_sati":9,"napomena_original":"tačan unos radnika bez izmena","napomena":"profesionalno preformulisan opis radova"}

Pravila:
- Odgovaraj isključivo na srpskom jeziku, koristeći srpsku terminologiju (npr. "kablovski" a ne "kabelski", "razvodni" a ne "distributivni")
- vreme_od i vreme_do moraju biti u formatu HH:MM (24h)
- Prepoznaj različite formate unosa vremena (npr. "7-16", "07 do 16", "7h-16h", "od 7 do 16")
- ukupno_sati = ukupna razlika između vreme_do i vreme_od kao decimalni broj
- Ako vreme nije moguće pouzdano prepoznati, vrati null za vreme_od, vreme_do i ukupno_sati
- Ako je vreme_do < vreme_od, pretpostavi prelazak preko ponoći
- napomena_original: prepiši DOSLOVNO ceo tekst radnika osim sata i minuta — ne ispravljati greške, ne menjati ništa
- Ako tekst sadrži VIŠE VRSTA POSLA sa navedenim trajanjem (npr. "2h prepravke, ostatak kabliranje"), koristi format sa segmentima
- U segmentima: opis_original je doslovan tekst tog dela, opis je profesionalna verzija sa ispravljenim greškama
- prepravka: true ako segment opisuje prepravke, izmene projekta, korekcije, popravke grešaka — inače false
- Zbir sati u segmentima treba da odgovara ukupno_sati
- Ako nema jasnih segmenata, koristi format bez segmenata sa poljem napomena
- Za polje napomena (bez segmenata): interno ispravi greške pa preformuliši profesionalno
- Ne dodavati radove ili detalje koji nisu navedeni
- Odgovaraj ISKLJUČIVO validnim JSON-om
PROMPT;
                } elseif ($tip === 'materijal') {
                    $katalogStmt = $this->db->query("
                        SELECT naziv, jm FROM katalog_materijala
                        WHERE aktivan=1 AND master_id IS NULL
                        ORDER BY naziv ASC
                    ");
                    $katalogArtikli = $katalogStmt->fetchAll(\PDO::FETCH_ASSOC);
                    $katalogTekst = implode("\n", array_map(fn($a) => "- {$a['naziv']} ({$a['jm']})", $katalogArtikli));

                    $systemPrompt = <<<PROMPT
Ti si asistent koji parsira utrošeni elektro materijal sa gradilišta, opisan slobodnim tekstom na srpskom jeziku.
Izvuci listu potrošenog materijala i vrati ISKLJUČIVO validan JSON bez dodatnog teksta, bez markdown oznaka i bez objašnjenja.

Format odgovora:
{"stavke":[{"naziv":"Kabl N2XH 5x1,5","kolicina":69,"jm":"m"}]}

Katalog poznatih artikala (koristi ISKLJUČIVO tačne nazive iz kataloga kada postoji podudaranje):
$katalogTekst

Pravila:
- Odgovaraj isključivo na srpskom jeziku, koristeći srpsku terminologiju (npr. "kablovski" a ne "kabelski", "razvodni" a ne "distributivni")
- Prepoznaj artikal čak i ako je napisan skraćeno, sa slovnim greškama ili drugačijim redosledom reči
- Ako postoji potpuno ili približno podudaranje sa katalogom, OBAVEZNO koristi TAČAN naziv i jedinicu mere iz kataloga
- Nikada ne pretpostavljati tip ili oznaku materijala (npr. N2XH, PP-Y, PP00) ako nije jasno navedena ili prepoznata iz kataloga
- Ako artikal nije u katalogu, napiši što precizniji naziv bez izmišljanja tehničkih detalja
- Prepoznaj različite načine pisanja količine: "50m", "50 m", "50 metara", "5 kom", "3x", "2 rolne", "1 pakovanje"
- Prepoznaj decimalne vrednosti sa tačkom ili zarezom (npr. 1.5 ili 1,5)
- kolicina je decimalni broj bez jedinice mere
- jm može biti samo: m, Kom, kg, m2, kpl, l, pak, rol
- Ako jedinica nije navedena, zaključi iz tipa materijala (kabl → m, utičnica/kutija/osigurač → Kom, crevo/buzir → m)
- Ispraviti samo očigledne slovne greške
- Ako se isti artikal pojavljuje više puta, saberi količine u jednu stavku
- Ne razdvajati jedan unos na više artikala osim ako je eksplicitno navedeno
- Ako nijedan materijal nije prepoznat, vrati: {"stavke":[]}
- Odgovaraj ISKLJUČIVO validnim JSON-om
PROMPT;
                } else {
                    // Nabavka — šta treba nabaviti
                    $katalogStmt2 = $this->db->query("
                        SELECT naziv, jm FROM katalog_materijala
                        WHERE aktivan=1 AND master_id IS NULL ORDER BY naziv ASC
                    ");
                    $katalogTekst2 = implode("\n", array_map(fn($a) => "- {$a['naziv']} ({$a['jm']})", $katalogStmt2->fetchAll(\PDO::FETCH_ASSOC)));

                    $systemPrompt = <<<PROMPT
Ti si asistent koji parsira zahteve za nabavku elektromaterijala na srpskom jeziku.
Iz slobodnog teksta izvuci listu materijala koji treba nabaviti i vrati ISKLJUČIVO validan JSON.

Format: {"stavke":[{"naziv":"Kabl N2XH 3x1,5","kolicina":50,"jm":"m","napomena":""},{"naziv":"Razvodna doza","kolicina":10,"jm":"Kom","napomena":"sa poklopcem"}]}

Katalog poznatih artikala (koristi ISKLJUČIVO tačne nazive iz kataloga kada postoji podudaranje):
$katalogTekst2

Pravila:
- Odgovaraj isključivo na srpskom jeziku, koristeći srpsku terminologiju
- Prepoznaj artikal čak i ako je napisan skraćeno ili sa greškama
- Ako postoji podudaranje sa katalogom, OBAVEZNO koristi TAČAN naziv iz kataloga
- kolicina: broj ili null ako nije navedena
- jm: m, Kom, kg, m2, kpl, l, pak, rol
- napomena: posebni zahtevi za tu stavku, ili prazan string
- Ako nijedan materijal nije prepoznat, vrati: {"stavke":[]}
- Odgovaraj ISKLJUČIVO validnim JSON-om
PROMPT;
                }

                $payload = json_encode([
                    'model' => 'claude-sonnet-4-5',
                    'max_tokens' => 800,
                    'system'     => $systemPrompt,
                    'messages'   => [['role' => 'user', 'content' => $tekst]]
                ]);

                $ch = curl_init('https://api.anthropic.com/v1/messages');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $payload,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'x-api-key: ' . $apiKey,
                        'anthropic-version: 2023-06-01',
                    ],
                    CURLOPT_TIMEOUT => 30,
                ]);

                $response = curl_exec($ch);
                $curlErr  = curl_error($ch);
                curl_close($ch);

                if ($curlErr) $this->json(['ok' => false, 'err' => 'cURL greška: ' . $curlErr]);

                $apiData = json_decode($response, true);
                if (!empty($apiData['error'])) {
                    $this->json(['ok' => false, 'err' => 'API greška: ' . ($apiData['error']['message'] ?? 'Nepoznata')]);
                }

                $rawText = $apiData['content'][0]['text'] ?? '';
                $rawText = preg_replace('/^```json\s*/i', '', trim($rawText));
                $rawText = preg_replace('/```\s*$/i', '', $rawText);
                $rawText = trim($rawText);

                $parsed = json_decode($rawText, true);
                if (!$parsed) $this->json(['ok' => false, 'err' => 'AI nije vratio validan JSON. Pokušaj ponovo.']);

                $this->json(['ok' => true, 'tip' => $tip, 'data' => $parsed]);
                break;

            case 'danas_upisi_vreme':
                $stavka_id   = (int)($_POST['stavka_id']    ?? 0) ?: null;
                $meta_json   = $_POST['meta']                ?? '';
                $grad_id     = (int)($_POST['gradiliste_id'] ?? 0) ?: null;
                $grad_naziv  = trim($_POST['gradiliste_naziv'] ?? '');

                if (!$meta_json) $this->json(['ok' => false, 'err' => 'Nedostaju podaci.']);

                // Ako je vezan za stavku — proveri pristup
                if ($stavka_id) {
                    $check = $this->db->prepare("SELECT 1 FROM raspored_radnici WHERE stavka_id=? AND radnik_id=?");
                    $check->execute([$stavka_id, $uid]);
                    if (!$check->fetch()) $this->json(['ok' => false, 'err' => 'Nemate pristup ovoj stavci.']);

                    // Dohvati gradilište iz stavke ako nije zadato
                    if (!$grad_id && !$grad_naziv) {
                        $gStmt = $this->db->prepare("SELECT rs.gradiliste_id, g.naziv FROM raspored_stavke rs LEFT JOIN gradilista g ON rs.gradiliste_id=g.id WHERE rs.id=?");
                        $gStmt->execute([$stavka_id]);
                        $gRow = $gStmt->fetch(\PDO::FETCH_ASSOC);
                        if ($gRow) {
                            $grad_id    = $gRow['gradiliste_id'];
                            $grad_naziv = $gRow['naziv'] ?? '';
                        }
                    }
                }

                $meta = json_decode($meta_json, true);
                if (!$meta) $this->json(['ok' => false, 'err' => 'Neispravan JSON.']);

                $stmt = $this->db->prepare("
                    INSERT INTO raspored_vreme
                        (stavka_id, radnik_id, datum, vreme_od, vreme_do, ukupno_sati, napomena, napomena_original, segmenti, meta, gradiliste_id, gradiliste_naziv)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                // Ako ima segmenata, napomena je konkatenacija opisa svih segmenata
                $napomena = $meta['napomena'] ?? null;
                if (!$napomena && !empty($meta['segmenti'])) {
                    $napomena = implode('; ', array_map(fn($s) => $s['opis'] ?? '', $meta['segmenti']));
                }

                $stmt->execute([
                    $stavka_id, $uid, date('Y-m-d'),
                    $meta['vreme_od']          ?? null,
                    $meta['vreme_do']          ?? null,
                    isset($meta['ukupno_sati']) ? (float)$meta['ukupno_sati'] : null,
                    $napomena,
                    $meta['napomena_original'] ?? null,
                    !empty($meta['segmenti']) ? json_encode($meta['segmenti'], JSON_UNESCAPED_UNICODE) : null,
                    $meta_json,
                    $grad_id,
                    $grad_naziv
                ]);

                // Sačuvaj preferencu gradilišta
                if ($grad_id || $grad_naziv) {
                    $this->sacuvajPreferencu($uid, 'slobodan_gradiliste_id', (string)($grad_id ?: ''));
                    $this->sacuvajPreferencu($uid, 'slobodan_gradiliste_naziv', $grad_naziv);
                }

                $this->json(['ok' => true]);
                break;

            case 'danas_upisi_materijal':
                $stavka_id   = (int)($_POST['stavka_id']     ?? 0) ?: null;
                $meta_json   = $_POST['meta']                 ?? '';
                $grad_id     = (int)($_POST['gradiliste_id']  ?? 0) ?: null;
                $grad_naziv  = trim($_POST['gradiliste_naziv'] ?? '');

                if (!$meta_json) $this->json(['ok' => false, 'err' => 'Nedostaju podaci.']);

                // Ako je vezan za stavku — proveri odgovornost
                if ($stavka_id) {
                    $check = $this->db->prepare("SELECT odgovoran_id FROM raspored_stavke WHERE id=?");
                    $check->execute([$stavka_id]);
                    $odgovoran_id = (int)$check->fetchColumn();
                    if ((int)$odgovoran_id !== (int)$uid) $this->json(['ok' => false, 'err' => 'Niste odgovorni za unos materijala.']);

                    if (!$grad_id && !$grad_naziv) {
                        $gStmt = $this->db->prepare("SELECT rs.gradiliste_id, g.naziv FROM raspored_stavke rs LEFT JOIN gradilista g ON rs.gradiliste_id=g.id WHERE rs.id=?");
                        $gStmt->execute([$stavka_id]);
                        $gRow = $gStmt->fetch(\PDO::FETCH_ASSOC);
                        if ($gRow) {
                            $grad_id    = $gRow['gradiliste_id'];
                            $grad_naziv = $gRow['naziv'] ?? '';
                        }
                    }
                }

                $meta = json_decode($meta_json, true);
                if (!$meta || empty($meta['stavke'])) $this->json(['ok' => false, 'err' => 'Nema stavki materijala.']);

                $datum = date('Y-m-d');
                $stmt  = $this->db->prepare("
                    INSERT INTO raspored_materijal (stavka_id, radnik_id, datum, naziv, kolicina, jm, gradiliste_id, gradiliste_naziv)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                foreach ($meta['stavke'] as $s) {
                    $naziv    = trim($s['naziv']    ?? '');
                    $kolicina = (float)($s['kolicina'] ?? 0);
                    $jm       = trim($s['jm']       ?? 'Kom');
                    if (!$naziv || $kolicina <= 0) continue;

                    // Mapiraj na katalog
                    $katStmt = $this->db->prepare("
                        SELECT id FROM katalog_materijala
                        WHERE aktivan=1 AND master_id IS NULL
                          AND REPLACE(LOWER(naziv),' ','') = REPLACE(LOWER(?),' ','')
                        LIMIT 1
                    ");
                    $katStmt->execute([$naziv]);
                    if (!$katStmt->fetchColumn()) {
                        // Dodaj u katalog ako ne postoji
                        $this->db->prepare("INSERT INTO katalog_materijala (kataloski_broj, naziv, jm, dobavljac) VALUES ('', ?, ?, 'interni')")
                            ->execute([$naziv, $jm]);
                    }

                    $stmt->execute([$stavka_id, $uid, $datum, $naziv, $kolicina, $jm, $grad_id, $grad_naziv]);
                }

                // Sačuvaj preferencu
                if ($grad_id || $grad_naziv) {
                    $this->sacuvajPreferencu($uid, 'slobodan_gradiliste_id', (string)($grad_id ?: ''));
                    $this->sacuvajPreferencu($uid, 'slobodan_gradiliste_naziv', $grad_naziv);
                }

                $this->json(['ok' => true]);
                break;

            case 'danas_upisi_nabavku':
                $stavka_id      = (int)($_POST['stavka_id']     ?? 0) ?: null;
                $meta_json      = $_POST['meta']                 ?? '';
                $tekst_original = trim($_POST['tekst_original']  ?? '');
                $grad_id        = (int)($_POST['gradiliste_id']  ?? 0) ?: null;
                $grad_naziv     = trim($_POST['gradiliste_naziv'] ?? '');
                $prioritet      = in_array($_POST['prioritet'] ?? '', ['normalno','hitno']) ? $_POST['prioritet'] : 'normalno';

                if (!$meta_json || !$tekst_original) $this->json(['ok' => false, 'err' => 'Nedostaju podaci.']);

                if ($stavka_id) {
                    $check = $this->db->prepare("SELECT 1 FROM raspored_radnici WHERE stavka_id=? AND radnik_id=?");
                    $check->execute([$stavka_id, $uid]);
                    if (!$check->fetch()) $this->json(['ok' => false, 'err' => 'Nemate pristup ovoj stavci.']);

                    if (!$grad_id && !$grad_naziv) {
                        $gStmt = $this->db->prepare("SELECT rs.gradiliste_id, g.naziv FROM raspored_stavke rs LEFT JOIN gradilista g ON rs.gradiliste_id=g.id WHERE rs.id=?");
                        $gStmt->execute([$stavka_id]);
                        $gRow = $gStmt->fetch(\PDO::FETCH_ASSOC);
                        if ($gRow) { $grad_id = $gRow['gradiliste_id']; $grad_naziv = $gRow['naziv'] ?? ''; }
                    }
                }

                $this->db->prepare("
                    INSERT INTO nabavka_zahtevi
                        (stavka_id, radnik_id, gradiliste_id, gradiliste_naziv, tekst_original, stavke, status, prioritet, kreator_id)
                    VALUES (?, ?, ?, ?, ?, ?, 'novo', ?, ?)
                ")->execute([$stavka_id, $uid, $grad_id, $grad_naziv, $tekst_original, $meta_json, $prioritet, $uid]);

                // Push obaveštenje za AT/AF (osim onome ko je uneo nabavku)
                $ph = implode(',', array_fill(0, count(self::NABAVKA_NOTIFIKACIJE), '?'));
                $st = $this->db->prepare("SELECT id FROM admin_korisnici WHERE uloga IN ($ph) AND aktivan=1");
                $st->execute(self::NABAVKA_NOTIFIKACIJE);
                $nabavkaIds = array_filter($st->fetchAll(\PDO::FETCH_COLUMN), fn($x) => (int)$x !== (int)$uid);
                PushController::notifyUsers($nabavkaIds, [
                    'title' => '🛒 Nova nabavka',
                    'body'  => mb_substr($tekst_original, 0, 120),
                    'url'   => BASE_URL . '/?page=nabavka',
                    'tag'   => 'nabavka-' . $uid . '-' . time(),
                    'icon'  => BASE_URL . '/public/icon-192.png',
                ]);

                $this->json(['ok' => true]);
                break;

            case 'danas_vreme_get':
                $stavka_id = (int)($_POST['stavka_id'] ?? 0);
                $stmt = $this->db->prepare("
                    SELECT rv.*, k.ime AS radnik_ime
                    FROM raspored_vreme rv
                    JOIN admin_korisnici k ON rv.radnik_id = k.id
                    WHERE rv.stavka_id = ?
                    ORDER BY rv.datum DESC, rv.created_at DESC
                ");
                $stmt->execute([$stavka_id]);
                $unosi = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($unosi as &$u) {
                    $u['meta'] = $u['meta'] ? json_decode($u['meta'], true) : [];
                }
                $this->json(['ok' => true, 'unosi' => $unosi]);
                break;

            case 'danas_sacuvaj_preferencu':
                $kljuc   = trim($_POST['kljuc']   ?? '');
                $vrednost = trim($_POST['vrednost'] ?? '');
                if (!$kljuc) $this->json(['ok' => false]);
                $this->sacuvajPreferencu($uid, $kljuc, $vrednost);
                $this->json(['ok' => true]);
                break;

            case 'danas_ucitaj_preferencu':
                $kljuc = trim($_POST['kljuc'] ?? '');
                $stmt = $this->db->prepare("SELECT vrednost FROM korisnik_preference WHERE korisnik_id=? AND kljuc=?");
                $stmt->execute([$uid, $kljuc]);
                $vrednost = $stmt->fetchColumn();
                $this->json(['ok' => true, 'vrednost' => $vrednost ?: null]);
                break;

            default:
                $this->json(['ok' => false, 'err' => 'Nepoznata akcija.']);
        }
    }

    private function sacuvajPreferencu(int $uid, string $kljuc, string $vrednost): void
    {
        $this->db->prepare("
            INSERT INTO korisnik_preference (korisnik_id, kljuc, vrednost)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE vrednost = ?, updated_at = NOW()
        ")->execute([$uid, $kljuc, $vrednost, $vrednost]);
    }

    private function notifikuj(int $primalac_id, string $posiljalac, string $poruka): void
    {
        try {
            $stmt = $this->db->prepare("SELECT platforma, platforma2 FROM admin_korisnici WHERE id=?");
            $stmt->execute([$primalac_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return;

            $platforme = array_unique(array_filter([$row['platforma'] ?? '', $row['platforma2'] ?? '']));

            foreach ($platforme as $platforma) {
                if ($platforma === 'ios') {
                    $stmt2 = $this->db->prepare("SELECT chat_id FROM telegram_subscriptions WHERE korisnik_id=? AND aktivan=1");
                    $stmt2->execute([$primalac_id]);
                    $token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
                    $text  = urlencode("\xf0\x9f\x92\xac {$posiljalac}: {$poruka}\n\nekosarna.com/mvc/?page=danas");
                    foreach ($stmt2->fetchAll(\PDO::FETCH_COLUMN) as $chat_id) {
                        @file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text={$text}");
                    }
                }
            }
        } catch (\Throwable $e) {}
    }
}
