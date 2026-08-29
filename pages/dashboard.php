<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/ReporteController.php';

Auth::checkRole(['Administrador']);

$datos       = (new ReporteController())->dashboard();
$indicadores = $datos['indicadores'];
$catalogo    = $datos['catalogo'];

$titulo = 'Dashboard';
$activo = 'dashboard';

require __DIR__ . '/../components/layout_inicio.php';
?>

<?php require __DIR__ . '/../components/flash.php'; ?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Hola, <?= e(explode(' ', Auth::nombre())[0]) ?></h2>
        <p class="mt-0.5 text-sm text-slate-500">Así viene el negocio hoy.</p>
    </div>
    <a href="<?= BASE_URL ?>reportes" class="btn-secundario btn-sm">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M5 9.2h3V19H5V9.2zM10.6 5h2.8v14h-2.8V5zm5.6 8H19v6h-2.8v-6z" />
        </svg>
        Ver reportes completos
    </a>
</div>

<!-- ===================== Indicadores ===================== -->
<div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

    <div class="tarjeta p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">Ventas de hoy</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?= money($indicadores['venta_hoy']) ?></p>
                <p class="mt-1 text-xs text-slate-400">
                    <?= (int) $indicadores['tickets_hoy'] ?> comprobante(s)
                </p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.2 14.6l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.86-7.01L19.42 4l-3.87 7H8.53L4.27 2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7.42c-.13 0-.25-.11-.22-.4z" />
                </svg>
            </span>
        </div>
    </div>

    <div class="tarjeta p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">Ventas del mes</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?= money($indicadores['venta_mes']) ?></p>
                <p class="mt-1 text-xs text-slate-400">
                    <?= (int) $indicadores['tickets_mes'] ?> comprobante(s)
                </p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-marca-100 text-marca-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M5 9.2h3V19H5V9.2zM10.6 5h2.8v14h-2.8V5zm5.6 8H19v6h-2.8v-6z" />
                </svg>
            </span>
        </div>
    </div>

    <div class="tarjeta p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">Ticket promedio</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?= money($indicadores['ticket_promedio']) ?></p>
                <p class="mt-1 text-xs text-slate-400">Promedio del mes en curso</p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2z" />
                </svg>
            </span>
        </div>
    </div>

    <a href="<?= BASE_URL ?>productos?stock_bajo=1" class="tarjeta block p-5 transition hover:border-red-200 hover:shadow-md">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">Stock bajo mínimo</p>
                <p class="mt-2 text-3xl font-bold <?= (int) $catalogo['bajo_minimo'] > 0 ? 'text-red-600' : 'text-slate-900' ?>">
                    <?= (int) $catalogo['bajo_minimo'] ?>
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    de <?= (int) $catalogo['total'] ?> productos activos
                </p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-100 text-red-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
                </svg>
            </span>
        </div>
    </a>
</div>

<div class="mb-5">

    <!-- Alerta de stock -->
    <div class="tarjeta overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div class="flex items-center gap-2">
                <h2 class="font-semibold text-slate-800">Productos por reponer</h2>
                <?php if (!empty($datos['stockBajo'])): ?>
                    <span class="badge-rojo"><?= count($datos['stockBajo']) ?></span>
                <?php endif; ?>
            </div>
            <a href="<?= BASE_URL ?>productos?stock_bajo=1"
                class="text-xs font-medium text-marca-600 hover:text-marca-700">Ver todos</a>
        </div>

        <div class="overflow-x-auto">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Mínimo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($datos['stockBajo'])): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-400">
                                Ningún producto está por debajo del mínimo.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($datos['stockBajo'] as $producto): ?>
                        <tr>
                            <td class="font-medium text-slate-800"><?= e($producto['nombre']) ?></td>
                            <td class="text-xs text-slate-500"><?= e($producto['categoria']) ?></td>
                            <td class="text-center">
                                <span class="badge-rojo"><?= (int) $producto['stock'] ?></span>
                            </td>
                            <td class="text-center text-slate-500"><?= (int) $producto['stock_minimo'] ?></td>
                            <td class="text-right">
                                <a href="<?= BASE_URL ?>inventario?id_producto=<?= (int) $producto['id_producto'] ?>"
                                    class="btn-secundario btn-sm">Reponer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== Últimas ventas ===================== -->
<div class="tarjeta overflow-hidden">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <h2 class="font-semibold text-slate-800">Últimas ventas registradas</h2>
        <a href="<?= BASE_URL ?>ventas" class="text-xs font-medium text-marca-600 hover:text-marca-700">
            Ver historial completo
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Comprobante</th>
                    <th>Fecha</th>
                    <th>Cajero</th>
                    <th class="text-right">Total</th>
                    <th class="text-center">Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($datos['ultimasVentas'])): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                            Todavía no se han registrado ventas.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($datos['ultimasVentas'] as $venta): ?>
                    <tr>
                        <td class="font-mono text-sm font-medium text-slate-800"><?= e($venta['comprobante']) ?></td>
                        <td class="whitespace-nowrap text-xs text-slate-500"><?= fecha_hora($venta['fecha']) ?></td>
                        <td class="text-xs text-slate-500"><?= e($venta['cajero']) ?></td>
                        <td class="text-right font-semibold text-slate-800"><?= money($venta['total']) ?></td>
                        <td class="text-center">
                            <?php if ($venta['estado'] === 'EMITIDA'): ?>
                                <span class="badge-verde">Emitida</span>
                            <?php else: ?>
                                <span class="badge-rojo">Anulada</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a href="<?= BASE_URL ?>venta/<?= (int) $venta['id_venta'] ?>"
                                class="btn-secundario btn-sm">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../components/layout_fin.php'; ?>
