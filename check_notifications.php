<?php
define('ROOT', __DIR__);
define('APP',  ROOT . '/app');

spl_autoload_register(function (string $class): void {
    $file = APP . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once APP . '/Core/Config.php';
require_once APP . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store');

\Core\Auth::start();

if (!\Core\Auth::check()) {
    echo json_encode(['ok' => false, 'auth' => false]);
    exit;
}

$uid = \Core\Auth::id();
$db  = \Core\Database::get();

try {
    // 1. Neprocitane inbox poruke
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM poruke
        WHERE (primalac_id = ? OR primalac_id IS NULL)
          AND posiljalac_id != ?
          AND procitano = 0
          AND roditelj_id IS NULL
    ");
    $stmt->execute([$uid, $uid]);
    $neprocitane_poruke = (int)$stmt->fetchColumn();

    // 2. Sve stavke gde korisnik ima pristup (pisao ili radnik)
    $stmt_stavke = $db->prepare("
    SELECT DISTINCT rs.id
    FROM raspored_stavke rs
    JOIN raspored_dani rd ON rs.dan_id = rd.id
    JOIN radne_nedelje rn ON rd.nedelja_id = rn.id
    WHERE EXISTS (SELECT 1 FROM raspored_poruke rp2 WHERE rp2.stavka_id = rs.id AND rp2.autor_id = ?)
       OR EXISTS (SELECT 1 FROM raspored_radnici rr WHERE rr.stavka_id = rs.id AND rr.radnik_id = ?)
       OR rn.kreator_id = ?
");
$stmt_stavke->execute([$uid, $uid, $uid]);
    $stavke_ids = $stmt_stavke->fetchAll(\PDO::FETCH_COLUMN);

    $neprocitane_raspored = 0;
    foreach ($stavke_ids as $stavka_id) {
        // Uzmi referentni datum (max od vidjeno_do i poslednje moje poruke)
        $v = $db->prepare("SELECT vidjeno_do FROM raspored_vidjeno WHERE stavka_id=? AND korisnik_id=?");
        $v->execute([$stavka_id, $uid]);
        $vidjeno_do = $v->fetchColumn();

        $m = $db->prepare("SELECT MAX(created_at) FROM raspored_poruke WHERE stavka_id=? AND autor_id=?");
        $m->execute([$stavka_id, $uid]);
        $poslednja_moja = $m->fetchColumn();

        $referentni = $poslednja_moja;
        if ($vidjeno_do && (!$referentni || $vidjeno_do > $referentni)) {
            $referentni = $vidjeno_do;
        }

        $n = $db->prepare("
            SELECT COUNT(*) FROM raspored_poruke
            WHERE stavka_id=? AND autor_id!=?
            " . ($referentni ? "AND created_at > ?" : "") . "
        ");
        if ($referentni) {
            $n->execute([$stavka_id, $uid, $referentni]);
        } else {
            $n->execute([$stavka_id, $uid]);
        }
        $neprocitane_raspored += (int)$n->fetchColumn();
    }

    echo json_encode([
        'ok'                   => true,
        'neprocitane_poruke'   => $neprocitane_poruke,
        'neprocitane_raspored' => $neprocitane_raspored,
        'ukupno'               => $neprocitane_poruke + $neprocitane_raspored,
    ]);

} catch (\Throwable $e) {
    echo json_encode(['ok' => false, 'err' => $e->getMessage()]);
}
