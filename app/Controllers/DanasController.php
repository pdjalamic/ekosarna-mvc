<?php
namespace Controllers;

use Core\Auth;

class DanasController extends \Core\Controller
{
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
            $stmt = $this->db->prepare("
                SELECT rs.*,
                       g.naziv AS gradiliste_naziv,
                       rd.boja,
                       rd.datum AS dan_datum,
                       rr.vreme_od,
                       rr.vreme_do
                FROM raspored_radnici rr
                JOIN raspored_stavke rs  ON rr.stavka_id = rs.id
                JOIN raspored_dani rd    ON rs.dan_id = rd.id
                JOIN radne_nedelje rn    ON rd.nedelja_id = rn.id
                LEFT JOIN gradilista g   ON rs.gradiliste_id = g.id
                WHERE rr.radnik_id = ? AND rd.datum = ?
                ORDER BY rr.vreme_od ASC, rs.id ASC
            ");
            $stmt->execute([$uid, $d]);
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

        $this->view('danas/index', compact('dani', 'datum', 'datumi', 'uid'));
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
                $tip   = in_array($_POST['tip'] ?? '', ['vreme', 'materijal']) ? $_POST['tip'] : 'vreme';

                if (!$tekst) $this->json(['ok' => false, 'err' => 'Tekst je prazan.']);

                $apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? '';
                if (!$apiKey) $this->json(['ok' => false, 'err' => 'ANTHROPIC_API_KEY nije postavljen u .env']);

                if ($tip === 'vreme') {
                    $systemPrompt = <<<PROMPT
Ti si asistent koji parsira kratke opise radnog vremena električara na srpskom jeziku.
Iz slobodnog teksta izvuci vreme rada i vrati SAMO validan JSON bez ikakvog teksta pre ili posle, bez markdown oznaka.

Format odgovora:
{"vreme_od":"07:00","vreme_do":"16:00","ukupno_sati":9,"napomena":"kratak opis rada"}

Pravila:
- vreme_od i vreme_do u formatu HH:MM (24h)
- ukupno_sati = razlika vreme_do - vreme_od (ceo broj)
- napomena: kratko šta je rađeno, max 120 znakova, izostavi ako nije navedeno
- Izostavi polja kojih nema u tekstu
- Odgovaraj SAMO validnim JSON-om, ništa više
PROMPT;
                } else {
                    $systemPrompt = <<<PROMPT
Ti si asistent koji parsira utrošeni materijal sa gradilišta, opisan slobodnim tekstom na srpskom jeziku.
Izvuci listu potrošenog materijala i vrati SAMO validan JSON bez ikakvog teksta pre ili posle, bez markdown oznaka.

Format odgovora:
{"stavke":[{"naziv":"Kabal 3x1.5 N2XH","kolicina":69,"jm":"m"},{"naziv":"Buzir HF F16","kolicina":130,"jm":"m"}]}

Pravila:
- naziv materijala što precizniji i standardizovaniji
- jm: m, kom, kg, m2, kpl, l, pak
- Odgovaraj SAMO validnim JSON-om, ništa više
PROMPT;
                }

                $payload = json_encode([
                    'model'      => 'claude-haiku-4-5-20251001',
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
                $stavka_id = (int)($_POST['stavka_id'] ?? 0);
                $meta_json = $_POST['meta'] ?? '';
                if (!$stavka_id || !$meta_json) $this->json(['ok' => false, 'err' => 'Nedostaju podaci.']);

                $check = $this->db->prepare("SELECT 1 FROM raspored_radnici WHERE stavka_id=? AND radnik_id=?");
                $check->execute([$stavka_id, $uid]);
                if (!$check->fetch()) $this->json(['ok' => false, 'err' => 'Nemate pristup ovoj stavci.']);

                $meta = json_decode($meta_json, true);
                if (!$meta) $this->json(['ok' => false, 'err' => 'Neispravan JSON.']);

                $stmt = $this->db->prepare("
                    INSERT INTO raspored_vreme
                        (stavka_id, radnik_id, datum, vreme_od, vreme_do, ukupno_sati, napomena, meta)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $stavka_id, $uid, date('Y-m-d'),
                    $meta['vreme_od'] ?? null,
                    $meta['vreme_do'] ?? null,
                    isset($meta['ukupno_sati']) ? (float)$meta['ukupno_sati'] : null,
                    $meta['napomena'] ?? null,
                    $meta_json
                ]);
                $this->json(['ok' => true]);
                break;

            case 'danas_upisi_materijal':
                $stavka_id = (int)($_POST['stavka_id'] ?? 0);
                $meta_json = $_POST['meta'] ?? '';
                if (!$stavka_id || !$meta_json) $this->json(['ok' => false, 'err' => 'Nedostaju podaci.']);

                $check = $this->db->prepare("SELECT odgovoran_id FROM raspored_stavke WHERE id=?");
                $check->execute([$stavka_id]);
                $odgovoran_id = (int)$check->fetchColumn();
                if ($odgovoran_id !== $uid) $this->json(['ok' => false, 'err' => 'Niste odgovorni za unos materijala.']);

                $meta = json_decode($meta_json, true);
                if (!$meta || empty($meta['stavke'])) $this->json(['ok' => false, 'err' => 'Nema stavki materijala.']);

                $datum = date('Y-m-d');
                $stmt  = $this->db->prepare("
                    INSERT INTO raspored_materijal (stavka_id, radnik_id, datum, naziv, kolicina, jm)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                foreach ($meta['stavke'] as $s) {
                    $naziv    = trim($s['naziv'] ?? '');
                    $kolicina = (float)($s['kolicina'] ?? 0);
                    $jm       = trim($s['jm'] ?? 'kom');
                    if (!$naziv || $kolicina <= 0) continue;
                    $stmt->execute([$stavka_id, $uid, $datum, $naziv, $kolicina, $jm]);
                }
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

            default:
                $this->json(['ok' => false, 'err' => 'Nepoznata akcija.']);
        }
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
