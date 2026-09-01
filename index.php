<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/middleware/Auth.php';

// Si ya hay sesión activa, cada rol entra a su pantalla inicial.
if (!Auth::invitado()) {
    header('Location: ' . BASE_URL . Auth::destinoInicial());
    exit;
}

header('Location: ' . BASE_URL . 'login');
exit;
