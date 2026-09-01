<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/ClienteController.php';

Auth::checkRole(['Administrador', 'Cajero']);

$controlador = new ClienteController();

// ---------------------------------------------------------------- acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        flash('error', 'La sesión expiró. Vuelva a intentarlo.');
        redirigir('clientes');
    }

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $resultado = $controlador->guardar($_POST);
    } elseif ($accion === 'estado' && Auth::esAdministrador()) {
        $resultado = $controlador->cambiarEstado($_POST['id_cliente'] ?? 0, $_POST['estado'] ?? 1);
    } else {
        $resultado = ['ok' => false, 'mensaje' => 'Acción no permitida para su rol.'];
    }

    flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
    redirigir('clientes');
}

// ---------------------------------------------------------------- consulta
$filtros = [
    'busqueda'       => trim($_GET['q'] ?? ''),
    'tipo_documento' => $_GET['tipo'] ?? '',
];

$clientes = $controlador->listar($filtros);

$titulo = 'Clientes';
$activo = 'clientes';

require __DIR__ . '/../components/layout_inicio.php';
?>

<?php require __DIR__ . '/../components/flash.php'; ?>

<p class="mb-4 text-sm text-slate-500">Base de clientes para emitir boletas y facturas.</p>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <form method="GET" action="<?= BASE_URL ?>clientes" class="flex flex-wrap items-end gap-2">
        <div>
            <label for="q" class="etiqueta">Buscar</label>
            <input type="search" id="q" name="q" value="<?= e($filtros['busqueda']) ?>"
                class="campo w-60" placeholder="Documento, nombre o razón social">
        </div>

        <div>
            <label for="tipo" class="etiqueta">Documento</label>
            <select id="tipo" name="tipo" class="campo w-32">
                <option value="">Todos</option>
                <option value="DNI" <?= $filtros['tipo_documento'] === 'DNI' ? 'selected' : '' ?>>DNI</option>
                <option value="RUC" <?= $filtros['tipo_documento'] === 'RUC' ? 'selected' : '' ?>>RUC</option>
            </select>
        </div>

        <button type="submit" class="btn-secundario">Filtrar</button>
        <a href="<?= BASE_URL ?>clientes" class="btn-secundario">Limpiar</a>
    </form>

    <button type="button" class="btn-primario" onclick="abrirModal()">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
        </svg>
        Nuevo cliente
    </button>
</div>

