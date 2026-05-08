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

        // Ako je datum eksplicitno zadat u URL-u, koristi njega
        if (isset($_GET['datum'])) {
            $datum = $_GET['datum'];
        } else {
            // Inače pronađi najbliži dan sa rasporedom (danas ili u budućnosti)
            $datum = $this->najblizjiDanSaRasporedom($uid);
        }

        // Datumi: 1 pre, izabrani, 2 posle
        // Generiši 4 datuma preskačući nedjelju (0 = nedjelja)
$datumi = [];
$dodato = 0;
$offset = -1;
while ($dodato < 4) {
    $d = date('Y-m-d', strtotime($datum . " $offset days"));
    if (date('w', strtotime($d)) !== '0') { // 0 = nedjelja
        $datumi[] = $d;
        $dodato++;
    }
    $offset++;
}
        // Stavke za sva 4 dana
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

        $this->view('danas/index', compact('dani', 'datum', 'datumi'));
    }

    private function najblizjiDanSaRasporedom(int $uid): string
    {
        // Traži danas ili najbliži budući dan sa rasporedom
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

        // Ako nema budućih, uzmi poslednji prošli
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

                // Notifikuj sve koji su pisali u ovom threadu (osim pošiljaoca)
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
                // android → Push (TODO)
                // web → polling pokriva automatski
            }
        } catch (\Throwable $e) {}
    }
}
