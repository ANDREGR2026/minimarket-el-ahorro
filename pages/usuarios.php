<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/UsuarioController.php';

Auth::checkRole(['Administrador']);

$controlador = new UsuarioController();

// ---------------------------------------------------------------- acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        flash('error', 'La sesión expiró. Vuelva a intentarlo.');
        redirigir('usuarios');
    }

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $resultado = $controlador->guardar($_POST, Auth::rol());
    } elseif ($accion === 'estado') {
        $resultado = $controlador->cambiarEstado(
            $_POST['id_usuario'] ?? 0,
            $_POST['estado'] ?? 1,
            Auth::id()
        );
    } else {
        $resultado = ['ok' => false, 'mensaje' => 'Acción no reconocida.'];
    }

    flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
    redirigir('usuarios');
}

// ---------------------------------------------------------------- consulta
$busqueda = trim($_GET['q'] ?? '');
$rol      = $_GET['rol'] ?? '';
$usuarios = $controlador->listar($busqueda, $rol);

$titulo = 'Usuarios';
$activo = 'usuarios';

require __DIR__ . '/../components/layout_inicio.php';
?>

<?php require __DIR__ . '/../components/flash.php'; ?>

<p class="mb-4 text-sm text-slate-500">Cuentas de acceso al sistema y sus roles.</p>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <form method="GET" action="<?= BASE_URL ?>usuarios" class="flex flex-wrap items-end gap-2">
        <div>
            <label for="q" class="etiqueta">Buscar</label>
            <input type="search" id="q" name="q" value="<?= e($busqueda) ?>"
                class="campo w-56" placeholder="Nombre o usuario">
        </div>

        <div>
            <label for="rol" class="etiqueta">Rol</label>
            <select id="rol" name="rol" class="campo w-40">
                <option value="">Todos</option>
                <?php if (Auth::esSuperAdministrador()): ?>
                    <option value="SuperAdministrador" <?= $rol === 'SuperAdministrador' ? 'selected' : '' ?>>SuperAdministrador</option>
                <?php endif; ?>
                <option value="Administrador" <?= $rol === 'Administrador' ? 'selected' : '' ?>>Administrador</option>
                <option value="Cajero" <?= $rol === 'Cajero' ? 'selected' : '' ?>>Cajero</option>
            </select>
        </div>

        <button type="submit" class="btn-secundario">Filtrar</button>
        <a href="<?= BASE_URL ?>usuarios" class="btn-secundario">Limpiar</a>
    </form>

    <button type="button" class="btn-primario" onclick="abrirModal()">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
        </svg>
        Nuevo usuario
    </button>
</div>

