<?php

/**
 * Muestra el PDF del comprobante en el navegador.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/ComprobanteController.php';
require_once __DIR__ . '/../controllers/VentaController.php';

Auth::checkRole(['Administrador', 'Cajero']);

$idVenta = (int) ($_GET['id'] ?? 0);
$venta   = (new VentaController())->obtener($idVenta);

// El cajero solo puede imprimir las ventas que él mismo registró
if ($venta && !Auth::esAdministrador() && (int) $venta['id_usuario'] !== Auth::id()) {
    flash('error', 'Solo puede consultar las ventas que usted registró.');
    redirigir('ventas');
}

$resultado = (new ComprobanteController())->generar($idVenta, 'I');

// Solo se llega aquí si la venta no existe: generar() termina el script al
// enviar el PDF.
if (!$resultado['ok']) {
    flash('error', $resultado['mensaje']);
    redirigir('ventas');
}
