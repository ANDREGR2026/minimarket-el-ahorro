<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/controllers/RecuperacionController.php';

/**
 * Pruebas del flujo de recuperación de contraseña por correo.
 *
 * Valida: mensaje genérico (exista o no el correo), token expirado,
 * token ya consumido, contraseñas que no coinciden y el caso exitoso.
 */
class RecuperacionControllerTest extends TestCase
{
    private function crearController($mockModelo = null): RecuperacionController
    {
        $ref        = new ReflectionClass(RecuperacionController::class);
        $controller = $ref->newInstanceWithoutConstructor();

        $mock = $mockModelo ?? $this->createMock(Usuario::class);

        $prop = $ref->getProperty('modelo');
        $prop->setAccessible(true);
        $prop->setValue($controller, $mock);

        return $controller;
    }

    // ─── solicitar() ──────────────────────────────────────────────────

    public function test_solicitar_email_invalido_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->solicitar('no-es-un-correo');

        $this->assertFalse($resultado['ok']);
    }

    public function test_solicitar_email_inexistente_retorna_mensaje_generico(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorEmail')->willReturn(false);
        $mock->expects($this->never())->method('guardarTokenReset');

        $controller = $this->crearController($mock);
        $resultado  = $controller->solicitar('noexiste@correo.com');

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('Si el correo está registrado', $resultado['mensaje']);
    }

    public function test_solicitar_email_existente_genera_token_y_mismo_mensaje_generico(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorEmail')->willReturn([
            'id_usuario' => 3,
            'nombre'     => 'Admin',
            'email'      => 'admin@correo.com',
        ]);
        $mock->expects($this->once())->method('guardarTokenReset')
            ->with(3, $this->isType('string'), $this->isType('string'));

        $controller = $this->crearController($mock);
        $resultado  = $controller->solicitar('admin@correo.com');

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('Si el correo está registrado', $resultado['mensaje']);
    }

    // ─── validarToken() ───────────────────────────────────────────────

    public function test_validar_token_vacio_es_invalido(): void
    {
        $controller = $this->crearController();

        $this->assertFalse($controller->validarToken(''));
    }

    public function test_validar_token_expirado_o_inexistente_es_invalido(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorTokenReset')->willReturn(false);

        $controller = $this->crearController($mock);

        $this->assertFalse($controller->validarToken('token-cualquiera'));
    }

    public function test_validar_token_vigente_es_valido(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorTokenReset')->willReturn(['id_usuario' => 1]);

        $controller = $this->crearController($mock);

        $this->assertTrue($controller->validarToken('token-vigente'));
    }

    // ─── restablecer() ────────────────────────────────────────────────

    public function test_restablecer_token_invalido_retorna_error(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorTokenReset')->willReturn(false);
        $mock->expects($this->never())->method('actualizarPassword');

        $controller = $this->crearController($mock);
        $resultado  = $controller->restablecer('token-vencido', 'nueva123', 'nueva123');

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('inválido', $resultado['mensaje']);
    }

    public function test_restablecer_password_corta_retorna_error(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorTokenReset')->willReturn(['id_usuario' => 1]);
        $mock->expects($this->never())->method('actualizarPassword');

        $controller = $this->crearController($mock);
        $resultado  = $controller->restablecer('token-valido', '123', '123');

        $this->assertFalse($resultado['ok']);
    }

    public function test_restablecer_passwords_no_coinciden_retorna_error(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorTokenReset')->willReturn(['id_usuario' => 1]);
        $mock->expects($this->never())->method('actualizarPassword');

        $controller = $this->crearController($mock);
        $resultado  = $controller->restablecer('token-valido', 'clave123', 'clave456');

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('no coinciden', $resultado['mensaje']);
    }

    public function test_restablecer_exitoso_actualiza_password_y_limpia_token(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorTokenReset')->willReturn(['id_usuario' => 7]);
        $mock->expects($this->once())->method('actualizarPassword')->with(7, 'claveNueva1');
        $mock->expects($this->once())->method('limpiarTokenReset')->with(7);

        $controller = $this->crearController($mock);
        $resultado  = $controller->restablecer('token-valido', 'claveNueva1', 'claveNueva1');

        $this->assertTrue($resultado['ok']);
    }
}
