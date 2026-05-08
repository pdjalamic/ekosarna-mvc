<?php
namespace Controllers;

use Core\Auth;
use Models\KontaktForma;

class KontaktController extends \Core\Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $filter   = $_GET['filter'] ?? 'sve';
        $search   = trim($_GET['q'] ?? '');
        $page     = max(1, (int)($_GET['page_num'] ?? 1));
        $per_page = 25;

        $where = []; $params = [];

        if ($filter === 'neprocitano') { $where[] = 'procitano = 0'; }
        if ($filter === 'procitano')   { $where[] = 'procitano = 1'; }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(ime_prezime LIKE ? OR firma LIKE ? OR telefon LIKE ? OR email LIKE ? OR grad LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $total       = KontaktForma::count($where, $params);
        $rows        = KontaktForma::getPage($page, $per_page, $where, $params);
        $neprocitano = KontaktForma::getUnreadCount();
        $total_pages = max(1, (int)ceil($total / $per_page));

        $this->view('kontakt/index', compact(
            'rows', 'total', 'neprocitano', 'total_pages',
            'page', 'filter', 'search', 'per_page'
        ));
    }

    public function ajax(string $action, int $id): void
    {
        switch ($action) {
            case 'update_firma':
                $val = mb_substr(trim(strip_tags($_POST['value'] ?? '')), 0, 200);
                KontaktForma::updateField($id, 'firma', $val);
                $this->json(['ok' => true]);
                break;

            case 'update_grad':
                $val = mb_substr(trim(strip_tags($_POST['value'] ?? '')), 0, 100);
                KontaktForma::updateField($id, 'grad', $val);
                $this->json(['ok' => true]);
                break;

            case 'update_komentar':
                $val = mb_substr(trim(strip_tags($_POST['value'] ?? '')), 0, 2000);
                KontaktForma::updateField($id, 'komentar', $val);
                $this->json(['ok' => true]);
                break;

            case 'toggle_procitano':
                $procitano = KontaktForma::toggleRead($id);
                $this->json(['ok' => true, 'procitano' => $procitano]);
                break;

            case 'delete':
                KontaktForma::delete($id);
                $this->json(['ok' => true]);
                break;

            default:
                $this->json(['ok' => false, 'err' => 'Nepoznata akcija.']);
        }
    }
}
