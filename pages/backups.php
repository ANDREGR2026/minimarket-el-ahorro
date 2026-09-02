<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/BackupController.php';

Auth::checkRole(['Administrador']);

$controlador = new BackupController();

// ---------------------------------------------------------------- descarga
if (isset($_GET['descargar'])) {
    if (!$controlador->descargar($_GET['descargar'])) {
        flash('error', 'El backup solicitado no existe.');
        redirigir('backups');
    }
}

// ---------------------------------------------------------------- acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        flash('error', 'La sesión expiró. Vuelva a intentarlo.');
        redirigir('backups');
    }

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar_configuracion') {
        $resultado = $controlador->guardarConfiguracion($_POST);
    } elseif ($accion === 'generar') {
        $resultado = $controlador->generarManual();
    } else {
        $resultado = ['ok' => false, 'mensaje' => 'Acción no reconocida.'];
    }

    flash($resultado['ok'] ? 'exito' : 'error', $resultado['mensaje']);
    redirigir('backups');
}

$config  = $controlador->configuracionActual();
$backups = $controlador->listar();

$titulo = 'Backups';
$activo = 'backups';

require __DIR__ . '/../components/layout_inicio.php';
?>

<?php require __DIR__ . '/../components/flash.php'; ?>

<p class="mb-5 text-sm text-slate-500">
    Respaldo de la base de datos. Se genera un archivo .sql con toda la información del sistema,
    guardado en la carpeta <code class="font-mono text-xs">storage/backups/</code> del servidor.
</p>

<div class="mb-5 grid gap-5 lg:grid-cols-3">

    <!-- ===================== Configuración ===================== -->
    <div class="tarjeta lg:col-span-1">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-800">Configuración</h2>
            <p class="mt-0.5 text-xs text-slate-500">
                Cada vez que un administrador entra al sistema, se revisa si ya toca generar uno.
            </p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>backups" class="space-y-4 px-5 py-5">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="guardar_configuracion">

            <div>
                <label for="frecuencia" class="etiqueta">Frecuencia</label>
                <select id="frecuencia" name="frecuencia" class="campo">
                    <?php foreach (['Desactivado', 'Diario', 'Semanal', 'Mensual'] as $opcion): ?>
                        <option value="<?= $opcion ?>" <?= $config['frecuencia'] === $opcion ? 'selected' : '' ?>>
                            <?= $opcion ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="conservar" class="etiqueta">Backups a conservar</label>
                <input type="number" id="conservar" name="conservar" class="campo" min="1" max="365"
                    value="<?= (int) $config['conservar'] ?>">
                <p class="mt-1 text-xs text-slate-400">
                    Al superar este número, se borra automáticamente el más antiguo.
                </p>
            </div>

            <button type="submit" class="btn-primario w-full">Guardar configuración</button>
        </form>

        <div class="border-t border-slate-200 px-5 py-5">
            <form method="POST" action="<?= BASE_URL ?>backups">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="generar">
                <button type="submit" class="btn-secundario w-full">
                    Generar backup ahora
                </button>
            </form>
        </div>
    </div>

    <!-- ===================== Listado ===================== -->
    <div class="lg:col-span-2">
        <div class="tarjeta overflow-hidden">

            <!-- ---- Vista en tarjetas (mobile) ---- -->
            <div class="divide-y divide-slate-100 sm:hidden">
                <?php if (empty($backups)): ?>
                    <p class="px-4 py-10 text-center text-slate-400">Todavía no hay ningún backup generado.</p>
                <?php endif; ?>

                <?php foreach ($backups as $backup): ?>
                    <div class="p-4">
                        <p class="truncate font-mono text-xs font-medium text-slate-800"><?= e($backup['nombre']) ?></p>
                        <div class="mt-2 flex items-center justify-between text-sm">
                            <span class="text-slate-500"><?= fecha_hora($backup['fecha']) ?></span>
                            <span class="text-xs text-slate-500"><?= tamano_legible($backup['tamano']) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>backups?descargar=<?= urlencode($backup['nombre']) ?>"
                            class="btn-secundario btn-sm mt-3 inline-block">
                            Descargar
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ---- Vista en tabla (sm en adelante) ---- -->
            <div class="hidden overflow-x-auto sm:block">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Archivo</th>
                            <th>Fecha</th>
                            <th class="text-right">Tamaño</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-slate-400">
                                    Todavía no hay ningún backup generado.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td class="font-mono text-xs text-slate-700"><?= e($backup['nombre']) ?></td>
                                <td class="text-xs text-slate-500"><?= fecha_hora($backup['fecha']) ?></td>
                                <td class="text-right text-xs text-slate-500"><?= tamano_legible($backup['tamano']) ?></td>
                                <td class="text-right">
                                    <a href="<?= BASE_URL ?>backups?descargar=<?= urlencode($backup['nombre']) ?>"
                                        class="btn-secundario btn-sm">
                                        Descargar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                <?= count($backups) ?> backup(s)
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../components/layout_fin.php'; ?>
