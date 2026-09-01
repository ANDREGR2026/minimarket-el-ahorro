<?php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../lib/Mailer.php';

/**
 * Recuperación de contraseña por correo electrónico.
 *
 * Flujo: el usuario pide un enlace (solicitar), recibe un correo con un
 * token de un solo uso (nunca se guarda en claro, solo su hash), y lo usa
 * para fijar una contraseña nueva (restablecer) dentro de los 30 minutos
 * siguientes.
 */
class RecuperacionController
{
    private const MINUTOS_VALIDEZ = 30;

    private $modelo;

    public function __construct()
    {
        $this->modelo = new Usuario();
    }

    /**
     * Genera el token y envía el correo si el email pertenece a un usuario
     * activo. Responde siempre el mismo mensaje genérico, exista o no la
     * cuenta, para no revelar qué correos están registrados.
     *
     * @return array ['ok' => bool, 'mensaje' => string]
     */
    public function solicitar($email)
    {
        $email = trim($email);
        $mensajeGenerico = 'Si el correo está registrado, enviamos un enlace para restablecer la contraseña.';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'mensaje' => 'Ingrese un correo electrónico válido.'];
        }

        $registro = $this->modelo->obtenerPorEmail($email);

        if ($registro) {
            $token     = bin2hex(random_bytes(32));
            $hashToken = hash('sha256', $token);
            $expira    = date('Y-m-d H:i:s', time() + self::MINUTOS_VALIDEZ * 60);

            $this->modelo->guardarTokenReset($registro['id_usuario'], $hashToken, $expira);

            $enlace = BASE_URL . 'restablecer?token=' . $token;
            $cuerpo = '<p>Hola ' . htmlspecialchars($registro['nombre'], ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Recibimos una solicitud para restablecer tu contraseña en EL AHORRO.</p>'
                . '<p><a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '">Restablecer mi contraseña</a></p>'
                . '<p>Este enlace vence en ' . self::MINUTOS_VALIDEZ . ' minutos. Si no solicitaste esto, ignora el correo.</p>';

            Mailer::enviar($email, 'Recuperar contraseña — EL AHORRO', $cuerpo);
        }

        return ['ok' => true, 'mensaje' => $mensajeGenerico];
    }

    /**
     * Valida que un token exista y no haya vencido.
     */
    public function validarToken($token)
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        return (bool) $this->modelo->obtenerPorTokenReset(hash('sha256', $token));
    }

    /**
     * Fija la contraseña nueva y consume el token (un solo uso).
     *
     * @return array ['ok' => bool, 'mensaje' => string]
     */
    public function restablecer($token, $passwordNueva, $passwordConfirmacion)
    {
        if (!is_string($token) || $token === '') {
            return ['ok' => false, 'mensaje' => 'Enlace inválido.'];
        }

        $registro = $this->modelo->obtenerPorTokenReset(hash('sha256', $token));

        if (!$registro) {
            return ['ok' => false, 'mensaje' => 'El enlace es inválido o ya venció. Solicite uno nuevo.'];
        }

        if (strlen($passwordNueva) < 6) {
            return ['ok' => false, 'mensaje' => 'La contraseña debe tener al menos 6 caracteres.'];
        }

        if ($passwordNueva !== $passwordConfirmacion) {
            return ['ok' => false, 'mensaje' => 'Las contraseñas no coinciden.'];
        }

        $this->modelo->actualizarPassword($registro['id_usuario'], $passwordNueva);
        $this->modelo->limpiarTokenReset($registro['id_usuario']);

        return ['ok' => true, 'mensaje' => 'Contraseña actualizada. Ya puede iniciar sesión.'];
    }
}
