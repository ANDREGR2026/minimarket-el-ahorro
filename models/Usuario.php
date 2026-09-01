<?php

require_once __DIR__ . '/../database/Conexion.php';

/**
 * Acceso a datos de los usuarios del sistema.
 */
class Usuario
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    /**
     * Busca un usuario activo por su nombre de usuario.
     */
    public function obtenerPorUsuario($usuario)
    {
        $sql = "
            SELECT id_usuario, nombre, usuario, password, rol, estado
            FROM usuarios
            WHERE usuario = :usuario AND estado = 1
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':usuario' => $usuario]);

        return $stmt->fetch();
    }

    public function obtenerPorId($id)
    {
        $sql = "
            SELECT id_usuario, nombre, usuario, rol, estado, created_at
            FROM usuarios
            WHERE id_usuario = :id
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    /**
     * Lista los usuarios, opcionalmente filtrados por nombre o rol.
     */
    public function listar($busqueda = '', $rol = '')
    {
        $sql = "
            SELECT id_usuario, nombre, usuario, rol, estado, created_at
            FROM usuarios
            WHERE 1 = 1
        ";
        $parametros = [];

        if ($busqueda !== '') {
            // Cada placeholder debe ser único: las sentencias no son emuladas
            $sql .= " AND (nombre LIKE :busqueda_nombre OR usuario LIKE :busqueda_usuario)";
            $parametros[':busqueda_nombre']  = '%' . $busqueda . '%';
            $parametros[':busqueda_usuario'] = '%' . $busqueda . '%';
        }

        if ($rol !== '') {
            $sql .= " AND rol = :rol";
            $parametros[':rol'] = $rol;
        }

        $sql .= " ORDER BY nombre ASC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Indica si el nombre de usuario ya esta tomado por otro registro.
     */
    public function usuarioExiste($usuario, $idExcluir = null)
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario";
        $parametros = [':usuario' => $usuario];

        if ($idExcluir !== null) {
            $sql .= " AND id_usuario <> :id";
            $parametros[':id'] = $idExcluir;
        }

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function crear($nombre, $usuario, $password, $rol)
    {
        $sql = "
            INSERT INTO usuarios (nombre, usuario, password, rol, estado)
            VALUES (:nombre, :usuario, :password, :rol, 1)
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':nombre'   => $nombre,
            ':usuario'  => $usuario,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':rol'      => $rol,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    /**
     * Actualiza los datos del usuario. La contrasena solo cambia si se envia.
     */
    public function actualizar($id, $nombre, $usuario, $rol, $estado, $password = null)
    {
        $sql = "
            UPDATE usuarios
            SET nombre = :nombre, usuario = :usuario, rol = :rol, estado = :estado
        ";
        $parametros = [
            ':nombre'  => $nombre,
            ':usuario' => $usuario,
            ':rol'     => $rol,
            ':estado'  => $estado,
            ':id'      => $id,
        ];

        if ($password !== null && $password !== '') {
            $sql .= ", password = :password";
            $parametros[':password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id_usuario = :id";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute($parametros);
    }

    /**
     * Baja logica: el usuario deja de poder iniciar sesion pero conserva su historial.
     */
    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->conexion->prepare(
            "UPDATE usuarios SET estado = :estado WHERE id_usuario = :id"
        );

        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    /**
     * Busca un usuario activo por su correo electronico.
     */
    public function obtenerPorEmail($email)
    {
        $sql = "
            SELECT id_usuario, nombre, usuario, email, rol, estado
            FROM usuarios
            WHERE email = :email AND estado = 1
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch();
    }

    /**
     * Guarda el hash del token de recuperacion y su fecha de expiracion.
     * El token en claro nunca se persiste.
     */
    public function guardarTokenReset($idUsuario, $hashToken, $expira)
    {
        $stmt = $this->conexion->prepare("
            UPDATE usuarios
            SET reset_token_hash = :hash, reset_token_expira = :expira
            WHERE id_usuario = :id
        ");

        return $stmt->execute([
            ':hash'   => $hashToken,
            ':expira' => $expira,
            ':id'     => $idUsuario,
        ]);
    }

    /**
     * Busca un usuario activo por el hash del token de recuperacion,
     * solo si aun no ha expirado.
     */
    public function obtenerPorTokenReset($hashToken)
    {
        $sql = "
            SELECT id_usuario, nombre, usuario, email, rol, estado
            FROM usuarios
            WHERE reset_token_hash = :hash
              AND reset_token_expira > NOW()
              AND estado = 1
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':hash' => $hashToken]);

        return $stmt->fetch();
    }

    /**
     * Invalida el token de recuperacion (se usa tras restablecer o al vencer).
     */
    public function limpiarTokenReset($idUsuario)
    {
        $stmt = $this->conexion->prepare("
            UPDATE usuarios
            SET reset_token_hash = NULL, reset_token_expira = NULL
            WHERE id_usuario = :id
        ");

        return $stmt->execute([':id' => $idUsuario]);
    }

    /**
     * Cambia la contrasena de un usuario (usado por la recuperacion por email).
     */
    public function actualizarPassword($idUsuario, $passwordPlano)
    {
        $stmt = $this->conexion->prepare("
            UPDATE usuarios SET password = :password WHERE id_usuario = :id
        ");

        return $stmt->execute([
            ':password' => password_hash($passwordPlano, PASSWORD_DEFAULT),
            ':id'       => $idUsuario,
        ]);
    }

    /**
     * Cuenta cuantos administradores activos quedan.
     * Evita que el sistema se quede sin ningun administrador.
     */
    public function contarAdministradoresActivos($idExcluir = null)
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE rol = 'Administrador' AND estado = 1";
        $parametros = [];

        if ($idExcluir !== null) {
            $sql .= " AND id_usuario <> :id";
            $parametros[':id'] = $idExcluir;
        }

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn();
    }
}
