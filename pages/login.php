<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/LoginController.php';
require_once __DIR__ . '/../models/Configuracion.php';

$nombreComercial = (new Configuracion())->obtener('nombre_comercial', 'EL AHORRO');

// Si ya inició sesión, no tiene sentido volver al login.
if (!Auth::invitado()) {
    redirigir(Auth::destinoInicial());
}

$error   = '';
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');

    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        $error = 'La sesión expiró. Vuelva a intentarlo.';
    } else {
        $controlador = new LoginController();
        $resultado   = $controlador->autenticar($usuario, $_POST['password'] ?? '');

        if ($resultado['ok']) {
            redirigir($resultado['destino']);
        }

        $error = $resultado['mensaje'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión · <?= e($nombreComercial) ?></title>
    <link rel="icon" href="<?= BASE_URL ?>assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset_url('assets/css/tailwind.css') ?>">
</head>

<body class="min-h-screen bg-slate-900 font-sans">

    <div class="flex min-h-screen">

        <!-- Panel de presentación (solo en pantallas grandes) -->
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden p-12 lg:flex">
            <img src="<?= BASE_URL ?>assets/img/login-hero.jpg" alt=""
                class="absolute inset-0 h-full w-full object-cover" style="object-position: 60% 50%;">
            <div class="absolute inset-0"
                style="background: linear-gradient(100deg, #1d63d8 28%, rgba(29,99,216,0.82) 55%, rgba(29,99,216,0.35) 100%);">
            </div>

            <div class="relative flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 text-xl font-bold text-white">A</span>
                <span class="text-lg font-semibold text-white"><?= e($nombreComercial) ?></span>
            </div>

            <div class="relative">
                <h2 class="text-4xl leading-tight font-bold text-white">
                    Sistema Web para la<br>Gestión de un Minimarket
                </h2>
                <p class="mt-5 max-w-md text-marca-100">
                    Punto de venta, control de inventario, registro de clientes y reportes
                    de gestión en una sola plataforma.
                </p>

            </div>

            <!-- Spacer para centrar el texto con justify-between -->
            <div></div>
        </div>

        <!-- Formulario -->
        <div class="flex w-full items-center justify-center bg-slate-50 p-6 lg:w-1/2">
            <div class="w-full max-w-sm">

                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-marca-600 text-xl font-bold text-white">A</span>
                    <span class="text-lg font-semibold text-slate-800"><?= e($nombreComercial) ?></span>
                </div>

                <h1 class="text-2xl font-bold text-slate-900">Iniciar sesión</h1>
                <p class="mt-1.5 text-sm text-slate-500">
                    Ingrese sus credenciales para acceder al sistema.
                </p>

                <?php if ($error !== ''): ?>
                    <div class="alerta-error mt-6">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                        </svg>
                        <span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>login" class="mt-6 space-y-5" autocomplete="off">
                    <?= csrf_field() ?>

                    <div>
                        <label for="usuario" class="etiqueta">Usuario</label>
                        <input type="text" id="usuario" name="usuario" class="campo"
                            value="<?= e($usuario) ?>" placeholder="admin" required autofocus>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label for="password" class="etiqueta">Contraseña</label>
                            <a href="<?= BASE_URL ?>recuperar" class="text-xs font-medium text-marca-600 hover:text-marca-700">
                                ¿Olvidó su contraseña?
                            </a>
                        </div>
                        <div class="relative">
                            <input type="password" id="password" name="password" class="campo pr-11"
                                placeholder="••••••••" required>
                            <button type="button" id="ver-password"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
                                aria-label="Mostrar contraseña">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 4.5C7 4.5 2.7 7.6 1 12c1.7 4.4 6 7.5 11 7.5s9.3-3.1 11-7.5c-1.7-4.4-6-7.5-11-7.5zm0 12.5a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primario w-full py-2.5">
                        Ingresar
                    </button>
                </form>

                <div class="mt-8 rounded-lg border border-slate-200 bg-white p-4">
                    <p class="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        Cuentas de prueba
                    </p>
                    <div class="space-y-1 text-sm text-slate-600">
                        <p><span class="badge-morado">SuperAdmin</span> superadmin / super123</p>
                        <p><span class="badge-azul">Administrador</span> admin / admin123</p>
                        <p><span class="badge-gris">Cajero</span> cajero / cajero123</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mostrar u ocultar la contraseña
        document.getElementById('ver-password').addEventListener('click', function() {
            const campo = document.getElementById('password');
            campo.type = campo.type === 'password' ? 'text' : 'password';
        });
    </script>
</body>

</html>
