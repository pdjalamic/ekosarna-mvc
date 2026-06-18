<?php
namespace Controllers;

use Core\Auth;

/**
 * SettingsController — Podešavanja (Profil, Bezbednost, Obaveštenja, Odjava).
 * Faza 1: placeholder. Funkcionalnost se dodaje u Fazi 2.
 */
class SettingsController extends \Core\Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $this->view('settings/index');
    }
}
