<?php
namespace Controllers;

use Core\Auth;
use Models\InterniZadatak;
use Models\Korisnik;

class ZadaciController extends \Core\Controller
{
    // Zadatke zadaju (i menjaju/brišu) samo ove uloge; primaju ih samo ove.
    const ZADACI_ZADAJU  = ['Direktor', 'Inženjer na gradilištu'];
    const ZADACI_PRIMAJU = ['AT', 'AF'];

    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::get();
    }

    public function index(): void
    {
        Auth::requireLogin();
        InterniZadatak::migrate();

        $uid = Auth::id();

        // Prihvatio filter: '' (default) = svi, <id> = konkretna osoba
        $zdodRaw = $_GET['zdod'] ?? '';
        $prihvatioMode = (ctype_digit((string)$zdodRaw) && (int)$zdodRaw > 0) ? (int)$zdodRaw : 'svi';

        $filters = [
            'status'     => $_GET['zstatus']    ?? '',
            'q'          => trim($_GET['zq']    ?? ''),
            'kategorija' => trim($_GET['zkat']  ?? ''),
            'dodeljeno'  => $prihvatioMode,
        ];

        // Opseg po tome ko je PRIHVATIO zadatak
        $uScope = function($z) use ($prihvatioMode) {
            if ($prihvatioMode === 'svi') return true;
            return (int)($z['prihvaceno_id'] ?? 0) === (int)$prihvatioMode;
        };

        $zsort = in_array($_GET['zsort'] ?? '', ['rok_asc','rok_desc','default'])
                 ? ($_GET['zsort'] ?? 'default')
                 : 'default';

        $svi_zadaci = InterniZadatak::getAll([
            'status'     => $filters['status'],
            'q'          => $filters['q'],
            'kategorija' => $filters['kategorija'],
        ]);

        // Primeni opseg (svi / konkretna osoba)
        $svi_zadaci = array_values(array_filter($svi_zadaci, $uScope));

        // Dohvati dodatne podatke
        foreach ($svi_zadaci as &$z) {
            if ($z['prihvaceno_id'] ?? null) {
                $s = $this->db->prepare("SELECT ime FROM admin_korisnici WHERE id=?");
                $s->execute([$z['prihvaceno_id']]);
                $z['prihvaceno_ime'] = $s->fetchColumn() ?: null;
            } else {
                $z['prihvaceno_ime'] = null;
            }
            $s = $this->db->prepare("SELECT COUNT(*) FROM zadaci_komentari WHERE zadatak_id=?");
            $s->execute([$z['id']]);
            $z['komentar_count'] = (int)$s->fetchColumn();
            $s = $this->db->prepare("
                SELECT zk.*, k.ime AS autor_ime FROM zadaci_komentari zk
                JOIN admin_korisnici k ON zk.autor_id=k.id
                WHERE zk.zadatak_id=? ORDER BY zk.created_at ASC
            ");
            $s->execute([$z['id']]);
            $z['komentari'] = $s->fetchAll(\PDO::FETCH_ASSOC);
        }
        unset($z);

        // Sakrij završene po defaultu
if ($filters['status'] === '') {
    $svi_zadaci = array_values(array_filter($svi_zadaci, fn($z) => $z['status'] !== 'zavrseno'));
}

        // Grupa za default redosled:
        // 0 = neprihvaćen + dodeljen (čeka prihvatanje), 1 = neprihvaćen + nedodeljen,
        // 2 = moji prihvaćeni, 3 = tuđi prihvaćeni
        $grupa = function($z) use ($uid) {
            if (empty($z['prihvaceno_id'])) {
                return !empty($z['dodeljeno_id']) ? 0 : 1;
            }
            return ((int)$z['prihvaceno_id'] === (int)$uid) ? 2 : 3;
        };

        // Sortiranje
        usort($svi_zadaci, function($a, $b) use ($zsort, $grupa) {
            if ($zsort === 'rok_asc') {
                if (!$a['rok'] && !$b['rok']) return $b['id'] - $a['id'];
                if (!$a['rok']) return 1;
                if (!$b['rok']) return -1;
                return strcmp($a['rok'], $b['rok']);
            }
            if ($zsort === 'rok_desc') {
                if (!$a['rok'] && !$b['rok']) return $b['id'] - $a['id'];
                if (!$a['rok']) return 1;
                if (!$b['rok']) return -1;
                return strcmp($b['rok'], $a['rok']);
            }
            // Default: po grupi (neprihvaćeni → nedodeljeni → moji → ostali),
            // unutar grupe po datumu unosa opadajuće (najnoviji prvi)
            $ga = $grupa($a); $gb = $grupa($b);
            if ($ga !== $gb) return $ga - $gb;
            $ca = $a['datum_kreiranja'] ?? ''; $cb = $b['datum_kreiranja'] ?? '';
            if ($ca !== $cb) return strcmp($cb, $ca);
            return $b['id'] - $a['id'];
        });

        $ukupno     = count($svi_zadaci);
        $stranica   = max(1, (int)($_GET['str'] ?? 1));
        $po_stranici = 15;
        $stranica   = min($stranica, max(1, (int)ceil($ukupno / $po_stranici)));
        $zadaci     = array_slice($svi_zadaci, ($stranica-1)*$po_stranici, $po_stranici);

        $kategorije = InterniZadatak::getKategorije();
        $korisnici  = Korisnik::getAll();

        // Ko sme da zadaje (vidi "+ Novi zadatak", ✎, 🗑) i lista primalaca za dodelu
        $mozeZadati = in_array(Auth::uloga(), self::ZADACI_ZADAJU, true);
        $primaoci   = array_values(array_filter($korisnici, function($u) {
            return !empty($u['aktivan']) && in_array($u['uloga'], self::ZADACI_PRIMAJU, true);
        }));

        // Brojači prate prikazani opseg (isti scope + q + kategorija, sve statuse)
        $scopeBase = InterniZadatak::getAll([
            'q'          => $filters['q'],
            'kategorija' => $filters['kategorija'],
        ]);
        $scopeBase = array_filter($scopeBase, $uScope);
        $svi      = count($scopeBase);
        $otvoreno = count(array_filter($scopeBase, fn($z) => $z['status'] === 'otvoreno'));
        $u_toku   = count(array_filter($scopeBase, fn($z) => $z['status'] === 'u_toku'));
        $zavrseno = count(array_filter($scopeBase, fn($z) => $z['status'] === 'zavrseno'));

        $this->view('zadaci/index', compact(
            'zadaci', 'kategorije', 'korisnici', 'primaoci', 'filters',
            'svi', 'otvoreno', 'u_toku', 'zavrseno', 'uid', 'mozeZadati',
            'stranica', 'po_stranici', 'ukupno', 'zsort'
        ));
    }

    public function ajax(string $action, int $id): void
    {
        InterniZadatak::migrate();
        $uid = Auth::id();

        switch ($action) {
            case 'zadatak_add':
                $this->requireZadaje();
                $tekst = trim($_POST['tekst'] ?? '');
                if (!$tekst) $this->json(['ok' => false, 'err' => 'Tekst je obavezan.']);
                $dodeljeno = (int)($_POST['dodeljeno_id'] ?? 0) ?: null;
                $newId = InterniZadatak::create([
                    'tekst'        => mb_substr($tekst, 0, 2000),
                    'kategorija'   => mb_substr(trim($_POST['kategorija'] ?? ''), 0, 100),
                    'status'       => 'otvoreno',
                    'rok'          => $_POST['rok'] ?? '',
                    'kreirao_id'   => $uid,
                    'dodeljeno_id' => $dodeljeno,
                ]);

                // Push obaveštenje primaocima (dodeljeni, ili svi AT/AF) — osim onome ko zadaje
                if ($dodeljeno) {
                    $primaociIds = [$dodeljeno];
                } else {
                    $ph = implode(',', array_fill(0, count(self::ZADACI_PRIMAJU), '?'));
                    $st = $this->db->prepare("SELECT id FROM admin_korisnici WHERE uloga IN ($ph) AND aktivan=1");
                    $st->execute(self::ZADACI_PRIMAJU);
                    $primaociIds = $st->fetchAll(\PDO::FETCH_COLUMN);
                }
                $primaociIds = array_filter($primaociIds, fn($x) => (int)$x !== (int)$uid);
                PushController::notifyUsers($primaociIds, [
                    'title' => '📋 Novi zadatak',
                    'body'  => mb_substr($tekst, 0, 120),
                    'url'   => BASE_URL . '/?page=zadaci',
                    'tag'   => 'zadatak-' . $newId,
                    'icon'  => BASE_URL . '/public/icon-192.png',
                ]);

                $this->json(['ok' => true, 'id' => $newId]);
                break;

            case 'zadatak_status':
                $status = $_POST['status'] ?? '';
                if (!in_array($status, ['otvoreno', 'u_toku', 'zavrseno'])) {
                    $this->json(['ok' => false, 'err' => 'Nevalidan status.']);
                }
                InterniZadatak::updateStatus($id, $status);
                $this->json(['ok' => true]);
                break;

            case 'zadatak_prihvati':
                $stmt = $this->db->prepare("SELECT dodeljeno_id, prihvaceno_id FROM interni_zadaci WHERE id=?");
                $stmt->execute([$id]);
                $z = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$z) $this->json(['ok' => false, 'err' => 'Zadatak nije pronađen.']);
                if ($z['prihvaceno_id']) $this->json(['ok' => false, 'err' => 'Zadatak je već prihvaćen.']);

                $this->db->prepare("
                    UPDATE interni_zadaci
                    SET prihvaceno_id=?, prihvaceno_at=NOW(), status='u_toku'
                    WHERE id=?
                ")->execute([$uid, $id]);
                $this->json(['ok' => true]);
                break;

            case 'zadatak_komentar':
                $tekst = trim($_POST['tekst'] ?? '');
                if (!$tekst) $this->json(['ok' => false, 'err' => 'Komentar je prazan.']);

                // Svako može komentarisati zadatke kojima pripada
                $this->db->prepare("
                    INSERT INTO zadaci_komentari (zadatak_id, autor_id, tekst) VALUES (?,?,?)
                ")->execute([$id, $uid, $tekst]);
                $this->json(['ok' => true]);
                break;

            case 'zadatak_edit':
                $this->requireZadaje();
                $tekst = trim($_POST['tekst'] ?? '');
                if (!$tekst) $this->json(['ok' => false, 'err' => 'Tekst je obavezan.']);
                InterniZadatak::update($id, [
                    'tekst'        => mb_substr($tekst, 0, 2000),
                    'kategorija'   => mb_substr(trim($_POST['kategorija'] ?? ''), 0, 100),
                    'status'       => $_POST['status'] ?? 'otvoreno',
                    'rok'          => $_POST['rok'] ?? '',
                    'dodeljeno_id' => (int)($_POST['dodeljeno_id'] ?? 0) ?: null,
                ]);
                $this->json(['ok' => true]);
                break;

            case 'zadatak_komentari_novi':
                $after = $_POST['after'] ?? '2000-01-01 00:00:00';
                $stmt  = $this->db->prepare("
                    SELECT zk.*, k.ime AS autor_ime
                    FROM zadaci_komentari zk
                    JOIN admin_korisnici k ON zk.autor_id = k.id
                    WHERE zk.zadatak_id = ? AND zk.created_at > ?
                    ORDER BY zk.created_at ASC
                ");
                $stmt->execute([$id, $after]);
                $novi = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $this->json(['ok' => true, 'komentari' => $novi]);
                break;

            case 'zadatak_delete':
                $this->requireZadaje();
                InterniZadatak::delete($id);
                $this->json(['ok' => true]);
                break;

            default:
                $this->json(['ok' => false, 'err' => 'Nepoznata akcija.']);
        }
    }

    private function requireZadaje(): void
    {
        if (!in_array(Auth::uloga(), self::ZADACI_ZADAJU, true)) {
            $this->json(['ok' => false, 'err' => 'Nemate pravo da zadajete zadatke.']);
        }
    }
}
