<?php

require_once __DIR__ . '/../models/Usuario.php';

/**
 * Mantenimiento de los usuarios del sistema. Accesible a Administrador y
 * SuperAdministrador; solo este último puede conceder el rol Administrador.
 */
class UsuarioController
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new Usuario();
    }

    public function listar($busqueda = '', $rol = '')
    {
        return $this->modelo->listar($busqueda, $rol);
    }

    public function obtener($id)
    {
        return $this->modelo->obtenerPorId($id);
    }

    /**
     * @param array  $datos     datos del formulario
     * @param string $rolActor  rol de quien realiza la operación; solo un
     *                          SuperAdministrador puede conceder el rol Administrador.
     *                          Nadie puede conceder SuperAdministrador desde aquí.
     */
    public function guardar($datos, $rolActor = 'SuperAdministrador')
    {
        $id       = !empty($datos['id_usuario']) ? (int) $datos['id_usuario'] : null;
        $nombre   = trim($datos['nombre'] ?? '');
        $usuario  = trim($datos['usuario'] ?? '');
        $password = $datos['password'] ?? '';
        $rolSolicitado = $datos['rol'] ?? 'Cajero';
        $estado   = isset($datos['estado']) ? (int) $datos['estado'] : 1;

        $existente = null;

        if ($id !== null) {
            $existente = $this->modelo->obtenerPorId($id);

            if ($existente && $existente['rol'] === 'SuperAdministrador') {
                return ['ok' => false, 'mensaje' => 'No se puede modificar la cuenta SuperAdministrador.'];
            }
        }

        if ($rolActor === 'SuperAdministrador') {
            // El SuperAdministrador decide libremente entre Cajero y Administrador
            $rol = $rolSolicitado === 'Administrador' ? 'Administrador' : 'Cajero';
        } elseif ($existente && $existente['rol'] === 'Administrador') {
            // Un Administrador no puede ascender ni degradar a otro Administrador:
            // solo puede tocar sus demas datos (nombre, usuario, contrasena, estado).
            $rol = 'Administrador';
        } else {
            $rol = 'Cajero';
        }

        if ($nombre === '') {
            return ['ok' => false, 'mensaje' => 'El nombre completo es obligatorio.'];
        }

        if ($usuario === '') {
            return ['ok' => false, 'mensaje' => 'El nombre de usuario es obligatorio.'];
        }

        if (!preg_match('/^[a-zA-Z0-9._]{4,50}$/', $usuario)) {
            return [
                'ok'      => false,
                'mensaje' => 'El usuario debe tener entre 4 y 50 caracteres y solo letras, números, punto o guión bajo.',
            ];
        }

        if ($this->modelo->usuarioExiste($usuario, $id)) {
            return ['ok' => false, 'mensaje' => 'Ese nombre de usuario ya está en uso.'];
        }

        // Al crear siempre se pide contrasena; al editar solo si se quiere cambiar
        if ($id === null && $password === '') {
            return ['ok' => false, 'mensaje' => 'Ingrese una contraseña para el nuevo usuario.'];
        }

        if ($password !== '' && strlen($password) < 6) {
            return ['ok' => false, 'mensaje' => 'La contraseña debe tener al menos 6 caracteres.'];
        }

        if ($id === null) {
            $this->modelo->crear($nombre, $usuario, $password, $rol);

            return ['ok' => true, 'mensaje' => 'Usuario registrado correctamente.'];
        }

        // El sistema no puede quedarse sin ningun administrador activo
        $perderiaAdmin = ($rol !== 'Administrador' || $estado === 0);

        if ($perderiaAdmin && $this->modelo->contarAdministradoresActivos($id) === 0) {
            return [
                'ok'      => false,
                'mensaje' => 'Debe existir al menos un administrador activo en el sistema.',
            ];
        }

        $this->modelo->actualizar($id, $nombre, $usuario, $rol, $estado, $password);

        return ['ok' => true, 'mensaje' => 'Usuario actualizado correctamente.'];
    }

    public function cambiarEstado($id, $estado, $idUsuarioActual)
    {
        $id     = (int) $id;
        $estado = (int) $estado;

        if ($id === (int) $idUsuarioActual && $estado === 0) {
            return ['ok' => false, 'mensaje' => 'No puede desactivar su propio usuario.'];
        }

        $existente = $this->modelo->obtenerPorId($id);

        if ($existente && $existente['rol'] === 'SuperAdministrador') {
            return ['ok' => false, 'mensaje' => 'No se puede modificar la cuenta SuperAdministrador.'];
        }

        if ($estado === 0 && $this->modelo->contarAdministradoresActivos($id) === 0) {
            return [
                'ok'      => false,
                'mensaje' => 'Debe existir al menos un administrador activo en el sistema.',
            ];
        }

        $this->modelo->cambiarEstado($id, $estado);

        return [
            'ok'      => true,
            'mensaje' => $estado === 1 ? 'Usuario activado.' : 'Usuario desactivado.',
        ];
    }
}
