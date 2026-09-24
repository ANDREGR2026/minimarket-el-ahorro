<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/ConfiguracionController.php';
require_once __DIR__ . '/../lib/Identidad.php';
Auth::checkRole(['Administrador']);
$modelo = new Configuracion();
$defaults = [
    'nombre_comercial'  => 'EL AHORRO',
    'razon_social'      => 'MINIMARKET EL AHORRO S.A.C.',
    'ruc'               => '20558877991',
    'telefono'          => '987654321',
    'direccion'         => '',
    'email_contacto'    => 'contacto@elahorro.com',
    'igv'               => '18',
    'moneda'            => 'S/',
    'color_principal'   => '#257aeb',
    'color_oscuro'      => '#0f172b',
    'ventas_limite'     => '200',
    'dias_anulacion'    => '0',
    'stock_minimo_def'  => '5',
];
$valores = array_merge($defaults, $modelo->todos());
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validar($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $error = 'La sesión expiró. Recargue la página e inténtelo nuevamente.';
    } else {
        $resultado = (new ConfiguracionController())->guardar($_POST, $_FILES['logo'] ?? []);
        if ($resultado['ok']) {
            flash('exito', $resultado['mensaje']);
            redirigir('configuracion');
        }
        $error = $resultado['mensaje'];
        foreach ($valores as $clave => $valor) if (isset($_POST[$clave]) && is_string($_POST[$clave])) $valores[$clave] = $_POST[$clave];
    }
}
$titulo = 'Configuración';
$activo = 'configuracion';
require __DIR__ . '/../components/layout_inicio.php';
require __DIR__ . '/../components/flash.php';
?>
<div class="w-full">
    <h2 class="text-xl font-bold">Personaliza tu minimarket</h2>
    <p class="mt-2 mb-6 text-sm text-slate-500">Actualiza los datos de contacto, la identidad visual y los parámetros operativos de tu negocio.</p>
    <?php if ($error): ?><p role="alert" class="mb-5 rounded-lg bg-red-50 p-4 text-red-700"><?= e($error) ?></p><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <!-- ── Datos del minimarket ── -->
        <section class="tarjeta p-6">
            <h3 class="mb-5 font-semibold">Datos del minimarket</h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <?php foreach ([
                    'nombre_comercial' => ['Nombre comercial', 'text', 80],
                    'razon_social'     => ['Razón social', 'text', 150],
                    'ruc'              => ['RUC', 'text', 11],
                    'telefono'         => ['Teléfono', 'tel', 30],
                    'direccion'        => ['Dirección', 'text', 250],
                    'email_contacto'   => ['Correo de contacto', 'email', 150],
                ] as $clave => [$etiqueta, $tipo, $maximo]): ?>
                <div>
                    <label class="etiqueta" for="<?= $clave ?>"><?= $etiqueta ?></label>
                    <input class="campo" id="<?= $clave ?>" name="<?= $clave ?>" type="<?= $tipo ?>" maxlength="<?= $maximo ?>" value="<?= e($valores[$clave]) ?>" required>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ── Parámetros fiscales ── -->
        <section class="tarjeta p-6">
            <h3 class="mb-1 font-semibold">Parámetros fiscales</h3>
            <p class="mb-5 text-sm text-slate-500">Estos valores afectan el cálculo de todos los comprobantes. Cámbielos solo si hay una modificación oficial.</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="etiqueta" for="igv">IGV (%)</label>
                    <input class="campo" id="igv" name="igv" type="number" min="0" max="99" step="1" value="<?= e($valores['igv']) ?>" required>
                    <p class="mt-1 text-xs text-slate-500">Porcentaje de IGV vigente. Actualmente <?= e($valores['igv']) ?>%.</p>
                </div>
                <div>
                    <label class="etiqueta" for="moneda">Símbolo de moneda</label>
                    <input class="campo" id="moneda" name="moneda" type="text" maxlength="5" value="<?= e($valores['moneda']) ?>" required>
                    <p class="mt-1 text-xs text-slate-500">Ej: S/ (soles), $ (dólares), € (euros).</p>
                </div>
            </div>
        </section>

        <!-- ── Parámetros operativos ── -->
        <section class="tarjeta p-6">
            <h3 class="mb-1 font-semibold">Parámetros operativos</h3>
            <p class="mb-5 text-sm text-slate-500">Controlan el comportamiento del sistema sin afectar los registros existentes.</p>
            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label class="etiqueta" for="ventas_limite">Límite de ventas en historial</label>
                    <input class="campo" id="ventas_limite" name="ventas_limite" type="number" min="50" max="2000" step="50" value="<?= e($valores['ventas_limite']) ?>" required>
                    <p class="mt-1 text-xs text-slate-500">Máximo de ventas que muestra el historial por consulta. Entre 50 y 2 000.</p>
                </div>
                <div>
                    <label class="etiqueta" for="dias_anulacion">Días máximos para anular</label>
                    <input class="campo" id="dias_anulacion" name="dias_anulacion" type="number" min="0" max="365" step="1" value="<?= e($valores['dias_anulacion']) ?>" required>
                    <p class="mt-1 text-xs text-slate-500">Días desde la emisión en que se permite anular. <strong>0 = sin límite</strong>.</p>
                </div>
                <div>
                    <label class="etiqueta" for="stock_minimo_def">Stock mínimo por defecto</label>
                    <input class="campo" id="stock_minimo_def" name="stock_minimo_def" type="number" min="1" max="999" step="1" value="<?= e($valores['stock_minimo_def']) ?>" required>
                    <p class="mt-1 text-xs text-slate-500">Valor inicial del stock mínimo al crear un producto nuevo.</p>
                </div>
            </div>
        </section>

        <!-- ── Logo del negocio ── -->
        <section class="tarjeta p-6">
            <h3 class="mb-5 font-semibold">Logo del negocio</h3>
            <div class="flex flex-wrap items-center gap-6">
                <img id="logo-preview" src="<?= e(asset_url(Identidad::logo())) ?>" alt="Logo actual del minimarket"
                     class="h-24 w-24 rounded-xl border border-slate-200 bg-white object-contain p-2">
                <div class="flex-1 space-y-3">
                    <!-- Input de archivo estilizado -->
                    <div>
                        <label class="etiqueta" for="logo">Seleccionar imagen</label>
                        <label for="logo"
                               class="mt-1 flex w-full cursor-pointer items-center gap-3 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-600 hover:border-marca-400 hover:bg-slate-50 transition">
                            <svg class="h-5 w-5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <span id="logo-nombre">Ningún archivo seleccionado</span>
                        </label>
                        <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/webp" class="sr-only">
                        <p class="mt-2 text-xs text-slate-500">PNG, JPG o WebP. Máximo 2 MB y 4096 × 4096 píxeles.</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="quitar_logo" value="1"> Usar el logo predeterminado
                    </label>
                </div>
            </div>
        </section>

        <!-- ── Colores de la marca ── -->
        <section class="tarjeta p-6">
            <h3 class="font-semibold">Colores de la marca</h3>
            <p class="mt-2 mb-5 text-sm text-slate-500">El color principal se usa en botones y navegación. El color oscuro se usa en fondos y textos oscuros. Elige tonos oscuros para conservar la legibilidad del texto blanco.</p>
            <div class="grid gap-5 sm:grid-cols-2">
            <?php foreach (['color_principal' => 'Color principal', 'color_oscuro' => 'Color oscuro'] as $clave => $etiqueta): ?>
                <div>
                    <label class="etiqueta" for="<?= $clave ?>"><?= $etiqueta ?></label>
                    <div class="flex items-center gap-3">
                        <input type="color" id="<?= $clave ?>" name="<?= $clave ?>"
                               value="<?= e(preg_match('/^#[a-f0-9]{6}$/iD', $valores[$clave]) ? $valores[$clave] : '#257aeb') ?>"
                               class="h-11 w-20 cursor-pointer">
                        <output for="<?= $clave ?>" class="text-sm font-mono"><?= e($valores[$clave]) ?></output>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <button id="colores-originales" type="button" class="btn-secundario mt-5">Restablecer colores actuales de EL AHORRO</button>
        </section>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm text-slate-500">Los cambios se aplican al guardar.</p>
            <button class="btn-primario" type="submit">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 3h13l3 3v15H4ZM8 3v6h8V3M8 21v-8h8v8"/></svg>
                <span>Guardar configuración</span>
            </button>
        </div>
    </form>
</div>
<script src="<?= asset_url('assets/js/configuracion.js') ?>" defer></script>
<?php require __DIR__ . '/../components/layout_fin.php'; ?>
