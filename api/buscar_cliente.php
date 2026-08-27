<?php

/**
 * Endpoint JSON: busca clientes por documento, nombre o razón social.
 *
 *   GET api/buscar_cliente?q=10245678
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../controllers/VentaController.php';

Auth::checkRole(['Administrador', 'Cajero']);

$termino = trim($_GET['q'] ?? '');

if (mb_strlen($termino) < 2) {
    json_respuesta(['ok' => true, 'clientes' => []]);
}

json_respuesta([
    'ok'       => true,
    'clientes' => (new VentaController())->buscarClientes($termino),
]);
