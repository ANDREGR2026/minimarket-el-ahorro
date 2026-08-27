<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/controllers/CategoriaController.php';

/**
 * Pruebas del controlador de categorías.
 *
 * Se inyecta un mock del modelo Categoria mediante Reflection para
 * evitar la conexión a la base de datos.
 */
class CategoriaControllerTest extends TestCase
{
    private function crearController($mockModelo = null): CategoriaController
    {
        $ref        = new ReflectionClass(CategoriaController::class);
        $controller = $ref->newInstanceWithoutConstructor();

        $mock = $mockModelo ?? $this->createMock(Categoria::class);

        $prop = $ref->getProperty('modelo');
        $prop->setAccessible(true);
        $prop->setValue($controller, $mock);

        return $controller;
    }

    // ─── guardar(): validaciones ─────────────────────────────────────

    public function test_guardar_nombre_vacio_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->guardar(['nombre' => '', 'descripcion' => '']);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('obligatorio', $resultado['mensaje']);
    }

    public function test_guardar_nombre_supera_80_caracteres_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->guardar(['nombre' => str_repeat('A', 81), 'descripcion' => '']);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('80', $resultado['mensaje']);
    }

    public function test_guardar_nombre_duplicado_retorna_error(): void
    {
        $mock = $this->createMock(Categoria::class);
        $mock->method('nombreExiste')->willReturn(true);

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar(['nombre' => 'Bebidas', 'descripcion' => '']);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('Ya existe', $resultado['mensaje']);
    }

    // ─── guardar(): creación ─────────────────────────────────────────

    public function test_guardar_crear_categoria_exitoso(): void
    {
        $mock = $this->createMock(Categoria::class);
        $mock->method('nombreExiste')->willReturn(false);
        $mock->expects($this->once())->method('crear')
             ->with('Bebidas', 'Bebidas frías y calientes');

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'nombre'      => 'Bebidas',
            'descripcion' => 'Bebidas frías y calientes',
        ]);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('registrada', $resultado['mensaje']);
    }

    // ─── guardar(): actualización ────────────────────────────────────

    public function test_guardar_actualizar_categoria_exitoso(): void
    {
        $mock = $this->createMock(Categoria::class);
        $mock->method('nombreExiste')->willReturn(false);
        $mock->method('contarProductos')->willReturn(0);
        $mock->expects($this->once())->method('actualizar');

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'id_categoria' => 1,
            'nombre'       => 'Bebidas',
            'descripcion'  => 'Actualizada',
            'estado'       => 1,
        ]);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('actualizada', $resultado['mensaje']);
    }

    public function test_guardar_desactivar_con_productos_activos_retorna_error(): void
    {
        $mock = $this->createMock(Categoria::class);
        $mock->method('nombreExiste')->willReturn(false);
        $mock->method('contarProductos')->willReturn(5);

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'id_categoria' => 1,
            'nombre'       => 'Bebidas',
            'descripcion'  => '',
            'estado'       => 0,
        ]);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('productos activos', $resultado['mensaje']);
    }

    public function test_guardar_desactivar_sin_productos_exitoso(): void
    {
        $mock = $this->createMock(Categoria::class);
        $mock->method('nombreExiste')->willReturn(false);
        $mock->method('contarProductos')->willReturn(0);
        $mock->expects($this->once())->method('actualizar');

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'id_categoria' => 1,
            'nombre'       => 'Bebidas',
            'descripcion'  => '',
            'estado'       => 0,
        ]);

        $this->assertTrue($resultado['ok']);
    }

    // ─── cambiarEstado() ─────────────────────────────────────────────

    public function test_cambiar_estado_desactivar_con_productos_retorna_error(): void
    {
        $mock = $this->createMock(Categoria::class);
        $mock->method('contarProductos')->willReturn(3);

        $controller = $this->crearController($mock);
        $resultado  = $controller->cambiarEstado(1, 0);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('productos activos', $resultado['mensaje']);
    }

    public function test_cambiar_estado_activar_exitoso(): void
    {
        $mock = $this->createMock(Categoria::class);
        $mock->expects($this->once())->method('cambiarEstado')->with(1, 1);

        $controller = $this->crearController($mock);
        $resultado  = $controller->cambiarEstado(1, 1);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('activada', $resultado['mensaje']);
    }

    public function test_cambiar_estado_desactivar_exitoso(): void
    {
        $mock = $this->createMock(Categoria::class);
        $mock->method('contarProductos')->willReturn(0);
        $mock->expects($this->once())->method('cambiarEstado')->with(1, 0);

        $controller = $this->crearController($mock);
        $resultado  = $controller->cambiarEstado(1, 0);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('desactivada', $resultado['mensaje']);
    }
}
