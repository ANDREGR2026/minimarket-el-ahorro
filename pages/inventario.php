<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/InventarioController.php';
require_once __DIR__ . '/../controllers/ProductoController.php';

Auth::checkRole(['Administrador', 'Almacenero']);

$controlador = new InventarioController();

// ---------------------------------------------------------------- acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        flash('error', 'La sesión expiró. Vuelva a intentarlo.');
        redirigir('inventario');
    }

    $resultado = $controlador->registrar($_POST, Auth::id());

    flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
    redirigir('inventario' . (!empty($_POST['id_producto_actual'])
        ? '?id_producto=' . (int) $_POST['id_producto_actual']
        : ''));
}

// ---------------------------------------------------------------- consulta
$idProducto      = (int) ($_GET['id_producto'] ?? 0);
$productoFiltro  = null;

if ($idProducto > 0) {
    $productoFiltro = (new ProductoController())->obtener($idProducto);

    if (!$productoFiltro) {
        flash('error', 'El producto solicitado no existe.');
        redirigir('productos');
    }

    $movimientos = $controlador->kardex($idProducto);
} else {
    $filtros = [
        'tipo'     => $_GET['tipo'] ?? '',
        'busqueda' => trim($_GET['q'] ?? ''),
        'desde'    => $_GET['desde'] ?? '',
        'hasta'    => $_GET['hasta'] ?? '',
    ];

    $movimientos = $controlador->movimientos($filtros, 150);
}

$productos = $controlador->productosActivos();

$titulo = $productoFiltro ? 'Inventario · ' . $productoFiltro['nombre'] : 'Inventario';
$activo = 'inventario';

require __DIR__ . '/../components/layout_inicio.php';
?>

<?php require __DIR__ . '/../components/flash.php'; ?>

<?php if (!$productoFiltro): ?>
    <p class="mb-4 text-sm text-slate-500">Entradas, salidas y ajustes de stock. Toda variación queda registrada.</p>
<?php endif; ?>

