<?php
// OBRISATI NAKON KORISCENJA
define('ROOT', __DIR__);
define('APP',  ROOT . '/app');
spl_autoload_register(function(string $c): void {
    $f = APP . '/' . str_replace('\\', '/', $c) . '.php';
    if (file_exists($f)) require_once $f;
});
require_once APP . '/Core/Config.php';
require_once APP . '/helpers.php';
use Core\Database as DB;

echo "<pre>";

// Prikazi sve subscriptions
$stmt = DB::prepare("SELECT * FROM telegram_subscriptions");
$stmt->execute();
$subs = $stmt->fetchAll();
echo "Telegram subscriptions:\n";
foreach ($subs as $s) {
    echo "  ID:{$s['id']} chat_id:{$s['chat_id']} ime:{$s['ime']} username:{$s['username']} korisnik_id:" . ($s['korisnik_id'] ?? 'NULL') . "\n";
}

echo "\nAdmin korisnici:\n";
$stmt2 = DB::prepare("SELECT id, ime, username, COALESCE(telegram_username,'') as tg FROM admin_korisnici");
$stmt2->execute();
$korisnici = $stmt2->fetchAll();
foreach ($korisnici as $k) {
    echo "  ID:{$k['id']} ime:{$k['ime']} username:{$k['username']} telegram_username:{$k['tg']}\n";
}

// Ako ima jedan subscription i jedan korisnik sa telegram_username — poveži
if (isset($_GET['povezi']) && $_GET['povezi']) {
    $sub_id = (int)$_GET['sub_id'];
    $kor_id = (int)$_GET['kor_id'];
    DB::prepare("UPDATE telegram_subscriptions SET korisnik_id=? WHERE id=?")
      ->execute([$kor_id, $sub_id]);
    echo "\n✓ Povezano sub_id=$sub_id sa korisnik_id=$kor_id\n";
}

echo "\nDa povežeš ručno, otvori:\n";
foreach ($subs as $s) {
    foreach ($korisnici as $k) {
        echo "  ?povezi=1&sub_id={$s['id']}&kor_id={$k['id']} — {$s['ime']} → {$k['ime']}\n";
    }
}
echo "</pre>";
