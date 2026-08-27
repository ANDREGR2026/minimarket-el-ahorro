<?php
require_once __DIR__ . '/tests/bootstrap.php';
require_once __DIR__ . '/tests/Unit/InventarioModelTest.php';

$t = new InventarioModelTest('test_registrar_entrada');
// Hacemos el setUp público mediante Reflection para llamarlo
$ref = new ReflectionClass(InventarioModelTest::class);
$method = $ref->getMethod('setUp');
$method->setAccessible(true);
$method->invoke($t);

$conexion = Conexion::conectar();
$refProp = $ref->getProperty('idProducto');
$refProp->setAccessible(true);
$id = $refProp->getValue($t);

var_dump("ID PRODUCTO: " . $id);

$stmt = $conexion->query("SELECT stock FROM productos WHERE id_producto = " . (int)$id);
var_dump("STOCK: " . $stmt->fetchColumn());

