<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../models/Configuracion.php';

Auth::check();

$destino = Auth::esAdministrador() ? 'dashboard' : 'pos';
$nombreComercial = (new Configuracion())->obtener('nombre_comercial', 'EL AHORRO');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso denegado · <?= e($nombreComercial) ?></title>
    <link rel="icon" href="<?= BASE_URL ?>assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset_url('assets/css/tailwind.css') ?>">
</head>

<body class="flex min-h-screen items-center justify-center bg-slate-100 p-6 font-sans">

    <div class="tarjeta w-full max-w-md p-8 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
            <svg class="h-7 w-7 text-amber-600" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm3 12c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z" />
            </svg>
        </div>

        <h1 class="mt-5 text-xl font-bold text-slate-900">Acceso denegado</h1>
        <p class="mt-2 text-sm text-slate-600">
            Su rol de <strong><?= e(Auth::rol()) ?></strong> no tiene permiso para
            entrar a esta sección del sistema.
        </p>

        <a href="<?= BASE_URL . $destino ?>" class="btn-primario mt-6">
            Volver a mi pantalla principal
        </a>
    </div>
</body>

</html>
