<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once ROOT_PATH . '/models/Usuario.php';

class UsuarioModelTest extends DatabaseTestCase
{
    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = new Usuario();
    }

    public function test_crear_y_obtener_por_usuario(): void
    {
        $id = $this->usuario->crear('Admin Test', 'admin.test', 'hash123', 'Administrador');
        $this->assertGreaterThan(0, $id);

        $userDB = $this->usuario->obtenerPorUsuario('admin.test');
        $this->assertEquals('Admin Test', $userDB['nombre']);
        $this->assertTrue(password_verify('hash123', $userDB['password']));
        $this->assertEquals('Administrador', $userDB['rol']);
    }

    public function test_usuario_existe(): void
    {
        $this->usuario->crear('User 1', 'user1', 'hash', 'Cajero');

        $this->assertTrue($this->usuario->usuarioExiste('user1'));
        $this->assertFalse($this->usuario->usuarioExiste('user2'));
    }

    public function test_contar_administradores_activos(): void
    {
        $inicial = $this->usuario->contarAdministradoresActivos();

        $id1 = $this->usuario->crear('Admin 1', 'admin1', 'hash', 'Administrador');
        $id2 = $this->usuario->crear('Admin 2', 'admin2', 'hash', 'Administrador');
        
        $this->assertEquals($inicial + 2, $this->usuario->contarAdministradoresActivos());

        $this->usuario->cambiarEstado($id1, 0);
        $this->assertEquals($inicial + 1, $this->usuario->contarAdministradoresActivos());
    }

    public function test_actualizar(): void
    {
        $id = $this->usuario->crear('Old Name', 'old.user', 'hash', 'Cajero');

        $this->usuario->actualizar($id, 'New Name', 'new.user', 'Administrador', 1, 'newhash');

        $user = $this->usuario->obtenerPorId($id);
        // $user de obtenerPorId NO retorna password, solo id, nombre, usuario, rol, estado.
        // Así que debemos verificar el password con obtenerPorUsuario
        $userDB = $this->usuario->obtenerPorUsuario('new.user');
        
        $this->assertEquals('New Name', $user['nombre']);
        $this->assertEquals('new.user', $user['usuario']);
        $this->assertTrue(password_verify('newhash', $userDB['password']));
        $this->assertEquals('Administrador', $user['rol']);
    }
}
