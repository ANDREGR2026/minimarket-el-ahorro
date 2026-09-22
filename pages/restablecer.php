<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/RecuperacionController.php';
require_once __DIR__ . '/../models/Configuracion.php';

$nombreComercial = (new Configuracion())->obtener('nombre_comercial', 'EL AHORRO');

if (!Auth::invitado()) {
    redirigir(Auth::destinoInicial());
}

$controlador = new RecuperacionController();
$token       = trim($_GET['token'] ?? $_POST['token'] ?? '');
$mensaje     = '';
$exito       = false;
$tokenValido = $controlador->validarToken($token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        $mensaje = 'La sesión expiró. Vuelva a intentarlo.';
    } else {
        $resultado = $controlador->restablecer(
            $token,
            $_POST['password'] ?? '',
            $_POST['password_confirmacion'] ?? ''
        );

        $mensaje = $resultado['mensaje'];
        $exito   = $resultado['ok'];

        if (!$exito) {
            // Puede haber vencido justo ahora: revalidar para la vista.
            $tokenValido = $controlador->validarToken($token);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña · <?= e($nombreComercial) ?></title>
    <link rel="icon" href="<?= BASE_URL ?>assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset_url('assets/css/tailwind.css') ?>">
</head>

<body class="min-h-screen bg-slate-50 font-sans">

    <div class="flex min-h-screen items-center justify-center p-6">
        <div class="w-full max-w-sm">

            <div class="mb-8 flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl" aria-hidden="true"><img src="<?= BASE_URL ?>assets/img/favicon.svg" alt="" class="h-full w-full"></span>
                <span class="text-lg font-semibold text-slate-800"><?= e($nombreComercial) ?></span>
            </div>

            <h1 class="text-2xl font-bold text-slate-900">Restablecer contraseña</h1>

            <?php if ($mensaje !== ''): ?>
                <div class="mt-6 <?= $exito ? 'rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700' : 'alerta-error' ?>">
                    <?= e($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if ($exito): ?>
                <p class="mt-6 text-center text-sm">
                    <a href="<?= BASE_URL ?>login" class="font-medium text-marca-600 hover:text-marca-700">
                        Ir a iniciar sesión
                    </a>
                </p>

            <?php elseif (!$tokenValido): ?>
                <p class="mt-4 text-sm text-slate-500">
                    El enlace es inválido o ya venció.
                </p>
                <p class="mt-6 text-center text-sm">
                    <a href="<?= BASE_URL ?>recuperar" class="font-medium text-marca-600 hover:text-marca-700">
                        Solicitar un enlace nuevo
                    </a>
                </p>

            <?php else: ?>
                <p class="mt-1.5 text-sm text-slate-500">
                    Elija una contraseña nueva para su cuenta.
                </p>

                <form method="POST" action="<?= BASE_URL ?>restablecer" class="mt-6 space-y-5" autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">

                    <div>
                        <label for="password" class="etiqueta">Contraseña nueva</label>
                        <input type="password" id="password" name="password" class="campo"
                            placeholder="••••••••" minlength="6" required autofocus>
                    </div>

                    <div>
                        <label for="password_confirmacion" class="etiqueta">Confirmar contraseña</label>
                        <input type="password" id="password_confirmacion" name="password_confirmacion" class="campo"
                            placeholder="••••••••" minlength="6" required>
                    </div>

                    <button type="submit" class="btn-primario w-full py-2.5">
                        Restablecer contraseña
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
