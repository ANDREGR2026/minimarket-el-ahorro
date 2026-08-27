<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/InventarioController.php';

Auth::checkRole(['Administrador']);

$controlador = new InventarioController();

// ---------------------------------------------------------------- acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        flash('error', 'La sesión expiró. Vuelva a intentarlo.');
        redirigir('inventario');
    }

    $resultado = $controlador->registrar($_POST, Auth::id());

    flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
    redirigir('inventario');
}

// ---------------------------------------------------------------- consulta
$filtros = [
    'tipo'     => $_GET['tipo'] ?? '',
    'busqueda' => trim($_GET['q'] ?? ''),
    'desde'    => $_GET['desde'] ?? '',
    'hasta'    => $_GET['hasta'] ?? '',
];

$movimientos = $controlador->movimientos($filtros, 150);
$productos   = $controlador->productosActivos();

$titulo = 'Inventario';
$activo = 'inventario';

require __DIR__ . '/../components/layout_inicio.php';
?>

<?php require __DIR__ . '/../components/flash.php'; ?>

<div class="grid gap-5 lg:grid-cols-3">

    <!-- ===================== Formulario de movimiento ===================== -->
    <div class="tarjeta lg:col-span-1">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-800">Registrar movimiento</h2>
            <p class="mt-0.5 text-xs text-slate-500">
                Toda variación de stock queda registrada en el kardex.
            </p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>inventario" class="space-y-4 px-5 py-5">
            <?= csrf_field() ?>

            <div>
                <label for="id_producto" class="etiqueta">Producto <span class="text-red-500">*</span></label>
                <select id="id_producto" name="id_producto" class="campo" required onchange="mostrarStock()">
                    <option value="">Seleccione un producto...</option>
                    <?php foreach ($productos as $producto): ?>
                        <option value="<?= (int) $producto['id_producto'] ?>"
                            data-stock="<?= (int) $producto['stock'] ?>"
                            data-unidad="<?= e($producto['unidad_medida']) ?>">
                            <?= e($producto['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p id="stock-actual" class="mt-1 text-xs text-slate-400"></p>
            </div>

            <div>
                <label for="tipo-mov" class="etiqueta">Tipo de movimiento <span class="text-red-500">*</span></label>
                <select id="tipo-mov" name="tipo" class="campo" required onchange="cambiarTipo()">
                    <option value="ENTRADA">Entrada — ingreso de mercadería</option>
                    <option value="SALIDA">Salida — merma, rotura o vencimiento</option>
                    <option value="AJUSTE">Ajuste — conteo físico</option>
                </select>
            </div>

            <div>
                <label for="cantidad" class="etiqueta">
                    <span id="etiqueta-cantidad">Cantidad</span> <span class="text-red-500">*</span>
                </label>
                <input type="number" id="cantidad" name="cantidad" class="campo" min="0" value="1" required>
                <p id="ayuda-cantidad" class="mt-1 text-xs text-slate-400">
                    Unidades que ingresan al stock.
                </p>
            </div>

            <div>
                <label for="motivo" class="etiqueta">Motivo <span class="text-red-500">*</span></label>
                <input type="text" id="motivo" name="motivo" class="campo" maxlength="255" required
                    placeholder="Ej.: Compra a proveedor / Producto vencido">
            </div>

            <button type="submit" class="btn-primario w-full">Registrar movimiento</button>
        </form>
    </div>

    <!-- ===================== Historial de movimientos ===================== -->
    <div class="lg:col-span-2">
        <form method="GET" action="<?= BASE_URL ?>inventario"
            class="mb-4 flex flex-wrap items-end gap-2">
            <div>
                <label for="q" class="etiqueta">Producto</label>
                <input type="search" id="q" name="q" value="<?= e($filtros['busqueda']) ?>"
                    class="campo w-44" placeholder="Buscar...">
            </div>

            <div>
                <label for="tipo" class="etiqueta">Tipo</label>
                <select id="tipo" name="tipo" class="campo w-32">
                    <option value="">Todos</option>
                    <?php foreach (['ENTRADA', 'SALIDA', 'AJUSTE'] as $tipo): ?>
                        <option value="<?= $tipo ?>" <?= $filtros['tipo'] === $tipo ? 'selected' : '' ?>>
                            <?= ucfirst(strtolower($tipo)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="desde" class="etiqueta">Desde</label>
                <input type="date" id="desde" name="desde" value="<?= e($filtros['desde']) ?>" class="campo w-36">
            </div>

            <div>
                <label for="hasta" class="etiqueta">Hasta</label>
                <input type="date" id="hasta" name="hasta" value="<?= e($filtros['hasta']) ?>" class="campo w-36">
            </div>

            <button type="submit" class="btn-secundario">Filtrar</button>
            <a href="<?= BASE_URL ?>inventario" class="btn-secundario">Limpiar</a>
        </form>

        <div class="tarjeta overflow-hidden">
            <div class="max-h-[560px] overflow-auto">
                <table class="tabla">
                    <thead class="sticky top-0">
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-center">Stock</th>
                            <th>Motivo</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimientos)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-slate-400">
                                    Todavía no hay movimientos de inventario registrados.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($movimientos as $movimiento): ?>
                            <tr>
                                <td class="whitespace-nowrap text-xs text-slate-500">
                                    <?= fecha_hora($movimiento['fecha']) ?>
                                </td>
                                <td class="font-medium text-slate-800"><?= e($movimiento['producto']) ?></td>
                                <td class="text-center">
                                    <?php
                                    $clasesTipo = [
                                        'ENTRADA' => 'badge-verde',
                                        'SALIDA'  => 'badge-rojo',
                                        'AJUSTE'  => 'badge-ambar',
                                    ];
                                    ?>
                                    <span class="<?= $clasesTipo[$movimiento['tipo']] ?>">
                                        <?= ucfirst(strtolower($movimiento['tipo'])) ?>
                                    </span>
                                </td>
                                <td class="text-center font-semibold">
                                    <?= $movimiento['tipo'] === 'SALIDA' ? '−' : '+' ?><?= (int) $movimiento['cantidad'] ?>
                                </td>
                                <td class="text-center text-xs text-slate-500">
                                    <?= (int) $movimiento['stock_anterior'] ?> &rarr;
                                    <strong class="text-slate-700"><?= (int) $movimiento['stock_nuevo'] ?></strong>
                                </td>
                                <td class="text-xs text-slate-500"><?= e($movimiento['motivo']) ?></td>
                                <td class="text-xs text-slate-500"><?= e($movimiento['usuario']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                <?= count($movimientos) ?> movimiento(s)
            </div>
        </div>
    </div>
</div>

<script>
    // Muestra el stock actual del producto seleccionado
    function mostrarStock() {
        const select = document.getElementById('id_producto');
        const opcion = select.options[select.selectedIndex];
        const texto = document.getElementById('stock-actual');

        if (!opcion.value) {
            texto.textContent = '';
            return;
        }

        texto.textContent = 'Stock actual: ' + opcion.dataset.stock + ' ' +
            opcion.dataset.unidad.toLowerCase();
    }

    // En un ajuste la cantidad es el stock contado, no la diferencia
    function cambiarTipo() {
        const tipo = document.getElementById('tipo-mov').value;
        const etiqueta = document.getElementById('etiqueta-cantidad');
        const ayuda = document.getElementById('ayuda-cantidad');

        if (tipo === 'AJUSTE') {
            etiqueta.textContent = 'Stock contado';
            ayuda.textContent = 'Cantidad real encontrada en el conteo físico.';
        } else if (tipo === 'SALIDA') {
            etiqueta.textContent = 'Cantidad';
            ayuda.textContent = 'Unidades que salen del stock.';
        } else {
            etiqueta.textContent = 'Cantidad';
            ayuda.textContent = 'Unidades que ingresan al stock.';
        }
    }
</script>

<?php require __DIR__ . '/../components/layout_fin.php'; ?>
