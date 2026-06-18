<?php
namespace Controllers;

use Core\Auth;

/**
 * NovostiController — "Prikaži novosti" sa home stranice.
 * Za sada prazna stranica; sadržaj se dodaje kasnije.
 */
class NovostiController extends \Core\Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $this->view('novosti/index');
    }
}
