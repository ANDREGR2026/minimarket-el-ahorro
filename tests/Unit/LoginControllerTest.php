<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/controllers/LoginController.php';

/**
 * Pruebas del controlador de autenticación.
 *
 * Valida: campos vacíos, usuario inexistente (mensaje genérico),
 * contraseña incorrecta (mensaje genérico), login exitoso con
 * destino según rol (dashboard o pos).
 */
class LoginControllerTest extends TestCase
{
    private function crearController($mockModelo = null): LoginController
    {
        $ref        = new ReflectionClass(LoginController::class);
        $controller = $ref->newInstanceWithoutConstructor();

        $mock = $mockModelo ?? $this->createMock(Usuario::class);

        $prop = $ref->getProperty('modelo');
        $prop->setAccessible(true);
        $prop->setValue($controller, $mock);

        return $controller;
    }

    protected function setUp(): void
    {
        // Reiniciar sesión si fue destruida por un test anterior
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
    }

    // ─── Campos vacíos ──────────────────────────────────────────────

    public function test_autenticar_ambos_campos_vacios_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->autenticar('', '');

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('usuario', $resultado['mensaje']);
    }

    public function test_autenticar_usuario_vacio_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->autenticar('', 'password123');

        $this->assertFalse($resultado['ok']);
    }

    public function test_autenticar_password_vacio_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->autenticar('admin', '');

        $this->assertFalse($resultado['ok']);
    }

    // ─── Credenciales incorrectas (mensaje genérico) ─────────────────

    public function test_autenticar_usuario_inexistente_retorna_error_generico(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorUsuario')->willReturn(false);

        $controller = $this->crearController($mock);
        $resultado  = $controller->autenticar('noexiste', 'password123');

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('incorrectos', $resultado['mensaje']);
    }

    public function test_autenticar_contrasena_incorrecta_retorna_error_generico(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorUsuario')->willReturn([
            'id_usuario' => 1,
            'nombre'     => 'Admin',
            'usuario'    => 'admin',
            'password'   => password_hash('clave_correcta', PASSWORD_DEFAULT),
            'rol'        => 'Administrador',
        ]);

        $controller = $this->crearController($mock);
        $resultado  = $controller->autenticar('admin', 'clave_incorrecta');

        $this->assertFalse($resultado['ok']);
        // Mismo mensaje que usuario inexistente: no revela si el usuario existe
        $this->assertStringContainsString('incorrectos', $resultado['mensaje']);
    }

    // ─── Login exitoso ───────────────────────────────────────────────

    public function test_autenticar_administrador_destino_dashboard(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorUsuario')->willReturn([
            'id_usuario' => 1,
            'nombre'     => 'Admin',
            'usuario'    => 'admin',
            'password'   => password_hash('clave123', PASSWORD_DEFAULT),
            'rol'        => 'Administrador',
        ]);

        $controller = $this->crearController($mock);
        $resultado  = $controller->autenticar('admin', 'clave123');

        $this->assertTrue($resultado['ok']);
        $this->assertSame('dashboard', $resultado['destino']);
        $this->assertSame(1, $_SESSION['usuario']['id_usuario']);
        $this->assertSame('Administrador', $_SESSION['usuario']['rol']);
    }

    public function test_autenticar_superadministrador_destino_dashboard(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorUsuario')->willReturn([
            'id_usuario' => 9,
            'nombre'     => 'Super Administrador',
            'usuario'    => 'superadmin',
            'password'   => password_hash('super123', PASSWORD_DEFAULT),
            'rol'        => 'SuperAdministrador',
        ]);

        $controller = $this->crearController($mock);
        $resultado  = $controller->autenticar('superadmin', 'super123');

        $this->assertTrue($resultado['ok']);
        $this->assertSame('dashboard', $resultado['destino']);
    }

    public function test_autenticar_cajero_destino_pos(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorUsuario')->willReturn([
            'id_usuario' => 2,
            'nombre'     => 'María López',
            'usuario'    => 'cajero1',
            'password'   => password_hash('clave456', PASSWORD_DEFAULT),
            'rol'        => 'Cajero',
        ]);

        $controller = $this->crearController($mock);
        $resultado  = $controller->autenticar('cajero1', 'clave456');

        $this->assertTrue($resultado['ok']);
        $this->assertSame('pos', $resultado['destino']);
        $this->assertSame(2, $_SESSION['usuario']['id_usuario']);
    }

    public function test_autenticar_exitoso_guarda_datos_en_sesion(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorUsuario')->willReturn([
            'id_usuario' => 5,
            'nombre'     => 'Pedro García',
            'usuario'    => 'pedro.garcia',
            'password'   => password_hash('segura789', PASSWORD_DEFAULT),
            'rol'        => 'Cajero',
        ]);

        $controller = $this->crearController($mock);
        $controller->autenticar('pedro.garcia', 'segura789');

        $this->assertArrayHasKey('usuario', $_SESSION);
        $this->assertSame(5, $_SESSION['usuario']['id_usuario']);
        $this->assertSame('Pedro García', $_SESSION['usuario']['nombre']);
        $this->assertSame('pedro.garcia', $_SESSION['usuario']['usuario']);
        $this->assertSame('Cajero', $_SESSION['usuario']['rol']);
    }
}
