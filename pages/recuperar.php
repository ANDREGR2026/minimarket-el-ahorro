<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/RecuperacionController.php';
require_once __DIR__ . '/../models/Configuracion.php';

$nombreComercial = (new Configuracion())->obtener('nombre_comercial', 'EL AHORRO');

if (!Auth::invitado()) {
    redirigir(Auth::esAdministrador() ? 'dashboard' : 'pos');
}

$mensaje = '';
$tipo    = '';
$email   = '';
$enviado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        $mensaje = 'La sesión expiró. Vuelva a intentarlo.';
        $tipo    = 'error';
    } else {
        $resultado = (new RecuperacionController())->solicitar($email);
        $mensaje   = $resultado['mensaje'];
        $tipo      = $resultado['ok'] ? 'exito' : 'error';
        $enviado   = $resultado['ok'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña · <?= e($nombreComercial) ?></title>
    <link rel="icon" href="<?= BASE_URL ?>assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset_url('assets/css/tailwind.css') ?>">
</head>

<body class="min-h-screen bg-slate-50 font-sans">

    <div class="flex min-h-screen items-center justify-center p-6">
        <div class="w-full max-w-sm">

            <div class="mb-8 flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-marca-600 text-xl font-bold text-white">A</span>
                <span class="text-lg font-semibold text-slate-800"><?= e($nombreComercial) ?></span>
            </div>

            <h1 class="text-2xl font-bold text-slate-900">Recuperar contraseña</h1>
            <p class="mt-1.5 text-sm text-slate-500">
                Ingrese el correo asociado a su cuenta y le enviaremos un enlace para
                restablecer su contraseña.
            </p>

            <?php if ($mensaje !== ''): ?>
                <div class="mt-6 <?= $tipo === 'error' ? 'alerta-error' : 'rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700' ?>">
                    <?= e($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if (!$enviado): ?>
                <form method="POST" action="<?= BASE_URL ?>recuperar" class="mt-6 space-y-5" autocomplete="off">
                    <?= csrf_field() ?>

                    <div>
                        <label for="email" class="etiqueta">Correo electrónico</label>
                        <input type="email" id="email" name="email" class="campo"
                            value="<?= e($email) ?>" placeholder="correo@ejemplo.com" required autofocus>
                    </div>

                    <button type="submit" class="btn-primario w-full py-2.5">
                        Enviar enlace de recuperación
                    </button>
                </form>
            <?php endif; ?>

            <p class="mt-6 text-center text-sm text-slate-500">
                <a href="<?= BASE_URL ?>login" class="font-medium text-marca-600 hover:text-marca-700">
                    Volver a iniciar sesión
                </a>
            </p>
        </div>
    </div>
</body>

</html>
