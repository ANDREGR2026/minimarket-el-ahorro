<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/ReporteController.php';

Auth::checkRole(['Administrador']);

$controlador = new ReporteController();

[$desde, $hasta] = ReporteController::rango($_GET['desde'] ?? null, $_GET['hasta'] ?? null);

$datos   = $controlador->reportes($desde, $hasta);
$resumen = $datos['resumen'];

$titulo  = 'Reportes';
$activo  = 'reportes';
$scripts = ['assets/js/chart.min.js'];

require __DIR__ . '/../components/layout_inicio.php';
?>

<!-- ===================== Filtros y exportación ===================== -->
<div class="mb-5 flex flex-wrap items-end justify-between gap-3 no-imprimir">
    <form method="GET" action="<?= BASE_URL ?>reportes" class="flex flex-wrap items-end gap-2">
        <div>
            <label for="desde" class="etiqueta">Desde</label>
            <input type="date" id="desde" name="desde" value="<?= e($desde) ?>" class="campo w-40">
        </div>

        <div>
            <label for="hasta" class="etiqueta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="<?= e($hasta) ?>" class="campo w-40">
        </div>

        <button type="submit" class="btn-secundario">Generar</button>
    </form>

    <div class="flex flex-wrap gap-2">
        <?php foreach ([
            'hoy'      => ['Hoy', date('Y-m-d'), date('Y-m-d')],
            'semana'   => ['Últimos 7 días', date('Y-m-d', strtotime('-6 days')), date('Y-m-d')],
            'mes'      => ['Este mes', date('Y-m-01'), date('Y-m-d')],
            'anterior' => ['Mes anterior', date('Y-m-01', strtotime('first day of last month')),
                           date('Y-m-t', strtotime('last day of last month'))],
        ] as $atajo): ?>
            <a href="<?= BASE_URL ?>reportes?desde=<?= $atajo[1] ?>&hasta=<?= $atajo[2] ?>"
                class="btn-secundario btn-sm <?= $desde === $atajo[1] && $hasta === $atajo[2] ? 'ring-marca-500 ring-2' : '' ?>">
                <?= $atajo[0] ?>
            </a>
        <?php endforeach; ?>

        <a href="<?= BASE_URL ?>exportar?formato=pdf&desde=<?= e($desde) ?>&hasta=<?= e($hasta) ?>"
            target="_blank" class="btn-primario btn-sm">PDF</a>

        <a href="<?= BASE_URL ?>exportar?formato=csv&desde=<?= e($desde) ?>&hasta=<?= e($hasta) ?>"
            class="btn-exito btn-sm">Excel (CSV)</a>
    </div>
</div>

<div class="mb-5 rounded-lg bg-marca-50 px-4 py-2.5 text-sm text-marca-800">
    Reporte del <strong><?= fecha_corta($desde) ?></strong> al <strong><?= fecha_corta($hasta) ?></strong>
</div>

<!-- ===================== Resumen del período ===================== -->
<div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <?php
    $tarjetas = [
        ['Comprobantes',    (int) $resumen['tickets'],           'slate'],
        ['Op. gravada',     money($resumen['subtotal']),         'slate'],
        ['IGV recaudado',   money($resumen['igv']),              'amber'],
        ['Total vendido',   money($resumen['total']),            'emerald'],
        ['Ticket promedio', money($resumen['ticket_promedio']),  'marca'],
    ];
    $colores = [
        'slate'   => 'text-slate-900',
        'amber'   => 'text-amber-600',
        'emerald' => 'text-emerald-600',
        'marca'   => 'text-marca-600',
    ];
    ?>
    <?php foreach ($tarjetas as $tarjeta): ?>
        <div class="tarjeta p-5">
            <p class="text-xs font-medium tracking-wide text-slate-400 uppercase"><?= $tarjeta[0] ?></p>
            <p class="mt-2 text-2xl font-bold <?= $colores[$tarjeta[2]] ?>"><?= $tarjeta[1] ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- ===================== Gráficos ===================== -->
