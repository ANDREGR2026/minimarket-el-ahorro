<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once ROOT_PATH . '/models/Venta.php';
require_once ROOT_PATH . '/models/Producto.php';
require_once ROOT_PATH . '/models/Categoria.php';

class VentaModelTest extends DatabaseTestCase
{
    private Venta $venta;
    private int $idProducto;

    protected function setUp(): void
    {
        // No llamamos a parent::setUp() para evitar la transacciÃ³n global
        // porque Venta::registrar inicia la suya propia y PDO fallarÃ­a.
        $_ENV['DB_NAME'] = 'minimarket_test';
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_USER'] = 'root';
        $_ENV['DB_PASS'] = '';
        $this->conexion = Conexion::conectar();
        
        $this->venta = new Venta();
        
        // Limpiar base de datos
        $this->conexion->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE ventas; TRUNCATE detalle_venta; TRUNCATE movimientos_inventario; TRUNCATE productos; TRUNCATE categorias; TRUNCATE usuarios; SET FOREIGN_KEY_CHECKS = 1;");

        $cat = new Categoria();
        $idCat = $cat->crear('Cat Venta', 'Desc');

        $prod = new Producto();
        $this->idProducto = $prod->crear([
            'codigo_barras' => 'VEN123', 'nombre' => 'Prod Venta', 'descripcion' => null,
            'id_categoria' => $idCat, 'precio_compra' => 1, 'precio_venta' => 2,
            'stock_minimo' => 1, 'stock' => 0, 'unidad_medida' => 'UNIDAD', 'imagen' => null
        ]);
        
        $this->conexion->exec("UPDATE productos SET stock = 10 WHERE id_producto = {$this->idProducto}");
        $this->conexion->exec("INSERT INTO usuarios (id_usuario, nombre, usuario, password, rol, estado) VALUES (99, 'U', 'u', 'p', 'Cajero', 1)");
        
        $this->conexion->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE series_comprobante; SET FOREIGN_KEY_CHECKS = 1;");
        $this->conexion->exec("INSERT INTO series_comprobante (tipo_comprobante, serie, ultimo_correlativo) VALUES ('BOLETA', 'B001', 0)");
    }

    protected function tearDown(): void
    {
        // Limpiar despuÃ©s de la prueba
        $this->conexion->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE ventas; TRUNCATE detalle_venta; TRUNCATE movimientos_inventario; TRUNCATE productos; TRUNCATE categorias; TRUNCATE usuarios; SET FOREIGN_KEY_CHECKS = 1;");
    }

    public function test_registrar_venta_exitosamente(): void
    {
        $carrito = [
            ['id_producto' => $this->idProducto, 'cantidad' => 3, 'precio' => 2, 'subtotal' => 6]
        ];
        $datosPago = [
            'tipo_comprobante' => 'BOLETA',
            'metodo_pago' => 'EFECTIVO',
            'monto_pagado' => 10,
            'id_cliente' => null,
            'igv' => 18,
            'id_usuario' => 99
        ];

        $res = $this->venta->registrar($carrito, $datosPago);

        $this->assertTrue($res['ok']);
        $this->assertArrayHasKey('id_venta', $res);
        $this->assertArrayHasKey('comprobante', $res);

        // Verificar stock reducido
        $stmt = $this->conexion->query("SELECT stock FROM productos WHERE id_producto = {$this->idProducto}");
        $this->assertEquals(7, $stmt->fetchColumn());

        // Verificar detalle insertado
        $detalles = $this->venta->detalle($res['id_venta']);
        $this->assertCount(1, $detalles);
        $this->assertEquals(3, $detalles[0]['cantidad']);
    }

    public function test_registrar_venta_sin_stock_retorna_error(): void
    {
        $carrito = [
            ['id_producto' => $this->idProducto, 'cantidad' => 15, 'precio' => 2, 'subtotal' => 30] // Stock es 10
        ];
        $datosPago = [
            'tipo_comprobante' => 'BOLETA', 'metodo_pago' => 'EFECTIVO', 'monto_pagado' => 30, 'id_cliente' => null, 'igv' => 18, 'id_usuario' => 99
        ];

        $res = $this->venta->registrar($carrito, $datosPago);

        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('stock insuficiente', strtolower($res['mensaje']));

        // Verificar que el stock no cambió por el rollback de la DB (aunque Venta no hace catch con return aquí, asumiendo que Venta::registrar envuelve en transacción. Wait, Venta::registrar hace commit/rollback internamente)
        $stmt = $this->conexion->query("SELECT stock FROM productos WHERE id_producto = {$this->idProducto}");
        $this->assertEquals(10, $stmt->fetchColumn());
    }

    public function test_anular_venta_restaura_stock(): void
    {
        // Registrar venta
        $resVenta = $this->venta->registrar(
            [['id_producto' => $this->idProducto, 'cantidad' => 4, 'precio' => 2, 'subtotal' => 8]],
            ['tipo_comprobante' => 'BOLETA', 'metodo_pago' => 'EFECTIVO', 'monto_pagado' => 10, 'id_cliente' => null, 'igv' => 18, 'id_usuario' => 99]
        );
        $idVenta = $resVenta['id_venta'];

        $resAnular = $this->venta->anular($idVenta, 'Error en el cobro', 99);
        $this->assertTrue($resAnular['ok']);

        // El stock debería volver a 10
        $stmt = $this->conexion->query("SELECT stock FROM productos WHERE id_producto = {$this->idProducto}");
        $this->assertEquals(10, $stmt->fetchColumn());

        // Venta debe figurar como anulada
        $ventaDB = $this->venta->obtenerPorId($idVenta);
        $this->assertEquals('ANULADA', $ventaDB['estado']);
    }
}
