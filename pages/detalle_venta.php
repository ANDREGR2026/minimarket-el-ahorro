<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/VentaController.php';

Auth::checkRole(['Administrador', 'Cajero']);

$controlador = new VentaController();

$idVenta = (int) ($_GET['id'] ?? 0);
$venta   = $controlador->obtener($idVenta);

if (!$venta) {
    flash('error', 'La venta solicitada no existe.');
    redirigir('ventas');
}

// El cajero solo puede ver sus propias ventas
if (!Auth::esAdministrador() && (int) $venta['id_usuario'] !== Auth::id()) {
    flash('error', 'Solo puede consultar las ventas que usted registró.');
    redirigir('ventas');
}

$detalle = $controlador->detalle($idVenta);
$numero  = $venta['serie'] . '-' . str_pad($venta['correlativo'], 6, '0', STR_PAD_LEFT);

$titulo = 'Venta ' . $numero;
$activo = 'ventas';

require __DIR__ . '/../components/layout_inicio.php';
?>

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <a href="<?= BASE_URL ?>ventas" class="text-sm font-medium text-marca-600 hover:text-marca-700">
        &larr; Volver al historial
    </a>

    <a href="<?= BASE_URL ?>comprobante/<?= (int) $venta['id_venta'] ?>" target="_blank" class="btn-primario">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7a1 1 0 110-2 1 1 0 010 2zm-1-9H6v4h12V3z" />
        </svg>
        Imprimir comprobante
    </a>
</div>

<?php if ($venta['estado'] === 'ANULADA'): ?>
    <div class="alerta-error mb-5">
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
        </svg>
        <span>
            <strong>Comprobante anulado</strong> el <?= fecha_hora($venta['fecha_anulacion']) ?>.
            Motivo: <?= e($venta['motivo_anulacion']) ?>
        </span>
    </div>
<?php endif; ?>

<div class="grid gap-5 lg:grid-cols-3">

    <!-- ===================== Datos del comprobante ===================== -->
    <div class="tarjeta lg:col-span-1">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-800">Datos del comprobante</h2>
        </div>

        <dl class="divide-y divide-slate-100 px-5 text-sm">
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Número</dt>
                <dd class="font-mono font-medium text-slate-800"><?= e($numero) ?></dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Tipo</dt>
                <dd class="font-medium text-slate-800">
                    <?= e(ucfirst(strtolower($venta['tipo_comprobante']))) ?>
                </dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Fecha</dt>
                <dd class="text-slate-800"><?= fecha_hora($venta['fecha']) ?></dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Cajero</dt>
                <dd class="text-slate-800"><?= e($venta['cajero']) ?></dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Cliente</dt>
                <dd class="text-right text-slate-800">
                    <?= e($venta['cliente'] ?: 'Cliente varios') ?>
                    <?php if (!empty($venta['numero_documento'])): ?>
                        <span class="block text-xs text-slate-400">
                            <?= e($venta['tipo_documento']) ?> <?= e($venta['numero_documento']) ?>
                        </span>
                    <?php endif; ?>
                </dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Método de pago</dt>
                <dd class="text-slate-800"><?= e(ucfirst(strtolower($venta['metodo_pago']))) ?></dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-slate-500">Estado</dt>
                <dd>
                    <?php if ($venta['estado'] === 'EMITIDA'): ?>
                        <span class="badge-verde">Emitida</span>
                    <?php else: ?>
                        <span class="badge-rojo">Anulada</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </div>

    <!-- ===================== Detalle de productos ===================== -->
    <div class="lg:col-span-2">
        <div class="tarjeta overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-800">Productos vendidos</h2>
            </div>

            <!-- ---- Vista en tarjetas (mobile) ---- -->
            <div class="divide-y divide-slate-100 sm:hidden">
                <?php foreach ($detalle as $linea): ?>
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-slate-800"><?= e($linea['producto']) ?></p>
                                <p class="text-xs text-slate-400"><?= e($linea['codigo_barras']) ?></p>
                            </div>
                            <span class="shrink-0 font-semibold text-slate-800"><?= money($linea['subtotal']) ?></span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            <?= (int) $linea['cantidad'] ?> <?= e(strtolower($linea['unidad_medida'])) ?>
                            × <?= money($linea['precio_unitario']) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ---- Vista en tabla (sm en adelante) ---- -->
            <div class="hidden overflow-x-auto sm:block">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-right">P. Unitario</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalle as $linea): ?>
                            <tr>
                                <td>
                                    <p class="font-medium text-slate-800"><?= e($linea['producto']) ?></p>
                                    <p class="text-xs text-slate-400"><?= e($linea['codigo_barras']) ?></p>
                                </td>
                                <td class="text-center">
                                    <?= (int) $linea['cantidad'] ?>
                                    <span class="text-xs text-slate-400"><?= e(strtolower($linea['unidad_medida'])) ?></span>
                                </td>
                                <td class="text-right"><?= money($linea['precio_unitario']) ?></td>
                                <td class="text-right font-semibold text-slate-800"><?= money($linea['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Totales -->
            <div class="space-y-2 border-t border-slate-200 bg-slate-50 px-5 py-4 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Operación gravada</span>
                    <span><?= money($venta['subtotal']) ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>IGV</span>
                    <span><?= money($venta['igv']) ?></span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-lg font-bold text-slate-900">
                    <span>Total</span>
                    <span><?= money($venta['total']) ?></span>
                </div>

                <?php if ($venta['metodo_pago'] === 'EFECTIVO'): ?>
                    <div class="flex justify-between pt-1 text-slate-600">
                        <span>Monto recibido</span>
                        <span><?= money($venta['monto_pagado']) ?></span>
                    </div>
                    <div class="flex justify-between font-medium text-emerald-700">
                        <span>Vuelto</span>
                        <span><?= money($venta['vuelto']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../components/layout_fin.php'; ?>
