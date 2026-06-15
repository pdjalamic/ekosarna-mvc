<?php
/**
 * badge_counts.php — vraća broj novih stavki za sidebar badge-ove
 * Poziva se svakih 60s iz headera
 */
header('Content-Type: application/json; charset=utf-8');

// Bootstrap
define('ROOT', __DIR__);
define('APP',  ROOT . '/app');

spl_autoload_register(function (string $class): void {
    $file = APP . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once APP . '/Core/Config.php';
require_once APP . '/helpers.php';

\Core\Auth::start();

if (!\Core\Auth::check()) {
    echo json_encode(['ok' => false]);
    exit;
}

try {
    $db  = \Core\Database::get();
    $uid = \Core\Auth::id();

    if (\Core\Auth::isElektricar()) {
        // Električar vidi samo nabavku
        $nabavka = (int)$db->query("
            SELECT COUNT(*) FROM nabavka_zahtevi
            WHERE status = 'novo' AND radnik_id = " . $uid
        )->fetchColumn();
        echo json_encode(['ok' => true, 'kontakt' => 0, 'zadaci' => 0, 'nabavka' => $nabavka]);
    } else {
        // Admin / Operater
        $kontakt = (int)$db->query("
            SELECT COUNT(*) FROM kontakt_forme WHERE procitano = 0
        ")->fetchColumn();

        $zadaci = (int)$db->query("
            SELECT COUNT(*) FROM interni_zadaci
            WHERE prihvaceno_id IS NULL
              AND dodeljeno_id IS NOT NULL
              AND status != 'zavrseno'
        ")->fetchColumn();

        $nabavka = (int)$db->query("
            SELECT COUNT(*) FROM nabavka_zahtevi WHERE status = 'novo'
        ")->fetchColumn();

        echo json_encode(['ok' => true, 'kontakt' => $kontakt, 'zadaci' => $zadaci, 'nabavka' => $nabavka]);
    }
} catch (\Throwable $e) {
    echo json_encode(['ok' => false]);
}
