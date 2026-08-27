<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/controllers/ProductoController.php';

/**
 * Pruebas del controlador de productos.
 *
 * Valida: código de barras, nombre, categoría, precios, stock,
 * imagen y código duplicado.
 */
class ProductoControllerTest extends TestCase
{
    private function crearController($mockModelo = null): ProductoController
    {
        $ref        = new ReflectionClass(ProductoController::class);
        $controller = $ref->newInstanceWithoutConstructor();

        $mock = $mockModelo ?? $this->createMock(Producto::class);

        $prop = $ref->getProperty('modelo');
        $prop->setAccessible(true);
        $prop->setValue($controller, $mock);

        return $controller;
    }

    /**
     * Datos válidos base. Stock = 0 para evitar la creación interna
     * de Inventario que requiere conexión a la BD.
     */
    private function datosValidos(): array
    {
        return [
            'codigo_barras' => '7750000000001',
            'nombre'        => 'Galleta Soda',
            'descripcion'   => 'Paquete de galletas',
            'id_categoria'  => 1,
            'precio_compra' => 2.50,
            'precio_venta'  => 3.50,
            'stock'         => 0,
            'stock_minimo'  => 5,
            'unidad_medida' => 'UNIDAD',
        ];
    }

    // ─── Validaciones ────────────────────────────────────────────────

    public function test_guardar_codigo_barras_vacio_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos = $this->datosValidos();
        $datos['codigo_barras'] = '';

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('código de barras', $resultado['mensaje']);
    }

    public function test_guardar_nombre_vacio_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos = $this->datosValidos();
        $datos['nombre'] = '';

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('nombre', $resultado['mensaje']);
    }

    public function test_guardar_categoria_no_seleccionada_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos = $this->datosValidos();
        $datos['id_categoria'] = 0;

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('categoría', $resultado['mensaje']);
    }

    public function test_guardar_precio_compra_negativo_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos = $this->datosValidos();
        $datos['precio_compra'] = -1;

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('negativos', $resultado['mensaje']);
    }

    public function test_guardar_precio_venta_negativo_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos = $this->datosValidos();
        $datos['precio_venta'] = -1;

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
    }

    public function test_guardar_precio_venta_cero_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos = $this->datosValidos();
        $datos['precio_venta'] = 0;

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('mayor a cero', $resultado['mensaje']);
    }

    public function test_guardar_precio_venta_menor_que_compra_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos = $this->datosValidos();
        $datos['precio_compra'] = 5.00;
        $datos['precio_venta']  = 3.00;

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('menor que el precio de compra', $resultado['mensaje']);
    }

    public function test_guardar_stock_negativo_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos = $this->datosValidos();
        $datos['stock'] = -1;

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('negativo', $resultado['mensaje']);
    }

    public function test_guardar_stock_minimo_negativo_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos = $this->datosValidos();
        $datos['stock_minimo'] = -1;

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('negativo', $resultado['mensaje']);
    }

    public function test_guardar_codigo_duplicado_retorna_error(): void
    {
        $mock = $this->createMock(Producto::class);
        $mock->method('codigoExiste')->willReturn(true);

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar($this->datosValidos());

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('Ya existe', $resultado['mensaje']);
    }

    // ─── Creación y actualización ────────────────────────────────────

    public function test_guardar_crear_producto_exitoso(): void
    {
        $mock = $this->createMock(Producto::class);
        $mock->method('codigoExiste')->willReturn(false);
        $mock->expects($this->once())->method('crear')->willReturn(1);

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar($this->datosValidos());

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('registrado', $resultado['mensaje']);
    }

    public function test_guardar_actualizar_producto_exitoso(): void
    {
        $mock = $this->createMock(Producto::class);
        $mock->method('codigoExiste')->willReturn(false);
        $mock->expects($this->once())->method('actualizar');

        $controller = $this->crearController($mock);
        $datos = $this->datosValidos();
        $datos['id_producto'] = 1;
        $datos['estado']      = 1;

        $resultado = $controller->guardar($datos);
        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('actualizado', $resultado['mensaje']);
    }

    // ─── cambiarEstado() ─────────────────────────────────────────────

    public function test_cambiar_estado_activar(): void
    {
        $mock = $this->createMock(Producto::class);
        $mock->expects($this->once())->method('cambiarEstado')->with(1, 1);

        $controller = $this->crearController($mock);
        $resultado  = $controller->cambiarEstado(1, 1);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('activado', $resultado['mensaje']);
    }

    public function test_cambiar_estado_desactivar(): void
    {
        $mock = $this->createMock(Producto::class);
        $mock->expects($this->once())->method('cambiarEstado')->with(1, 0);

        $controller = $this->crearController($mock);
        $resultado  = $controller->cambiarEstado(1, 0);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('desactivado', $resultado['mensaje']);
    }
}