<?php if ($productoFiltro): ?>
    <div class="mb-5">
        <a href="<?= BASE_URL ?>productos" class="text-sm font-medium text-marca-600 hover:text-marca-700">
            &larr; Volver al catálogo
        </a>
    </div>

    <!-- ===================== Ficha del producto ===================== -->
    <div class="tarjeta mb-5 p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-center gap-4">
                <?php if (!empty($productoFiltro['imagen'])): ?>
                    <img src="<?= BASE_URL ?>assets/img/productos/<?= e($productoFiltro['imagen']) ?>"
                        alt="" class="h-16 w-16 shrink-0 rounded-xl object-cover ring-1 ring-slate-200">
                <?php else: ?>
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2 3 7v10l9 5 9-5V7l-9-5z" />
                        </svg>
                    </span>
                <?php endif; ?>

                <div class="min-w-0">
                    <h2 class="truncate text-lg font-semibold text-slate-800"><?= e($productoFiltro['nombre']) ?></h2>
                    <p class="truncate text-sm text-slate-500">
                        <?= e($productoFiltro['categoria']) ?> ·
                        <span class="font-mono text-xs"><?= e($productoFiltro['codigo_barras']) ?></span>
                    </p>
                </div>
            </div>

            <div class="grid w-full grid-cols-3 gap-3 text-center sm:w-auto sm:gap-6">
                <div>
                    <p class="text-xs tracking-wide text-slate-400 uppercase">Stock actual</p>
                    <p class="text-xl font-bold sm:text-2xl <?= (int) $productoFiltro['stock'] <= (int) $productoFiltro['stock_minimo'] ? 'text-red-600' : 'text-slate-800' ?>">
                        <?= (int) $productoFiltro['stock'] ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-slate-400 uppercase">Stock mínimo</p>
                    <p class="text-xl font-bold text-slate-800 sm:text-2xl"><?= (int) $productoFiltro['stock_minimo'] ?></p>
                </div>
                <div>
                    <p class="text-xs tracking-wide text-slate-400 uppercase">Precio venta</p>
                    <p class="text-xl font-bold text-slate-800 sm:text-2xl"><?= money($productoFiltro['precio_venta']) ?></p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="grid gap-5 lg:grid-cols-3">

    <!-- ===================== Formulario de movimiento ===================== -->
    <div class="tarjeta lg:col-span-1">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-800">Registrar movimiento</h2>
            <p class="mt-0.5 text-xs text-slate-500">
                Entrada, salida o ajuste — elija el tipo y el motivo.
            </p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>inventario" class="space-y-4 px-5 py-5">
            <?= csrf_field() ?>
            <input type="hidden" name="id_producto_actual" value="<?= (int) $idProducto ?>">

            <div>
                <label for="id_producto" class="etiqueta">Producto <span class="text-red-500">*</span></label>
                <select id="id_producto" name="id_producto" class="campo" required onchange="mostrarStock()">
                    <option value="">Seleccione un producto...</option>
                    <?php foreach ($productos as $producto): ?>
                        <option value="<?= (int) $producto['id_producto'] ?>"
                            data-stock="<?= (int) $producto['stock'] ?>"
                            data-unidad="<?= e($producto['unidad_medida']) ?>"
                            <?= (int) $producto['id_producto'] === $idProducto ? 'selected' : '' ?>>
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
        <?php if (!$productoFiltro): ?>
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
        <?php else: ?>
            <h3 class="mb-3 font-semibold text-slate-800">Kardex del producto</h3>
        <?php endif; ?>

        <?php
        $clasesTipo = [
            'ENTRADA' => 'badge-verde',
            'SALIDA'  => 'badge-rojo',
            'AJUSTE'  => 'badge-ambar',
        ];
        ?>

        <div class="tarjeta overflow-hidden">

            <!-- ---- Vista en tarjetas (mobile) ---- -->
            <div class="max-h-[560px] divide-y divide-slate-100 overflow-auto sm:hidden">
                <?php if (empty($movimientos)): ?>
                    <p class="px-4 py-10 text-center text-slate-400">
                        Todavía no hay movimientos de inventario registrados.
                    </p>
                <?php endif; ?>

                <?php foreach ($movimientos as $movimiento): ?>
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <?php if (!$productoFiltro): ?>
                                    <p class="truncate text-sm font-medium text-slate-800"><?= e($movimiento['producto']) ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-slate-400"><?= fecha_hora($movimiento['fecha']) ?></p>
                            </div>
                            <span class="<?= $clasesTipo[$movimiento['tipo']] ?> shrink-0">
                                <?= ucfirst(strtolower($movimiento['tipo'])) ?>
                            </span>
                        </div>

                        <div class="mt-3 flex items-center justify-between text-sm">
                            <span class="text-slate-500">Cantidad</span>
                            <span class="font-semibold text-slate-800">
                                <?= (int) $movimiento['stock_nuevo'] < (int) $movimiento['stock_anterior'] ? '−' : '+' ?><?= (int) $movimiento['cantidad'] ?>
                            </span>
                        </div>

                        <div class="mt-1 flex items-center justify-between text-sm">
                            <span class="text-slate-500">Stock</span>
                            <span class="text-xs text-slate-500">
                                <?= (int) $movimiento['stock_anterior'] ?> &rarr;
                                <strong class="text-slate-700"><?= (int) $movimiento['stock_nuevo'] ?></strong>
                            </span>
                        </div>

                        <p class="mt-2 text-xs text-slate-500">
                            <?= e($movimiento['motivo']) ?>
                            <?php if ($productoFiltro && !empty($movimiento['id_venta'])): ?>
                                <a href="<?= BASE_URL ?>venta/<?= (int) $movimiento['id_venta'] ?>"
                                    class="ml-1 font-medium text-marca-600 hover:text-marca-700">ver venta</a>
                            <?php endif; ?>
                        </p>
                        <p class="mt-1 text-xs text-slate-400">Registró: <?= e($movimiento['usuario']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ---- Vista en tabla (sm en adelante) ---- -->
            <div class="hidden max-h-[560px] overflow-auto sm:block">
                <table class="tabla">
                    <thead class="sticky top-0">
                        <tr>
                            <th>Fecha</th>
                            <?php if (!$productoFiltro): ?><th>Producto</th><?php endif; ?>
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
                                <td colspan="<?= $productoFiltro ? 6 : 7 ?>" class="px-4 py-10 text-center text-slate-400">
                                    Todavía no hay movimientos de inventario registrados.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($movimientos as $movimiento): ?>
                            <tr>
                                <td class="whitespace-nowrap text-xs text-slate-500">
                                    <?= fecha_hora($movimiento['fecha']) ?>
                                </td>
                                <?php if (!$productoFiltro): ?>
                                    <td class="font-medium text-slate-800"><?= e($movimiento['producto']) ?></td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <span class="<?= $clasesTipo[$movimiento['tipo']] ?>">
                                        <?= ucfirst(strtolower($movimiento['tipo'])) ?>
                                    </span>
                                </td>
                                <td class="text-center font-semibold">
                                    <?= (int) $movimiento['stock_nuevo'] < (int) $movimiento['stock_anterior'] ? '−' : '+' ?><?= (int) $movimiento['cantidad'] ?>
                                </td>
                                <td class="text-center text-xs text-slate-500">
                                    <?= (int) $movimiento['stock_anterior'] ?> &rarr;
                                    <strong class="text-slate-700"><?= (int) $movimiento['stock_nuevo'] ?></strong>
                                </td>
                                <td class="text-xs text-slate-500">
                                    <?= e($movimiento['motivo']) ?>
                                    <?php if ($productoFiltro && !empty($movimiento['id_venta'])): ?>
                                        <a href="<?= BASE_URL ?>venta/<?= (int) $movimiento['id_venta'] ?>"
                                            class="ml-1 font-medium text-marca-600 hover:text-marca-700">ver venta</a>
                                    <?php endif; ?>
                                </td>
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

    document.addEventListener('DOMContentLoaded', mostrarStock);
</script>

<?php require __DIR__ . '/../components/layout_fin.php'; ?>
