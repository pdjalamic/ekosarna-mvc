<?php
namespace Controllers;

use Core\Auth;
use Models\InterniZadatak;
use Models\Korisnik;

class ZadaciController extends \Core\Controller
{
    // Zadatke zadaju (i menjaju/brišu) samo ove uloge; primaju ih svi aktivni korisnici (bilo koji tim).
    const ZADACI_ZADAJU  = ['Direktor', 'Inženjer na gradilištu'];

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

        // Kapija za preuzimanje/pregled fajla (?dl=<id>) — proverava pravo, pa stream-uje i izlazi.
        if (isset($_GET['dl'])) {
            $this->streamFajl((int)$_GET['dl']);
            return;
        }

        // Prihvatio filter: '' (default) = svi, <id> = konkretna osoba
        $zdodRaw = $_GET['zdod'] ?? '';
        $prihvatioMode = (ctype_digit((string)$zdodRaw) && (int)$zdodRaw > 0) ? (int)$zdodRaw : 'svi';

        $jeDirektor = (Auth::uloga() === 'Direktor');

        // Učitaj članove SVIH zadataka jednim upitom (za vidljivost, redosled i prikaz).
        $clanoviMap = [];
        $rsCl = $this->db->query("
            SELECT zc.zadatak_id, zc.korisnik_id, zc.prihvatio, zc.prihvatio_at, k.ime AS korisnik_ime
            FROM zadaci_clanovi zc
            JOIN admin_korisnici k ON k.id = zc.korisnik_id
            ORDER BY zc.prihvatio DESC, k.ime ASC
        ");
        foreach ($rsCl->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $clanoviMap[(int)$r['zadatak_id']][] = $r;
        }
        $jeClanZad = function($z, $kid) use ($clanoviMap) {
            foreach ($clanoviMap[(int)$z['id']] ?? [] as $c) {
                if ((int)$c['korisnik_id'] === (int)$kid) return true;
            }
            return false;
        };

        // Učitaj nadolazeće (neposlate) podsetnike svih zadataka.
        $alarmiMap = [];
        $rsAl = $this->db->query("
            SELECT a.id, a.zadatak_id, a.korisnik_id, a.postavio_id, a.send_at, a.poruka,
                   k.ime AS korisnik_ime
            FROM zadaci_alarmi a
            LEFT JOIN admin_korisnici k ON k.id = a.korisnik_id
            WHERE a.poslato = 0
            ORDER BY a.send_at ASC
        ");
        foreach ($rsAl->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $alarmiMap[(int)$r['zadatak_id']][] = $r;
        }

        // Učitaj fajlove svih zadataka.
        $fajloviMap = [];
        $rsF = $this->db->query("
            SELECT zf.*, k.ime AS dodao_ime
            FROM zadaci_fajlovi zf
            LEFT JOIN admin_korisnici k ON k.id = zf.dodao_id
            ORDER BY zf.dodat_at ASC
        ");
        foreach ($rsF->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $fajloviMap[(int)$r['zadatak_id']][] = $r;
        }

        // Vidljivost: Direktor vidi sve; ostali vide samo zadatke kojih su član
        // (dodeljen/pozvan) ili koje su sami kreirali — neprihvaćen zadatak vidi
        // samo onaj kome je namenjen.
        $vidljivo = function($z) use ($uid, $jeDirektor, $jeClanZad) {
            if ($jeDirektor) return true;
            if ((int)$z['kreirao_id'] === (int)$uid) return true;
            return $jeClanZad($z, $uid);
        };

        $filters = [
            'status'     => $_GET['zstatus']    ?? '',
            'q'          => trim($_GET['zq']    ?? ''),
            'kategorija' => trim($_GET['zkat']  ?? ''),
            'dodeljeno'  => $prihvatioMode,
        ];

        // Opseg po tome ko je PRIHVATIO zadatak (član sa prihvatio=1)
        $uScope = function($z) use ($prihvatioMode, $clanoviMap) {
            if ($prihvatioMode === 'svi') return true;
            foreach ($clanoviMap[(int)$z['id']] ?? [] as $c) {
                if ((int)$c['korisnik_id'] === (int)$prihvatioMode && $c['prihvatio']) return true;
            }
            return false;
        };

        $zsort = in_array($_GET['zsort'] ?? '', ['rok_asc','rok_desc','default'])
                 ? ($_GET['zsort'] ?? 'default')
                 : 'default';

        $svi_zadaci = InterniZadatak::getAll([
            'status'     => $filters['status'],
            'q'          => $filters['q'],
            'kategorija' => $filters['kategorija'],
        ]);

        // Primeni opseg (svi / konkretna osoba) + vidljivost (član / kreator / Direktor)
        $svi_zadaci = array_values(array_filter($svi_zadaci, $uScope));
        $svi_zadaci = array_values(array_filter($svi_zadaci, $vidljivo));

        // Dohvati dodatne podatke
        foreach ($svi_zadaci as &$z) {
            $z['clanovi'] = $clanoviMap[(int)$z['id']] ?? [];
            $z['alarmi']  = $alarmiMap[(int)$z['id']] ?? [];
            $z['fajlovi'] = $fajloviMap[(int)$z['id']] ?? [];
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

        // Grupa za default (inicijalni) redosled:
        //  • Direktor vidi SVE: (0) neprihvaćeni → (1) prihvaćeni.
        //  • Ostali: (0) neprihvaćeni → (1) koje sam JA prihvatio → (2) tuđi prihvaćeni.
        // Unutar grupe: najnoviji unos prvi (id opadajuće).
        $grupa = function($z) use ($uid, $jeDirektor, $clanoviMap) {
            $anyAccepted = false; $iAccepted = false;
            foreach ($clanoviMap[(int)$z['id']] ?? [] as $c) {
                if ($c['prihvatio']) {
                    $anyAccepted = true;
                    if ((int)$c['korisnik_id'] === (int)$uid) $iAccepted = true;
                }
            }
            if ($jeDirektor) {
                return $anyAccepted ? 1 : 0;
            }
            if (!$anyAccepted) return 0;
            return $iAccepted ? 1 : 2;
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
            // Default: po grupi (vidi $grupa), unutar grupe najnoviji unos prvi (id opadajuće)
            $ga = $grupa($a); $gb = $grupa($b);
            if ($ga !== $gb) return $ga - $gb;
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
        // Primaoci: svi aktivni korisnici (bilo koji tim), osim samog zadavaoca (nema samododele)
        $primaoci   = array_values(array_filter($korisnici, function($u) use ($uid) {
            return !empty($u['aktivan']) && (int)$u['id'] !== (int)$uid;
        }));

        // Brojači prate prikazani opseg (isti scope + q + kategorija, sve statuse)
        $scopeBase = InterniZadatak::getAll([
            'q'          => $filters['q'],
            'kategorija' => $filters['kategorija'],
        ]);
        $scopeBase = array_filter($scopeBase, $uScope);
        $scopeBase = array_filter($scopeBase, $vidljivo);
        $svi      = count($scopeBase);
        $otvoreno = count(array_filter($scopeBase, fn($z) => $z['status'] === 'otvoreno'));
        $u_toku   = count(array_filter($scopeBase, fn($z) => $z['status'] === 'u_toku'));
        $zavrseno = count(array_filter($scopeBase, fn($z) => $z['status'] === 'zavrseno'));

        $this->view('zadaci/index', compact(
            'zadaci', 'kategorije', 'korisnici', 'primaoci', 'filters',
            'svi', 'otvoreno', 'u_toku', 'zavrseno', 'uid', 'mozeZadati',
            'stranica', 'po_stranici', 'ukupno', 'zsort', 'jeDirektor'
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
                // Prima jednu ili više osoba (dodeljeno_id ili dodeljeno_id[])
                $raw = $_POST['dodeljeno_id'] ?? [];
                $dodeljeniIds = array_values(array_unique(array_filter(
                    array_map('intval', is_array($raw) ? $raw : [$raw]),
                    fn($x) => $x > 0
                )));
                if (!$dodeljeniIds) $this->json(['ok' => false, 'err' => 'Izaberi kome dodeljuješ zadatak.']);
                $newId = InterniZadatak::create([
                    'tekst'        => mb_substr($tekst, 0, 2000),
                    'kategorija'   => mb_substr(trim($_POST['kategorija'] ?? ''), 0, 100),
                    'status'       => 'otvoreno',
                    'rok'          => $_POST['rok'] ?? '',
                    'kreirao_id'   => $uid,
                    'dodeljeno_id' => $dodeljeniIds[0], // prvi u staru kolonu (kompatibilnost)
                ]);
                // Svi dodeljeni postaju članovi (svako prihvata zasebno)
                InterniZadatak::dodajClanove($newId, $dodeljeniIds, null);

                // Push obaveštenje svim dodeljenima — osim ako je sam sebi zadao
                $primaociIds = array_values(array_filter($dodeljeniIds, fn($x) => (int)$x !== (int)$uid));
                PushController::notifyKanali($primaociIds, [
                    'title' => '📋 Novi zadatak',
                    'body'  => mb_substr($tekst, 0, 120),
                    'url'   => BASE_URL . '/?page=zadaci&openz=' . $newId,
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
                $stmt = $this->db->prepare("SELECT dodeljeno_id, prihvaceno_id, kreirao_id, tekst FROM interni_zadaci WHERE id=?");
                $stmt->execute([$id]);
                $z = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$z) $this->json(['ok' => false, 'err' => 'Zadatak nije pronađen.']);
                // Samo član (dodeljen/pozvan) može da prihvati; svako prihvata zasebno.
                if (!InterniZadatak::jeClan($id, $uid)) {
                    $this->json(['ok' => false, 'err' => 'Niste pozvani na ovaj zadatak.']);
                }
                InterniZadatak::prihvati($id, $uid);

                // Stara kolona: prvi koji prihvati (kompatibilnost sa prikazom)
                if (empty($z['prihvaceno_id'])) {
                    $this->db->prepare("UPDATE interni_zadaci SET prihvaceno_id=?, prihvaceno_at=NOW() WHERE id=? AND prihvaceno_id IS NULL")
                             ->execute([$uid, $id]);
                }
                // Status u_toku na prvo prihvatanje (ne diraj završene)
                $this->db->prepare("UPDATE interni_zadaci SET status='u_toku' WHERE id=? AND status='otvoreno'")
                         ->execute([$id]);

                // Obavesti zadavaoca da je zadatak prihvaćen — osim ako je sam sebi zadao
                $tekstZad = (string)($z['tekst'] ?? '');
                $primaociIds = array_filter([(int)$z['kreirao_id']], fn($x) => $x && (int)$x !== (int)$uid);
                if ($primaociIds) {
                    PushController::notifyKanali($primaociIds, [
                        'title' => '✅ Zadatak prihvaćen',
                        'body'  => Auth::ime() . ' je prihvatio zadatak: ' . mb_substr($tekstZad, 0, 100),
                        'url'   => BASE_URL . '/?page=zadaci&openz=' . $id,
                        'tag'   => 'zadatak-' . $id,
                        'icon'  => BASE_URL . '/public/icon-192.png',
                    ]);
                }
                $this->json(['ok' => true]);
                break;

            case 'zadatak_pozovi':
                $st = $this->db->prepare("SELECT kreirao_id, tekst FROM interni_zadaci WHERE id=?");
                $st->execute([$id]);
                $z = $st->fetch(\PDO::FETCH_ASSOC);
                if (!$z) $this->json(['ok' => false, 'err' => 'Zadatak nije pronađen.']);
                // Pozivati može svako ko je na zadatku (član), kreator ili Direktor.
                $sme = InterniZadatak::jeClan($id, $uid)
                       || (int)$z['kreirao_id'] === (int)$uid
                       || Auth::uloga() === 'Direktor';
                if (!$sme) $this->json(['ok' => false, 'err' => 'Nemate pravo da pozivate na ovaj zadatak.']);

                $raw = $_POST['osobe'] ?? [];
                $ids = array_values(array_unique(array_filter(
                    array_map('intval', is_array($raw) ? $raw : [$raw]),
                    fn($x) => $x > 0
                )));
                if (!$ids) $this->json(['ok' => false, 'err' => 'Izaberi koga pozivaš.']);

                // Notifikuj samo ZAISTA nove članove (da ne dupliramo poziv)
                $postojeci = InterniZadatak::clanIds($id);
                $novi = array_values(array_diff($ids, $postojeci));
                if (!$novi) $this->json(['ok' => false, 'err' => 'Sve izabrane osobe su već na zadatku.']);

                InterniZadatak::dodajClanove($id, $novi, $uid);

                $primaociIds = array_values(array_filter($novi, fn($x) => (int)$x !== (int)$uid));
                if ($primaociIds) {
                    PushController::notifyKanali($primaociIds, [
                        'title' => '➕ Pozvan si na zadatak',
                        'body'  => Auth::ime() . ' te je pozvao: ' . mb_substr((string)$z['tekst'], 0, 100),
                        'url'   => BASE_URL . '/?page=zadaci&openz=' . $id,
                        'tag'   => 'zadatak-' . $id,
                        'icon'  => BASE_URL . '/public/icon-192.png',
                    ]);
                }
                $this->json(['ok' => true]);
                break;

            case 'zadatak_alarm_add':
                // $id = zadatak_id
                $st = $this->db->prepare("SELECT kreirao_id FROM interni_zadaci WHERE id=?");
                $st->execute([$id]);
                $z = $st->fetch(\PDO::FETCH_ASSOC);
                if (!$z) $this->json(['ok' => false, 'err' => 'Zadatak nije pronađen.']);
                // Podsetnik može da postavi svako ko ima pristup zadatku.
                $vidi = InterniZadatak::jeClan($id, $uid)
                        || (int)$z['kreirao_id'] === (int)$uid
                        || Auth::uloga() === 'Direktor';
                if (!$vidi) $this->json(['ok' => false, 'err' => 'Nemate pristup ovom zadatku.']);

                $sendAt = $this->normAlarmTime($_POST['kada'] ?? '');
                if (!$sendAt) $this->json(['ok' => false, 'err' => 'Neispravan datum/vreme podsetnika.']);
                if (strtotime($sendAt) < time()) $this->json(['ok' => false, 'err' => 'Podsetnik mora biti u budućnosti.']);

                $tip = ($_POST['tip'] ?? 'licni') === 'tim' ? 'tim' : 'licni';
                $korisnikId = $uid; // lični podsetnik
                if ($tip === 'tim') {
                    // Za ceo tim sme kreator ili Direktor/Inženjer (ZADACI_ZADAJU).
                    $smeTim = (int)$z['kreirao_id'] === (int)$uid
                              || in_array(Auth::uloga(), self::ZADACI_ZADAJU, true);
                    if (!$smeTim) $this->json(['ok' => false, 'err' => 'Podsetnik za ceo tim može da postavi kreator ili Direktor/Inženjer.']);
                    $korisnikId = null;
                }
                $poruka = mb_substr(trim($_POST['poruka'] ?? ''), 0, 255);
                InterniZadatak::dodajAlarm($id, $korisnikId, $uid, $sendAt, $poruka);
                $this->json(['ok' => true]);
                break;

            case 'zadatak_alarm_delete':
                // $id = alarm id
                $st = $this->db->prepare("
                    SELECT a.postavio_id, z.kreirao_id
                    FROM zadaci_alarmi a JOIN interni_zadaci z ON z.id = a.zadatak_id
                    WHERE a.id = ?
                ");
                $st->execute([$id]);
                $a = $st->fetch(\PDO::FETCH_ASSOC);
                if (!$a) $this->json(['ok' => false, 'err' => 'Podsetnik nije pronađen.']);
                $sme = (int)$a['postavio_id'] === (int)$uid
                       || (int)$a['kreirao_id'] === (int)$uid
                       || Auth::uloga() === 'Direktor';
                if (!$sme) $this->json(['ok' => false, 'err' => 'Nemate pravo da otkažete ovaj podsetnik.']);
                InterniZadatak::obrisiAlarm($id);
                $this->json(['ok' => true]);
                break;

            case 'zadatak_fajl_add':
                // $id = zadatak_id. Kačiti može svako ko je na zadatku (član), kreator ili Direktor.
                $st = $this->db->prepare("SELECT kreirao_id, tekst FROM interni_zadaci WHERE id=?");
                $st->execute([$id]);
                $z = $st->fetch(\PDO::FETCH_ASSOC);
                if (!$z) $this->json(['ok' => false, 'err' => 'Zadatak nije pronađen.']);
                $sme = InterniZadatak::jeClan($id, $uid)
                       || (int)$z['kreirao_id'] === (int)$uid
                       || Auth::uloga() === 'Direktor';
                if (!$sme) $this->json(['ok' => false, 'err' => 'Nemate pravo da kačite fajl na ovaj zadatak.']);

                $files = $_FILES['fajlovi'] ?? null;
                if (!$files || empty($files['name']) || !is_array($files['name'])) {
                    $this->json(['ok' => false, 'err' => 'Nema fajla.']);
                }

                $dir = UPLOAD_DIR . 'zadaci/';
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                // Blokiraj direktan HTTP pristup folderu — fajl ide samo kroz ?dl kapiju.
                $hta = $dir . '.htaccess';
                if (!is_file($hta)) {
                    @file_put_contents($hta,
                        "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n" .
                        "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n"
                    );
                }
                $dozvoljeni = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','zip','rar'];

                $sacuvano = []; $greske = []; $noviFajlovi = [];
                $n = count($files['name']);
                for ($i = 0; $i < $n; $i++) {
                    $errc = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                    $orig = $files['name'][$i] ?? '';
                    if ($errc === UPLOAD_ERR_NO_FILE) continue;
                    if ($errc !== UPLOAD_ERR_OK) {
                        $greske[] = $orig . ': ' . (in_array($errc, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'prevelik (server limit)' : 'greška uploada');
                        continue;
                    }
                    $size = (int)($files['size'][$i] ?? 0);
                    if ($size <= 0)                  { $greske[] = $orig . ': prazan';            continue; }
                    if ($size > 25 * 1024 * 1024)    { $greske[] = $orig . ': veći od 25 MB';     continue; }
                    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                    if (!in_array($ext, $dozvoljeni, true)) { $greske[] = $orig . ': tip nije dozvoljen'; continue; }

                    $fname = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
                    if (!move_uploaded_file($files['tmp_name'][$i], $dir . $fname)) {
                        $greske[] = $orig . ': snimanje nije uspelo';
                        continue;
                    }
                    $tip = ($files['type'][$i] ?? '') ?: $this->mimeFromExt($ext);
                    $nazivClean = mb_substr($orig, 0, 255);
                    $fid = InterniZadatak::dodajFajl($id, $nazivClean, $fname, $tip, $size, $uid);
                    $noviFajlovi[] = [
                        'id'        => $fid,
                        'naziv'     => $nazivClean,
                        'velicina'  => $size,
                        'ext'       => $ext,
                        'dodao_ime' => Auth::ime(),
                        'url'       => BASE_URL . '/?page=zadaci&dl=' . $fid,
                    ];
                    $sacuvano[] = $orig;
                }

                if (!$sacuvano) {
                    $this->json(['ok' => false, 'err' => 'Nijedan fajl nije sačuvan. ' . implode('; ', $greske)]);
                }

                // Notifikacija (jednom): svi koji su prihvatili + kreator, osim onoga ko kači.
                $primaociIds = InterniZadatak::prihvatiliIds($id);
                $primaociIds[] = (int)$z['kreirao_id'];
                $primaociIds = array_values(array_unique(array_filter(
                    $primaociIds, fn($x) => $x && (int)$x !== (int)$uid
                )));
                if ($primaociIds) {
                    $br   = count($sacuvano);
                    $opis = $br === 1
                        ? 'fajl „' . $sacuvano[0] . '"'
                        : ($br . ' ' . ($br < 5 ? 'fajla' : 'fajlova'));
                    PushController::notifyKanali($primaociIds, [
                        'title' => '📎 Novi fajl na zadatku',
                        'body'  => Auth::ime() . ' je dodao ' . $opis . ' — ' . mb_substr((string)$z['tekst'], 0, 80),
                        'url'   => BASE_URL . '/?page=zadaci&openz=' . $id,
                        'tag'   => 'zadatak-' . $id,
                        'icon'  => BASE_URL . '/public/icon-192.png',
                    ]);
                }
                $this->json(['ok' => true, 'sacuvano' => count($sacuvano), 'greske' => $greske, 'fajlovi' => $noviFajlovi]);
                break;

            case 'zadatak_fajl_delete':
                // $id = fajl id. Briše onaj ko je okačio, kreator ili Direktor.
                $f = InterniZadatak::getFajl($id);
                if (!$f) $this->json(['ok' => false, 'err' => 'Fajl nije pronađen.']);
                $sme = (int)$f['dodao_id'] === (int)$uid
                       || (int)$f['kreirao_id'] === (int)$uid
                       || Auth::uloga() === 'Direktor';
                if (!$sme) $this->json(['ok' => false, 'err' => 'Nemate pravo da obrišete ovaj fajl.']);
                InterniZadatak::obrisiFajl($id);
                $this->json(['ok' => true]);
                break;

            case 'zadatak_komentar':
                $tekst = trim($_POST['tekst'] ?? '');
                if (!$tekst) $this->json(['ok' => false, 'err' => 'Komentar je prazan.']);

                // Svako može komentarisati zadatke kojima pripada
                $this->db->prepare("
                    INSERT INTO zadaci_komentari (zadatak_id, autor_id, tekst) VALUES (?,?,?)
                ")->execute([$id, $uid, $tekst]);
                $komId = (int)$this->db->lastInsertId();

                // Vrati upisani komentar (da klijent sinhronizuje poll-kursor i prikaz)
                $st = $this->db->prepare("
                    SELECT zk.id, zk.autor_id, zk.tekst, zk.created_at, k.ime AS autor_ime
                    FROM zadaci_komentari zk JOIN admin_korisnici k ON zk.autor_id=k.id
                    WHERE zk.id=?
                ");
                $st->execute([$komId]);
                $kom = $st->fetch(\PDO::FETCH_ASSOC);

                // Obavesti SVE učesnike razgovora, osim autora komentara:
                //   kreator + svi članovi (dodeljeni/pozvani) + svi koji su ranije komentarisali.
                // (Član dobija i PRE prihvatanja; treća osoba u prepisci takođe.)
                $st = $this->db->prepare("SELECT kreirao_id FROM interni_zadaci WHERE id=?");
                $st->execute([$id]);
                $zk = $st->fetch(\PDO::FETCH_ASSOC) ?: [];
                $ucesnici = InterniZadatak::clanIds($id);
                $ucesnici[] = (int)($zk['kreirao_id'] ?? 0);
                $ca = $this->db->prepare("SELECT DISTINCT autor_id FROM zadaci_komentari WHERE zadatak_id=?");
                $ca->execute([$id]);
                foreach ($ca->fetchAll(\PDO::FETCH_COLUMN) as $aid) {
                    $ucesnici[] = (int)$aid;
                }
                $primaociIds = array_values(array_unique(array_filter(
                    $ucesnici,
                    fn($x) => $x && (int)$x !== (int)$uid
                )));
                if ($primaociIds) {
                    PushController::notifyKanali($primaociIds, [
                        'title' => '💬 Komentar na zadatak',
                        'body'  => 'Komentar na zadatak od ' . Auth::ime() . ': ' . mb_substr($tekst, 0, 50),
                        'url'   => BASE_URL . '/?page=zadaci&openz=' . $id,
                        'tag'   => 'zadatak-' . $id,
                        'icon'  => BASE_URL . '/public/icon-192.png',
                    ]);
                }
                $this->json(['ok' => true, 'komentar' => $kom]);
                break;

            case 'zadatak_edit':
                $this->requireZadaje();
                $tekst = trim($_POST['tekst'] ?? '');
                if (!$tekst) $this->json(['ok' => false, 'err' => 'Tekst je obavezan.']);
                $dodeljeno = (int)($_POST['dodeljeno_id'] ?? 0) ?: null;
                InterniZadatak::update($id, [
                    'tekst'        => mb_substr($tekst, 0, 2000),
                    'kategorija'   => mb_substr(trim($_POST['kategorija'] ?? ''), 0, 100),
                    'status'       => $_POST['status'] ?? 'otvoreno',
                    'rok'          => $_POST['rok'] ?? '',
                    'dodeljeno_id' => $dodeljeno,
                ]);
                // Ako je kroz izmenu dodeljena osoba, osiguraj da je član (da vidi zadatak).
                if ($dodeljeno) InterniZadatak::dodajClanove($id, [$dodeljeno], null);
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

    /** "YYYY-MM-DDTHH:MM" (datetime-local) -> "YYYY-MM-DD HH:MM:SS"; '' ako neispravno. */
    private function normAlarmTime(string $v): string
    {
        $v = str_replace('T', ' ', trim($v));
        if ($v === '') return '';
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v)) $v .= ':00';
        $ts = strtotime($v);
        return $ts ? date('Y-m-d H:i:s', $ts) : '';
    }

    /**
     * Šalje dospele podsetnike (poslato=0 AND send_at<=NOW()). Zove ga zadaci_cron.php.
     * URL je host-nezavisan (/mvc/...) jer u cron-u BASE_URL nije pouzdan; sw.js ga re-bazira.
     */
    public function posaljiAlarme(): array
    {
        InterniZadatak::migrate();
        $poslato = 0; $log = [];

        $due = $this->db->query("
            SELECT a.id, a.zadatak_id, a.korisnik_id, a.poruka, z.tekst, z.status
            FROM zadaci_alarmi a
            JOIN interni_zadaci z ON z.id = a.zadatak_id
            WHERE a.poslato = 0 AND a.send_at <= NOW()
            ORDER BY a.send_at ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $markStmt = $this->db->prepare("UPDATE zadaci_alarmi SET poslato=1 WHERE id=?");

        foreach ($due as $a) {
            // Završen zadatak: ne zvoni, ali markiraj da podsetnik ne visi.
            if (($a['status'] ?? '') === 'zavrseno') {
                $markStmt->execute([$a['id']]);
                $log[] = "Alarm #{$a['id']} preskočen (zadatak završen).";
                continue;
            }

            $primaoci = $a['korisnik_id']
                ? [(int)$a['korisnik_id']]
                : InterniZadatak::clanIds((int)$a['zadatak_id']);
            $primaoci = array_values(array_unique(array_filter($primaoci)));

            if ($primaoci) {
                $poruka = trim((string)$a['poruka']) !== ''
                    ? $a['poruka']
                    : mb_substr((string)$a['tekst'], 0, 100);
                PushController::notifyKanali($primaoci, [
                    'title' => '⏰ Podsetnik za zadatak',
                    'body'  => $poruka,
                    'url'   => '/mvc/?page=zadaci&openz=' . (int)$a['zadatak_id'],
                    'tag'   => 'zadatak-alarm-' . (int)$a['id'],
                    'icon'  => '/mvc/public/icon-192.png',
                ]);
                $poslato++;
            }
            $markStmt->execute([$a['id']]);
            $log[] = "Alarm #{$a['id']} (zadatak {$a['zadatak_id']}) -> " . count($primaoci) . " primaoca.";
        }

        return ['poslato' => $poslato, 'log' => $log];
    }

    /**
     * Stream fajla sa zadatka uz proveru pristupa.
     * Preuzeti sme: onaj ko je PRIHVATIO zadatak, kreator, Direktor ili onaj ko je okačio fajl.
     * ?download=1 forsira preuzimanje (attachment), inače inline (za openModal pregled).
     */
    private function streamFajl(int $fid): void
    {
        $uid = Auth::id();
        $f = InterniZadatak::getFajl($fid);
        if (!$f) { http_response_code(404); echo 'Fajl ne postoji.'; exit; }

        $st = $this->db->prepare("SELECT 1 FROM zadaci_clanovi WHERE zadatak_id=? AND korisnik_id=? AND prihvatio=1");
        $st->execute([(int)$f['zadatak_id'], $uid]);
        $prihvatio = (bool)$st->fetchColumn();

        $sme = $prihvatio
               || (int)$f['kreirao_id'] === (int)$uid
               || (int)$f['dodao_id'] === (int)$uid
               || Auth::uloga() === 'Direktor';
        if (!$sme) { http_response_code(403); echo 'Nemate pristup ovom fajlu (potrebno je prihvatiti zadatak).'; exit; }

        $path = UPLOAD_DIR . 'zadaci/' . $f['putanja'];
        if (!is_file($path)) { http_response_code(404); echo 'Fajl nije pronađen na disku.'; exit; }

        $dispo = isset($_GET['download']) ? 'attachment' : 'inline';
        $naziv = $f['naziv'];
        header('Content-Type: ' . ($f['tip'] ?: 'application/octet-stream'));
        header("Content-Disposition: $dispo; filename=\"" . addslashes($naziv) . "\"; filename*=UTF-8''" . rawurlencode($naziv));
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
            'gif' => 'image/gif',  'webp' => 'image/webp', 'pdf' => 'application/pdf',
            'txt' => 'text/plain',  'csv' => 'text/csv',    'zip' => 'application/zip',
            'doc' => 'application/msword',
            'docx'=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx'=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }
}
