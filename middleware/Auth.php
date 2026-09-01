<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Control de acceso por sesión y por rol.
 * Roles del sistema: SuperAdministrador, Administrador, Cajero y Almacenero.
 * SuperAdministrador es un rol único y fijo (creado por seed) que hereda
 * todo lo que puede ver y hacer un Administrador.
 */
class Auth
{
    /**
     * Exige que exista un usuario autenticado; si no, manda al login.
     */
    public static function check()
    {
        if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id_usuario'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
    }

    /**
     * Indica si el rol de sesión cumple con alguno de los roles permitidos.
     * SuperAdministrador siempre cumple donde se permite Administrador.
     *
     * @param array $rolesPermitidos por ejemplo ['Administrador']
     */
    public static function tieneRolPermitido($rolesPermitidos = [])
    {
        $rol = self::rol();

        if ($rol === 'SuperAdministrador' && in_array('Administrador', $rolesPermitidos, true)) {
            return true;
        }

        return in_array($rol, $rolesPermitidos, true);
    }

    /**
     * Exige que el usuario tenga uno de los roles permitidos.
     *
     * @param array $rolesPermitidos por ejemplo ['Administrador']
     */
    public static function checkRole($rolesPermitidos = [])
    {
        self::check();

        if (!self::tieneRolPermitido($rolesPermitidos)) {
            header('Location: ' . BASE_URL . 'acceso-denegado');
            exit;
        }
    }

    public static function usuario()
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function id()
    {
        return $_SESSION['usuario']['id_usuario'] ?? null;
    }

    public static function rol()
    {
        return $_SESSION['usuario']['rol'] ?? null;
    }

    public static function nombre()
    {
        return $_SESSION['usuario']['nombre'] ?? '';
    }

    public static function esAdministrador()
    {
        return in_array(self::rol(), ['Administrador', 'SuperAdministrador'], true);
    }

    public static function esSuperAdministrador()
    {
        return self::rol() === 'SuperAdministrador';
    }

    public static function esAlmacenero()
    {
        return self::rol() === 'Almacenero';
    }

    public static function invitado()
    {
        return !isset($_SESSION['usuario']);
    }

    /**
     * Cierra la sesión por completo.
     */
    public static function logout()
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
