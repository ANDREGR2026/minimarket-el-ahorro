<?php

/**
 * Genera ventas de demostración de los últimos 30 días.
 *
 * Sirve para que el dashboard y los reportes tengan datos al momento de
 * sustentar el proyecto. Las ventas se registran con el mismo modelo que
 * usa el punto de venta, así que el stock y el kardex quedan consistentes.
 *
 * Ejecutar desde la carpeta del proyecto:
 *     php database/demo_ventas.php
 *
 * Para borrar los datos de demostración y volver a empezar, reimportar
 * schema.sql y seed.sql.
 */

if (PHP_SAPI !== 'cli') {
    exit('Este script solo se ejecuta por línea de comandos.');
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Venta.php';

$conexion = Conexion::conectar();
$modelo   = new Venta();

$productos = $conexion->query(
    "SELECT id_producto, precio_venta, stock FROM productos WHERE estado = 1 AND stock > 5"
)->fetchAll();

$clientes = $conexion->query(
    "SELECT id_cliente, tipo_documento FROM clientes WHERE estado = 1"
)->fetchAll();

$cajeros = $conexion->query(
    "SELECT id_usuario FROM usuarios WHERE estado = 1"
)->fetchAll(PDO::FETCH_COLUMN);

$metodos    = ['EFECTIVO', 'EFECTIVO', 'EFECTIVO', 'TARJETA', 'YAPE', 'PLIN'];
$registradas = 0;
$fallidas    = 0;

// Entre 2 y 6 ventas por día durante los últimos 30 días
for ($dia = 29; $dia >= 0; $dia--) {
    $ventasDelDia = random_int(2, 6);

    for ($i = 0; $i < $ventasDelDia; $i++) {
        // Carrito de 1 a 5 productos distintos
        $carrito = [];
        $elegidos = (array) array_rand($productos, min(random_int(1, 5), count($productos)));

        foreach ($elegidos as $indice) {
            $carrito[] = [
                'id_producto' => $productos[$indice]['id_producto'],
                'cantidad'    => random_int(1, 3),
            ];
        }

        // Una de cada seis ventas es una factura a un cliente con RUC
        $esFactura = random_int(1, 6) === 1;

        if ($esFactura) {
            $conRuc = array_values(array_filter($clientes, fn($c) => $c['tipo_documento'] === 'RUC'));
            $cliente = $conRuc[array_rand($conRuc)]['id_cliente'];
        } else {
            // Dos de cada tres boletas van sin cliente identificado
            $cliente = random_int(1, 3) === 1
                ? $clientes[array_rand($clientes)]['id_cliente']
                : null;
        }

        $resultado = $modelo->registrar($carrito, [
            'id_usuario'       => $cajeros[array_rand($cajeros)],
            'id_cliente'       => $cliente,
            'tipo_comprobante' => $esFactura ? 'FACTURA' : 'BOLETA',
            'metodo_pago'      => $metodos[array_rand($metodos)],
            'monto_pagado'     => 999999, // suficiente para cualquier total
        ]);

        if (!$resultado['ok']) {
            $fallidas++;
            continue;
        }

        // Backdatear la venta y sus movimientos de kardex
        $fecha = date('Y-m-d', strtotime("-$dia day"))
            . ' ' . str_pad(random_int(8, 21), 2, '0', STR_PAD_LEFT)
            . ':' . str_pad(random_int(0, 59), 2, '0', STR_PAD_LEFT) . ':00';

        $conexion->prepare("UPDATE ventas SET fecha = :fecha WHERE id_venta = :id")
            ->execute([':fecha' => $fecha, ':id' => $resultado['id_venta']]);

        $conexion->prepare("UPDATE movimientos_inventario SET fecha = :fecha WHERE id_venta = :id")
            ->execute([':fecha' => $fecha, ':id' => $resultado['id_venta']]);

        // El vuelto calculado con el monto ficticio no sirve: se corrige
        $conexion->prepare("
            UPDATE ventas
            SET monto_pagado = CASE WHEN metodo_pago = 'EFECTIVO'
                                    THEN CEIL(total / 10) * 10 ELSE total END,
                vuelto = CASE WHEN metodo_pago = 'EFECTIVO'
                              THEN CEIL(total / 10) * 10 - total ELSE 0 END
            WHERE id_venta = :id
        ")->execute([':id' => $resultado['id_venta']]);

        $registradas++;
    }
}

echo "Ventas de demostración registradas: $registradas\n";

if ($fallidas > 0) {
    echo "Ventas omitidas por falta de stock: $fallidas\n";
}
