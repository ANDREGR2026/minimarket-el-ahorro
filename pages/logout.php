<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/LoginController.php';

$controlador = new LoginController();
$controlador->cerrarSesion();

redirigir('login');
