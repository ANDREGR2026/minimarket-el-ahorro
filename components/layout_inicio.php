<?php

/**
 * Cabecera comun de todas las pantallas internas.
 *
 * Antes de incluir este archivo, la pagina debe definir:
 *   $titulo  -> texto que va en el <title> y en la barra superior
 *   $activo  -> clave del menu que queda resaltada
 */

require_once __DIR__ . '/menu.php';
require_once __DIR__ . '/../models/Configuracion.php';

$titulo = $titulo ?? 'Minimarket';
$activo = $activo ?? '';
$__rolActual = Auth::rol();
$__nombreComercial = (new Configuracion())->obtener('nombre_comercial', 'EL AHORRO');

// El backup automatico no depende de un cron del sistema operativo: se
// revisa de paso en cada pantalla que abre un administrador, y si segun la
// frecuencia configurada ya toca, se genera en silencio.
if (Auth::esAdministrador()) {
    require_once __DIR__ . '/../controllers/BackupController.php';
    (new BackupController())->ejecutarSiCorresponde();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> · <?= e($__nombreComercial) ?></title>
    <link rel="icon" href="<?= BASE_URL ?>assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= asset_url('assets/css/tailwind.css') ?>">
<?php require __DIR__ . '/identidad.php'; ?>
</head>

<body class="min-h-screen bg-slate-100 font-sans text-slate-800">

    <div class="flex min-h-screen">

        <!-- ================= Menú lateral ================= -->
        <aside id="sidebar"
            class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 transition-transform lg:translate-x-0 no-imprimir">

            <div class="flex h-16 items-center gap-2.5 border-b border-slate-700/60 px-5">
                <img src="<?= e(asset_url(Identidad::logo())) ?>" alt="<?= e($__nombreComercial) ?>"
                     class="h-9 w-9 shrink-0 rounded-lg object-contain">
                <div class="leading-tight">
                    <p class="text-sm font-semibold text-white"><?= e($__nombreComercial) ?></p>
                    <p class="text-[11px] text-slate-400">Sistema de gestión</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <?php foreach (menu_items() as $item): ?>
                    <?php if (!Auth::tieneRolPermitido($item['roles'])) continue; ?>
                    <a href="<?= BASE_URL . $item['ruta'] ?>"
                        class="nav-item <?= $activo === $item['clave'] ? 'nav-item-activo' : '' ?>">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <?= $item['icono'] ?>
                        </svg>
                        <span><?= e($item['texto']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="border-t border-slate-700/60 p-3">
                <div class="flex items-center gap-3 rounded-lg px-3 py-2.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-700 text-sm font-semibold text-white">
                        <?= e(mb_strtoupper(mb_substr(Auth::nombre(), 0, 1))) ?>
                    </span>
                    <div class="min-w-0 leading-tight">
                        <p class="truncate text-sm font-medium text-white"><?= e(Auth::nombre()) ?></p>
                        <p class="text-[11px] text-slate-400"><?= e($__rolActual) ?></p>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>logout"
                    class="nav-item mt-1 text-slate-400 hover:text-white">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" />
                    </svg>
                    <span>Cerrar sesión</span>
                </a>
            </div>
        </aside>

        <!-- Fondo oscuro al abrir el menú en móvil -->
        <div id="sidebar-overlay"
            class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden no-imprimir"></div>

        <!-- ================= Contenido ================= -->
        <div class="flex min-w-0 flex-1 flex-col lg:ml-64">

            <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-4 sm:px-6 no-imprimir">
                <button id="btn-menu" type="button"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                    aria-label="Abrir menú">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z" />
                    </svg>
                </button>

                <h1 class="min-w-0 flex-1 truncate text-lg font-semibold text-slate-800">
                    <?= e($titulo) ?>
                </h1>

                <div class="hidden text-right text-xs leading-tight text-slate-500 sm:block">
                    <p class="font-medium text-slate-700"><?= e(ucfirst(strftime_es())) ?></p>
                    <p><?= e($__nombreComercial) ?></p>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
