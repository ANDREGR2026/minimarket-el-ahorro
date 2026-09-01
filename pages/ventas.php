<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/VentaController.php';

Auth::checkRole(['Administrador', 'Cajero']);

$controlador = new VentaController();

// ---------------------------------------------------------------- acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        flash('error', 'La sesión expiró. Vuelva a intentarlo.');
        redirigir('ventas');
    }

    // Solo el Administrador puede anular un comprobante
    if (!Auth::esAdministrador()) {
        flash('error', 'Solo un administrador puede anular una venta.');
        redirigir('ventas');
    }

    $resultado = $controlador->anular(
        $_POST['id_venta'] ?? 0,
        $_POST['motivo_anulacion'] ?? '',
        Auth::id()
    );

    flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
    redirigir('ventas');
}

// ---------------------------------------------------------------- consulta
$filtros = [
    'desde'            => $_GET['desde'] ?? date('Y-m-01'),
    'hasta'            => $_GET['hasta'] ?? date('Y-m-d'),
    'estado'           => $_GET['estado'] ?? '',
    'tipo_comprobante' => $_GET['tipo'] ?? '',
    'busqueda'         => trim($_GET['q'] ?? ''),
];

// El cajero solo ve sus propias ventas
if (!Auth::esAdministrador()) {
    $filtros['id_usuario'] = Auth::id();
}

$ventas = $controlador->listar($filtros);

// Totales del listado mostrado
$totalEmitido = 0;
$totalAnulado = 0;
foreach ($ventas as $venta) {
    if ($venta['estado'] === 'EMITIDA') {
        $totalEmitido += (float) $venta['total'];
    } else {
        $totalAnulado += (float) $venta['total'];
    }
}

$titulo = 'Ventas';
$activo = 'ventas';

require __DIR__ . '/../components/layout_inicio.php';
?>

<?php require __DIR__ . '/../components/flash.php'; ?>

<p class="mb-4 text-sm text-slate-500">
    <?= Auth::esAdministrador() ? 'Historial de comprobantes emitidos y anulados.' : 'Historial de sus comprobantes emitidos.' ?>
</p>

<form method="GET" action="<?= BASE_URL ?>ventas" class="mb-5 flex flex-wrap items-end gap-2">
    <div>
        <label for="desde" class="etiqueta">Desde</label>
        <input type="date" id="desde" name="desde" value="<?= e($filtros['desde']) ?>" class="campo w-40">
    </div>

    <div>
        <label for="hasta" class="etiqueta">Hasta</label>
        <input type="date" id="hasta" name="hasta" value="<?= e($filtros['hasta']) ?>" class="campo w-40">
    </div>

    <div>
        <label for="tipo" class="etiqueta">Comprobante</label>
        <select id="tipo" name="tipo" class="campo w-32">
            <option value="">Todos</option>
            <option value="BOLETA" <?= $filtros['tipo_comprobante'] === 'BOLETA' ? 'selected' : '' ?>>Boleta</option>
            <option value="FACTURA" <?= $filtros['tipo_comprobante'] === 'FACTURA' ? 'selected' : '' ?>>Factura</option>
        </select>
    </div>

    <div>
        <label for="estado" class="etiqueta">Estado</label>
        <select id="estado" name="estado" class="campo w-32">
            <option value="">Todos</option>
            <option value="EMITIDA" <?= $filtros['estado'] === 'EMITIDA' ? 'selected' : '' ?>>Emitida</option>
            <option value="ANULADA" <?= $filtros['estado'] === 'ANULADA' ? 'selected' : '' ?>>Anulada</option>
        </select>
    </div>

    <div>
        <label for="q" class="etiqueta">Buscar</label>
        <input type="search" id="q" name="q" value="<?= e($filtros['busqueda']) ?>"
            class="campo w-48" placeholder="N° comprobante o documento">
    </div>

    <button type="submit" class="btn-secundario">Filtrar</button>
    <a href="<?= BASE_URL ?>ventas" class="btn-secundario">Limpiar</a>
</form>

<!-- Resumen del periodo consultado -->
<div class="mb-5 grid gap-4 sm:grid-cols-3">
    <div class="tarjeta p-4">
        <p class="text-xs tracking-wide text-slate-400 uppercase">Comprobantes</p>
        <p class="mt-1 text-2xl font-bold text-slate-800"><?= count($ventas) ?></p>
    </div>
    <div class="tarjeta p-4">
        <p class="text-xs tracking-wide text-slate-400 uppercase">Total emitido</p>
        <p class="mt-1 text-2xl font-bold text-emerald-600"><?= money($totalEmitido) ?></p>
    </div>
    <div class="tarjeta p-4">
        <p class="text-xs tracking-wide text-slate-400 uppercase">Total anulado</p>
        <p class="mt-1 text-2xl font-bold text-red-500"><?= money($totalAnulado) ?></p>
    </div>
</div>

