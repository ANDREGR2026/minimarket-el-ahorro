<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/VentaController.php';

Auth::checkRole(['Administrador', 'Cajero']);

$configuracion = (new VentaController())->configuracion();
$igv           = (float) ($configuracion['igv'] ?? 18);

$titulo  = 'Punto de Venta';
$activo  = 'pos';
$scripts = ['assets/js/pos.js'];

require __DIR__ . '/../components/layout_inicio.php';
?>

<!-- Datos que necesita pos.js -->
<script>
    window.POS = {
        baseUrl: <?= json_encode(BASE_URL) ?>,
        csrf: <?= json_encode(csrf_token()) ?>,
        igv: <?= json_encode($igv) ?>
    };
</script>

<div class="mb-5">
    <h2 class="text-base font-semibold text-slate-800">Nueva venta</h2>
    <p class="mt-0.5 text-sm text-slate-500">Busque productos, arme el carrito y cobre.</p>
</div>

<div class="grid gap-5 lg:grid-cols-5">

    <!-- ============================================================
         Columna izquierda: búsqueda y carrito
         ============================================================ -->
    <div class="lg:col-span-3">

        <div class="tarjeta mb-4 p-4">
            <label for="buscador" class="etiqueta">
                Buscar producto o escanear código de barras
            </label>
            <div class="flex gap-2">
                <div class="relative min-w-0 flex-1">
                    <input type="text" id="buscador" class="campo py-2.5 pl-10"
                        placeholder="Escriba el nombre o escanee el código..." autocomplete="off" autofocus>
                    <svg class="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-slate-400"
                        viewBox="0 0 24 24" fill="currentColor">
                        <path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 10-.7.7l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0a4.5 4.5 0 110-9 4.5 4.5 0 010 9z" />
                    </svg>
                </div>
                <button type="button" id="btn-camara" title="Escanear con cámara"
                    class="btn-secundario flex items-center gap-1.5 px-3">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9.4 4 7.8 6H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2h-3.8L14.6 4H9.4zM12 9a5 5 0 110 10 5 5 0 010-10zm0 2a3 3 0 100 6 3 3 0 000-6z" />
                    </svg>
                    <span class="hidden sm:inline">Cámara</span>
                </button>
            </div>

            <!-- Resultados de la búsqueda -->
            <div id="resultados" class="mt-3 hidden max-h-72 overflow-y-auto rounded-lg border border-slate-200"></div>
        </div>

        <div class="tarjeta overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                <h2 class="font-semibold text-slate-800">Productos de la venta</h2>
                <button type="button" id="btn-vaciar"
                    class="text-xs font-medium text-red-600 hover:text-red-700">
                    Vaciar carrito
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-right">P. Unit.</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-right">Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="carrito">
                        <tr id="carrito-vacio">
                            <td colspan="5" class="px-4 py-12 text-center text-slate-400">
                                El carrito está vacío. Busque un producto para empezar.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================================
         Columna derecha: cliente, comprobante y cobro
         ============================================================ -->
    <div class="lg:col-span-2">
        <div class="tarjeta lg:sticky lg:top-20">

            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-800">Datos del comprobante</h2>
            </div>

            <div class="space-y-4 px-5 py-4">

                <!-- Tipo de comprobante -->
                <div>
                    <label class="etiqueta">Tipo de comprobante</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label id="opcion-boleta"
                            class="flex cursor-pointer items-center justify-center rounded-lg border border-marca-600 bg-marca-50 px-3 py-2 text-sm font-medium text-marca-700">
                            <input type="radio" name="tipo_comprobante" value="BOLETA" class="sr-only" checked>
                            Boleta
                        </label>
                        <label id="opcion-factura"
                            class="flex cursor-pointer items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-600">
                            <input type="radio" name="tipo_comprobante" value="FACTURA" class="sr-only">
                            Factura
                        </label>
                    </div>
                    <p id="aviso-factura" class="mt-1 hidden text-xs text-amber-600">
                        La factura exige seleccionar un cliente con RUC.
                    </p>
                </div>

                <!-- Cliente -->
                <div>
                    <label for="buscador-cliente" class="etiqueta">Cliente</label>

                    <div id="cliente-elegido" class="mb-2 hidden rounded-lg bg-marca-50 px-3 py-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p id="cliente-nombre" class="truncate text-sm font-medium text-marca-900"></p>
                                <p id="cliente-documento" class="text-xs text-marca-700"></p>
                            </div>
                            <button type="button" id="btn-quitar-cliente"
                                class="text-xs font-medium text-marca-700 hover:text-marca-900">Quitar</button>
                        </div>
                    </div>

                    <input type="text" id="buscador-cliente" class="campo"
                        placeholder="Documento o nombre (opcional)" autocomplete="off">
                    <div id="resultados-cliente"
                        class="mt-1 hidden max-h-48 overflow-y-auto rounded-lg border border-slate-200"></div>
                </div>

                <!-- Método de pago -->
                <div>
                    <label for="metodo_pago" class="etiqueta">Método de pago</label>
                    <select id="metodo_pago" class="campo">
                        <option value="EFECTIVO">Efectivo</option>
                        <option value="TARJETA">Tarjeta</option>
                        <option value="YAPE">Yape</option>
                        <option value="PLIN">Plin</option>
                    </select>
                </div>
            </div>

            <!-- Totales -->
            <div class="space-y-2 border-t border-slate-200 bg-slate-50 px-5 py-4 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Op. gravada</span>
                    <span id="t-subtotal">S/ 0.00</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>IGV (<?= (int) $igv ?>%)</span>
                    <span id="t-igv">S/ 0.00</span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-lg font-bold text-slate-900">
                    <span>Total</span>
                    <span id="t-total">S/ 0.00</span>
                </div>
            </div>

            <!-- Cobro en efectivo -->
            <div id="bloque-efectivo" class="space-y-3 border-t border-slate-200 px-5 py-4">
                <div>
                    <label for="monto_pagado" class="etiqueta">Monto recibido (S/)</label>
                    <input type="number" id="monto_pagado" class="campo text-right text-lg font-semibold"
                        step="0.10" min="0" value="0.00">
                </div>

                <div class="flex flex-wrap gap-1.5">
                    <?php foreach ([10, 20, 50, 100, 200] as $billete): ?>
                        <button type="button" class="btn-secundario btn-sm billete" data-monto="<?= $billete ?>">
                            S/ <?= $billete ?>
                        </button>
                    <?php endforeach; ?>
                    <button type="button" class="btn-secundario btn-sm" id="btn-exacto">Exacto</button>
                </div>

                <div class="flex justify-between rounded-lg bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">
                    <span>Vuelto</span>
                    <span id="t-vuelto">S/ 0.00</span>
                </div>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                <button type="button" id="btn-cobrar" class="btn-exito w-full py-3 text-base" disabled>
                    Registrar venta (F9)
                </button>
                <p id="mensaje-venta" class="mt-2 hidden text-center text-xs font-medium"></p>
            </div>
        </div>
    </div>
