<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once ROOT_PATH . '/models/Reporte.php';
require_once ROOT_PATH . '/models/Venta.php';
require_once ROOT_PATH . '/models/Producto.php';
require_once ROOT_PATH . '/models/Categoria.php';

class ReporteModelTest extends DatabaseTestCase
{
    private Reporte $reporte;
    private Venta $venta;
    private int $idProducto;
    private int $idCategoria;

    protected function setUp(): void
    {
        // No llamamos a parent::setUp(): Venta::registrar abre su propia
        // transacción y chocaría con la transacción global de la clase base.
        $_ENV['DB_NAME'] = 'minimarket_test';
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_USER'] = 'root';
        $_ENV['DB_PASS'] = '';
        $this->conexion = Conexion::conectar();

        $this->reporte = new Reporte();
        $this->venta = new Venta();

        $this->conexion->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE ventas; TRUNCATE detalle_venta; TRUNCATE movimientos_inventario; TRUNCATE productos; TRUNCATE categorias; TRUNCATE usuarios; TRUNCATE series_comprobante; SET FOREIGN_KEY_CHECKS = 1;");

        $cat = new Categoria();
        $this->idCategoria = $cat->crear('Cat Reporte', 'Desc');

        $prod = new Producto();
        $this->idProducto = $prod->crear([
            'codigo_barras' => 'REP123', 'nombre' => 'Prod Reporte', 'descripcion' => null,
            'id_categoria' => $this->idCategoria, 'precio_compra' => 1, 'precio_venta' => 5,
            'stock_minimo' => 1, 'stock' => 0, 'unidad_medida' => 'UNIDAD', 'imagen' => null
        ]);
        $this->conexion->exec("UPDATE productos SET stock = 100 WHERE id_producto = {$this->idProducto}");

        $this->conexion->exec("INSERT INTO usuarios (id_usuario, nombre, usuario, password, rol, estado) VALUES (99, 'Cajero Uno', 'u1', 'p', 'Cajero', 1)");
        $this->conexion->exec("INSERT INTO series_comprobante (tipo_comprobante, serie, ultimo_correlativo) VALUES ('BOLETA', 'B001', 0)");
    }

    protected function tearDown(): void
    {
        $this->conexion->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE ventas; TRUNCATE detalle_venta; TRUNCATE movimientos_inventario; TRUNCATE productos; TRUNCATE categorias; TRUNCATE usuarios; TRUNCATE series_comprobante; SET FOREIGN_KEY_CHECKS = 1;");
    }

    /** Registra una venta EMITIDA real usando Venta::registrar. */
    private function registrarVenta(int $cantidad, float $precio): array
    {
        $carrito = [
            ['id_producto' => $this->idProducto, 'cantidad' => $cantidad, 'precio' => $precio, 'subtotal' => $cantidad * $precio]
        ];
        $datosPago = [
            'tipo_comprobante' => 'BOLETA',
            'metodo_pago' => 'EFECTIVO',
            'monto_pagado' => $cantidad * $precio,
            'id_cliente' => null,
            'igv' => 18,
            'id_usuario' => 99,
        ];

        return $this->venta->registrar($carrito, $datosPago);
    }

    public function test_indicadores_suma_venta_del_dia(): void
    {
        $this->registrarVenta(2, 5); // 10
        $this->registrarVenta(3, 5); // 15

        $ind = $this->reporte->indicadores();

        $this->assertEquals(25.0, (float) $ind['venta_hoy']);
        $this->assertEquals(2, (int) $ind['tickets_hoy']);
        $this->assertEquals(25.0, (float) $ind['venta_total']);
    }

    public function test_indicadores_ignora_venta_anulada(): void
    {
        $res = $this->registrarVenta(2, 5);
        $this->venta->anular($res['id_venta'], 'Prueba', 99);

        $ind = $this->reporte->indicadores();

        $this->assertEquals(0.0, (float) $ind['venta_hoy']);
        $this->assertEquals(0, (int) $ind['tickets_hoy']);
    }

