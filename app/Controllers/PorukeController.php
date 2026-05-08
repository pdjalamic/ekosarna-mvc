<?php
namespace Controllers;

use Core\Auth;

class PorukeController extends \Core\Controller
{
    private $db;

    public function dispatch(): void
    {
        Auth::requireLogin();
        $this->db = \Core\Database::get();

        $view = $_GET['view'] ?? '';
        $tab  = $_GET['tab']  ?? 'inbox';

        // Posebni view-ovi
        if ($view === 'thread')        { $this->thread(); return; }
        if ($view === 'nova')          { $this->nova(); return; }
        if ($view === 'nova_zadatak')  { $this->novaZadatak(); return; }
        if ($view === 'nova_nabavka')  { $this->novaNabavka(); return; }

        // AJAX akcije
        if (isset($_POST['_poruke_action'])) {
            $this->handleAjax($_POST['_poruke_action']);
            return;
        }

        // Tabovi
        match ($tab) {
            'zadaci'    => $this->zadaci(),
            'nabavka'   => $this->nabavka(),
            'gradilista' => $this->gradilistaTab(),
            'izvestaji' => $this->izvestaji(),
            default     => $this->inbox(),
        };
    }

    // ═══════════════════════════════════════
    // TAB: INBOX
    // ═══════════════════════════════════════
    private function inbox(): void
    {
        $uid     = Auth::id();
        $subtab  = $_GET['subtab'] ?? 'primljene';
        $inbox   = $this->getInbox($uid);
        $poslato = $this->getPoslato($uid);
        $this->view('poruke/inbox', compact('inbox', 'poslato', 'subtab'));
    }

    // ═══════════════════════════════════════
    // TAB: ZADACI
    // ═══════════════════════════════════════
    private function zadaci(): void
    {
        $uid    = Auth::id();
        $filter = $_GET['filter'] ?? 'sve';

        $where  = ["p.tip = 'zadatak'", "p.roditelj_id IS NULL",
                   "(p.primalac_id = ? OR p.posiljalac_id = ?)"];
        $params = [$uid, $uid];

        if ($filter !== 'sve') {
            $where[]  = "p.status = ?";
            $params[] = $filter;
        }

        $sql = "SELECT p.*,
                       pos.ime AS posiljalac_ime,
                       prim.ime AS primalac_ime,
                       g.naziv AS gradiliste_naziv
                FROM poruke p
                JOIN admin_korisnici pos ON p.posiljalac_id = pos.id
                LEFT JOIN admin_korisnici prim ON p.primalac_id = prim.id
                LEFT JOIN gradilista g ON p.gradiliste_id = g.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $zadaci = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Decode meta
        foreach ($zadaci as &$z) {
            $z['meta'] = $z['meta'] ? json_decode($z['meta'], true) : [];
        }

        $this->view('poruke/zadaci', compact('zadaci', 'filter'));
    }

    // ═══════════════════════════════════════
    // TAB: NABAVKA
    // ═══════════════════════════════════════
    private function nabavka(): void
    {
        $uid    = Auth::id();
        $filter = $_GET['filter'] ?? 'sve';

        $where  = ["p.tip = 'nabavka'", "p.roditelj_id IS NULL"];
        $params = [];

        if ($filter !== 'sve') {
            $where[]  = "p.status = ?";
            $params[] = $filter;
        }

        $sql = "SELECT p.*,
                       pos.ime AS posiljalac_ime,
                       prim.ime AS primalac_ime,
                       g.naziv AS gradiliste_naziv
                FROM poruke p
                JOIN admin_korisnici pos ON p.posiljalac_id = pos.id
                LEFT JOIN admin_korisnici prim ON p.primalac_id = prim.id
                LEFT JOIN gradilista g ON p.gradiliste_id = g.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $nabavke = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($nabavke as &$n) {
            $n['meta'] = $n['meta'] ? json_decode($n['meta'], true) : [];
        }

        $this->view('poruke/nabavka', compact('nabavke', 'filter'));
    }