<div class="tarjeta overflow-hidden">
    <div class="overflow-x-auto">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th class="text-center">Rol</th>
                    <th>Registrado</th>
                    <th class="text-center">Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td class="font-medium text-slate-800">
                            <?= e($usuario['nombre']) ?>
                            <?php if ((int) $usuario['id_usuario'] === Auth::id()): ?>
                                <span class="badge-azul ml-1">Usted</span>
                            <?php endif; ?>
                        </td>
                        <td class="font-mono text-xs text-slate-500"><?= e($usuario['usuario']) ?></td>
                        <td class="text-center">
                            <?php
                            $claseRol = match ($usuario['rol']) {
                                'SuperAdministrador' => 'badge-morado',
                                'Administrador'       => 'badge-azul',
                                default               => 'badge-gris',
                            };
                            ?>
                            <span class="<?= $claseRol ?>">
                                <?= e($usuario['rol']) ?>
                            </span>
                        </td>
                        <td class="text-xs text-slate-500"><?= fecha_corta($usuario['created_at']) ?></td>
                        <td class="text-center">
                            <?php if ((int) $usuario['estado'] === 1): ?>
                                <span class="badge-verde">Activo</span>
                            <?php else: ?>
                                <span class="badge-gris">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <?php if ($usuario['rol'] === 'SuperAdministrador'): ?>
                                <span class="text-xs text-slate-400">Cuenta fija</span>
                            <?php elseif ($usuario['rol'] === 'Administrador' && !Auth::esSuperAdministrador() && (int) $usuario['id_usuario'] !== Auth::id()): ?>
                                <span class="text-xs text-slate-400">Solo el SuperAdministrador puede editarlo</span>
                            <?php else: ?>
                                <button type="button" class="btn-secundario btn-sm"
                                    onclick='editar(<?= json_encode($usuario, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    Editar
                                </button>

                                <?php if ((int) $usuario['id_usuario'] !== Auth::id()): ?>
                                    <form method="POST" action="<?= BASE_URL ?>usuarios" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="accion" value="estado">
                                        <input type="hidden" name="id_usuario" value="<?= (int) $usuario['id_usuario'] ?>">
                                        <input type="hidden" name="estado" value="<?= (int) $usuario['estado'] === 1 ? 0 : 1 ?>">
                                        <button type="submit"
                                            class="<?= (int) $usuario['estado'] === 1 ? 'btn-peligro' : 'btn-exito' ?> btn-sm">
                                            <?= (int) $usuario['estado'] === 1 ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
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
        <form method="POST" action="<?= BASE_URL ?>usuarios" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id_usuario" id="id_usuario">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 id="modal-titulo" class="text-lg font-semibold text-slate-800">Nuevo usuario</h2>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="nombre" class="etiqueta">Nombre completo <span class="text-red-500">*</span></label>
                    <input type="text" id="nombre" name="nombre" class="campo" maxlength="100" required>
                </div>

                <div>
                    <label for="usuario" class="etiqueta">Usuario <span class="text-red-500">*</span></label>
                    <input type="text" id="usuario" name="usuario" class="campo" maxlength="50" required>
                    <p class="mt-1 text-xs text-slate-400">
                        Entre 4 y 50 caracteres: letras, números, punto o guión bajo.
                    </p>
                </div>

                <div>
                    <label for="password" class="etiqueta">
                        Contraseña <span id="password-obligatorio" class="text-red-500">*</span>
                    </label>
                    <input type="password" id="password" name="password" class="campo" minlength="6">
                    <p id="ayuda-password" class="mt-1 text-xs text-slate-400">
                        Mínimo 6 caracteres.
                    </p>
                </div>

                <div>
                    <label for="rol-form" class="etiqueta">Rol <span class="text-red-500">*</span></label>
                    <select id="rol-form" name="rol" class="campo" required>
                        <option value="Cajero">Cajero — solo punto de venta y consultas</option>
                        <?php if (Auth::esSuperAdministrador()): ?>
                            <option value="Administrador">Administrador — acceso total</option>
                        <?php endif; ?>
                    </select>
                    <?php if (!Auth::esSuperAdministrador()): ?>
                        <p class="mt-1 text-xs text-slate-400">
                            Solo el SuperAdministrador puede crear cuentas Administrador.
                        </p>
                    <?php endif; ?>
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

    function abrirModal() {
        document.getElementById('modal-titulo').textContent = 'Nuevo usuario';
        document.getElementById('id_usuario').value = '';
        document.getElementById('nombre').value = '';
        document.getElementById('usuario').value = '';
        document.getElementById('password').value = '';
        document.getElementById('password').required = true;
        document.getElementById('password-obligatorio').classList.remove('hidden');
        document.getElementById('ayuda-password').textContent = 'Mínimo 6 caracteres.';
        document.getElementById('rol-form').value = 'Cajero';
        document.getElementById('bloque-estado').classList.add('hidden');
        modal.classList.replace('hidden', 'flex');
        document.getElementById('nombre').focus();
    }

    function editar(usuario) {
        document.getElementById('modal-titulo').textContent = 'Editar usuario';
        document.getElementById('id_usuario').value = usuario.id_usuario;
        document.getElementById('nombre').value = usuario.nombre;
        document.getElementById('usuario').value = usuario.usuario;
        document.getElementById('password').value = '';
        document.getElementById('password').required = false;
        document.getElementById('password-obligatorio').classList.add('hidden');
        document.getElementById('ayuda-password').textContent =
            'Deje este campo vacío para mantener la contraseña actual.';
        document.getElementById('rol-form').value = usuario.rol;
        document.getElementById('estado').value = usuario.estado;
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
