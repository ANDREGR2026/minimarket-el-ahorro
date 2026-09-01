<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/controllers/UsuarioController.php';

/**
 * Pruebas del controlador de usuarios.
 *
 * Valida: nombre, formato de usuario, contraseña, protección del
 * último administrador y autodesactivación.
 */
class UsuarioControllerTest extends TestCase
{
    private function crearController($mockModelo = null): UsuarioController
    {
        $ref        = new ReflectionClass(UsuarioController::class);
        $controller = $ref->newInstanceWithoutConstructor();

        $mock = $mockModelo ?? $this->createMock(Usuario::class);

        $prop = $ref->getProperty('modelo');
        $prop->setAccessible(true);
        $prop->setValue($controller, $mock);

        return $controller;
    }

    // ─── guardar(): validaciones ─────────────────────────────────────

    public function test_guardar_nombre_vacio_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->guardar([
            'nombre' => '', 'usuario' => 'admin', 'password' => '123456', 'rol' => 'Cajero',
        ]);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('nombre', $resultado['mensaje']);
    }

    public function test_guardar_usuario_vacio_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->guardar([
            'nombre' => 'Juan', 'usuario' => '', 'password' => '123456', 'rol' => 'Cajero',
        ]);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('usuario', $resultado['mensaje']);
    }

    public function test_guardar_usuario_caracteres_especiales_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->guardar([
            'nombre' => 'Juan', 'usuario' => 'us@r!', 'password' => '123456', 'rol' => 'Cajero',
        ]);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('letras', $resultado['mensaje']);
    }

    public function test_guardar_usuario_menor_4_caracteres_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->guardar([
            'nombre' => 'Juan', 'usuario' => 'abc', 'password' => '123456', 'rol' => 'Cajero',
        ]);

        $this->assertFalse($resultado['ok']);
    }

    public function test_guardar_usuario_duplicado_retorna_error(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(true);

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'nombre' => 'Juan', 'usuario' => 'admin', 'password' => '123456', 'rol' => 'Cajero',
        ]);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('ya está en uso', $resultado['mensaje']);
    }

    // ─── Contraseña ──────────────────────────────────────────────────

    public function test_guardar_crear_sin_contrasena_retorna_error(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(false);

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'nombre' => 'Juan', 'usuario' => 'juan.perez', 'password' => '', 'rol' => 'Cajero',
        ]);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('contraseña', $resultado['mensaje']);
    }

    public function test_guardar_contrasena_menor_6_caracteres_retorna_error(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(false);

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'nombre' => 'Juan', 'usuario' => 'juan.perez', 'password' => '12345', 'rol' => 'Cajero',
        ]);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('6 caracteres', $resultado['mensaje']);
    }

    // ─── Creación ────────────────────────────────────────────────────

    public function test_guardar_crear_usuario_exitoso(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(false);
        $mock->expects($this->once())->method('crear');

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'nombre' => 'Juan Pérez', 'usuario' => 'juan.perez', 'password' => 'clave123', 'rol' => 'Cajero',
        ]);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('registrado', $resultado['mensaje']);
    }

    public function test_guardar_rol_invalido_se_normaliza_a_cajero(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(false);
        $mock->expects($this->once())->method('crear')
             ->with('Juan', 'juan.perez', $this->anything(), 'Cajero');

        $controller = $this->crearController($mock);
        $controller->guardar([
            'nombre' => 'Juan', 'usuario' => 'juan.perez', 'password' => 'clave123', 'rol' => 'SuperAdmin',
        ]);
    }

    // ─── Restricción de rol por actor ──────────────────────────────────

    public function test_guardar_administrador_actor_no_puede_crear_administrador(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(false);
        $mock->expects($this->once())->method('crear')
             ->with('Juan', 'juan.perez', $this->anything(), 'Cajero');

        $controller = $this->crearController($mock);
        $controller->guardar([
            'nombre' => 'Juan', 'usuario' => 'juan.perez', 'password' => 'clave123', 'rol' => 'Administrador',
        ], 'Administrador');
    }

    public function test_guardar_superadministrador_actor_si_puede_crear_administrador(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(false);
        $mock->expects($this->once())->method('crear')
             ->with('Juan', 'juan.perez', $this->anything(), 'Administrador');

        $controller = $this->crearController($mock);
        $controller->guardar([
            'nombre' => 'Juan', 'usuario' => 'juan.perez', 'password' => 'clave123', 'rol' => 'Administrador',
        ], 'SuperAdministrador');
    }

    public function test_guardar_administrador_actor_si_puede_crear_almacenero(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(false);
        $mock->expects($this->once())->method('crear')
             ->with('Juan', 'juan.perez', $this->anything(), 'Almacenero');

        $controller = $this->crearController($mock);
        $controller->guardar([
            'nombre' => 'Juan', 'usuario' => 'juan.perez', 'password' => 'clave123', 'rol' => 'Almacenero',
        ], 'Administrador');
    }

    public function test_guardar_superadministrador_actor_si_puede_crear_almacenero(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(false);
        $mock->expects($this->once())->method('crear')
             ->with('Juan', 'juan.perez', $this->anything(), 'Almacenero');

        $controller = $this->crearController($mock);
        $controller->guardar([
            'nombre' => 'Juan', 'usuario' => 'juan.perez', 'password' => 'clave123', 'rol' => 'Almacenero',
        ], 'SuperAdministrador');
    }

    public function test_guardar_nadie_puede_crear_superadministrador(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(false);
        $mock->expects($this->once())->method('crear')
             ->with('Juan', 'juan.perez', $this->anything(), 'Cajero');

        $controller = $this->crearController($mock);
        $controller->guardar([
            'nombre' => 'Juan', 'usuario' => 'juan.perez', 'password' => 'clave123', 'rol' => 'SuperAdministrador',
        ], 'SuperAdministrador');
    }

    public function test_guardar_rechaza_editar_cuenta_superadministrador(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorId')->willReturn(['id_usuario' => 9, 'rol' => 'SuperAdministrador']);
        $mock->expects($this->never())->method('actualizar');

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'id_usuario' => 9, 'nombre' => 'Nuevo Nombre', 'usuario' => 'superadmin',
            'password' => '', 'rol' => 'Cajero', 'estado' => 1,
        ], 'SuperAdministrador');

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('SuperAdministrador', $resultado['mensaje']);
    }

    public function test_guardar_administrador_actor_no_degrada_a_otro_administrador(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorId')->willReturn(['id_usuario' => 5, 'rol' => 'Administrador']);
        $mock->method('usuarioExiste')->willReturn(false);
        $mock->method('contarAdministradoresActivos')->willReturn(2);
        $mock->expects($this->once())->method('actualizar')
             ->with(5, 'Otro Admin', 'otro.admin', 'Administrador', 1, '');

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'id_usuario' => 5, 'nombre' => 'Otro Admin', 'usuario' => 'otro.admin',
            'password' => '', 'rol' => 'Cajero', 'estado' => 1,
        ], 'Administrador');

        $this->assertTrue($resultado['ok']);
    }

    // ─── Actualización ───────────────────────────────────────────────

    public function test_guardar_actualizar_sin_cambiar_contrasena(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(false);
        $mock->method('contarAdministradoresActivos')->willReturn(1);
        $mock->expects($this->once())->method('actualizar');

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'id_usuario' => 1, 'nombre' => 'Juan', 'usuario' => 'juan.perez',
            'password' => '', 'rol' => 'Cajero', 'estado' => 1,
        ]);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('actualizado', $resultado['mensaje']);
    }

    // ─── Protección del último administrador ─────────────────────────

    public function test_guardar_protege_ultimo_administrador_al_cambiar_rol(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(false);
        $mock->method('contarAdministradoresActivos')->willReturn(0);

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'id_usuario' => 1, 'nombre' => 'Admin', 'usuario' => 'admin',
            'password' => '', 'rol' => 'Cajero', 'estado' => 1,
        ]);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('administrador activo', $resultado['mensaje']);
    }

    public function test_guardar_protege_ultimo_administrador_al_desactivar(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('usuarioExiste')->willReturn(false);
        $mock->method('contarAdministradoresActivos')->willReturn(0);

        $controller = $this->crearController($mock);
        $resultado  = $controller->guardar([
            'id_usuario' => 1, 'nombre' => 'Admin', 'usuario' => 'admin',
            'password' => '', 'rol' => 'Administrador', 'estado' => 0,
        ]);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('administrador activo', $resultado['mensaje']);
    }

    // ─── cambiarEstado() ─────────────────────────────────────────────

    public function test_cambiar_estado_desactivar_propio_usuario_retorna_error(): void
    {
        $controller = $this->crearController();
        $resultado  = $controller->cambiarEstado(5, 0, 5); // mismo ID

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('su propio', $resultado['mensaje']);
    }

    public function test_cambiar_estado_protege_ultimo_admin(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('contarAdministradoresActivos')->willReturn(0);

        $controller = $this->crearController($mock);
        $resultado  = $controller->cambiarEstado(1, 0, 2);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('administrador activo', $resultado['mensaje']);
    }

    public function test_cambiar_estado_rechaza_cuenta_superadministrador(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('obtenerPorId')->willReturn(['id_usuario' => 9, 'rol' => 'SuperAdministrador']);
        $mock->expects($this->never())->method('cambiarEstado');

        $controller = $this->crearController($mock);
        $resultado  = $controller->cambiarEstado(9, 0, 2);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('SuperAdministrador', $resultado['mensaje']);
    }

    public function test_cambiar_estado_activar_exitoso(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->expects($this->once())->method('cambiarEstado')->with(1, 1);

        $controller = $this->crearController($mock);
        $resultado  = $controller->cambiarEstado(1, 1, 2);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('activado', $resultado['mensaje']);
    }

    public function test_cambiar_estado_desactivar_exitoso(): void
    {
        $mock = $this->createMock(Usuario::class);
        $mock->method('contarAdministradoresActivos')->willReturn(1);
        $mock->expects($this->once())->method('cambiarEstado')->with(1, 0);

        $controller = $this->crearController($mock);
        $resultado  = $controller->cambiarEstado(1, 0, 2);

        $this->assertTrue($resultado['ok']);
        $this->assertStringContainsString('desactivado', $resultado['mensaje']);
    }
}
