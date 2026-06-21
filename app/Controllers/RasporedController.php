<?php
namespace Controllers;

use Core\Auth;

class RasporedController extends \Core\Controller
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::get();
    }

    public function index(): void
    {
        Auth::requireKancelarija();

        $boje = ['#1a3a6e', '#2563eb', '#7c3aed', '#0891b2', '#059669', '#b45309'];

        // Ako nema datum parametra, nađi nedelju sa najnovijim unosom
        if (!isset($_GET['datum'])) {
            $najnovijaStmt = $this->db->query("
                SELECT rn.datum_od FROM radne_nedelje rn
                ORDER BY rn.datum_od DESC LIMIT 1
            ");
            $najnovija = $najnovijaStmt->fetchColumn();
            $datum = $najnovija ?: date('Y-m-d');
        } else {
            $datum = $_GET['datum'];
        }

        $ts    = strtotime($datum);
        $dow   = (int)date('N', $ts);
        $ponedeljak = date('Y-m-d', strtotime('-' . ($dow - 1) . ' days', $ts));
        $petak      = date('Y-m-d', strtotime('+' . (5 - $dow) . ' days', $ts));
        $subota     = date('Y-m-d', strtotime('+' . (6 - $dow) . ' days', $ts));

        $stmt = $this->db->prepare("SELECT * FROM radne_nedelje WHERE datum_od=? LIMIT 1");
        $stmt->execute([$ponedeljak]);
        $nedelja = $stmt->fetch(\PDO::FETCH_ASSOC);

        $dani = [];
        $nacrti = [];
        if ($nedelja) {
            $stmt = $this->db->prepare("SELECT * FROM raspored_dani WHERE nedelja_id=? ORDER BY datum ASC");
            $stmt->execute([$nedelja['id']]);
            $dani = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($dani as &$dan) {
                $stmt = $this->db->prepare("
                    SELECT rs.*, g.naziv AS gradiliste_naziv
                    FROM raspored_stavke rs
                    LEFT JOIN gradilista g ON rs.gradiliste_id = g.id
                    WHERE rs.dan_id = ?
                    ORDER BY rs.redosled ASC, rs.id ASC
                ");
                $stmt->execute([$dan['id']]);
                $stavke = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                $uid_for_count = Auth::id();
                foreach ($stavke as &$s) {
                    $stmt2 = $this->db->prepare("
                        SELECT rr.radnik_id, rr.vreme_od, rr.vreme_do, k.ime
                        FROM raspored_radnici rr
                        JOIN admin_korisnici k ON rr.radnik_id = k.id
                        WHERE rr.stavka_id = ?
                        ORDER BY k.ime
                    ");
                    $stmt2->execute([$s['id']]);
                    $s['radnici'] = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

                    // Ukupan broj poruka
                    $pc = $this->db->prepare("SELECT COUNT(*) FROM raspored_poruke WHERE stavka_id=?");
                    $pc->execute([$s['id']]);
                    $s['poruka_count'] = (int)$pc->fetchColumn();

                    // Nove poruke — koristi raspored_vidjeno tabelu
                    $moja = $this->db->prepare("SELECT MAX(created_at) FROM raspored_poruke WHERE stavka_id=? AND autor_id=?");
                    $moja->execute([$s['id'], $uid_for_count]);
                    $poslednja_moja = $moja->fetchColumn();

                    $vStmt = $this->db->prepare("SELECT vidjeno_do FROM raspored_vidjeno WHERE stavka_id=? AND korisnik_id=?");
                    $vStmt->execute([$s['id'], $uid_for_count]);
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
                        $nova->execute([$s['id'], $uid_for_count, $referentni]);
                        $s['nove_poruke_count'] = (int)$nova->fetchColumn();
                        $s['nova_poruka'] = $s['nove_poruke_count'] > 0;
                    } else {
                        $s['nova_poruka'] = false;
                        $s['nove_poruke_count'] = 0;
                    }
                }
                unset($s);

                // Razdvoj objavljene (idu u glavnu tabelu) od nacrta (gornji blok)
                $objavljene = [];
                $nacrtStavke = [];
                foreach ($stavke as $st) {
                    if (($st['status'] ?? 'objavljeno') === 'nacrt') $nacrtStavke[] = $st;
                    else $objavljene[] = $st;
                }
                $dan['stavke'] = $objavljene;

                if ($nacrtStavke) {
                    $dan_index = (int)round((strtotime($dan['datum']) - strtotime($ponedeljak)) / 86400);
                    $nacrti[] = [
                        'dan_id'    => $dan['id'],
                        'datum'     => $dan['datum'],
                        'dan_index' => $dan_index,
                        'stavke'    => $nacrtStavke,
                    ];
                }
            }
            unset($dan);
        }

        // Unos materijala / dodela u raspored: teren + gradilište
        // (operativa + terenske uloge), bez admina (Direktor/AT/AF).
        $uloge_raspored = array_merge(Auth::ULOGE_OPERATER, Auth::ULOGE_ELEKTRICAR);
        $ph_elektricar = implode(',', array_fill(0, count($uloge_raspored), '?'));
        $stmt_elektricari = $this->db->prepare("
            SELECT id, ime, uloga FROM admin_korisnici
            WHERE uloga IN ($ph_elektricar) AND aktivan = 1
            ORDER BY ime
        ");
        $stmt_elektricari->execute($uloge_raspored);
        $elektricari = $stmt_elektricari->fetchAll(\PDO::FETCH_ASSOC);
        // Označi „vođe" (uvek ponuđene za odgovornog za materijal, i kad nisu na zadatku).
        foreach ($elektricari as &$_e) {
            $_e['vodja'] = in_array($_e['uloga'], Auth::ULOGE_ODGOVORAN_MAT, true) ? 1 : 0;
        }
        unset($_e);

        $gradilista = \Controllers\GradilistaController::getAktivna();

        $prethodna_nedelja = date('Y-m-d', strtotime($ponedeljak . ' -7 days'));
        $sledeca_nedelja   = date('Y-m-d', strtotime($ponedeljak . ' +7 days'));

        // Sve nedelje za dropdown
        $sve_nedelje = $this->db->query("
            SELECT datum_od, datum_do FROM radne_nedelje
            ORDER BY datum_od DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('raspored/index', compact(
            'nedelja', 'dani', 'nacrti', 'elektricari', 'gradilista',
            'ponedeljak', 'petak', 'subota', 'boje',
            'prethodna_nedelja', 'sledeca_nedelja', 'sve_nedelje'
        ));
    }

    public function ajax(string $action, int $id): void
    {
        Auth::requireKancelarija();

        switch ($action) {

            case 'raspored_init_nedelja':
                $datum_od = $_POST['datum_od'] ?? '';
                $datum_do = $_POST['datum_do'] ?? '';
                if (!$datum_od || !$datum_do) $this->json(['ok' => false, 'err' => 'Nema datuma.']);

                $stmt = $this->db->prepare("SELECT id FROM radne_nedelje WHERE datum_od=?");
                $stmt->execute([$datum_od]);
                $postojeca = $stmt->fetchColumn();
                if ($postojeca) {
                    $this->json(['ok' => true, 'id' => $postojeca, 'postojeca' => true]);
                }

                $stmt = $this->db->prepare("
                    INSERT INTO radne_nedelje (kreator_id, datum_od, datum_do, status)
                    VALUES (?, ?, ?, 'priprema')
                ");
                $stmt->execute([Auth::id(), $datum_od, $datum_do]);
                $nedelja_id = $this->db->lastInsertId();

                $boje = ['#1a3a6e', '#2563eb', '#7c3aed', '#0891b2', '#059669', '#b45309'];
                $ts = strtotime($datum_od);
                for ($i = 0; $i < 6; $i++) {
                    $datum = date('Y-m-d', strtotime("+$i days", $ts));
                    $stmt = $this->db->prepare("INSERT INTO raspored_dani (nedelja_id, datum, boja) VALUES (?, ?, ?)");
                    $stmt->execute([$nedelja_id, $datum, $boje[$i]]);
                }

                $this->json(['ok' => true, 'id' => $nedelja_id, 'postojeca' => false]);
                break;

            case 'raspored_dodaj_stavku':
                $dan_id        = (int)($_POST['dan_id'] ?? 0);
                $gradiliste_id = (int)($_POST['gradiliste_id'] ?? 0) ?: null;
                $opis          = trim($_POST['opis'] ?? '');
                $radnici_data  = json_decode($_POST['radnici_json'] ?? '[]', true);
                $izborTip      = in_array($_POST['obavesti_tip'] ?? 'odmah', ['odmah','zakazano','ne'], true) ? $_POST['obavesti_tip'] : 'odmah';
                $obavesti_at   = $this->normDatetime((string)($_POST['obavesti_at'] ?? ''));
                $obavesti_at_store = ($izborTip === 'zakazano') ? $obavesti_at : null;
                $odgovoran_id  = (int)($_POST['odgovoran_id'] ?? 0) ?: null;
                $odgovoran_ime = $this->imeRadnika($odgovoran_id);
                $status        = (($_POST['status'] ?? '') === 'nacrt') ? 'nacrt' : 'objavljeno';
                // Nacrt PAMTI izbor ($izborTip se upisuje u stavku) ali NE šalje/zakazuje sada
                // → za stvarno slanje koristimo $obavesti_tip='ne'. Šalje tek „Objavi".
                $obavesti_tip  = ($status === 'nacrt') ? 'ne' : $izborTip;

                if (!$dan_id) $this->json(['ok' => false, 'err' => 'Nema dana.']);

                // Dohvati info o danu
                $danStmt = $this->db->prepare("SELECT rd.datum, rn.datum_od, rn.datum_do FROM raspored_dani rd JOIN radne_nedelje rn ON rd.nedelja_id = rn.id WHERE rd.id=?");
                $danStmt->execute([$dan_id]);
                $danInfo = $danStmt->fetch(\PDO::FETCH_ASSOC);

                // Gradilište naziv
                $gNaziv = '';
                if ($gradiliste_id) {
                    $gs = $this->db->prepare("SELECT naziv FROM gradilista WHERE id=?");
                    $gs->execute([$gradiliste_id]);
                    $gNaziv = $gs->fetchColumn() ?: '';
                }

                $stmt = $this->db->prepare("SELECT COALESCE(MAX(redosled),0)+1 FROM raspored_stavke WHERE dan_id=?");
                $stmt->execute([$dan_id]);
                $redosled = (int)$stmt->fetchColumn();

                $stmt = $this->db->prepare("
                    INSERT INTO raspored_stavke (dan_id, gradiliste_id, opis, redosled, odgovoran_id, status, obavesti_tip, obavesti_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$dan_id, $gradiliste_id, $opis, $redosled, $odgovoran_id, $status, $izborTip, $obavesti_at_store]);
                $stavka_id = $this->db->lastInsertId();

                $datum_fmt   = $danInfo ? date('d.m.Y', strtotime($danInfo['datum'])) : '';
                $upozorenja  = [];
                $radnik_ids  = [];
                foreach ($radnici_data as $r) {
                    $radnik_id = (int)($r['id'] ?? 0);
                    $vreme_od  = $r['vreme_od'] ?? null;
                    $vreme_do  = $r['vreme_do'] ?? null;
                    if (!$radnik_id) continue;
                    $radnik_ids[] = $radnik_id;

                    // Proveri preklapanje
                    $overlap = $this->proveritPreklapanje($radnik_id, $dan_id, $stavka_id, $vreme_od, $vreme_do);
                    if ($overlap) {
                        $imeStmt = $this->db->prepare("SELECT ime FROM admin_korisnici WHERE id=?");
                        $imeStmt->execute([$radnik_id]);
                        $upozorenja[] = [
                            'radnik_id'  => $radnik_id,
                            'ime'        => $imeStmt->fetchColumn(),
                            'preklapanje'=> $overlap,
                        ];
                    }

                    $stmt = $this->db->prepare("
                        INSERT IGNORE INTO raspored_radnici (stavka_id, radnik_id, vreme_od, vreme_do)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$stavka_id, $radnik_id, $vreme_od ?: null, $vreme_do ?: null]);

                    // Obaveštenje
                    $jeOdgovoran = ($odgovoran_id && $odgovoran_id === $radnik_id);
                    $poruka = "📋 Dodeljen si na zadatak: {$opis}\n🏗️ Gradilište: {$gNaziv}\n📅 Datum: {$datum_fmt}\n🕐 Vreme: " . substr($vreme_od,0,5) . " – " . substr($vreme_do,0,5);
                    if ($jeOdgovoran) {
                        $poruka .= "\n📦 Ti si odgovoran za unos materijala";
                    } elseif ($odgovoran_id && $odgovoran_ime !== '') {
                        $poruka .= "\n📦 {$odgovoran_ime} odgovoran za unos materijala";
                    }

                    if ($obavesti_tip === 'odmah') {
                        $this->notifikuj($radnik_id, Auth::ime(), $poruka, $danInfo['datum'] ?? '', $stavka_id);
                    } elseif ($obavesti_tip === 'zakazano' && $obavesti_at) {
                        $this->zakaziObavestenje($stavka_id, $radnik_id, $poruka, $obavesti_at, $danInfo['datum'] ?? '');
                    }
                }

                // Odgovoran za materijal koji NIJE radnik na zadatku — zasebno obaveštenje (jednom).
                if ($odgovoran_id && !in_array($odgovoran_id, $radnik_ids, true)) {
                    $pO = $this->porukaOdgovoran(true, $opis, $gNaziv, $datum_fmt);
                    $this->obavestiIliZakazi($obavesti_tip, $obavesti_at, $stavka_id, $odgovoran_id, $pO, $danInfo['datum'] ?? '');
                }

                $this->json(['ok' => true, 'stavka_id' => $stavka_id, 'upozorenja' => $upozorenja]);
                break;

            case 'raspored_izmeni_stavku':
                $gradiliste_id = (int)($_POST['gradiliste_id'] ?? 0) ?: null;
                $opis          = trim($_POST['opis'] ?? '');
                $radnici_novi  = json_decode($_POST['radnici_json'] ?? '[]', true);
                $izborTip      = in_array($_POST['obavesti_tip'] ?? 'odmah', ['odmah','zakazano','ne'], true) ? $_POST['obavesti_tip'] : 'odmah';
                $obavesti_at   = $this->normDatetime((string)($_POST['obavesti_at'] ?? ''));
                $obavesti_at_store = ($izborTip === 'zakazano') ? $obavesti_at : null;
                $odgovoran_id  = (int)($_POST['odgovoran_id'] ?? 0) ?: null;
                $odgovoran_ime = $this->imeRadnika($odgovoran_id);
                $status        = (($_POST['status'] ?? '') === 'nacrt') ? 'nacrt' : 'objavljeno';
                // Nacrt PAMTI izbor ali NE šalje/zakazuje sada (šalje tek „Objavi").
                $obavesti_tip  = ($status === 'nacrt') ? 'ne' : $izborTip;

                // Staro stanje
                $staroStmt = $this->db->prepare("SELECT * FROM raspored_stavke WHERE id=?");
                $staroStmt->execute([$id]);
                $staro = $staroStmt->fetch(\PDO::FETCH_ASSOC);

                $stariRadnici = [];
                $stmt = $this->db->prepare("SELECT rr.*, k.ime FROM raspored_radnici rr JOIN admin_korisnici k ON rr.radnik_id=k.id WHERE rr.stavka_id=?");
                $stmt->execute([$id]);
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $sr) {
                    $stariRadnici[$sr['radnik_id']] = $sr;
                }

                // Gradilište naziv staro/novo
                $gStaroNaziv = '';
                if ($staro['gradiliste_id']) {
                    $gs = $this->db->prepare("SELECT naziv FROM gradilista WHERE id=?");
                    $gs->execute([$staro['gradiliste_id']]);
                    $gStaroNaziv = $gs->fetchColumn() ?: '';
                }
                $gNovoNaziv = '';
                if ($gradiliste_id) {
                    $gs = $this->db->prepare("SELECT naziv FROM gradilista WHERE id=?");
                    $gs->execute([$gradiliste_id]);
                    $gNovoNaziv = $gs->fetchColumn() ?: '';
                }

                // Dohvati datum
                $danStmt = $this->db->prepare("SELECT datum FROM raspored_dani WHERE id=?");
                $danStmt->execute([$staro['dan_id']]);
                $danDatum  = (string)$danStmt->fetchColumn();
                $datum_fmt = date('d.m.Y', strtotime($danDatum));

                // Ažuriraj stavku (sa odgovoran_id, status i zapamćenim izborom obaveštenja)
                $stmt = $this->db->prepare("UPDATE raspored_stavke SET gradiliste_id=?, opis=?, odgovoran_id=?, status=?, obavesti_tip=?, obavesti_at=? WHERE id=?");
                $stmt->execute([$gradiliste_id, $opis, $odgovoran_id, $status, $izborTip, $obavesti_at_store, $id]);

                // Otkaži ranije ZAKAZANE (još neposlate) za ovu stavku — izbegava duplikate
                // i zastarele poruke; po izboru ispod se naprave nove.
                $this->db->prepare("DELETE FROM raspored_obavestenja WHERE stavka_id=? AND poslato=0")->execute([$id]);

                // Nova lista radnika
                $noviMap = [];
                foreach ($radnici_novi as $r) {
                    $noviMap[(int)$r['id']] = $r;
                }

                $upozorenja = [];

                // Uklonjeni radnici
                foreach ($stariRadnici as $radnik_id => $sr) {
                    if (!isset($noviMap[$radnik_id])) {
                        $poruka = "❌ Uklonjen si sa zadatka:\n📋 {$staro['opis']}\n🏗️ {$gStaroNaziv}\n📅 {$datum_fmt}";
                        if ($obavesti_tip === 'odmah') {
                            $this->notifikuj($radnik_id, Auth::ime(), $poruka, $danDatum, $id);
                        } elseif ($obavesti_tip === 'zakazano' && $obavesti_at) {
                            $this->zakaziObavestenje($id, $radnik_id, $poruka, $obavesti_at, $danDatum);
                        }
                    }
                }

                // Reset i dodaj nove
                $this->db->prepare("DELETE FROM raspored_radnici WHERE stavka_id=?")->execute([$id]);

                foreach ($noviMap as $radnik_id => $r) {
                    $vreme_od = $r['vreme_od'] ?? null;
                    $vreme_do = $r['vreme_do'] ?? null;

                    // Proveri preklapanje
                    $overlap = $this->proveritPreklapanje($radnik_id, $staro['dan_id'], $id, $vreme_od, $vreme_do);
                    if ($overlap) {
                        $imeStmt = $this->db->prepare("SELECT ime FROM admin_korisnici WHERE id=?");
                        $imeStmt->execute([$radnik_id]);
                        $upozorenja[] = [
                            'radnik_id'   => $radnik_id,
                            'ime'         => $imeStmt->fetchColumn(),
                            'preklapanje' => $overlap,
                        ];
                    }

                    $stmt = $this->db->prepare("
                        INSERT INTO raspored_radnici (stavka_id, radnik_id, vreme_od, vreme_do)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$id, $radnik_id, $vreme_od ?: null, $vreme_do ?: null]);

                    // Generiši log poruku
                    $izmene = [];
                    if (!isset($stariRadnici[$radnik_id])) {
                        $poruka = "✅ Dodeljen si na zadatak:\n📋 {$opis}\n🏗️ " . ($gNovoNaziv ?: $gStaroNaziv) . "\n📅 {$datum_fmt}\n🕐 " . substr($vreme_od,0,5) . " – " . substr($vreme_do,0,5);
                        if ($odgovoran_id && $odgovoran_id == $radnik_id) {
                            $poruka .= "\n📦 Ti si odgovoran za unos materijala";
                        } elseif ($odgovoran_id && $odgovoran_ime !== '') {
                            $poruka .= "\n📦 {$odgovoran_ime} odgovoran za unos materijala";
                        }
                    } else {
                        $star = $stariRadnici[$radnik_id];
                        if ($staro['gradiliste_id'] != $gradiliste_id) {
                            $izmene[] = "• Gradilište: {$gStaroNaziv} → {$gNovoNaziv}";
                        }
                        if ($staro['opis'] !== $opis) {
                            $izmene[] = "• Zadatak: {$staro['opis']} → {$opis}";
                        }
                        $starVod = substr($star['vreme_od'] ?? '', 0, 5);
                        $starVdo = substr($star['vreme_do'] ?? '', 0, 5);
                        $novVod  = substr($vreme_od ?? '', 0, 5);
                        $novVdo  = substr($vreme_do ?? '', 0, 5);
                        if ($starVod !== $novVod || $starVdo !== $novVdo) {
                            $izmene[] = "• Vreme: {$starVod}–{$starVdo} → {$novVod}–{$novVdo}";
                        }

                        $jeOdgovoranSad = ($odgovoran_id && $odgovoran_id == $radnik_id);
                        $bioOdgovoranPre = ($staro['odgovoran_id'] && $staro['odgovoran_id'] == $radnik_id);

                        if ($jeOdgovoranSad && !$bioOdgovoranPre) {
                            $izmene[] = "• 📦 Određen si za unos materijala";
                        } elseif (!$jeOdgovoranSad && $bioOdgovoranPre) {
                            $izmene[] = "• 📦 Više nisi odgovoran za unos materijala";
                        }

if (empty($izmene)) continue;

$poruka = "📝 Izmena rasporeda ({$datum_fmt}):\n🏗️ " . ($gNovoNaziv ?: $gStaroNaziv) . "\n" . implode("\n", $izmene);
                        if ($odgovoran_id && $odgovoran_id == $radnik_id) {
                            $poruka .= "\n📦 Ti si odgovoran za unos materijala";
                        } elseif ($odgovoran_id && $odgovoran_ime !== '') {
                            $poruka .= "\n📦 {$odgovoran_ime} odgovoran za unos materijala";
                        }
                    }

                    if ($obavesti_tip === 'odmah') {
                        $this->notifikuj($radnik_id, Auth::ime(), $poruka, $danDatum, $id);
                    } elseif ($obavesti_tip === 'zakazano' && $obavesti_at) {
                        $this->zakaziObavestenje($id, $radnik_id, $poruka, $obavesti_at, $danDatum);
                    }
                }

                // Promene odgovornosti za osobu koja NIJE radnik u novoj listi.
                // (Radnici svoju promenu odgovornosti dobijaju kroz gornje poruke.)
                $staroO = (int)($staro['odgovoran_id'] ?? 0);
                $noviO  = (int)($odgovoran_id ?? 0);
                if ($noviO && $noviO !== $staroO && !isset($noviMap[$noviO])) {
                    $pO = $this->porukaOdgovoran(true, $opis, ($gNovoNaziv ?: $gStaroNaziv), $datum_fmt);
                    $this->obavestiIliZakazi($obavesti_tip, $obavesti_at, $id, $noviO, $pO, $danDatum);
                }
                if ($staroO && $staroO !== $noviO && !isset($noviMap[$staroO]) && !isset($stariRadnici[$staroO])) {
                    $pO = $this->porukaOdgovoran(false, $staro['opis'], ($gStaroNaziv ?: $gNovoNaziv), $datum_fmt);
                    $this->obavestiIliZakazi($obavesti_tip, $obavesti_at, $id, $staroO, $pO, $danDatum);
                }

                $this->json(['ok' => true, 'upozorenja' => $upozorenja]);
                break;

            case 'raspored_obrisi_stavku':
                $staroStmt = $this->db->prepare("SELECT rs.*, g.naziv AS gn, rd.datum FROM raspored_stavke rs LEFT JOIN gradilista g ON rs.gradiliste_id=g.id JOIN raspored_dani rd ON rs.dan_id=rd.id WHERE rs.id=?");
                $staroStmt->execute([$id]);
                $stavo = $staroStmt->fetch(\PDO::FETCH_ASSOC);

                // Obaveštenja o otkazivanju samo za OBJAVLJENE (nacrt ekipa nikad nije ni videla).
                if ($stavo && ($stavo['status'] ?? 'objavljeno') === 'objavljeno') {
                    $datum_fmt = date('d.m.Y', strtotime($stavo['datum']));
                    $poruka = "❌ Otkazan zadatak:\n📋 {$stavo['opis']}\n🏗️ {$stavo['gn']}\n📅 {$datum_fmt}";
                    $radnik_ids = [];
                    $stmt = $this->db->prepare("SELECT radnik_id FROM raspored_radnici WHERE stavka_id=?");
                    $stmt->execute([$id]);
                    foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $radnik_id) {
                        $radnik_ids[] = (int)$radnik_id;
                        $this->notifikuj((int)$radnik_id, Auth::ime(), $poruka, $stavo['datum'] ?? '', $id);
                    }
                    // Odgovoran za materijal koji nije radnik — takođe obavesti o otkazivanju.
                    $odgO = (int)($stavo['odgovoran_id'] ?? 0);
                    if ($odgO && !in_array($odgO, $radnik_ids, true)) {
                        $this->notifikuj($odgO, Auth::ime(), $poruka, $stavo['datum'] ?? '', $id);
                    }
                }

                // Otkaži eventualne zakazane (neposlate) za ovu stavku — da cron ne pošalje
                // poruku o već obrisanom zadatku.
                $this->db->prepare("DELETE FROM raspored_obavestenja WHERE stavka_id=? AND poslato=0")->execute([$id]);
                $this->db->prepare("DELETE FROM raspored_stavke WHERE id=?")->execute([$id]);
                $this->json(['ok' => true]);
                break;

            case 'raspored_get_stavku':
                $stmt = $this->db->prepare("SELECT * FROM raspored_stavke WHERE id=?");
                $stmt->execute([$id]);
                $stavka = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$stavka) $this->json(['ok' => false]);

                $stmt = $this->db->prepare("
                    SELECT rr.radnik_id, rr.vreme_od, rr.vreme_do, k.ime
                    FROM raspored_radnici rr
                    JOIN admin_korisnici k ON rr.radnik_id = k.id
                    WHERE rr.stavka_id = ?
                ");
                $stmt->execute([$id]);
                $stavka['radnici'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // Izbor obaveštenja za modal izmene: zapamćen na stavci; a ako je objavljeno i
                // ima još NEPOSLATO zakazano (raspored_obavestenja), to je merodavno za prikaz.
                $tip = $stavka['obavesti_tip'] ?: 'odmah';
                $at  = $stavka['obavesti_at'] ? date('Y-m-d\TH:i', strtotime($stavka['obavesti_at'])) : '';
                $zStmt = $this->db->prepare("SELECT MIN(send_at) FROM raspored_obavestenja WHERE stavka_id=? AND poslato=0");
                $zStmt->execute([$id]);
                $zSendAt = $zStmt->fetchColumn();
                if ($zSendAt) { $tip = 'zakazano'; $at = date('Y-m-d\TH:i', strtotime($zSendAt)); }
                $stavka['obavesti_tip'] = $tip;
                $stavka['obavesti_at']  = $at;

                $this->json($stavka);
                break;

            case 'raspored_vreme_elektricara':
                $dan_id   = (int)($_POST['dan_id'] ?? 0);
                $iskljuci = (int)($_POST['iskljuci_stavku'] ?? 0); // pri izmeni: ne računaj sopstvenu stavku
                if (!$dan_id) $this->json(['ok' => true, 'vremena' => []]);

                $sql = "
                    SELECT rr.radnik_id, MAX(rr.vreme_do) AS poslednje_do
                    FROM raspored_radnici rr
                    JOIN raspored_stavke rs ON rr.stavka_id = rs.id
                    WHERE rs.dan_id = ?" . ($iskljuci ? " AND rs.id != ?" : "") . "
                    GROUP BY rr.radnik_id
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($iskljuci ? [$dan_id, $iskljuci] : [$dan_id]);
                $vremena = [];
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    $vremena[$row['radnik_id']] = $row['poslednje_do'];
                }
                $this->json(['ok' => true, 'vremena' => $vremena]);
                break;

            case 'raspored_kopiraj':
                $stmt = $this->db->prepare("SELECT * FROM radne_nedelje WHERE id=?");
                $stmt->execute([$id]);
                $orig = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$orig) $this->json(['ok' => false, 'err' => 'Nedelja ne postoji.']);

                $nova_datum_od = date('Y-m-d', strtotime($orig['datum_od'] . ' +7 days'));
                $nova_datum_do = date('Y-m-d', strtotime($nova_datum_od . ' +5 days'));

                $provera = $this->db->prepare("SELECT id FROM radne_nedelje WHERE datum_od=?");
                $provera->execute([$nova_datum_od]);
                if ($provera->fetchColumn()) {
                    $this->json(['ok' => false, 'err' => 'Raspored za nedelju ' . date('d.m.Y', strtotime($nova_datum_od)) . ' – ' . date('d.m.Y', strtotime($nova_datum_do)) . ' već postoji. Kopiranje nije moguće.']);
                }

                $stmt = $this->db->prepare("INSERT INTO radne_nedelje (kreator_id, datum_od, datum_do, status) VALUES (?, ?, ?, 'priprema')");
                $stmt->execute([Auth::id(), $nova_datum_od, $nova_datum_do]);
                $nova_nedelja_id = $this->db->lastInsertId();

                $stmt = $this->db->prepare("SELECT * FROM raspored_dani WHERE nedelja_id=? ORDER BY datum ASC");
                $stmt->execute([$orig['id']]);
                $orig_dani = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                $boje = ['#1a3a6e', '#2563eb', '#7c3aed', '#0891b2', '#059669', '#b45309'];
                $ts_novo = strtotime($nova_datum_od);

                foreach ($orig_dani as $i => $dan) {
                    $novi_datum = date('Y-m-d', strtotime("+$i days", $ts_novo));
                    $stmt = $this->db->prepare("INSERT INTO raspored_dani (nedelja_id, datum, boja) VALUES (?, ?, ?)");
                    $stmt->execute([$nova_nedelja_id, $novi_datum, $boje[$i] ?? $dan['boja']]);
                    $novi_dan_id = $this->db->lastInsertId();

                    $stmt = $this->db->prepare("SELECT * FROM raspored_stavke WHERE dan_id=?");
                    $stmt->execute([$dan['id']]);
                    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $s) {
                        // Kopiraj i odgovoran_id i status (nacrt ostaje nacrt)
                        $stmt = $this->db->prepare("INSERT INTO raspored_stavke (dan_id, gradiliste_id, opis, redosled, odgovoran_id, status) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$novi_dan_id, $s['gradiliste_id'], $s['opis'], $s['redosled'], $s['odgovoran_id'], $s['status'] ?? 'objavljeno']);
                        $nova_stavka_id = $this->db->lastInsertId();

                        $stmt = $this->db->prepare("SELECT * FROM raspored_radnici WHERE stavka_id=?");
                        $stmt->execute([$s['id']]);
                        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $rr) {
                            $this->db->prepare("INSERT INTO raspored_radnici (stavka_id, radnik_id, vreme_od, vreme_do) VALUES (?, ?, ?, ?)")
                                     ->execute([$nova_stavka_id, $rr['radnik_id'], $rr['vreme_od'], $rr['vreme_do']]);
                        }
                    }
                }

                $this->json(['ok' => true, 'redirect' => BASE_URL . '/?page=raspored&datum=' . urlencode($nova_datum_od)]);
                break;

            case 'raspored_poruke_get':
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
                foreach ($poruke as &$p) { $p['moja'] = ($p['autor_id'] == Auth::id()); }
                $this->json(['ok' => true, 'poruke' => $poruke]);
                break;

            case 'raspored_poruka_add':
                $stavka_id = (int)($_POST['stavka_id'] ?? 0);
                $sadrzaj   = trim($_POST['sadrzaj'] ?? '');
                if (!$sadrzaj || !$stavka_id) $this->json(['ok' => false, 'err' => 'Prazna poruka.']);

                $stmt = $this->db->prepare("INSERT INTO raspored_poruke (stavka_id, autor_id, sadrzaj) VALUES (?, ?, ?)");
                $stmt->execute([$stavka_id, Auth::id(), $sadrzaj]);

                // Datum stavke (za deep-link u notifikaciji)
                $dStmt = $this->db->prepare("SELECT rd.datum FROM raspored_stavke rs JOIN raspored_dani rd ON rs.dan_id=rd.id WHERE rs.id=?");
                $dStmt->execute([$stavka_id]);
                $pDatum = (string)$dStmt->fetchColumn();

                $stmt = $this->db->prepare("SELECT radnik_id FROM raspored_radnici WHERE stavka_id=?");
                $stmt->execute([$stavka_id]);
                foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $radnik_id) {
                    if ($radnik_id != Auth::id()) {
                        $this->notifikuj($radnik_id, Auth::ime(), '💬 Nova poruka na rasporedu: ' . mb_substr($sadrzaj, 0, 50), $pDatum, $stavka_id);
                    }
                }
                $this->json(['ok' => true]);
                break;

            case 'raspored_badge_refresh':
                $uid_r = Auth::id();
                $stavke_r = [];
                $stmt_r = $this->db->prepare("
                    SELECT rs.id FROM raspored_stavke rs
                    JOIN raspored_dani rd ON rs.dan_id = rd.id
                    WHERE rd.datum >= CURDATE() - INTERVAL 7 DAY
                ");
                $stmt_r->execute();
                foreach ($stmt_r->fetchAll(\PDO::FETCH_COLUMN) as $sid) {
                    $pc = $this->db->prepare("SELECT COUNT(*) FROM raspored_poruke WHERE stavka_id=?");
                    $pc->execute([$sid]);
                    $poruka_count = (int)$pc->fetchColumn();

                    $v = $this->db->prepare("SELECT vidjeno_do FROM raspored_vidjeno WHERE stavka_id=? AND korisnik_id=?");
                    $v->execute([$sid, $uid_r]);
                    $vidjeno_do = $v->fetchColumn();

                    $m = $this->db->prepare("SELECT MAX(created_at) FROM raspored_poruke WHERE stavka_id=? AND autor_id=?");
                    $m->execute([$sid, $uid_r]);
                    $poslednja_moja = $m->fetchColumn();

                    $referentni = $poslednja_moja;
                    if ($vidjeno_do && (!$referentni || $vidjeno_do > $referentni)) $referentni = $vidjeno_do;

                    $nova_count = 0;
                    if ($referentni) {
                        $n = $this->db->prepare("SELECT COUNT(*) FROM raspored_poruke WHERE stavka_id=? AND autor_id!=? AND created_at>?");
                        $n->execute([$sid, $uid_r, $referentni]);
                        $nova_count = (int)$n->fetchColumn();
                    } else if ($poruka_count > 0) {
                        $n = $this->db->prepare("SELECT COUNT(*) FROM raspored_poruke WHERE stavka_id=? AND autor_id!=?");
                        $n->execute([$sid, $uid_r]);
                        $nova_count = (int)$n->fetchColumn();
                    }

                    $stavke_r[] = [
                        'id'                => $sid,
                        'poruka_count'      => $poruka_count,
                        'nove_poruke_count' => $nova_count,
                        'nova_poruka'       => $nova_count > 0,
                    ];
                }
                $this->json(['ok' => true, 'stavke' => $stavke_r]);
                break;

            case 'raspored_obrisi_nedelju':
                Auth::requireKancelarija();
                $stmt = $this->db->prepare("SELECT datum_od FROM radne_nedelje WHERE id=?");
                $stmt->execute([$id]);
                $datum_od = $stmt->fetchColumn();
                if (!$datum_od) $this->json(['ok' => false, 'err' => 'Nedelja ne postoji.']);

                $ponedeljak_ovaj = date('Y-m-d', strtotime('monday this week'));
                if ($datum_od <= $ponedeljak_ovaj) {
                    $this->json(['ok' => false, 'err' => 'Nije dozvoljeno brisanje tekuće ili prošlih nedelja.']);
                }

                $this->db->prepare("DELETE FROM radne_nedelje WHERE id=?")->execute([$id]);
                $this->json(['ok' => true]);
                break;

            case 'raspored_oznaci_vidjeno':
                $stavka_id = (int)($_POST['stavka_id'] ?? 0);
                if ($stavka_id) {
                    $this->db->prepare("
                        INSERT INTO raspored_vidjeno (korisnik_id, stavka_id, vidjeno_do)
                        VALUES (?, ?, NOW())
                        ON DUPLICATE KEY UPDATE vidjeno_do = NOW()
                    ")->execute([Auth::id(), $stavka_id]);
                }
                $this->json(['ok' => true]);
                break;

            case 'raspored_objavi_stavku':
                if (!$id) $this->json(['ok' => false, 'err' => 'Nema stavke.']);
                $this->objaviStavkuInterno($id);
                $this->json(['ok' => true]);
                break;

            case 'raspored_objavi_dan':
                $dan_id = (int)($_POST['dan_id'] ?? $id);
                if (!$dan_id) $this->json(['ok' => false, 'err' => 'Nema dana.']);
                $stmt = $this->db->prepare("SELECT id FROM raspored_stavke WHERE dan_id=? AND status='nacrt'");
                $stmt->execute([$dan_id]);
                $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                foreach ($ids as $sid) {
                    $this->objaviStavkuInterno((int)$sid);
                }
                $this->json(['ok' => true, 'broj' => count($ids)]);
                break;

            default:
                $this->json(['ok' => false, 'err' => 'Nepoznata akcija.']);
        }
    }

    /**
     * Objavi jedan nacrt: status -> 'objavljeno' i obavesti svu ekipu na stavci.
     * Idempotentno: već objavljena stavka se preskače (bez ponovnog obaveštenja).
     */
    private function objaviStavkuInterno(int $stavka_id): void
    {
        $st = $this->db->prepare("
            SELECT rs.*, rd.datum
            FROM raspored_stavke rs
            JOIN raspored_dani rd ON rs.dan_id = rd.id
            WHERE rs.id = ?
        ");
        $st->execute([$stavka_id]);
        $s = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$s) return;
        if (($s['status'] ?? '') === 'objavljeno') return;

        $this->db->prepare("UPDATE raspored_stavke SET status='objavljeno' WHERE id=?")->execute([$stavka_id]);

        $gNaziv = '';
        if ($s['gradiliste_id']) {
            $gs = $this->db->prepare("SELECT naziv FROM gradilista WHERE id=?");
            $gs->execute([$s['gradiliste_id']]);
            $gNaziv = $gs->fetchColumn() ?: '';
        }
        $odgovoran_id  = $s['odgovoran_id'] ? (int)$s['odgovoran_id'] : null;
        $odgovoran_ime = $this->imeRadnika($odgovoran_id);
        $datum_fmt     = date('d.m.Y', strtotime($s['datum']));

        // Poštuj zapamćeni izbor obaveštenja sa (do tada) nacrta:
        //   ne → objavi bez obaveštenja; zakazano+buduće vreme → zakaži; inače → pošalji odmah.
        $objaviTip    = $s['obavesti_tip'] ?: 'odmah';
        $objaviAt     = $s['obavesti_at'] ?: '';
        $zakaziObjavu = ($objaviTip === 'zakazano' && $objaviAt !== '' && strtotime($objaviAt) > time());

        $rs = $this->db->prepare("SELECT radnik_id, vreme_od, vreme_do FROM raspored_radnici WHERE stavka_id=?");
        $rs->execute([$stavka_id]);
        $radnik_ids = [];
        foreach ($rs->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $radnik_id   = (int)$r['radnik_id'];
            $radnik_ids[] = $radnik_id;
            if ($objaviTip === 'ne') continue; // objavljeno, ali ekipa se ne obaveštava
            $jeOdgovoran = ($odgovoran_id && $odgovoran_id === $radnik_id);
            $poruka = "📋 Dodeljen si na zadatak: {$s['opis']}\n🏗️ Gradilište: {$gNaziv}\n📅 Datum: {$datum_fmt}\n🕐 Vreme: " . substr($r['vreme_od'],0,5) . " – " . substr($r['vreme_do'],0,5);
            if ($jeOdgovoran) {
                $poruka .= "\n📦 Ti si odgovoran za unos materijala";
            } elseif ($odgovoran_id && $odgovoran_ime !== '') {
                $poruka .= "\n📦 {$odgovoran_ime} odgovoran za unos materijala";
            }
            if ($zakaziObjavu) {
                $this->zakaziObavestenje($stavka_id, $radnik_id, $poruka, $objaviAt, $s['datum'] ?? '');
            } else {
                $this->notifikuj($radnik_id, Auth::ime(), $poruka, $s['datum'] ?? '', $stavka_id);
            }
        }

        // Odgovoran za materijal koji NIJE radnik na zadatku — zasebno obaveštenje.
        if ($objaviTip !== 'ne' && $odgovoran_id && !in_array($odgovoran_id, $radnik_ids, true)) {
            $pO = $this->porukaOdgovoran(true, $s['opis'], $gNaziv, $datum_fmt);
            if ($zakaziObjavu) {
                $this->zakaziObavestenje($stavka_id, $odgovoran_id, $pO, $objaviAt, $s['datum'] ?? '');
            } else {
                $this->notifikuj($odgovoran_id, Auth::ime(), $pO, $s['datum'] ?? '', $stavka_id);
            }
        }
    }

    /** Pošalji odmah ili zakaži — isto pravilo kao za radnike (odmah/zakazano). */
    private function obavestiIliZakazi(string $tip, ?string $at, int $stavka_id, int $radnik_id, string $poruka, string $datum): void
    {
        if ($tip === 'odmah') {
            $this->notifikuj($radnik_id, Auth::ime(), $poruka, $datum, $stavka_id);
        } elseif ($tip === 'zakazano' && $at) {
            $this->zakaziObavestenje($stavka_id, $radnik_id, $poruka, $at, $datum);
        }
    }

    /** Poruka osobi koja JESTE/NIJE odgovorna za unos materijala (a nije radnik na zadatku). */
    private function porukaOdgovoran(bool $dodeljen, string $opis, string $gNaziv, string $datum_fmt): string
    {
        $glava = $dodeljen
            ? "📦 Određen si za unos materijala"
            : "📦 Više nisi odgovoran za unos materijala";
        return "{$glava}:\n📋 {$opis}\n🏗️ {$gNaziv}\n📅 {$datum_fmt}";
    }

    /** datetime-local ("Y-m-dTH:i") → MySQL "Y-m-d H:i:s", ili null. */
    private function normDatetime(string $s): ?string
    {
        $s = str_replace('T', ' ', trim($s));
        if ($s === '') return null;
        if (strlen($s) === 16) $s .= ':00';
        return $s;
    }

    private function imeRadnika(?int $id): string
    {
        if (!$id) return '';
        $stmt = $this->db->prepare("SELECT ime FROM admin_korisnici WHERE id=?");
        $stmt->execute([$id]);
        return (string)($stmt->fetchColumn() ?: '');
    }

    private function proveritPreklapanje(int $radnik_id, int $dan_id, int $iskljuci_stavku_id, ?string $vreme_od, ?string $vreme_do): ?string
    {
        if (!$vreme_od || !$vreme_do) return null;

        $stmt = $this->db->prepare("
            SELECT rr.vreme_od, rr.vreme_do, g.naziv AS gradiliste
            FROM raspored_radnici rr
            JOIN raspored_stavke rs ON rr.stavka_id = rs.id
            LEFT JOIN gradilista g ON rs.gradiliste_id = g.id
            WHERE rr.radnik_id = ?
              AND rs.dan_id = ?
              AND rr.stavka_id != ?
              AND rr.vreme_od IS NOT NULL
              AND rr.vreme_do IS NOT NULL
              AND rr.vreme_od < ?
              AND rr.vreme_do > ?
        ");
        $stmt->execute([$radnik_id, $dan_id, $iskljuci_stavku_id, $vreme_do, $vreme_od]);
        $overlap = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($overlap) {
            return substr($overlap['vreme_od'],0,5) . '–' . substr($overlap['vreme_do'],0,5) .
                   ($overlap['gradiliste'] ? ' (' . $overlap['gradiliste'] . ')' : '');
        }
        return null;
    }

    private function zakaziObavestenje(int $stavka_id, int $radnik_id, string $poruka, string $send_at, string $datum = ''): void
    {
        try {
            // datetime-local šalje "YYYY-MM-DDTHH:MM" — MariaDB traži razmak i sekunde.
            $send_at = str_replace('T', ' ', trim($send_at));
            if (strlen($send_at) === 16) $send_at .= ':00';
            if ($send_at === '' ) return;

            $stmt = $this->db->prepare("
                INSERT INTO raspored_obavestenja (nedelja_id, stavka_id, radnik_id, poruka, datum, send_at, poslato)
                SELECT rd.nedelja_id, ?, ?, ?, ?, ?, 0
                FROM raspored_stavke rs
                JOIN raspored_dani rd ON rs.dan_id = rd.id
                WHERE rs.id = ?
                LIMIT 1
            ");
            $stmt->execute([$stavka_id, $radnik_id, $poruka, $datum, $send_at, $stavka_id]);
        } catch (\Throwable $e) {}
    }

    /**
     * Šalje sve DOSPELE zakazane notifikacije (poziva ga raspored_cron.php sa cPanel cron-a).
     * Bez ovog cron-a "Zakaži obaveštenje" ne radi — niko ne povuče okidač u zakazano vreme.
     * Vraća ['poslato' => N, 'log' => [...]].
     */
    public function posaljiZakazane(): array
    {
        $poslato = 0;
        $log = [];
        $stmt = $this->db->query("
            SELECT id, radnik_id, poruka, datum, stavka_id
            FROM raspored_obavestenja
            WHERE poslato = 0 AND radnik_id IS NOT NULL AND send_at <= NOW()
            ORDER BY send_at ASC
            LIMIT 200
        ");
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            // notifikuj() je best-effort (sve hvata interno); markiraj poslato i bez potvrde isporuke.
            $this->notifikuj(
                (int)$r['radnik_id'], '', (string)$r['poruka'],
                (string)($r['datum'] ?? ''), (int)($r['stavka_id'] ?? 0)
            );
            $this->db->prepare("UPDATE raspored_obavestenja SET poslato=1 WHERE id=?")->execute([$r['id']]);
            $poslato++;
            $log[] = "✓ #{$r['id']} → radnik {$r['radnik_id']}";
        }
        return ['poslato' => $poslato, 'log' => $log];
    }

    /**
     * Obaveštava radnika preko kanala podešenih u Timu (platforma + platforma2):
     *   android / web → web push (PushController), ios → Telegram.
     * $datum (Y-m-d) i $stavka_id služe da klik na notifikaciju otvori "Danas"
     * na tačan dan i da tag bude jedinstven po stavci (više zadataka = više notifikacija).
     */
    private function notifikuj(int $radnik_id, string $posiljalac, string $poruka, string $datum = '', int $stavka_id = 0): void
    {
        try {
            $stmt = $this->db->prepare("SELECT platforma, platforma2 FROM admin_korisnici WHERE id=?");
            $stmt->execute([$radnik_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return;

            $platforme = array_unique(array_filter([$row['platforma'] ?? '', $row['platforma2'] ?? '']));
            if (!$platforme) $platforme = ['android'];

            $wantWeb      = (bool)array_intersect($platforme, ['android', 'web']);
            $wantTelegram = in_array('ios', $platforme, true);

            // Web push (Android / Web kanal)
            if ($wantWeb) {
                $linije = explode("\n", $poruka);
                $naslov = trim($linije[0]) ?: 'Raspored';
                $telo   = trim(implode("\n", array_slice($linije, 1)));
                if ($telo === '') $telo = $naslov;
                // VAŽNO: putanja relativna na origin (sw.js je re-bazira), NE BASE_URL —
                // zakazane poruke šalje raspored_cron.php (CLI) gde je BASE_URL pogrešan
                // (nema $_SERVER), pa bi klik vodio na 404. App je uvek pod /mvc.
                $putanja = '/mvc/?page=danas' . ($datum ? '&datum=' . urlencode($datum) : '');
                PushController::notifyUsers([$radnik_id], [
                    'title' => mb_substr($naslov, 0, 80),
                    'body'  => mb_substr($telo, 0, 160),
                    'url'   => $putanja,
                    'tag'   => $stavka_id ? ('raspored-' . $stavka_id) : 'raspored',
                    'icon'  => '/mvc/public/icon-192.png',
                ]);
            }

            // Telegram (iOS kanal)
            if ($wantTelegram) {
                $stmt2 = $this->db->prepare("SELECT chat_id FROM telegram_subscriptions WHERE korisnik_id=? AND aktivan=1");
                $stmt2->execute([$radnik_id]);
                $token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
                $link  = 'ekosarna.com/mvc/?page=danas' . ($datum ? '&datum=' . urlencode($datum) : '');
                $text  = urlencode($poruka . "\n\n" . $link);
                foreach ($stmt2->fetchAll(\PDO::FETCH_COLUMN) as $chat_id) {
                    @file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text={$text}");
                }
            }
        } catch (\Throwable $e) {}
    }
}
