<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../models/Configuracion.php';
require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../controllers/CategoriaController.php';

Auth::checkRole(['Administrador', 'Cajero', 'Almacenero']);

$esAdmin     = Auth::esAdministrador();
// El almacenero no edita el catálogo, pero sí necesita llegar al kardex de cada producto
$verKardex   = $esAdmin || Auth::esAlmacenero();
$controlador = new ProductoController();
$categorias  = (new CategoriaController())->activas();
$stockMinDef = (int) (new Configuracion())->obtener('stock_minimo_def', 5);

// ---------------------------------------------------------------- acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // El cajero solo consulta el catálogo
    if (!$esAdmin) {
        flash('error', 'Su rol no permite modificar el catálogo de productos.');
        redirigir('productos');
    }

    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        flash('error', 'La sesión expiró. Vuelva a intentarlo.');
        redirigir('productos');
    }

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $_POST['id_usuario'] = Auth::id();
        $resultado = $controlador->guardar($_POST, $_FILES['imagen'] ?? null);
    } elseif ($accion === 'estado') {
        $resultado = $controlador->cambiarEstado($_POST['id_producto'] ?? 0, $_POST['estado'] ?? 1);
    } else {
        $resultado = ['ok' => false, 'mensaje' => 'Acción no reconocida.'];
    }

    flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
    redirigir('productos');
}

// ---------------------------------------------------------------- consulta
$filtros = [
    'busqueda'     => trim($_GET['q'] ?? ''),
    'id_categoria' => (int) ($_GET['categoria'] ?? 0),
    'stock_bajo'   => !empty($_GET['stock_bajo']),
];

$productos = $controlador->listar($filtros);

$titulo = 'Productos';
$activo = 'productos';

require __DIR__ . '/../components/layout_inicio.php';
?>

<?php require __DIR__ . '/../components/flash.php'; ?>

