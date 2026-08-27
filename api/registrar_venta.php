<?php

/**
 * Endpoint JSON que registra la venta enviada desde el punto de venta.
 *
 *   POST api/registrar_venta
 *   Cuerpo: {"csrf_token":"...", "carrito":[{"id_producto":1,"cantidad":2}],
 *            "tipo_comprobante":"BOLETA", "id_cliente":3,
 *            "metodo_pago":"EFECTIVO", "monto_pagado":50.00}
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/VentaController.php';

Auth::checkRole(['Administrador', 'Cajero']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_respuesta(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);
}

$cuerpo = json_decode(file_get_contents('php://input'), true);

if (!is_array($cuerpo)) {
    json_respuesta(['ok' => false, 'mensaje' => 'La solicitud no tiene un formato válido.'], 400);
}

if (!csrf_validar($cuerpo['csrf_token'] ?? '')) {
    json_respuesta(['ok' => false, 'mensaje' => 'La sesión expiró. Vuelva a iniciar sesión.'], 403);
}

$resultado = (new VentaController())->registrar(
    $cuerpo['carrito'] ?? [],
    $cuerpo,
    Auth::id()
);

json_respuesta($resultado, $resultado['ok'] ? 200 : 422);