<div class="tarjeta overflow-hidden">
    <div class="overflow-x-auto">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Documento</th>
                    <th>Contacto</th>
                    <th class="text-center">Compras</th>
                    <th class="text-right">Monto comprado</th>
                    <th class="text-center">Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientes)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-400">
                            No se encontraron clientes con esos filtros.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($clientes as $cliente): ?>
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800"><?= e($cliente['nombre_completo']) ?></p>
                            <?php if (!empty($cliente['direccion'])): ?>
                                <p class="text-xs text-slate-400"><?= e($cliente['direccion']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= $cliente['tipo_documento'] === 'RUC' ? 'badge-azul' : 'badge-gris' ?>">
                                <?= e($cliente['tipo_documento']) ?>
                            </span>
                            <span class="ml-1 font-mono text-xs"><?= e($cliente['numero_documento']) ?></span>
                        </td>
                        <td class="text-slate-500">
                            <?= e($cliente['telefono'] ?: '—') ?>
                            <?php if (!empty($cliente['email'])): ?>
                                <p class="text-xs text-slate-400"><?= e($cliente['email']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= (int) $cliente['total_compras'] ?></td>
                        <td class="text-right font-medium"><?= money($cliente['monto_comprado']) ?></td>
                        <td class="text-center">
                            <?php if ((int) $cliente['estado'] === 1): ?>
                                <span class="badge-verde">Activo</span>
                            <?php else: ?>
                                <span class="badge-gris">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <button type="button" class="btn-secundario btn-sm"
                                onclick='editar(<?= json_encode($cliente, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                Editar
                            </button>

                            <?php if (Auth::esAdministrador()): ?>
                                <form method="POST" action="<?= BASE_URL ?>clientes" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="accion" value="estado">
                                    <input type="hidden" name="id_cliente" value="<?= (int) $cliente['id_cliente'] ?>">
                                    <input type="hidden" name="estado" value="<?= (int) $cliente['estado'] === 1 ? 0 : 1 ?>">
                                    <button type="submit"
                                        class="<?= (int) $cliente['estado'] === 1 ? 'btn-peligro' : 'btn-exito' ?> btn-sm">
                                        <?= (int) $cliente['estado'] === 1 ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
        <?= count($clientes) ?> cliente(s) listado(s)
    </div>
</div>

<!-- ===================== Modal de alta y edición ===================== -->
<div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
    <div class="tarjeta my-8 w-full max-w-xl">
        <form method="POST" action="<?= BASE_URL ?>clientes">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id_cliente" id="id_cliente">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 id="modal-titulo" class="text-lg font-semibold text-slate-800">Nuevo cliente</h2>
            </div>

            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                <div>
                    <label for="tipo_documento" class="etiqueta">Tipo de documento</label>
                    <select id="tipo_documento" name="tipo_documento" class="campo" onchange="cambiarTipo()">
                        <option value="DNI">DNI (persona natural)</option>
                        <option value="RUC">RUC (empresa)</option>
                    </select>
                </div>

                <div>
                    <label for="numero_documento" class="etiqueta">
                        Número <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="numero_documento" name="numero_documento" class="campo"
                        inputmode="numeric" pattern="[0-9]*" maxlength="11" required>
                    <p id="ayuda-documento" class="mt-1 text-xs text-slate-400">El DNI tiene 8 dígitos.</p>
                </div>

                <!-- Campos de persona natural -->
                <div id="campo-nombres">
                    <label for="nombres" class="etiqueta">Nombres <span class="text-red-500">*</span></label>
                    <input type="text" id="nombres" name="nombres" class="campo" maxlength="100">
                </div>

                <div id="campo-apellidos">
                    <label for="apellidos" class="etiqueta">Apellidos <span class="text-red-500">*</span></label>
                    <input type="text" id="apellidos" name="apellidos" class="campo" maxlength="100">
                </div>

                <!-- Campo de empresa -->
                <div id="campo-razon" class="hidden sm:col-span-2">
                    <label for="razon_social" class="etiqueta">Razón social <span class="text-red-500">*</span></label>
                    <input type="text" id="razon_social" name="razon_social" class="campo" maxlength="150">
                </div>

                <div>
                    <label for="telefono" class="etiqueta">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" class="campo"
                        inputmode="numeric" pattern="[0-9 ]*" maxlength="20">
                </div>

                <div>
                    <label for="email" class="etiqueta">Correo electrónico</label>
                    <input type="email" id="email" name="email" class="campo" maxlength="100">
                </div>

                <div class="sm:col-span-2">
                    <label for="direccion" class="etiqueta">Dirección</label>
                    <input type="text" id="direccion" name="direccion" class="campo" maxlength="200">
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
                <button type="button" class="btn-secundario" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn-primario">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('modal');

    // Muestra los campos que corresponden al tipo de documento elegido
    function cambiarTipo() {
        const esRuc = document.getElementById('tipo_documento').value === 'RUC';

        document.getElementById('campo-nombres').classList.toggle('hidden', esRuc);
        document.getElementById('campo-apellidos').classList.toggle('hidden', esRuc);
        document.getElementById('campo-razon').classList.toggle('hidden', !esRuc);

        document.getElementById('numero_documento').maxLength = esRuc ? 11 : 8;
        document.getElementById('ayuda-documento').textContent =
            esRuc ? 'El RUC tiene 11 dígitos.' : 'El DNI tiene 8 dígitos.';
    }

    function abrirModal() {
        document.getElementById('modal-titulo').textContent = 'Nuevo cliente';
        ['id_cliente', 'numero_documento', 'nombres', 'apellidos', 'razon_social',
            'telefono', 'email', 'direccion'
        ].forEach(id => document.getElementById(id).value = '');
        document.getElementById('tipo_documento').value = 'DNI';
        document.getElementById('bloque-estado').classList.add('hidden');
        cambiarTipo();
        modal.classList.replace('hidden', 'flex');
        document.getElementById('numero_documento').focus();
    }

    function editar(cliente) {
        document.getElementById('modal-titulo').textContent = 'Editar cliente';
        document.getElementById('id_cliente').value = cliente.id_cliente;
        document.getElementById('tipo_documento').value = cliente.tipo_documento;
        document.getElementById('numero_documento').value = cliente.numero_documento;
        document.getElementById('nombres').value = cliente.nombres || '';
        document.getElementById('apellidos').value = cliente.apellidos || '';
        document.getElementById('razon_social').value = cliente.razon_social || '';
        document.getElementById('telefono').value = cliente.telefono || '';
        document.getElementById('email').value = cliente.email || '';
        document.getElementById('direccion').value = cliente.direccion || '';
        document.getElementById('estado').value = cliente.estado;
        document.getElementById('bloque-estado').classList.remove('hidden');
        cambiarTipo();
        modal.classList.replace('hidden', 'flex');
    }

    function cerrarModal() {
        modal.classList.replace('flex', 'hidden');
    }

    // Solo se aceptan dígitos en el número de documento
    document.getElementById('numero_documento').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });

    // El teléfono solo acepta dígitos y espacios (letras se descartan al escribir)
    document.getElementById('telefono').addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9 ]/g, '');
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) cerrarModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') cerrarModal();
    });
</script>

<?php require __DIR__ . '/../components/layout_fin.php'; ?>
