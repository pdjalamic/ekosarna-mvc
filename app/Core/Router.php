<?php
namespace Core;

class Router
{
    public function dispatch(): void
    {
        Auth::start();

        if (isset($_GET['logout'])) {
            Auth::logout();
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $page   = $_GET['page'] ?? 'login';
        $action = $_POST['_action'] ?? null;

        if ($action !== null && Auth::check()) {
            header('Content-Type: application/json; charset=utf-8');
            $this->handleAjax($action);
            exit;
        }

        if (!Auth::check()) {
            $ctrl = new \Controllers\AuthController();
            $ctrl->login();
            return;
        }

        Database::migrate();

        if ($page === 'push') {
            header('Content-Type: application/json');
            $pushAction = $_GET['action'] ?? '';
            if ($pushAction === 'vapid-key') {
                \Controllers\PushController::getPublicKey();
            } elseif ($pushAction === 'subscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                \Controllers\PushController::subscribe();
            } elseif ($pushAction === 'unsubscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                \Controllers\PushController::unsubscribe();
            }
            exit;
        }

        // Električar može samo danas i poruke
        if (Auth::isElektricar() && !in_array($page, ['danas', 'poruke', 'hr'])) {
            header('Location: ' . BASE_URL . '/?page=danas');
            exit;
        }

        match ($page) {
            'kontakt'    => (new \Controllers\KontaktController())->index(),
            'tim'        => (new \Controllers\TimController())->index(),
            'imenik'     => (new \Controllers\ImenikController())->index(),
            'zadaci'     => (new \Controllers\ZadaciController())->index(),
            'poruke'     => (new \Controllers\PorukeController())->dispatch(),
            'gradilista' => (new \Controllers\GradilistaController())->index(),
            'raspored'   => (new \Controllers\RasporedController())->index(),
            'danas'      => (new \Controllers\DanasController())->index(),
            'hr'         => isset($_GET['action']) && $_GET['action'] === 'karton'
                                ? (new \Controllers\HrController())->karton()
                                : (new \Controllers\HrController())->index(),
            default      => Auth::isElektricar()
                                ? (new \Controllers\DanasController())->index()
                                : (new \Controllers\RasporedController())->index(),
        };
    }

    private function handleAjax(string $action): void
    {
        $id = (int)($_POST['id'] ?? 0);

        try {
            // Kontakt
            if (in_array($action, ['update_firma','update_grad','update_komentar','toggle_procitano','delete'])) {
                (new \Controllers\KontaktController())->ajax($action, $id);
                return;
            }

            // Mail
            if ($action === 'send_mail') {
                (new \Controllers\MailController())->send();
                return;
            }

            // Interni zadaci
            if (str_starts_with($action, 'zadatak_')) {
                (new \Controllers\ZadaciController())->ajax($action, $id);
                return;
            }

            // Imenik
            if (str_starts_with($action, 'imenik_')) {
                if (!Auth::canImenik()) exit(json_encode(['ok' => false, 'err' => 'Nemate pristup.']));
                (new \Controllers\ImenikController())->ajax($action, $id);
                return;
            }

            // Gradilišta
            if (str_starts_with($action, 'gradiliste_')) {
                (new \Controllers\GradilistaController())->ajax($action, $id);
                return;
            }

            // Raspored
            if (str_starts_with($action, 'raspored_')) {
                (new \Controllers\RasporedController())->ajax($action, $id);
                return;
            }

            // HR
            if (str_starts_with($action, 'hr_')) {
                (new \Controllers\HrController())->ajax($action, $id);
                return;
            }

            // Danas (električar)
            if (str_starts_with($action, 'danas_')) {
                (new \Controllers\DanasController())->ajax($action, $id);
                return;
            }

            // Tim
            if ($action === 'tim_edit_user') {
                (new \Controllers\TimController())->ajax($action, $id);
                return;
            }
            if (str_starts_with($action, 'tim_')) {
                Auth::requireAdmin();
                (new \Controllers\TimController())->ajax($action, $id);
                return;
            }

            exit(json_encode(['ok' => false, 'err' => 'Nepoznata akcija.']));

        } catch (\PDOException $e) {
            http_response_code(500);
            exit(json_encode(['ok' => false, 'err' => $e->getMessage()]));
        }
    }
}