    // ═══════════════════════════════════════
    // TAB: GRADILIŠTA (pregled)
    // ═══════════════════════════════════════
    private function gradilistaTab(): void
    {
        $stmt = $this->db->query("
            SELECT g.*,
                   (SELECT putanja FROM gradilista_slike WHERE gradiliste_id = g.id
                    ORDER BY redosled ASC, id ASC LIMIT 1) AS prva_slika
            FROM gradilista g
            ORDER BY FIELD(g.status,'aktivno','pauza','zavrseno'), g.naziv
        ");
        $gradilista = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->view('poruke/gradilista-tab', compact('gradilista'));
    }

    // ═══════════════════════════════════════
    // TAB: IZVEŠTAJI
    // ═══════════════════════════════════════
    private function izvestaji(): void
    {
        $gradiliste_id = (int)($_GET['gradiliste_id'] ?? 0);

        $where  = ["p.tip = 'zadatak'", "p.roditelj_id IS NOT NULL",
                   "p.posiljalac_id = ?"];
        $params = [Auth::id()];

        if ($gradiliste_id) {
            // Izveštaji vezani za gradilište idu kroz roditelja
            $where[]  = "root.gradiliste_id = ?";
            $params[] = $gradiliste_id;
        }

        $stmt = $this->db->query("SELECT id, naziv FROM gradilista WHERE status='aktivno' ORDER BY naziv");
        $sva_gradilista = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Sve reply poruke na zadatke (izveštaji)
        $sql = "SELECT p.*,
                       pos.ime AS posiljalac_ime,
                       root.naslov AS zadatak_naslov,
                       g.naziv AS gradiliste_naziv
                FROM poruke p
                JOIN poruke root ON p.roditelj_id = root.id
                JOIN admin_korisnici pos ON p.posiljalac_id = pos.id
                LEFT JOIN gradilista g ON root.gradiliste_id = g.id
                WHERE root.tip = 'zadatak'
                " . ($gradiliste_id ? " AND root.gradiliste_id = $gradiliste_id" : "") . "
                ORDER BY p.created_at DESC
                LIMIT 100";

        $stmt = $this->db->query($sql);
        $izvestaji = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($izvestaji as &$iz) {
            $iz['meta'] = $iz['meta'] ? json_decode($iz['meta'], true) : [];
        }

        $this->view('poruke/izvestaji', compact('izvestaji', 'sva_gradilista', 'gradiliste_id'));
    }

    // ═══════════════════════════════════════
    // VIEW: NOVA PORUKA (tip=poruka)
    // ═══════════════════════════════════════
    private function nova(): void
    {
        $uid       = Auth::id();
        $korisnici = $this->getSviKorisnici($uid);
        $greska    = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $primalac_id = (isset($_POST['primalac_id']) && $_POST['primalac_id'] !== '')
                ? (int)$_POST['primalac_id'] : null;
            $naslov  = trim($_POST['naslov'] ?? '');
            $sadrzaj = trim($_POST['sadrzaj'] ?? '');

            if ($naslov && $sadrzaj) {
                $stmt = $this->db->prepare("
                    INSERT INTO poruke (posiljalac_id, primalac_id, naslov, sadrzaj, tip)
                    VALUES (?, ?, ?, ?, 'poruka')
                ");
                $stmt->execute([$uid, $primalac_id, $naslov, $sadrzaj]);
                $this->notifikuj($primalac_id, Auth::ime(), $naslov);
                header('Location: ' . BASE_URL . '/?page=poruke&subtab=poslato');
                exit;
            }
            $greska = 'Naslov i poruka su obavezni.';
        }

        $this->view('poruke/nova', compact('korisnici', 'greska'));
    }

    // ═══════════════════════════════════════
    // VIEW: NOVI ZADATAK (tip=zadatak)
    // ═══════════════════════════════════════
    private function novaZadatak(): void
    {
        $uid        = Auth::id();
        $korisnici  = $this->getSviKorisnici($uid);
        $gradilista = \Controllers\GradilistaController::getAll();
        $greska     = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $primalac_id   = (int)($_POST['primalac_id'] ?? 0) ?: null;
            $naslov        = trim($_POST['naslov'] ?? '');
            $sadrzaj       = trim($_POST['sadrzaj'] ?? '');
            $gradiliste_id = (int)($_POST['gradiliste_id'] ?? 0) ?: null;
            $datum         = $_POST['datum'] ?? null;
            $status        = 'otvoreno';

            $meta = json_encode([
                'datum'  => $datum,
            ]);

            if ($naslov && $sadrzaj) {
                $stmt = $this->db->prepare("
                    INSERT INTO poruke (posiljalac_id, primalac_id, naslov, sadrzaj, tip, status, meta, gradiliste_id)
                    VALUES (?, ?, ?, ?, 'zadatak', ?, ?, ?)
                ");
                $stmt->execute([$uid, $primalac_id, $naslov, $sadrzaj, $status, $meta, $gradiliste_id]);
                $this->notifikuj($primalac_id, Auth::ime(), '📋 Novi zadatak: ' . $naslov);
                header('Location: ' . BASE_URL . '/?page=poruke&tab=zadaci');
                exit;
            }
            $greska = 'Naslov i opis su obavezni.';
        }

        $this->view('poruke/nova-zadatak', compact('korisnici', 'gradilista', 'greska'));
    }

    // ═══════════════════════════════════════
    // VIEW: NOVA NABAVKA (tip=nabavka)
    // ═══════════════════════════════════════
    private function novaNabavka(): void
    {
        $uid        = Auth::id();
        $gradilista = \Controllers\GradilistaController::getAll();
        $greska     = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $naslov        = trim($_POST['naslov'] ?? '');
            $gradiliste_id = (int)($_POST['gradiliste_id'] ?? 0) ?: null;
            $tip_nabavke   = $_POST['tip_nabavke'] ?? 'lokalna';
            $stavke        = [];

            // Parsiraj stavke
            $nazivi    = $_POST['stavka_naziv']    ?? [];
            $kolicine  = $_POST['stavka_kolicina'] ?? [];
            $jedinice  = $_POST['stavka_jedinica'] ?? [];

            foreach ($nazivi as $i => $naziv) {
                $naziv = trim($naziv);
                if ($naziv) {
                    $stavke[] = [
                        'naziv'    => $naziv,
                        'kolicina' => (float)($kolicine[$i] ?? 1),
                        'jedinica' => trim($jedinice[$i] ?? 'kom'),
                    ];
                }
            }

            $meta = json_encode([
                'tip_nabavke' => $tip_nabavke,
                'stavke'      => $stavke,
            ]);

            if ($naslov && !empty($stavke)) {
                $stmt = $this->db->prepare("
                    INSERT INTO poruke (posiljalac_id, naslov, sadrzaj, tip, status, meta, gradiliste_id)
                    VALUES (?, ?, ?, 'nabavka', 'ceka', ?, ?)
                ");
                $sadrzaj = $tip_nabavke . ' nabavka — ' . count($stavke) . ' stavki';
                $stmt->execute([$uid, $naslov, $sadrzaj, $meta, $gradiliste_id]);
                header('Location: ' . BASE_URL . '/?page=poruke&tab=nabavka');
                exit;
            }
            $greska = 'Naslov i bar jedna stavka su obavezni.';
        }

        $this->view('poruke/nova-nabavka', compact('gradilista', 'greska'));
    }

    // ═══════════════════════════════════════
    // VIEW: THREAD
    // ═══════════════════════════════════════
    private function thread(): void
    {
        $id  = (int)($_GET['id'] ?? 0);
        $uid = Auth::id();

        if (!$id) { header('Location: ' . BASE_URL . '/?page=poruke'); exit; }

        $stmt = $this->db->prepare("UPDATE poruke SET procitano = 1 WHERE id = ? OR roditelj_id = ?");
        $stmt->execute([$id, $id]);

        $poruke = $this->getThread($id);
        if (empty($poruke)) { header('Location: ' . BASE_URL . '/?page=poruke'); exit; }

        $root = $poruke[0];
        $root['meta'] = $root['meta'] ? json_decode($root['meta'], true) : [];

        // Gradilište info
        $gradiliste = null;
        if ($root['gradiliste_id']) {
            $gs = $this->db->prepare("SELECT * FROM gradilista WHERE id=?");
            $gs->execute([$root['gradiliste_id']]);
            $gradiliste = $gs->fetch(\PDO::FETCH_ASSOC);
        }

        // Svi korisnici za reply dropdown (zadaci)
        $korisnici = $this->getSviKorisnici(0);

        // Handle POST — reply ili status izmena
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Izmena statusa (samo admin/posiljalac)
            if (isset($_POST['novi_status']) && (Auth::isAdmin() || $root['posiljalac_id'] == $uid)) {
                $ns = $_POST['novi_status'];
                $stmt = $this->db->prepare("UPDATE poruke SET status=? WHERE id=?");
                $stmt->execute([$ns, $id]);
                header('Location: ' . BASE_URL . '/?page=poruke&view=thread&id=' . $id);
                exit;
            }

            // Reply
            $sadrzaj = trim($_POST['sadrzaj'] ?? '');
            $meta_reply = null;

            // Ako je izveštaj (reply na zadatak) — parsiramo materijale
            if ($root['tip'] === 'zadatak' && !empty($_POST['materijali'])) {
                $meta_reply = json_encode(['materijali' => $_POST['materijali']]);
            }

            if ($sadrzaj) {
                $primalac_id = ($root['posiljalac_id'] == $uid)
                    ? $root['primalac_id']
                    : $root['posiljalac_id'];

                $stmt = $this->db->prepare("
                    INSERT INTO poruke (posiljalac_id, primalac_id, roditelj_id, naslov, sadrzaj, tip, meta)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $uid, $primalac_id, $id,
                    'Re: ' . $root['naslov'],
                    $sadrzaj, $root['tip'], $meta_reply
                ]);

                // Ako monter šalje izveštaj → auto status = u_toku
                if ($root['tip'] === 'zadatak' && $root['posiljalac_id'] != $uid && $root['status'] === 'otvoreno') {
                    $this->db->prepare("UPDATE poruke SET status='u_toku' WHERE id=?")->execute([$id]);
                }

                $this->notifikuj($primalac_id, Auth::ime(), $root['naslov']);
                header('Location: ' . BASE_URL . '/?page=poruke&view=thread&id=' . $id);
                exit;
            }
        }

