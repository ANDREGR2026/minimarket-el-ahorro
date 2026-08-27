<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once ROOT_PATH . '/models/Inventario.php';
require_once ROOT_PATH . '/models/Producto.php';
require_once ROOT_PATH . '/models/Categoria.php';

class InventarioModelTest extends DatabaseTestCase
{
    private Inventario $inventario;
    private int $idProducto;

    protected function setUp(): void
    {
        $_ENV['DB_NAME'] = 'minimarket_test';
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_USER'] = 'root';
        $_ENV['DB_PASS'] = '';
        $this->conexion = Conexion::conectar();
        $this->inventario = new Inventario();
        
        $this->conexion->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE movimientos_inventario; TRUNCATE productos; TRUNCATE categorias; SET FOREIGN_KEY_CHECKS = 1;");

        $cat = new Categoria();
        $idCat = $cat->crear('Cat Inv', 'Desc');

        $prod = new Producto();
        $this->idProducto = $prod->crear([
            'codigo_barras' => 'INV123', 'nombre' => 'Prod Inv', 'descripcion' => null,
            'id_categoria' => $idCat, 'precio_compra' => 1, 'precio_venta' => 2,
            'stock_minimo' => 1, 'stock' => 0, 'unidad_medida' => 'UNIDAD', 'imagen' => null
        ]);
        
        // Asignar stock inicial
        $this->conexion->exec("UPDATE productos SET stock = 10 WHERE id_producto = {$this->idProducto}");
        
        $this->conexion->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE usuarios; SET FOREIGN_KEY_CHECKS = 1;");
        $this->conexion->exec("INSERT INTO usuarios (id_usuario, nombre, usuario, password, rol, estado) VALUES (1, 'U', 'u', 'p', 'Administrador', 1)");
    }

    protected function tearDown(): void
    {
        $this->conexion->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE movimientos_inventario; TRUNCATE productos; TRUNCATE categorias; SET FOREIGN_KEY_CHECKS = 1;");
    }

    public function test_registrar_entrada(): void
    {
        $res = $this->inventario->registrarEntrada($this->idProducto, 5, 'Compra', 1);
        $this->assertTrue($res['ok']);

        $stmt = $this->conexion->query("SELECT stock FROM productos WHERE id_producto = {$this->idProducto}");
        $this->assertEquals(15, $stmt->fetchColumn());
        
        $kardex = $this->inventario->kardex($this->idProducto);
        $this->assertCount(1, $kardex);
        $this->assertEquals('ENTRADA', $kardex[0]['tipo']);
        $this->assertEquals(5, $kardex[0]['cantidad']);
        $this->assertEquals(15, $kardex[0]['stock_nuevo']);
    }

    public function test_registrar_salida(): void
    {
        $res = $this->inventario->registrarSalida($this->idProducto, 3, 'Merma', 1);
        $this->assertTrue($res['ok']);

        $stmt = $this->conexion->query("SELECT stock FROM productos WHERE id_producto = {$this->idProducto}");
        $this->assertEquals(7, $stmt->fetchColumn());
        
        $kardex = $this->inventario->kardex($this->idProducto);
        $this->assertCount(1, $kardex);
        $this->assertEquals('SALIDA', $kardex[0]['tipo']);
        $this->assertEquals(3, $kardex[0]['cantidad']);
        $this->assertEquals(7, $kardex[0]['stock_nuevo']);
    }

    public function test_registrar_salida_sin_stock_suficiente(): void
    {
        $res = $this->inventario->registrarSalida($this->idProducto, 15, 'Merma', 1); // Stock es 10
        $this->assertFalse($res['ok']);
        $this->assertStringContainsStringIgnoringCase('stock insuficiente', $res['mensaje']);

        $stmt = $this->conexion->query("SELECT stock FROM productos WHERE id_producto = {$this->idProducto}");
        $this->assertEquals(10, $stmt->fetchColumn()); // No cambió
    }

    public function test_registrar_ajuste(): void
    {
        // Ajuste a 12 (el stock actual es 10)
        $res = $this->inventario->registrarAjuste($this->idProducto, 12, 'Conteo fisico', 1);
        $this->assertTrue($res['ok']);

        $stmt = $this->conexion->query("SELECT stock FROM productos WHERE id_producto = {$this->idProducto}");
        $this->assertEquals(12, $stmt->fetchColumn());
        
        $kardex = $this->inventario->kardex($this->idProducto);
        $this->assertEquals('AJUSTE', $kardex[0]['tipo']);
        $this->assertEquals(12, $kardex[0]['stock_nuevo']);
    }
}