    public function test_ventas_por_dia_rellena_dias_sin_ventas(): void
    {
        $this->registrarVenta(1, 5);

        $serie = $this->reporte->ventasPorDia(7);

        $this->assertCount(7, $serie);
        $hoy = end($serie);
        $this->assertEquals(date('Y-m-d'), $hoy['dia']);
        $this->assertEquals(5.0, $hoy['total']);
        $this->assertEquals(1, $hoy['tickets']);

        // Días previos sin ventas quedan en cero, no ausentes
        $this->assertEquals(0.0, $serie[0]['total']);
    }

    public function test_productos_mas_vendidos(): void
    {
        $this->registrarVenta(5, 5);

        $top = $this->reporte->productosMasVendidos();

        $this->assertCount(1, $top);
        $this->assertEquals($this->idProducto, $top[0]['id_producto']);
        $this->assertEquals(5, $top[0]['unidades']);
        $this->assertEquals(25, $top[0]['importe']);
    }

    public function test_ventas_por_categoria(): void
    {
        $this->registrarVenta(4, 5);

        $porCategoria = $this->reporte->ventasPorCategoria();

        $this->assertCount(1, $porCategoria);
        $this->assertEquals('Cat Reporte', $porCategoria[0]['categoria']);
        $this->assertEquals(20, $porCategoria[0]['importe']);
    }

    public function test_ventas_por_cajero_solo_incluye_los_que_vendieron(): void
    {
        $this->conexion->exec("INSERT INTO usuarios (id_usuario, nombre, usuario, password, rol, estado) VALUES (98, 'Cajero Sin Ventas', 'u2', 'p', 'Cajero', 1)");

        $this->registrarVenta(2, 5);

        $porCajero = $this->reporte->ventasPorCajero();

        $this->assertCount(1, $porCajero);
        $this->assertEquals('Cajero Uno', $porCajero[0]['cajero']);
        $this->assertEquals(1, $porCajero[0]['tickets']);
        $this->assertEquals(10, $porCajero[0]['importe']);
    }

    public function test_resumen_rango(): void
    {
        $this->registrarVenta(2, 5);
        $hoy = date('Y-m-d');

        $resumen = $this->reporte->resumenRango($hoy, $hoy);

        $this->assertEquals(1, (int) $resumen['tickets']);
        $this->assertEquals(1, (int) $resumen['boletas']);
        $this->assertEquals(0, (int) $resumen['facturas']);
        $this->assertGreaterThan(0, (float) $resumen['total']);
    }

    public function test_ventas_por_metodo_pago(): void
    {
        $this->registrarVenta(2, 5);
        $hoy = date('Y-m-d');

        $porMetodo = $this->reporte->ventasPorMetodoPago($hoy, $hoy);

        $this->assertCount(1, $porMetodo);
        $this->assertEquals('EFECTIVO', $porMetodo[0]['metodo_pago']);
        $this->assertEquals(1, (int) $porMetodo[0]['tickets']);
    }

    public function test_detalle_ventas(): void
    {
        $this->registrarVenta(3, 5);
        $hoy = date('Y-m-d');

        $detalle = $this->reporte->detalleVentas($hoy, $hoy);

        $this->assertCount(1, $detalle);
        $this->assertEquals('BOLETA', $detalle[0]['tipo_comprobante']);
        $this->assertEquals('Cajero Uno', $detalle[0]['cajero']);
        $this->assertStringContainsString('B001-', $detalle[0]['comprobante']);
    }

    public function test_ultimas_ventas_respeta_limite_y_orden(): void
    {
        $primero = $this->registrarVenta(1, 5);
        $segundo = $this->registrarVenta(1, 5);

        $ultimas = $this->reporte->ultimasVentas(1);

        $this->assertCount(1, $ultimas);
        $this->assertEquals($segundo['id_venta'], $ultimas[0]['id_venta']);
    }
}
