<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/controllers/ClienteController.php';

/**
 * Pruebas del controlador de clientes.
 *
 * Valida las reglas de negocio: DNI de 8 dígitos, RUC de 11 dígitos,
 * campos requeridos según tipo de documento, formato de email y teléfono.
 */
class ClienteControllerTest extends TestCase
{
    private function crearController($mockModelo = null): ClienteController
    {
        $ref        = new ReflectionClass(ClienteController::class);
        $controller = $ref->newInstanceWithoutConstructor();

        $mock = $mockModelo ?? $this->createMock(Cliente::class);

        $prop = $ref->getProperty('modelo');
        $prop->setAccessible(true);
        $prop->setValue($controller, $mock);

        return $controller;
    }

    private function datosValidosDni(): array
    {
        return [
            'tipo_documento'   => 'DNI',
            'numero_documento' => '12345678',
            'nombres'          => 'Juan',
            'apellidos'        => 'Pérez',
            'razon_social'     => '',
            'telefono'         => '',
            'email'            => '',
            'direccion'        => '',
        ];
    }

    private function datosValidosRuc(): array
    {
        return [
            'tipo_documento'   => 'RUC',
            'numero_documento' => '20123456789',
            'nombres'          => '',
            'apellidos'        => '',
            'razon_social'     => 'Empresa SAC',
            'telefono'         => '',
            'email'            => '',
            'direccion'        => '',
        ];
    }

    // ─── Validaciones de documento ───────────────────────────────────

    public function test_guardar_documento_vacio_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos      = $this->datosValidosDni();
        $datos['numero_documento'] = '';

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('obligatorio', $resultado['mensaje']);
    }

    public function test_guardar_documento_con_letras_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos      = $this->datosValidosDni();
        $datos['numero_documento'] = '1234ABCD';

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('dígitos', $resultado['mensaje']);
    }

    public function test_guardar_dni_longitud_invalida_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos      = $this->datosValidosDni();
        $datos['numero_documento'] = '12345'; // 5 dígitos en vez de 8

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('8 dígitos', $resultado['mensaje']);
    }

    public function test_guardar_ruc_longitud_invalida_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos      = $this->datosValidosRuc();
        $datos['numero_documento'] = '2012345'; // 7 dígitos en vez de 11

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('11 dígitos', $resultado['mensaje']);
    }

    // ─── Validaciones de campos según tipo de documento ──────────────

    public function test_guardar_dni_sin_nombres_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos      = $this->datosValidosDni();
        $datos['nombres'] = '';

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('nombres', $resultado['mensaje']);
    }

    public function test_guardar_dni_sin_apellidos_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos      = $this->datosValidosDni();
        $datos['apellidos'] = '';

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('nombres', $resultado['mensaje']);
    }

    public function test_guardar_ruc_sin_razon_social_retorna_error(): void
    {
        $controller = $this->crearController();
        $datos      = $this->datosValidosRuc();
        $datos['razon_social'] = '';

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('razón social', $resultado['mensaje']);
    }

    // ─── Validaciones de contacto ────────────────────────────────────

    public function test_guardar_email_invalido_retorna_error(): void
    {
        $mock = $this->createMock(Cliente::class);
        $mock->method('documentoExiste')->willReturn(false);

        $controller = $this->crearController($mock);
        $datos      = $this->datosValidosDni();
        $datos['email'] = 'correo-invalido';

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('correo', $resultado['mensaje']);
    }

    public function test_guardar_telefono_invalido_retorna_error(): void
    {
        $mock = $this->createMock(Cliente::class);
        $mock->method('documentoExiste')->willReturn(false);

        $controller = $this->crearController($mock);
        $datos      = $this->datosValidosDni();
        $datos['telefono'] = 'abc-xyz';

        $resultado = $controller->guardar($datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('teléfono', $resultado['mensaje']);
    }

    // ─── Documento duplicado ─────────────────────────────────────────

    public function test_guardar_documento_duplicado_retorna_error(): void
    {
        $mock = $this->createMock(Cliente::class);
        $mock->method('documentoExiste')->willReturn(true);

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar($this->datosValidosDni());

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('Ya existe', $resultado['mensaje']);
    }

    // ─── Creación exitosa ────────────────────────────────────────────

    public function test_guardar_crear_cliente_dni_limpia_razon_social(): void
    {
        $mock = $this->createMock(Cliente::class);
        $mock->method('documentoExiste')->willReturn(false);
        $mock->expects($this->once())->method('crear')
             ->with($this->callback(function ($datos) {
                 return $datos['razon_social'] === null
                     && $datos['nombres'] === 'Juan'
                     && $datos['apellidos'] === 'Pérez';
             }));

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar($this->datosValidosDni());

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('registrado', $resultado['mensaje']);
    }

    public function test_guardar_crear_cliente_ruc_limpia_nombres_y_apellidos(): void
    {
        $mock = $this->createMock(Cliente::class);
        $mock->method('documentoExiste')->willReturn(false);
        $mock->expects($this->once())->method('crear')
             ->with($this->callback(function ($datos) {
                 return $datos['nombres'] === null
                     && $datos['apellidos'] === null
                     && $datos['razon_social'] === 'Empresa SAC';
             }));

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar($this->datosValidosRuc());

        $this->assertTrue($resultado['ok']);
    }

    public function test_guardar_campos_opcionales_vacios_se_convierten_a_null(): void
    {
        $mock = $this->createMock(Cliente::class);
        $mock->method('documentoExiste')->willReturn(false);
        $mock->expects($this->once())->method('crear')
             ->with($this->callback(function ($datos) {
                 return $datos['telefono'] === null
                     && $datos['email'] === null
                     && $datos['direccion'] === null;
             }));

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar($this->datosValidosDni());

        $this->assertTrue($resultado['ok']);
    }

    // ─── Actualización ───────────────────────────────────────────────

    public function test_guardar_actualizar_cliente_exitoso(): void
    {
        $mock = $this->createMock(Cliente::class);
        $mock->method('documentoExiste')->willReturn(false);
        $mock->expects($this->once())->method('actualizar');

        $controller = $this->crearController($mock);
        $datos      = $this->datosValidosDni();
        $datos['id_cliente'] = 1;
        $datos['estado']     = 1;

        $resultado = $controller->guardar($datos);
        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('actualizado', $resultado['mensaje']);
    }

    // ─── cambiarEstado() ─────────────────────────────────────────────

    public function test_cambiar_estado_activar(): void
    {
        $mock = $this->createMock(Cliente::class);
        $mock->expects($this->once())->method('cambiarEstado')->with(1, 1);

        $controller = $this->crearController($mock);
        $resultado  = $controller->cambiarEstado(1, 1);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('activado', $resultado['mensaje']);
    }

    public function test_cambiar_estado_desactivar(): void
    {
        $mock = $this->createMock(Cliente::class);
        $mock->expects($this->once())->method('cambiarEstado')->with(1, 0);

        $controller = $this->crearController($mock);
        $resultado  = $controller->cambiarEstado(1, 0);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('desactivado', $resultado['mensaje']);
    }
}
