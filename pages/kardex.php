<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/InventarioController.php';
require_once __DIR__ . '/../controllers/ProductoController.php';

Auth::checkRole(['Administrador']);

$idProducto = (int) ($_GET['id'] ?? 0);
$producto   = (new ProductoController())->obtener($idProducto);

if (!$producto) {
    flash('error', 'El producto solicitado no existe.');
    redirigir('productos');
}

$movimientos = (new InventarioController())->kardex($idProducto);

$titulo = 'Kardex · ' . $producto['nombre'];
$activo = 'productos';

require __DIR__ . '/../components/layout_inicio.php';
?>

<div class="mb-5">
    <a href="<?= BASE_URL ?>productos" class="text-sm font-medium text-marca-600 hover:text-marca-700">
        &larr; Volver al catálogo
    </a>
</div>

<!-- ===================== Ficha del producto ===================== -->
<div class="tarjeta mb-5 p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <?php if (!empty($producto['imagen'])): ?>
                <img src="<?= BASE_URL ?>assets/img/productos/<?= e($producto['imagen']) ?>"
                    alt="" class="h-16 w-16 rounded-xl object-cover ring-1 ring-slate-200">
            <?php else: ?>
                <span class="flex h-16 w-16 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2 3 7v10l9 5 9-5V7l-9-5z" />
                    </svg>
                </span>
            <?php endif; ?>

            <div>
                <h2 class="text-lg font-semibold text-slate-800"><?= e($producto['nombre']) ?></h2>
                <p class="text-sm text-slate-500">
                    <?= e($producto['categoria']) ?> ·
                    <span class="font-mono text-xs"><?= e($producto['codigo_barras']) ?></span>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6 text-center">
            <div>
                <p class="text-xs tracking-wide text-slate-400 uppercase">Stock actual</p>
                <p class="text-2xl font-bold <?= (int) $producto['stock'] <= (int) $producto['stock_minimo'] ? 'text-red-600' : 'text-slate-800' ?>">
                    <?= (int) $producto['stock'] ?>
                </p>
            </div>
            <div>
                <p class="text-xs tracking-wide text-slate-400 uppercase">Stock mínimo</p>
                <p class="text-2xl font-bold text-slate-800"><?= (int) $producto['stock_minimo'] ?></p>
            </div>
            <div>
                <p class="text-xs tracking-wide text-slate-400 uppercase">Precio venta</p>
                <p class="text-2xl font-bold text-slate-800"><?= money($producto['precio_venta']) ?></p>
            </div>
        </div>
    </div>
</div>

<!-- ===================== Movimientos ===================== -->
<div class="tarjeta overflow-hidden">
    <div class="border-b border-slate-200 px-5 py-4">
        <h3 class="font-semibold text-slate-800">Kardex del producto</h3>
        <p class="mt-0.5 text-xs text-slate-500">
            Historial completo de entradas, salidas y ajustes, del más reciente al más antiguo.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Stock anterior</th>
                    <th class="text-center">Stock nuevo</th>
                    <th>Motivo</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimientos)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-400">
                            Este producto todavía no registra movimientos.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($movimientos as $movimiento): ?>
                    <tr>
                        <td class="whitespace-nowrap text-xs text-slate-500">
                            <?= fecha_hora($movimiento['fecha']) ?>
                        </td>
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
                        <td class="text-center text-slate-500"><?= (int) $movimiento['stock_anterior'] ?></td>
                        <td class="text-center font-semibold text-slate-800"><?= (int) $movimiento['stock_nuevo'] ?></td>
                        <td class="text-xs text-slate-500">
                            <?= e($movimiento['motivo']) ?>
                            <?php if (!empty($movimiento['id_venta'])): ?>
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
</div>

<?php require __DIR__ . '/../components/layout_fin.php'; ?>
