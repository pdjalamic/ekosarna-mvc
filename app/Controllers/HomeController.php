<?php
namespace Controllers;

use Core\Auth;
use Core\Database;

/**
 * HomeController — mobilni "home screen" (mreža ikonica sa badge brojevima).
 * Prikazuje se kao landing strana na telefonu (vidi Router::dispatch default granu).
 * Brojači su isti kao u badge_counts.php / sidebar-u.
 */
class HomeController extends \Core\Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $db  = Database::get();
        $uid = Auth::id();

        $poruke = (int) PorukeController::neprocitane($uid);

        if (Auth::isElektricar()) {
            $nabavka = (int) $db->query(
                "SELECT COUNT(*) FROM nabavka_zahtevi WHERE status='novo' AND radnik_id=" . (int)$uid
            )->fetchColumn();
            $counts = ['poruke' => $poruke, 'nabavka' => $nabavka];
        } else {
            $kontakt = (int) $db->query(
                "SELECT COUNT(*) FROM kontakt_forme WHERE procitano = 0"
            )->fetchColumn();
            $zadaci = (int) $db->query(
                "SELECT COUNT(*) FROM interni_zadaci
                 WHERE prihvaceno_id IS NULL AND dodeljeno_id IS NOT NULL AND status != 'zavrseno'"
            )->fetchColumn();
            $nabavka = (int) $db->query(
                "SELECT COUNT(*) FROM nabavka_zahtevi WHERE status='novo'"
            )->fetchColumn();
            $counts = [
                'poruke'  => $poruke,
                'kontakt' => $kontakt,
                'zadaci'  => $zadaci,
                'nabavka' => $nabavka,
            ];
        }

        $this->view('home/index', compact('counts'));
    }
}
