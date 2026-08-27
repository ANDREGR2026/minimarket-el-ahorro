<?php

/**
 * Muestra el PDF del comprobante en el navegador.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/ComprobanteController.php';

Auth::checkRole(['Administrador', 'Cajero']);

$idVenta = (int) ($_GET['id'] ?? 0);

$resultado = (new ComprobanteController())->generar($idVenta, 'I');

// Solo se llega aquí si la venta no existe: generar() termina el script al
// enviar el PDF.
if (!$resultado['ok']) {
    flash('error', $resultado['mensaje']);
    redirigir('ventas');
}