</div>

<!-- ===================== Modal de escaneo por cámara ===================== -->
<div id="modal-camara" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
    <div class="tarjeta w-full max-w-sm overflow-hidden p-0">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
            <h2 class="font-semibold text-slate-800">Escanear código de barras</h2>
            <button type="button" id="btn-cerrar-camara" class="text-slate-400 hover:text-slate-600" aria-label="Cerrar">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.3 19.7l-1.41-1.42L8.59 12 2.3 5.71 3.71 4.3 10 10.59l6.29-6.29z" />
                </svg>
            </button>
        </div>
        <div class="relative bg-slate-900">
            <video id="video-camara" class="aspect-square w-full object-cover" playsinline autoplay muted></video>
        </div>
        <p id="camara-mensaje" class="px-5 py-3 text-center text-xs text-slate-500">
            Apunte la cámara al código de barras del producto.
        </p>
    </div>
</div>

<!-- ===================== Modal de venta registrada ===================== -->
<div id="modal-exito" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
    <div class="tarjeta w-full max-w-sm p-6 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
            <svg class="h-8 w-8 text-emerald-600" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
            </svg>
        </div>

        <h2 class="mt-4 text-lg font-bold text-slate-900">Venta registrada</h2>
        <p id="exito-comprobante" class="mt-1 font-mono text-sm text-slate-500"></p>

        <div class="mt-4 space-y-1 rounded-lg bg-slate-50 px-4 py-3 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">Total cobrado</span>
                <span id="exito-total" class="font-semibold text-slate-800"></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Vuelto</span>
                <span id="exito-vuelto" class="font-semibold text-emerald-700"></span>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-2">
            <a id="exito-imprimir" href="#" target="_blank" class="btn-primario">Imprimir</a>
            <button type="button" id="btn-nueva-venta" class="btn-secundario">Nueva venta</button>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../components/layout_fin.php'; ?>
