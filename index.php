<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/middleware/Auth.php';

// Si ya hay sesión activa, cada rol entra a su pantalla inicial.
if (!Auth::invitado()) {
    $destino = Auth::esAdministrador() ? 'dashboard' : 'pos';
    header('Location: ' . BASE_URL . $destino);
    exit;
}

header('Location: ' . BASE_URL . 'login');
exit;