<div class="tarjeta overflow-hidden">

    <!-- ---- Vista en tarjetas (mobile) ---- -->
    <div class="divide-y divide-slate-100 sm:hidden">
        <?php if (empty($ventas)): ?>
            <p class="px-4 py-10 text-center text-slate-400">No hay ventas registradas en el período seleccionado.</p>
        <?php endif; ?>

        <?php foreach ($ventas as $venta): ?>
            <?php $numero = $venta['serie'] . '-' . str_pad($venta['correlativo'], 6, '0', STR_PAD_LEFT); ?>
            <div class="p-4 <?= $venta['estado'] === 'ANULADA' ? 'opacity-60' : '' ?>">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-mono text-sm font-medium text-slate-800"><?= e($numero) ?></p>
                        <p class="text-xs text-slate-400"><?= e(ucfirst(strtolower($venta['tipo_comprobante']))) ?> · <?= fecha_hora($venta['fecha']) ?></p>
                    </div>
                    <?php if ($venta['estado'] === 'EMITIDA'): ?>
                        <span class="badge-verde shrink-0">Emitida</span>
                    <?php else: ?>
                        <span class="badge-rojo shrink-0">Anulada</span>
                    <?php endif; ?>
                </div>

                <p class="mt-2 text-sm text-slate-700"><?= e($venta['cliente'] ?: 'Cliente varios') ?></p>
                <p class="text-xs text-slate-400">Cajero: <?= e($venta['cajero']) ?></p>

                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="text-slate-500"><?= (int) $venta['items'] ?> ítem(s)</span>
                    <span class="font-semibold text-slate-800"><?= money($venta['total']) ?></span>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>venta/<?= (int) $venta['id_venta'] ?>"
                        class="btn-secundario btn-sm">Ver</a>

                    <a href="<?= BASE_URL ?>comprobante/<?= (int) $venta['id_venta'] ?>"
                        target="_blank" class="btn-secundario btn-sm">PDF</a>

                    <?php if ($venta['estado'] === 'EMITIDA' && Auth::esAdministrador()): ?>
                        <button type="button" class="btn-peligro btn-sm"
                            onclick="abrirAnulacion(<?= (int) $venta['id_venta'] ?>, '<?= e($numero) ?>')">
                            Anular
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ---- Vista en tabla (sm en adelante) ---- -->
    <div class="hidden overflow-x-auto sm:block">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Comprobante</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Cajero</th>
                    <th class="text-center">Ítems</th>
                    <th class="text-right">Total</th>
                    <th class="text-center">Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ventas)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                            No hay ventas registradas en el período seleccionado.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($ventas as $venta): ?>
                    <?php $numero = $venta['serie'] . '-' . str_pad($venta['correlativo'], 6, '0', STR_PAD_LEFT); ?>
                    <tr class="<?= $venta['estado'] === 'ANULADA' ? 'opacity-60' : '' ?>">
                        <td>
                            <p class="font-mono text-sm font-medium text-slate-800"><?= e($numero) ?></p>
                            <p class="text-xs text-slate-400"><?= e(ucfirst(strtolower($venta['tipo_comprobante']))) ?></p>
                        </td>
                        <td class="whitespace-nowrap text-xs text-slate-500"><?= fecha_hora($venta['fecha']) ?></td>
                        <td>
                            <p class="text-sm text-slate-700"><?= e($venta['cliente'] ?: 'Cliente varios') ?></p>
                            <?php if (!empty($venta['numero_documento'])): ?>
                                <p class="text-xs text-slate-400"><?= e($venta['numero_documento']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="text-xs text-slate-500"><?= e($venta['cajero']) ?></td>
                        <td class="text-center"><?= (int) $venta['items'] ?></td>
                        <td class="text-right font-semibold text-slate-800"><?= money($venta['total']) ?></td>
                        <td class="text-center">
                            <?php if ($venta['estado'] === 'EMITIDA'): ?>
                                <span class="badge-verde">Emitida</span>
                            <?php else: ?>
                                <span class="badge-rojo">Anulada</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="<?= BASE_URL ?>venta/<?= (int) $venta['id_venta'] ?>"
                                class="btn-secundario btn-sm">Ver</a>

                            <a href="<?= BASE_URL ?>comprobante/<?= (int) $venta['id_venta'] ?>"
                                target="_blank" class="btn-secundario btn-sm">PDF</a>

                            <?php if ($venta['estado'] === 'EMITIDA' && Auth::esAdministrador()): ?>
                                <button type="button" class="btn-peligro btn-sm"
                                    onclick="abrirAnulacion(<?= (int) $venta['id_venta'] ?>, '<?= e($numero) ?>')">
                                    Anular
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (Auth::esAdministrador()): ?>
    <!-- ===================== Modal de anulación ===================== -->
    <div id="modal-anular" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="tarjeta w-full max-w-md">
            <form method="POST" action="<?= BASE_URL ?>ventas">
                <?= csrf_field() ?>
                <input type="hidden" name="id_venta" id="anular-id">

                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-800">Anular comprobante</h2>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div class="alerta-aviso">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                        </svg>
                        <span>
                            Se anulará el comprobante <strong id="anular-numero" class="font-mono"></strong>
                            y las unidades vendidas volverán al stock. Esta acción no se puede deshacer.
                        </span>
                    </div>

                    <div>
                        <label for="motivo_anulacion" class="etiqueta">
                            Motivo de la anulación <span class="text-red-500">*</span>
                        </label>
                        <textarea id="motivo_anulacion" name="motivo_anulacion" class="campo" rows="3"
                            minlength="5" maxlength="255" required
                            placeholder="Ej.: Error en el registro de cantidades"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button type="button" class="btn-secundario" onclick="cerrarAnulacion()">Cancelar</button>
                    <button type="submit" class="btn-peligro">Anular venta</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modalAnular = document.getElementById('modal-anular');

        function abrirAnulacion(idVenta, numero) {
            document.getElementById('anular-id').value = idVenta;
            document.getElementById('anular-numero').textContent = numero;
            document.getElementById('motivo_anulacion').value = '';
            modalAnular.classList.replace('hidden', 'flex');
            document.getElementById('motivo_anulacion').focus();
        }

        function cerrarAnulacion() {
            modalAnular.classList.replace('flex', 'hidden');
        }

        modalAnular.addEventListener('click', (e) => {
            if (e.target === modalAnular) cerrarAnulacion();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') cerrarAnulacion();
        });
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../components/layout_fin.php'; ?>
