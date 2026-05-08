<?php
namespace Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $is_admin    = Auth::isAdmin();
        $can_imenik  = Auth::canImenik();
        $user_ime    = Auth::ime();
        $user_uloga  = Auth::uloga();
        $active_page = $_GET['page'] ?? 'kontakt'; // 'kontakt','tim','imenik'

        require APP . '/Views/layout/header.php';
        require APP . "/Views/{$view}.php";
        require APP . '/Views/layout/footer.php';
    }

    protected function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    protected function h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}