        // Refresh thread posle POST
        $poruke = $this->getThread($id);

        $roditelj_id = $id;
        $this->view('poruke/thread', compact('poruke', 'roditelj_id', 'root', 'gradiliste', 'korisnici'));
    }

    // ═══════════════════════════════════════
    // AJAX
    // ═══════════════════════════════════════
    private function handleAjax(string $action): void
    {
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'promeni_status') {
            if (!Auth::isAdmin()) { echo json_encode(['ok' => false]); exit; }
            $status = $_POST['status'] ?? '';
            $this->db->prepare("UPDATE poruke SET status=? WHERE id=?")
                     ->execute([$status, $id]);
            echo json_encode(['ok' => true]);
            exit;
        }

        echo json_encode(['ok' => false, 'err' => 'Nepoznata akcija.']);
        exit;
    }

    // ═══════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════
    private function getInbox(int $uid): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, pos.ime AS posiljalac_ime, prim.ime AS primalac_ime
            FROM poruke p
            JOIN admin_korisnici pos ON p.posiljalac_id = pos.id
            LEFT JOIN admin_korisnici prim ON p.primalac_id = prim.id
            WHERE p.tip = 'poruka'
              AND (p.primalac_id = ? OR p.primalac_id IS NULL)
              AND p.posiljalac_id != ?
              AND p.roditelj_id IS NULL
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$uid, $uid]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getPoslato(int $uid): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, pos.ime AS posiljalac_ime, prim.ime AS primalac_ime
            FROM poruke p
            JOIN admin_korisnici pos ON p.posiljalac_id = pos.id
            LEFT JOIN admin_korisnici prim ON p.primalac_id = prim.id
            WHERE p.tip = 'poruka'
              AND p.posiljalac_id = ?
              AND p.roditelj_id IS NULL
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$uid]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getThread(int $roditelj_id): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, pos.ime AS posiljalac_ime
            FROM poruke p
            JOIN admin_korisnici pos ON p.posiljalac_id = pos.id
            WHERE p.id = ? OR p.roditelj_id = ?
            ORDER BY p.created_at ASC
        ");
        $stmt->execute([$roditelj_id, $roditelj_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getSviKorisnici(int $iskljuci_id): array
    {
        if ($iskljuci_id) {
            $stmt = $this->db->prepare("SELECT id, ime, uloga FROM admin_korisnici WHERE aktivan=1 AND id!=? ORDER BY ime");
            $stmt->execute([$iskljuci_id]);
        } else {
            $stmt = $this->db->query("SELECT id, ime, uloga FROM admin_korisnici WHERE aktivan=1 ORDER BY ime");
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════
    // STATIC — badge u headeru
    // ═══════════════════════════════════════
    public static function neprocitane(int $uid): int
    {
        try {
            $db   = \Core\Database::get();
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM poruke
                WHERE (primalac_id = ? OR primalac_id IS NULL)
                  AND posiljalac_id != ?
                  AND procitano = 0
                  AND roditelj_id IS NULL
            ");
            $stmt->execute([$uid, $uid]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    // ═══════════════════════════════════════
    // NOTIFIKACIJE
    // ═══════════════════════════════════════
    private function notifikuj(?int $primalac_id, string $posiljalac, string $naslov): void
    {
        $db = $this->db;

        // Odredi platformu primaoca
        if ($primalac_id) {
            $stmt = $db->prepare("SELECT platforma FROM admin_korisnici WHERE id=?");
            $stmt->execute([$primalac_id]);
            $platforma = $stmt->fetchColumn() ?: 'android';
        }

        // Telegram (ios korisnici ili broadcast)
        if ($primalac_id === null) {
            $stmt = $db->query("
                SELECT ts.chat_id FROM telegram_subscriptions ts
                JOIN admin_korisnici k ON ts.korisnik_id = k.id
                WHERE ts.aktivan = 1 AND k.platforma = 'ios'
            ");
        } elseif (isset($platforma) && $platforma === 'ios') {
            $stmt = $db->prepare("SELECT chat_id FROM telegram_subscriptions WHERE korisnik_id=? AND aktivan=1");
            $stmt->execute([$primalac_id]);
        } else {
            $stmt = null;
        }

        if ($stmt) {
            $subscribers = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            if (!empty($subscribers)) {
                $token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
                $text  = urlencode("📩 {$posiljalac}: {$naslov}\n\nekosarna.com/mvc/?page=poruke");
                foreach ($subscribers as $chat_id) {
                    @file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text={$text}");
                }
            }
        }

        // TODO: Push notifikacije za android korisnici
    }
}
