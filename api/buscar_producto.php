<?php

/**
 * Endpoint JSON usado por el punto de venta.
 *
 *   GET api/buscar_producto?q=arroz     -> lista de coincidencias
 *   GET api/buscar_producto?codigo=775  -> un producto exacto (lector de barras)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/VentaController.php';

Auth::checkRole(['Administrador', 'Cajero']);

$controlador = new VentaController();

// Búsqueda exacta por código de barras
if (isset($_GET['codigo'])) {
    $codigo   = trim($_GET['codigo']);
    $producto = $controlador->productoPorCodigo($codigo);

    if (!$producto) {
        json_respuesta([
            'ok'      => false,
            'mensaje' => 'No existe un producto activo con el código ' . $codigo . '.',
        ], 404);
    }

    json_respuesta(['ok' => true, 'producto' => $producto]);
}

// Búsqueda por nombre o código parcial
$termino = trim($_GET['q'] ?? '');

if (mb_strlen($termino) < 2) {
    json_respuesta(['ok' => true, 'productos' => []]);
}

json_respuesta([
    'ok'        => true,
    'productos' => $controlador->buscarProductos($termino),
]);
