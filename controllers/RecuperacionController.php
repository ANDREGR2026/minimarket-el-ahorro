<?php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Configuracion.php';
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

            $enlace = $this->urlAbsoluta() . 'restablecer?token=' . $token;
            $nombreComercial = (new Configuracion())->obtener('nombre_comercial', 'EL AHORRO');
            $cuerpo = $this->plantillaCorreo($nombreComercial, [
                'titulo'    => 'Restablecer contraseña',
                'saludo'    => 'Hola ' . $registro['nombre'] . ',',
                'parrafos'  => [
                    'Recibimos una solicitud para restablecer la contraseña de su cuenta en ' . $nombreComercial . '.',
                    'Si fue usted, haga clic en el siguiente botón. Si no reconoce esta solicitud, puede ignorar este correo: su contraseña no cambiará.',
                ],
                'boton'     => ['texto' => 'Restablecer mi contraseña', 'url' => $enlace],
                'aviso'     => 'Este enlace vence en ' . self::MINUTOS_VALIDEZ . ' minutos y solo puede usarse una vez.',
            ]);

            Mailer::enviar($email, 'Restablecé tu contraseña — ' . $nombreComercial, $cuerpo);
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

    /**
     * Arma la URL absoluta del sitio (esquema + host + BASE_URL) para que
     * el enlace del correo funcione fuera del navegador donde se generó.
     * En CLI (pruebas, scripts) no hay $_SERVER['HTTP_HOST']: se usa
     * localhost como respaldo razonable para desarrollo.
     */
    private function urlAbsoluta()
    {
        $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $esquema . '://' . $host . BASE_URL;
    }

    /**
     * Plantilla HTML compartida por los correos transaccionales del sistema:
     * cabecera con el nombre del negocio, cuerpo en tarjeta blanca y pie de
     * página. Compatible con clientes de correo simples (tablas, estilos
     * inline, sin CSS externo ni JavaScript).
     */
    private function plantillaCorreo($nombreComercial, array $datos)
    {
        $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $parrafosHtml = '';
        foreach ($datos['parrafos'] as $parrafo) {
            $parrafosHtml .= '<p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">'
                . $e($parrafo) . '</p>';
        }

        $botonHtml = '';
        if (!empty($datos['boton'])) {
            $botonHtml = '
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;">
                    <tr>
                        <td style="border-radius:8px;background-color:#257aeb;">
                            <a href="' . $e($datos['boton']['url']) . '"
                                style="display:inline-block;padding:12px 28px;font-size:15px;font-weight:600;
                                       color:#ffffff;text-decoration:none;border-radius:8px;">
                                ' . $e($datos['boton']['texto']) . '
                            </a>
                        </td>
                    </tr>
                </table>
                <p style="margin:0 0 24px;color:#94a3b8;font-size:12px;line-height:1.5;word-break:break-all;">
                    Si el botón no funciona, copie y pegue este enlace en su navegador:<br>
                    <a href="' . $e($datos['boton']['url']) . '" style="color:#257aeb;">' . $e($datos['boton']['url']) . '</a>
                </p>';
        }

        $avisoHtml = '';
        if (!empty($datos['aviso'])) {
            $avisoHtml = '
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                    style="background-color:#fffbeb;border:1px solid #fde68a;border-radius:8px;margin:0 0 8px;">
                    <tr>
                        <td style="padding:12px 16px;color:#92400e;font-size:13px;line-height:1.5;">
                            ' . $e($datos['aviso']) . '
                        </td>
                    </tr>
                </table>';
        }

        return '
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
            style="background-color:#f1f5f9;padding:32px 16px;font-family:\'Segoe UI\',Arial,sans-serif;">
            <tr>
                <td align="center">
                    <table role="presentation" cellpadding="0" cellspacing="0" width="480"
                        style="max-width:480px;width:100%;background-color:#ffffff;border-radius:12px;
                               overflow:hidden;border:1px solid #e2e8f0;">
                        <tr>
                            <td style="background-color:#1d63d8;padding:24px 32px;">
                                <table role="presentation" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="width:36px;height:36px;background-color:rgba(255,255,255,0.15);
                                                   border-radius:8px;text-align:center;vertical-align:middle;
                                                   font-size:18px;font-weight:700;color:#ffffff;">A</td>
                                        <td style="padding-left:10px;font-size:16px;font-weight:600;color:#ffffff;">
                                            ' . $e($nombreComercial) . '
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:32px;">
                                <h1 style="margin:0 0 16px;color:#0f172a;font-size:20px;">' . $e($datos['titulo']) . '</h1>
                                <p style="margin:0 0 16px;color:#0f172a;font-size:15px;line-height:1.6;">' . $e($datos['saludo']) . '</p>
                                ' . $parrafosHtml . $botonHtml . $avisoHtml . '
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:20px 32px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
                                <p style="margin:0;color:#94a3b8;font-size:12px;line-height:1.5;">
                                    Este es un mensaje automático de ' . $e($nombreComercial) . '. No respondas a este correo.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>';
    }
}