<p class="mb-4 text-sm text-slate-500">Catálogo del minimarket: precios, stock y disponibilidad de cada producto.</p>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <form method="GET" action="<?= BASE_URL ?>productos" class="flex flex-wrap items-end gap-2">
        <div class="filtro-busqueda">
            <label for="q" class="etiqueta">Buscar</label>
            <input type="search" id="q" name="q" value="<?= e($filtros['busqueda']) ?>"
                class="campo" placeholder="Nombre o código de barras">
        </div>

        <div>
            <label for="categoria" class="etiqueta">Categoría</label>
            <select id="categoria" name="categoria" class="campo w-48">
                <option value="">Todas</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= (int) $categoria['id_categoria'] ?>"
                        <?= $filtros['id_categoria'] === (int) $categoria['id_categoria'] ? 'selected' : '' ?>>
                        <?= e($categoria['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <label class="flex items-center gap-2 pb-2 text-sm text-slate-600">
            <input type="checkbox" name="stock_bajo" value="1"
                class="h-4 w-4 rounded border-slate-300 text-marca-600"
                <?= $filtros['stock_bajo'] ? 'checked' : '' ?>>
            Solo stock bajo
        </label>

        <button type="submit" class="btn-secundario"><svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16l-6 7v6l-4 2v-8Z"/></svg><span>Filtrar</span></button>
        <a href="<?= BASE_URL ?>productos" class="btn-secundario"><svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11a9 9 0 1 1 3 8M3 4v7h7"/></svg><span>Limpiar</span></a>
    </form>

    <?php if ($esAdmin): ?>
        <button type="button" class="btn-primario" onclick="abrirModal()">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
            </svg>
            Nuevo producto
        </button>
    <?php endif; ?>
</div>

<div class="tarjeta overflow-hidden">

    <!-- ---- Vista en tarjetas (mobile) ---- -->
    <div class="divide-y divide-slate-100 sm:hidden">
        <?php if (empty($productos)): ?>
            <p class="px-4 py-10 text-center text-slate-400">No se encontraron productos con esos filtros.</p>
        <?php endif; ?>

        <?php foreach ($productos as $producto): ?>
            <?php $bajo = (int) $producto['stock'] <= (int) $producto['stock_minimo']; ?>
            <div class="p-4">
                <div class="flex items-start gap-3">
                    <?php if (!empty($producto['imagen'])): ?>
                        <img src="<?= BASE_URL ?>assets/img/productos/<?= e($producto['imagen']) ?>"
                            alt="" class="h-11 w-11 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
                    <?php else: ?>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2 3 7v10l9 5 9-5V7l-9-5z" />
                            </svg>
                        </span>
                    <?php endif; ?>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-slate-800"><?= e($producto['nombre']) ?></p>
                        <p class="text-xs text-slate-400"><?= e($producto['categoria']) ?> · <?= e($producto['codigo_barras']) ?></p>
                    </div>
                    <?php if ((int) $producto['estado'] === 1): ?>
                        <span class="badge-verde shrink-0">Activo</span>
                    <?php else: ?>
                        <span class="badge-gris shrink-0">Inactivo</span>
                    <?php endif; ?>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <p class="text-xs text-slate-400">P. compra</p>
                        <p class="font-medium text-slate-700"><?= money($producto['precio_compra']) ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">P. venta</p>
                        <p class="font-semibold text-slate-800"><?= money($producto['precio_venta']) ?></p>
                    </div>
                </div>

                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="text-slate-500">Stock</span>
                    <span>
                        <span class="<?= $bajo ? 'badge-rojo' : 'badge-verde' ?>">
                            <?= (int) $producto['stock'] ?> <?= e(strtolower($producto['unidad_medida'])) ?>
                        </span>
                        <span class="ml-1 text-[11px] text-slate-400">mín. <?= (int) $producto['stock_minimo'] ?></span>
                    </span>
                </div>

                <?php if ($verKardex): ?>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="<?= BASE_URL ?>inventario?id_producto=<?= (int) $producto['id_producto'] ?>"
                            class="btn-secundario btn-sm"><svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H5v18h14V8ZM14 3v5h5M8 12h8M8 16h6"/></svg><span>Kardex</span></a>

                        <?php if ($esAdmin): ?>
                            <button type="button" class="btn-secundario btn-sm"
                                onclick='editar(<?= json_encode($producto, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m4 16 12-12 4 4L8 20H4ZM14 6l4 4"/></svg><span>Editar</span></button>

                            <form method="POST" action="<?= BASE_URL ?>productos" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="estado">
                                <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">
                                <input type="hidden" name="estado" value="<?= (int) $producto['estado'] === 1 ? 0 : 1 ?>">
                                <button type="submit"
                                    class="<?= (int) $producto['estado'] === 1 ? 'btn-peligro' : 'btn-exito' ?> btn-sm">
                                    <?= (int) $producto['estado'] === 1 ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ---- Vista en tabla (sm en adelante) ---- -->
    <div class="hidden overflow-x-auto sm:block">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th class="text-right">P. Compra</th>
                    <th class="text-right">P. Venta</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Estado</th>
                    <?php if ($verKardex): ?><th class="text-right">Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productos)): ?>
                    <tr>
                        <td colspan="<?= $verKardex ? 7 : 6 ?>" class="px-4 py-10 text-center text-slate-400">
                            No se encontraron productos con esos filtros.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($productos as $producto): ?>
                    <?php $bajo = (int) $producto['stock'] <= (int) $producto['stock_minimo']; ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <?php if (!empty($producto['imagen'])): ?>
                                    <img src="<?= BASE_URL ?>assets/img/productos/<?= e($producto['imagen']) ?>"
                                        alt="" class="h-10 w-10 rounded-lg object-cover ring-1 ring-slate-200">
                                <?php else: ?>
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2 3 7v10l9 5 9-5V7l-9-5z" />
                                        </svg>
                                    </span>
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-800"><?= e($producto['nombre']) ?></p>
                                    <p class="text-xs text-slate-400"><?= e($producto['codigo_barras']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="text-slate-500"><?= e($producto['categoria']) ?></td>
                        <td class="text-right"><?= money($producto['precio_compra']) ?></td>
                        <td class="text-right font-semibold text-slate-800"><?= money($producto['precio_venta']) ?></td>
                        <td class="text-center">
                            <span class="<?= $bajo ? 'badge-rojo' : 'badge-verde' ?>">
                                <?= (int) $producto['stock'] ?> <?= e(strtolower($producto['unidad_medida'])) ?>
                            </span>
                            <p class="mt-0.5 text-[11px] text-slate-400">mín. <?= (int) $producto['stock_minimo'] ?></p>
                        </td>
                        <td class="text-center">
                            <?php if ((int) $producto['estado'] === 1): ?>
                                <span class="badge-verde">Activo</span>
                            <?php else: ?>
                                <span class="badge-gris">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($verKardex): ?>
                            <td class="text-right whitespace-nowrap">
                                <a href="<?= BASE_URL ?>inventario?id_producto=<?= (int) $producto['id_producto'] ?>"
                                    class="btn-secundario btn-sm"><svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H5v18h14V8ZM14 3v5h5M8 12h8M8 16h6"/></svg><span>Kardex</span></a>

                                <?php if ($esAdmin): ?>
                                    <button type="button" class="btn-secundario btn-sm"
                                        onclick='editar(<?= json_encode($producto, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m4 16 12-12 4 4L8 20H4ZM14 6l4 4"/></svg><span>Editar</span></button>

                                    <form method="POST" action="<?= BASE_URL ?>productos" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="accion" value="estado">
                                        <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">
                                        <input type="hidden" name="estado" value="<?= (int) $producto['estado'] === 1 ? 0 : 1 ?>">
                                        <button type="submit"
                                            class="<?= (int) $producto['estado'] === 1 ? 'btn-peligro' : 'btn-exito' ?> btn-sm">
                                            <?= (int) $producto['estado'] === 1 ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
        <?= count($productos) ?> producto(s) listado(s)
    </div>
</div>

<?php if ($esAdmin): ?>
    <!-- ===================== Modal de alta y edición ===================== -->
    <div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
        <div class="tarjeta my-8 w-full max-w-2xl">
            <form method="POST" action="<?= BASE_URL ?>productos" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id_producto" id="id_producto">

                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 id="modal-titulo" class="text-lg font-semibold text-slate-800">Nuevo producto</h2>
                </div>

                <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="nombre" class="etiqueta">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" id="nombre" name="nombre" class="campo" maxlength="150" required>
                    </div>

                    <div>
                        <label for="codigo_barras" class="etiqueta">Código de barras <span class="text-red-500">*</span></label>
                        <input type="text" id="codigo_barras" name="codigo_barras" class="campo" maxlength="50" required>
                    </div>

                    <div>
                        <label for="id_categoria" class="etiqueta">Categoría <span class="text-red-500">*</span></label>
                        <select id="id_categoria" name="id_categoria" class="campo" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= (int) $categoria['id_categoria'] ?>">
                                    <?= e($categoria['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="descripcion" class="etiqueta">Descripción</label>
                        <input type="text" id="descripcion" name="descripcion" class="campo" maxlength="255">
                    </div>

                    <div>
                        <label for="precio_compra" class="etiqueta">Precio de compra (S/)</label>
                        <input type="number" id="precio_compra" name="precio_compra" class="campo"
                            step="0.01" min="0" value="0.00">
                    </div>

                    <div>
                        <label for="precio_venta" class="etiqueta">
                            Precio de venta (S/) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="precio_venta" name="precio_venta" class="campo"
                            step="0.01" min="0.01" value="0.00" required>
                        <p class="mt-1 text-xs text-slate-400">El precio ya incluye el IGV.</p>
                    </div>

                    <div>
                        <label for="stock" class="etiqueta">Stock inicial</label>
                        <input type="number" id="stock" name="stock" class="campo" min="0" value="0">
                        <p id="ayuda-stock" class="mt-1 text-xs text-slate-400">
                            Al editar, el stock solo cambia desde Inventario.
                        </p>
                    </div>

                    <div>
                        <label for="stock_minimo" class="etiqueta">Stock mínimo</label>
                        <input type="number" id="stock_minimo" name="stock_minimo" class="campo" min="0" value="5">
                    </div>

                    <div>
                        <label for="unidad_medida" class="etiqueta">Unidad de venta</label>
                        <select id="unidad_medida" name="unidad_medida" class="campo" aria-describedby="ayuda-unidad-venta">
                            <?php foreach (['UNIDAD', 'BOLSA', 'BOTELLA', 'LATA', 'CAJA', 'PAQUETE', 'KILO', 'LITRO'] as $unidad): ?>
                                <option value="<?= $unidad ?>"><?= $unidad ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p id="ayuda-unidad-venta" class="mt-2 text-xs text-slate-500">Indica cómo se cuenta el stock y se cobra el precio: botella, bolsa, lata, paquete o unidad. Escribe el contenido en el nombre (por ejemplo, aceite 1 L o arroz 5 kg). Kilo y litro solo corresponden a venta a granel.</p>
                    </div>

                    <div>
                        <label for="imagen" class="etiqueta">Imagen (JPG, PNG o WEBP)</label>
                        <input type="file" id="imagen" name="imagen" accept="image/*"
                            class="campo file:mr-3 file:rounded file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-xs">
                    </div>

                    <div id="bloque-estado" class="hidden">
                        <label for="estado" class="etiqueta">Estado</label>
                        <select id="estado" name="estado" class="campo">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button type="button" class="btn-secundario" onclick="cerrarModal()"><svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M6 18 18 6"/></svg><span>Cancelar</span></button>
                    <button type="submit" class="btn-primario"><svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 3h13l3 3v15H4ZM8 3v6h8V3M8 21v-8h8v8"/></svg><span>Guardar</span></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modal');

        function abrirModal() {
            document.getElementById('modal-titulo').textContent = 'Nuevo producto';
            document.getElementById('id_producto').value = '';
            document.getElementById('nombre').value = '';
            document.getElementById('codigo_barras').value = '';
            document.getElementById('descripcion').value = '';
            document.getElementById('id_categoria').value = '';
            document.getElementById('precio_compra').value = '0.00';
            document.getElementById('precio_venta').value = '0.00';
            document.getElementById('stock').value = '0';
            document.getElementById('stock').readOnly = false;
            document.getElementById('stock_minimo').value = '<?= $stockMinDef ?>';
            document.getElementById('unidad_medida').value = 'UNIDAD';
            document.getElementById('imagen').value = '';
            document.getElementById('bloque-estado').classList.add('hidden');
            modal.classList.replace('hidden', 'flex');
            document.getElementById('nombre').focus();
        }

        function editar(producto) {
            document.getElementById('modal-titulo').textContent = 'Editar producto';
            document.getElementById('id_producto').value = producto.id_producto;
            document.getElementById('nombre').value = producto.nombre;
            document.getElementById('codigo_barras').value = producto.codigo_barras;
            document.getElementById('descripcion').value = producto.descripcion || '';
            document.getElementById('id_categoria').value = producto.id_categoria;
            document.getElementById('precio_compra').value = producto.precio_compra;
            document.getElementById('precio_venta').value = producto.precio_venta;
            document.getElementById('stock_minimo').value = producto.stock_minimo;
            document.getElementById('unidad_medida').value = producto.unidad_medida;
            document.getElementById('estado').value = producto.estado;
            document.getElementById('imagen').value = '';

            // El stock no se edita aquí: solo cambia por ventas o por inventario
            const stock = document.getElementById('stock');
            stock.value = producto.stock;
            stock.readOnly = true;

            document.getElementById('bloque-estado').classList.remove('hidden');
            modal.classList.replace('hidden', 'flex');
        }

        function cerrarModal() {
            modal.classList.replace('flex', 'hidden');
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) cerrarModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') cerrarModal();
        });
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../components/layout_fin.php'; ?>
