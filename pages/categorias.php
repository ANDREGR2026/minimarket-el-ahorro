<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/CategoriaController.php';

Auth::checkRole(['Administrador']);

$controlador = new CategoriaController();

// ---------------------------------------------------------------- acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        flash('error', 'La sesión expiró. Vuelva a intentarlo.');
        redirigir('categorias');
    }

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $resultado = $controlador->guardar($_POST);
    } elseif ($accion === 'estado') {
        $resultado = $controlador->cambiarEstado($_POST['id_categoria'] ?? 0, $_POST['estado'] ?? 1);
    } else {
        $resultado = ['ok' => false, 'mensaje' => 'Acción no reconocida.'];
    }

    flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
    redirigir('categorias');
}

$busqueda   = trim($_GET['q'] ?? '');
$categorias = $controlador->listar($busqueda);

$titulo = 'Categorías';
$activo = 'categorias';

require __DIR__ . '/../components/layout_inicio.php';
?>

<?php require __DIR__ . '/../components/flash.php'; ?>

<p class="mb-4 text-sm text-slate-500">Organización del catálogo. Cada producto pertenece a una categoría.</p>

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" action="<?= BASE_URL ?>categorias" class="flex flex-wrap gap-2">
        <input type="search" name="q" value="<?= e($busqueda) ?>"
            class="campo w-full sm:w-64" placeholder="Buscar categoría...">
        <button type="submit" class="btn-secundario">Buscar</button>
        <?php if ($busqueda !== ''): ?>
            <a href="<?= BASE_URL ?>categorias" class="btn-secundario">Limpiar</a>
        <?php endif; ?>
    </form>

    <button type="button" class="btn-primario" onclick="abrirModal()">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
        </svg>
        Nueva categoría
    </button>
</div>

<div class="tarjeta overflow-hidden">

    <!-- ---- Vista en tarjetas (mobile) ---- -->
    <div class="divide-y divide-slate-100 sm:hidden">
        <?php if (empty($categorias)): ?>
            <p class="px-4 py-10 text-center text-slate-400">No hay categorías que coincidan con la búsqueda.</p>
        <?php endif; ?>

        <?php foreach ($categorias as $categoria): ?>
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-800"><?= e($categoria['nombre']) ?></p>
                        <p class="text-xs text-slate-500"><?= e($categoria['descripcion'] ?: 'Sin descripción') ?></p>
                    </div>
                    <?php if ((int) $categoria['estado'] === 1): ?>
                        <span class="badge-verde shrink-0">Activa</span>
                    <?php else: ?>
                        <span class="badge-gris shrink-0">Inactiva</span>
                    <?php endif; ?>
                </div>

                <div class="mt-2 text-sm">
                    <span class="text-slate-500">Productos:</span>
                    <?php if ((int) $categoria['total_productos'] > 0): ?>
                        <a href="<?= BASE_URL ?>productos?categoria=<?= (int) $categoria['id_categoria'] ?>"
                            class="font-medium text-marca-600 hover:text-marca-700">
                            <?= (int) $categoria['total_productos'] ?>
                        </a>
                    <?php else: ?>
                        <span class="text-slate-400">0</span>
                    <?php endif; ?>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" class="btn-secundario btn-sm"
                        onclick='editar(<?= json_encode($categoria, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                        Editar
                    </button>

                    <form method="POST" action="<?= BASE_URL ?>categorias" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="estado">
                        <input type="hidden" name="id_categoria" value="<?= (int) $categoria['id_categoria'] ?>">
                        <input type="hidden" name="estado" value="<?= (int) $categoria['estado'] === 1 ? 0 : 1 ?>">
                        <button type="submit"
                            class="<?= (int) $categoria['estado'] === 1 ? 'btn-peligro' : 'btn-exito' ?> btn-sm">
                            <?= (int) $categoria['estado'] === 1 ? 'Desactivar' : 'Activar' ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ---- Vista en tabla (sm en adelante) ---- -->
    <div class="hidden overflow-x-auto sm:block">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Descripción</th>
                    <th class="text-center">Productos</th>
                    <th class="text-center">Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categorias)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-slate-400">
                            No hay categorías que coincidan con la búsqueda.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($categorias as $categoria): ?>
                    <tr>
                        <td class="font-medium text-slate-800"><?= e($categoria['nombre']) ?></td>
                        <td class="text-slate-500"><?= e($categoria['descripcion'] ?: '—') ?></td>
                        <td class="text-center">
                            <?php if ((int) $categoria['total_productos'] > 0): ?>
                                <a href="<?= BASE_URL ?>productos?categoria=<?= (int) $categoria['id_categoria'] ?>"
                                    class="font-medium text-marca-600 hover:text-marca-700">
                                    <?= (int) $categoria['total_productos'] ?>
                                </a>
                            <?php else: ?>
                                <span class="text-slate-400">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ((int) $categoria['estado'] === 1): ?>
                                <span class="badge-verde">Activa</span>
                            <?php else: ?>
                                <span class="badge-gris">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <button type="button" class="btn-secundario btn-sm"
                                onclick='editar(<?= json_encode($categoria, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                Editar
                            </button>

                            <form method="POST" action="<?= BASE_URL ?>categorias" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="estado">
                                <input type="hidden" name="id_categoria" value="<?= (int) $categoria['id_categoria'] ?>">
                                <input type="hidden" name="estado" value="<?= (int) $categoria['estado'] === 1 ? 0 : 1 ?>">
                                <button type="submit"
                                    class="<?= (int) $categoria['estado'] === 1 ? 'btn-peligro' : 'btn-exito' ?> btn-sm">
                                    <?= (int) $categoria['estado'] === 1 ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===================== Modal de alta y edición ===================== -->
<div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
    <div class="tarjeta w-full max-w-lg">
        <form method="POST" action="<?= BASE_URL ?>categorias">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id_categoria" id="id_categoria">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 id="modal-titulo" class="text-lg font-semibold text-slate-800">Nueva categoría</h2>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="nombre" class="etiqueta">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" id="nombre" name="nombre" class="campo" maxlength="80" required>
                </div>

                <div>
                    <label for="descripcion" class="etiqueta">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="campo" rows="3" maxlength="255"></textarea>
                </div>

                <div id="bloque-estado" class="hidden">
                    <label for="estado" class="etiqueta">Estado</label>
                    <select id="estado" name="estado" class="campo">
                        <option value="1">Activa</option>
                        <option value="0">Inactiva</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4">
                <button type="button" class="btn-secundario" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn-primario">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('modal');

    function abrirModal() {
        document.getElementById('modal-titulo').textContent = 'Nueva categoría';
        document.getElementById('id_categoria').value = '';
        document.getElementById('nombre').value = '';
        document.getElementById('descripcion').value = '';
        document.getElementById('bloque-estado').classList.add('hidden');
        modal.classList.replace('hidden', 'flex');
        document.getElementById('nombre').focus();
    }

    function editar(categoria) {
        document.getElementById('modal-titulo').textContent = 'Editar categoría';
        document.getElementById('id_categoria').value = categoria.id_categoria;
        document.getElementById('nombre').value = categoria.nombre;
        document.getElementById('descripcion').value = categoria.descripcion || '';
        document.getElementById('estado').value = categoria.estado;
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

<?php require __DIR__ . '/../components/layout_fin.php'; ?>
