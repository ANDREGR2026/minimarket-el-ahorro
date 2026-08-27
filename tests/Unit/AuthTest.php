<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/middleware/Auth.php';

/**
 * Pruebas del middleware de autenticación.
 *
 * Valida los métodos estáticos de lectura de sesión:
 * invitado(), usuario(), id(), nombre(), rol(), esAdministrador(), logout().
 *
 * No se prueban check() ni checkRole() porque llaman a header()+exit,
 * lo que detendría el proceso de PHPUnit.
 */
class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
    }

    // ─── invitado() ──────────────────────────────────────────────────

    public function test_invitado_verdadero_sin_sesion(): void
    {
        unset($_SESSION['usuario']);
        $this->assertTrue(Auth::invitado());
    }

    public function test_invitado_falso_con_sesion(): void
    {
        $_SESSION['usuario'] = [
            'id_usuario' => 1, 'nombre' => 'Admin',
            'usuario' => 'admin', 'rol' => 'Administrador',
        ];
        $this->assertFalse(Auth::invitado());
    }

    // ─── usuario() ──────────────────────────────────────────────────

    public function test_usuario_devuelve_datos_de_sesion(): void
    {
        $_SESSION['usuario'] = [
            'id_usuario' => 1, 'nombre' => 'Admin',
            'usuario' => 'admin', 'rol' => 'Administrador',
        ];

        $usuario = Auth::usuario();
        $this->assertSame(1, $usuario['id_usuario']);
        $this->assertSame('Admin', $usuario['nombre']);
    }

    public function test_usuario_devuelve_null_sin_sesion(): void
    {
        $this->assertNull(Auth::usuario());
    }

    // ─── id() ────────────────────────────────────────────────────────

    public function test_id_devuelve_id_del_usuario(): void
    {
        $_SESSION['usuario'] = [
            'id_usuario' => 5, 'nombre' => 'Test',
            'usuario' => 'test', 'rol' => 'Cajero',
        ];
        $this->assertSame(5, Auth::id());
    }

    public function test_id_devuelve_null_sin_sesion(): void
    {
        $this->assertNull(Auth::id());
    }

    // ─── rol() ───────────────────────────────────────────────────────

    public function test_rol_devuelve_rol_del_usuario(): void
    {
        $_SESSION['usuario'] = [
            'id_usuario' => 1, 'nombre' => 'Admin',
            'usuario' => 'admin', 'rol' => 'Administrador',
        ];
        $this->assertSame('Administrador', Auth::rol());
    }

    public function test_rol_devuelve_null_sin_sesion(): void
    {
        $this->assertNull(Auth::rol());
    }

    // ─── nombre() ────────────────────────────────────────────────────

    public function test_nombre_devuelve_nombre_del_usuario(): void
    {
        $_SESSION['usuario'] = [
            'id_usuario' => 1, 'nombre' => 'Juan Pérez',
            'usuario' => 'juan', 'rol' => 'Cajero',
        ];
        $this->assertSame('Juan Pérez', Auth::nombre());
    }

    public function test_nombre_devuelve_vacio_sin_sesion(): void
    {
        $this->assertSame('', Auth::nombre());
    }

    // ─── esAdministrador() ───────────────────────────────────────────

    public function test_es_administrador_verdadero(): void
    {
        $_SESSION['usuario'] = [
            'id_usuario' => 1, 'nombre' => 'Admin',
            'usuario' => 'admin', 'rol' => 'Administrador',
        ];
        $this->assertTrue(Auth::esAdministrador());
    }

    public function test_es_administrador_falso_para_cajero(): void
    {
        $_SESSION['usuario'] = [
            'id_usuario' => 2, 'nombre' => 'Cajero',
            'usuario' => 'cajero', 'rol' => 'Cajero',
        ];
        $this->assertFalse(Auth::esAdministrador());
    }

    public function test_es_administrador_falso_sin_sesion(): void
    {
        $this->assertFalse(Auth::esAdministrador());
    }

    // ─── logout() ────────────────────────────────────────────────────

    public function test_logout_limpia_sesion(): void
    {
        $_SESSION['usuario'] = [
            'id_usuario' => 1, 'nombre' => 'Admin',
            'usuario' => 'admin', 'rol' => 'Administrador',
        ];

        Auth::logout();

        $this->assertEmpty($_SESSION);
    }
}
