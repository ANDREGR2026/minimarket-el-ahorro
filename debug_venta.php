<?php
require_once __DIR__ . '/tests/bootstrap.php';
require_once ROOT_PATH . '/models/Venta.php';
require_once ROOT_PATH . '/models/Producto.php';
require_once ROOT_PATH . '/models/Categoria.php';

$conexion = Conexion::conectar();
$conexion->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE ventas; TRUNCATE detalle_venta; TRUNCATE movimientos_inventario; TRUNCATE productos; TRUNCATE categorias; TRUNCATE usuarios; SET FOREIGN_KEY_CHECKS = 1;");

$cat = new Categoria();
$idCat = $cat->crear('Cat Venta', 'Desc');

$prod = new Producto();
$idProducto = $prod->crear([
    'codigo_barras' => 'VEN123', 'nombre' => 'Prod Venta', 'descripcion' => null,
    'id_categoria' => $idCat, 'precio_compra' => 1, 'precio_venta' => 2,
    'stock_minimo' => 1, 'stock' => 0, 'unidad_medida' => 'UNIDAD', 'imagen' => null
]);

$conexion->exec("UPDATE productos SET stock = 10 WHERE id_producto = {$idProducto}");
$conexion->exec("INSERT INTO usuarios (id_usuario, nombre, usuario, password, rol, estado) VALUES (99, 'U', 'u', 'p', 'Cajero', 1)");

$conexion->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE series_comprobante; SET FOREIGN_KEY_CHECKS = 1;");
$conexion->exec("INSERT INTO series_comprobante (tipo_comprobante, serie, ultimo_correlativo) VALUES ('BOLETA', 'B001', 0)");

$venta = new Venta();
$carrito = [
    ['id_producto' => $idProducto, 'cantidad' => 3, 'precio' => 2, 'subtotal' => 6]
];
$datosPago = [
    'tipo_comprobante' => 'BOLETA',
    'metodo_pago' => 'EFECTIVO',
    'monto_pagado' => 10,
    'id_cliente' => null,
    'igv' => 18
];

$res = $venta->registrar($carrito, $datosPago, 99);
var_dump($res);
