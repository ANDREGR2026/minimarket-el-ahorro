<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/controllers/InventarioController.php';

/**
 * Pruebas del controlador de inventario.
 *
 * Valida: producto seleccionado, motivo, tipo de movimiento, cantidad,
 * y delegación correcta al modelo según tipo (ENTRADA/SALIDA/AJUSTE).
 */
class InventarioControllerTest extends TestCase
{
    private function crearController($mockInventario = null, $mockProductos = null): InventarioController
    {
        $ref        = new ReflectionClass(InventarioController::class);
        $controller = $ref->newInstanceWithoutConstructor();

        $inv  = $mockInventario ?? $this->createMock(Inventario::class);
        $prod = $mockProductos  ?? $this->createMock(Producto::class);

        $propInv = $ref->getProperty('inventario');
        $propInv->setAccessible(true);
        $propInv->setValue($controller, $inv);

        $propProd = $ref->getProperty('productos');
        $propProd->setAccessible(true);
        $propProd->setValue($controller, $prod);

        return $controller;
    }

    // ─── Validaciones ────────────────────────────────────────────────

    public function test_registrar_producto_no_seleccionado_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->registrar(
            ['id_producto' => 0, 'tipo' => 'ENTRADA', 'cantidad' => 10, 'motivo' => 'Compra'],
            1
        );

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('producto', $resultado['mensaje']);
    }

    public function test_registrar_motivo_vacio_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->registrar(
            ['id_producto' => 1, 'tipo' => 'ENTRADA', 'cantidad' => 10, 'motivo' => ''],
            1
        );

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('motivo', $resultado['mensaje']);
    }

    public function test_registrar_tipo_invalido_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->registrar(
            ['id_producto' => 1, 'tipo' => 'INVALIDO', 'cantidad' => 10, 'motivo' => 'Motivo'],
            1
        );

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('no válido', $resultado['mensaje']);
    }

    public function test_registrar_cantidad_negativa_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->registrar(
            ['id_producto' => 1, 'tipo' => 'ENTRADA', 'cantidad' => -5, 'motivo' => 'Motivo'],
            1
        );

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('negativa', $resultado['mensaje']);
    }

    // ─── Delegación al modelo ────────────────────────────────────────

    public function test_registrar_entrada_delega_al_modelo(): void
    {
        $mockInv = $this->createMock(Inventario::class);
        $mockInv->expects($this->once())
                ->method('registrarEntrada')
                ->with(1, 10, 'Compra semanal', 1)
                ->willReturn(['ok' => true, 'mensaje' => 'OK']);

        $controller = $this->crearController($mockInv);
        $resultado  = $controller->registrar(
            ['id_producto' => 1, 'tipo' => 'ENTRADA', 'cantidad' => 10, 'motivo' => 'Compra semanal'],
            1
        );

        $this->assertTrue($resultado['ok']);
    }

    public function test_registrar_salida_delega_al_modelo(): void
    {
        $mockInv = $this->createMock(Inventario::class);
        $mockInv->expects($this->once())
                ->method('registrarSalida')
                ->with(1, 3, 'Merma por vencimiento', 1)
                ->willReturn(['ok' => true, 'mensaje' => 'OK']);

        $controller = $this->crearController($mockInv);
        $resultado  = $controller->registrar(
            ['id_producto' => 1, 'tipo' => 'SALIDA', 'cantidad' => 3, 'motivo' => 'Merma por vencimiento'],
            1
        );

        $this->assertTrue($resultado['ok']);
    }

    public function test_registrar_ajuste_delega_al_modelo(): void
    {
        $mockInv = $this->createMock(Inventario::class);
        $mockInv->expects($this->once())
                ->method('registrarAjuste')
                ->with(1, 50, 'Conteo físico', 1)
                ->willReturn(['ok' => true, 'mensaje' => 'OK']);

        $controller = $this->crearController($mockInv);
        $resultado  = $controller->registrar(
            ['id_producto' => 1, 'tipo' => 'AJUSTE', 'cantidad' => 50, 'motivo' => 'Conteo físico'],
            1
        );

        $this->assertTrue($resultado['ok']);
    }
}
