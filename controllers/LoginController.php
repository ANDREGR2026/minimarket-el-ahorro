<?php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../middleware/Auth.php';

/**
 * Autenticacion de usuarios: inicio y cierre de sesion.
 */
class LoginController
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new Usuario();
    }

    /**
     * Valida las credenciales y abre la sesion.
     *
     * @return array ['ok' => bool, 'mensaje' => string, 'destino' => string]
     */
    public function autenticar($usuario, $password)
    {
        $usuario  = trim($usuario);
        $password = (string) $password;

        if ($usuario === '' || $password === '') {
            return ['ok' => false, 'mensaje' => 'Ingrese su usuario y su contraseña.'];
        }

        $registro = $this->modelo->obtenerPorUsuario($usuario);

        // Mensaje generico a proposito: no revela si el usuario existe o no.
        if (!$registro || !password_verify($password, $registro['password'])) {
            return ['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos.'];
        }

        // Evita la fijacion de sesion
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario' => (int) $registro['id_usuario'],
            'nombre'     => $registro['nombre'],
            'usuario'    => $registro['usuario'],
            'rol'        => $registro['rol'],
        ];

        $destino = Auth::esAdministrador() ? 'dashboard' : 'pos';

        return ['ok' => true, 'mensaje' => '', 'destino' => $destino];
    }

    public function cerrarSesion()
    {
        Auth::logout();
    }
}
