<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/controllers/VentaController.php';

/**
 * Pruebas del controlador de ventas / punto de venta.
 *
 * Valida: carrito vacío, líneas inválidas, método de pago,
 * consolidación de líneas repetidas y motivo de anulación.
 */
class VentaControllerTest extends TestCase
{
    private function crearController(
        $mockModelo    = null,
        $mockProductos = null,
        $mockClientes  = null,
        $mockConfig    = null
    ): VentaController {
        $ref        = new ReflectionClass(VentaController::class);
        $controller = $ref->newInstanceWithoutConstructor();

        $mocks = [
            'modelo'    => $mockModelo    ?? $this->createMock(Venta::class),
            'productos' => $mockProductos ?? $this->createMock(Producto::class),
            'clientes'  => $mockClientes  ?? $this->createMock(Cliente::class),
            'config'    => $mockConfig    ?? $this->createMock(Configuracion::class),
        ];

        foreach ($mocks as $nombre => $mock) {
            $prop = $ref->getProperty($nombre);
            $prop->setAccessible(true);
            $prop->setValue($controller, $mock);
        }

        return $controller;
    }

    // ─── registrar(): validaciones ───────────────────────────────────

    public function test_registrar_carrito_vacio_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->registrar([], ['metodo_pago' => 'EFECTIVO'], 1);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('producto', $resultado['mensaje']);
    }

    public function test_registrar_carrito_no_array_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->registrar('invalido', ['metodo_pago' => 'EFECTIVO'], 1);

        $this->assertFalse($resultado['ok']);
    }

    public function test_registrar_linea_id_producto_cero_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->registrar(
            [['id_producto' => 0, 'cantidad' => 1]],
            ['metodo_pago' => 'EFECTIVO'],
            1
        );

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('inválida', $resultado['mensaje']);
    }

    public function test_registrar_linea_cantidad_cero_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->registrar(
            [['id_producto' => 1, 'cantidad' => 0]],
            ['metodo_pago' => 'EFECTIVO'],
            1
        );

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('inválida', $resultado['mensaje']);
    }

    public function test_registrar_linea_cantidad_negativa_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->registrar(
            [['id_producto' => 1, 'cantidad' => -3]],
            ['metodo_pago' => 'EFECTIVO'],
            1
        );

        $this->assertFalse($resultado['ok']);
    }

    public function test_registrar_metodo_pago_invalido_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->registrar(
            [['id_producto' => 1, 'cantidad' => 2]],
            ['metodo_pago' => 'BITCOIN'],
            1
        );

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('Método de pago', $resultado['mensaje']);
    }

    // ─── registrar(): métodos de pago válidos ────────────────────────

    /**
     * @dataProvider metodosValidosProvider
     */
    public function test_registrar_metodos_pago_validos(string $metodo): void
    {
        $mockModelo = $this->createMock(Venta::class);
        $mockModelo->method('registrar')
                   ->willReturn(['ok' => true, 'mensaje' => 'OK']);

        $controller = $this->crearController($mockModelo);
        $resultado  = $controller->registrar(
            [['id_producto' => 1, 'cantidad' => 1]],
            ['metodo_pago' => $metodo, 'monto_pagado' => 100],
            1
        );

        $this->assertTrue($resultado['ok'], "El método de pago $metodo debería ser válido");
    }

    public static function metodosValidosProvider(): array
    {
        return [
            'efectivo' => ['EFECTIVO'],
            'tarjeta'  => ['TARJETA'],
            'yape'     => ['YAPE'],
            'plin'     => ['PLIN'],
        ];
    }

    // ─── registrar(): consolidación ──────────────────────────────────

    public function test_registrar_consolida_lineas_del_mismo_producto(): void
    {
        $mockModelo = $this->createMock(Venta::class);
        $mockModelo->expects($this->once())
                   ->method('registrar')
                   ->with(
                       $this->callback(function ($items) {
                           return count($items) === 1
                               && $items[0]['id_producto'] === 1
                               && $items[0]['cantidad'] === 5; // 2 + 3
                       }),
                       $this->anything()
                   )
                   ->willReturn(['ok' => true, 'mensaje' => 'OK']);

        $controller = $this->crearController($mockModelo);
        $resultado  = $controller->registrar(
            [
                ['id_producto' => 1, 'cantidad' => 2],
                ['id_producto' => 1, 'cantidad' => 3],
            ],
            ['metodo_pago' => 'EFECTIVO', 'monto_pagado' => 100],
            1
        );

        $this->assertTrue($resultado['ok']);
    }

    public function test_registrar_multiples_productos_diferentes(): void
    {
        $mockModelo = $this->createMock(Venta::class);
        $mockModelo->expects($this->once())
                   ->method('registrar')
                   ->with(
                       $this->callback(function ($items) {
                           return count($items) === 2;
                       }),
                       $this->anything()
                   )
                   ->willReturn(['ok' => true, 'mensaje' => 'OK']);

        $controller = $this->crearController($mockModelo);
        $resultado  = $controller->registrar(
            [
                ['id_producto' => 1, 'cantidad' => 2],
                ['id_producto' => 2, 'cantidad' => 1],
            ],
            ['metodo_pago' => 'EFECTIVO', 'monto_pagado' => 100],
            1
        );

        $this->assertTrue($resultado['ok']);
    }

    // ─── registrar(): delegación exitosa ─────────────────────────────

    public function test_registrar_delega_correctamente_al_modelo(): void
    {
        $mockModelo = $this->createMock(Venta::class);
        $mockModelo->expects($this->once())
                   ->method('registrar')
                   ->willReturn([
                       'ok'          => true,
                       'mensaje'     => 'Venta registrada',
                       'id_venta'    => 42,
                       'comprobante' => 'B001-000001',
                   ]);

        $controller = $this->crearController($mockModelo);
        $resultado  = $controller->registrar(
            [['id_producto' => 1, 'cantidad' => 2]],
            [
                'metodo_pago'      => 'YAPE',
                'tipo_comprobante' => 'BOLETA',
                'monto_pagado'     => 50,
            ],
            1
        );

        $this->assertTrue($resultado['ok']);
    }

    // ─── anular() ────────────────────────────────────────────────────

    public function test_anular_motivo_menor_5_caracteres_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->anular(1, 'err', 1);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('5 caracteres', $resultado['mensaje']);
    }

    public function test_anular_motivo_vacio_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->anular(1, '', 1);

        $this->assertFalse($resultado['ok']);
    }

    public function test_anular_exitoso_delega_al_modelo(): void
    {
        $mockModelo = $this->createMock(Venta::class);
        $mockModelo->expects($this->once())
                   ->method('anular')
                   ->with(1, 'Producto devuelto por el cliente', 1)
                   ->willReturn(['ok' => true, 'mensaje' => 'Anulada']);

        $controller = $this->crearController($mockModelo);
        $resultado  = $controller->anular(1, 'Producto devuelto por el cliente', 1);

        $this->assertTrue($resultado['ok']);
    }
}