<div class="mb-5 grid gap-5 lg:grid-cols-3">
    <div class="tarjeta p-5 lg:col-span-2">
        <h2 class="font-semibold text-slate-800">Evolución de las ventas</h2>
        <p class="mt-0.5 mb-4 text-xs text-slate-500">Importe facturado por día dentro del período.</p>
        <div class="h-64"><canvas id="grafico-evolucion"></canvas></div>
    </div>

    <div class="tarjeta p-5">
        <h2 class="font-semibold text-slate-800">Métodos de pago</h2>
        <p class="mt-0.5 mb-4 text-xs text-slate-500">Cómo pagaron los clientes.</p>
        <div class="h-64"><canvas id="grafico-metodos"></canvas></div>
    </div>
</div>

<!-- ===================== Tablas ===================== -->
<div class="grid gap-5 lg:grid-cols-2">

    <!-- Ventas por cajero -->
    <div class="tarjeta overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-800">Ventas por cajero</h2>
        </div>
        <!-- ---- Vista en tarjetas (mobile) ---- -->
        <div class="divide-y divide-slate-100 sm:hidden">
            <?php if (empty($datos['porCajero'])): ?>
                <p class="px-4 py-10 text-center text-slate-400">Sin ventas en el período.</p>
            <?php endif; ?>
            <?php foreach ($datos['porCajero'] as $fila): ?>
                <div class="flex items-center justify-between gap-3 p-4">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-800"><?= e($fila['cajero']) ?></p>
                        <p class="text-xs text-slate-500"><?= (int) $fila['tickets'] ?> comprobante(s) · prom. <?= money($fila['ticket_promedio']) ?></p>
                    </div>
                    <span class="shrink-0 font-semibold text-slate-800"><?= money($fila['importe']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ---- Vista en tabla (sm en adelante) ---- -->
        <div class="hidden overflow-x-auto sm:block">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Cajero</th>
                        <th class="text-center">Comprobantes</th>
                        <th class="text-right">Importe</th>
                        <th class="text-right">Ticket prom.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($datos['porCajero'])): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-400">
                                Sin ventas en el período.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($datos['porCajero'] as $fila): ?>
                        <tr>
                            <td class="font-medium text-slate-800"><?= e($fila['cajero']) ?></td>
                            <td class="text-center"><?= (int) $fila['tickets'] ?></td>
                            <td class="text-right font-semibold"><?= money($fila['importe']) ?></td>
                            <td class="text-right text-slate-500"><?= money($fila['ticket_promedio']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ventas por categoría -->
    <div class="tarjeta overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-800">Ventas por categoría</h2>
        </div>
        <!-- ---- Vista en tarjetas (mobile) ---- -->
        <div class="divide-y divide-slate-100 sm:hidden">
            <?php if (empty($datos['porCategoria'])): ?>
                <p class="px-4 py-10 text-center text-slate-400">Sin ventas en el período.</p>
            <?php endif; ?>
            <?php foreach ($datos['porCategoria'] as $fila): ?>
                <div class="flex items-center justify-between gap-3 p-4">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-800"><?= e($fila['categoria']) ?></p>
                        <p class="text-xs text-slate-500"><?= (int) $fila['unidades'] ?> unidad(es)</p>
                    </div>
                    <span class="shrink-0 font-semibold text-slate-800"><?= money($fila['importe']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ---- Vista en tabla (sm en adelante) ---- -->
        <div class="hidden overflow-x-auto sm:block">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th class="text-center">Unidades</th>
                        <th class="text-right">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($datos['porCategoria'])): ?>
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-slate-400">
                                Sin ventas en el período.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($datos['porCategoria'] as $fila): ?>
                        <tr>
                            <td class="font-medium text-slate-800"><?= e($fila['categoria']) ?></td>
                            <td class="text-center"><?= (int) $fila['unidades'] ?></td>
                            <td class="text-right font-semibold"><?= money($fila['importe']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Productos más vendidos -->
    <div class="tarjeta overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-800">Productos más vendidos</h2>
        </div>
        <!-- ---- Vista en tarjetas (mobile) ---- -->
        <div class="max-h-96 divide-y divide-slate-100 overflow-auto sm:hidden">
            <?php if (empty($datos['masVendidos'])): ?>
                <p class="px-4 py-10 text-center text-slate-400">Sin ventas en el período.</p>
            <?php endif; ?>
            <?php foreach ($datos['masVendidos'] as $indice => $fila): ?>
                <div class="flex items-center gap-3 p-4">
                    <span class="w-5 shrink-0 text-slate-400"><?= $indice + 1 ?></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-slate-800"><?= e($fila['nombre']) ?></p>
                        <p class="text-xs text-slate-400"><?= e($fila['categoria']) ?> · <?= (int) $fila['unidades'] ?> unid.</p>
                    </div>
                    <span class="shrink-0 font-semibold text-slate-800"><?= money($fila['importe']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ---- Vista en tabla (sm en adelante) ---- -->
        <div class="hidden max-h-96 overflow-auto sm:block">
            <table class="tabla">
                <thead class="sticky top-0">
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th class="text-center">Unidades</th>
                        <th class="text-right">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($datos['masVendidos'])): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-400">
                                Sin ventas en el período.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($datos['masVendidos'] as $indice => $fila): ?>
                        <tr>
                            <td class="text-slate-400"><?= $indice + 1 ?></td>
                            <td>
                                <p class="font-medium text-slate-800"><?= e($fila['nombre']) ?></p>
                                <p class="text-xs text-slate-400"><?= e($fila['categoria']) ?></p>
                            </td>
                            <td class="text-center font-semibold"><?= (int) $fila['unidades'] ?></td>
                            <td class="text-right"><?= money($fila['importe']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stock bajo -->
    <div class="tarjeta overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-800">Productos por reponer</h2>
            <p class="mt-0.5 text-xs text-slate-500">Independiente del período consultado.</p>
        </div>
        <!-- ---- Vista en tarjetas (mobile) ---- -->
        <div class="max-h-96 divide-y divide-slate-100 overflow-auto sm:hidden">
            <?php if (empty($datos['stockBajo'])): ?>
                <p class="px-4 py-10 text-center text-slate-400">Ningún producto está por debajo del mínimo.</p>
            <?php endif; ?>
            <?php foreach ($datos['stockBajo'] as $fila): ?>
                <div class="flex items-center justify-between gap-3 p-4">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-800"><?= e($fila['nombre']) ?></p>
                        <p class="text-xs text-slate-500"><?= e($fila['categoria']) ?></p>
                    </div>
                    <span class="shrink-0 text-sm">
                        <span class="badge-rojo"><?= (int) $fila['stock'] ?></span>
                        <span class="text-slate-400">de <?= (int) $fila['stock_minimo'] ?></span>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ---- Vista en tabla (sm en adelante) ---- -->
        <div class="hidden max-h-96 overflow-auto sm:block">
            <table class="tabla">
                <thead class="sticky top-0">
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Mínimo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($datos['stockBajo'])): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-400">
                                Ningún producto está por debajo del mínimo.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($datos['stockBajo'] as $fila): ?>
                        <tr>
                            <td class="font-medium text-slate-800"><?= e($fila['nombre']) ?></td>
                            <td class="text-xs text-slate-500"><?= e($fila['categoria']) ?></td>
                            <td class="text-center"><span class="badge-rojo"><?= (int) $fila['stock'] ?></span></td>
                            <td class="text-center text-slate-500"><?= (int) $fila['stock_minimo'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const evolucion = <?= json_encode($datos['porDia']) ?>;
    const metodos = <?= json_encode($datos['porMetodo']) ?>;

    document.addEventListener('DOMContentLoaded', function() {
        Chart.defaults.font.family = '"Segoe UI", system-ui, sans-serif';
        Chart.defaults.color = '#64748b';

        new Chart(document.getElementById('grafico-evolucion'), {
            type: 'bar',
            data: {
                labels: evolucion.map(d => d.etiqueta),
                datasets: [{
                    label: 'Ventas (S/)',
                    data: evolucion.map(d => d.total),
                    backgroundColor: '#257aeb',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => 'S/ ' + c.parsed.y.toFixed(2) } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => 'S/ ' + v } },
                    x: { grid: { display: false } }
                }
            }
        });

        if (metodos.length > 0) {
            new Chart(document.getElementById('grafico-metodos'), {
                type: 'pie',
                data: {
                    labels: metodos.map(m => m.metodo_pago),
                    datasets: [{
                        data: metodos.map(m => Number(m.importe)),
                        backgroundColor: ['#10b981', '#257aeb', '#8b5cf6', '#f59e0b'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } },
                        tooltip: {
                            callbacks: { label: c => c.label + ': S/ ' + c.parsed.toFixed(2) }
                        }
                    }
                }
            });
        }
    });
</script>

<?php require __DIR__ . '/../components/layout_fin.php'; ?>
