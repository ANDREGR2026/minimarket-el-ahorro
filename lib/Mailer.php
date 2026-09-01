<?php

/**
 * Envío de correo del sistema (recuperación de contraseña, etc).
 *
 * Usa PHPMailer vía SMTP si el proyecto lo tiene instalado
 * (composer require phpmailer/phpmailer agrega vendor/autoload.php
 * con la clase PHPMailer\PHPMailer\PHPMailer). Si no está disponible,
 * cae a la función mail() nativa de PHP como respaldo, para que el
 * flujo de recuperación funcione igual en un entorno de desarrollo
 * (Laragon con un relay SMTP local, sendmail, etc).
 *
 * Configuración por variables de entorno (.env):
 *   MAIL_HOST, MAIL_PORT, MAIL_USER, MAIL_PASS,
 *   MAIL_ENCRYPTION (tls|ssl), MAIL_FROM, MAIL_FROM_NOMBRE
 */
class Mailer
{
    /**
     * Envía un correo HTML. Devuelve true si se pudo enviar.
     */
    public static function enviar($destinatario, $asunto, $cuerpoHtml)
    {
        $vendorAutoload = ROOT_PATH . '/vendor/autoload.php';

        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
        }

        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return self::enviarConPhpMailer($destinatario, $asunto, $cuerpoHtml);
        }

        return self::enviarConMailNativo($destinatario, $asunto, $cuerpoHtml);
    }

    private static function enviarConPhpMailer($destinatario, $asunto, $cuerpoHtml)
    {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = getenv('MAIL_HOST') ?: 'localhost';
            $mail->SMTPAuth   = (bool) (getenv('MAIL_USER'));
            $mail->Username   = getenv('MAIL_USER') ?: '';
            $mail->Password   = getenv('MAIL_PASS') ?: '';
            $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: 'tls';
            $mail->Port       = (int) (getenv('MAIL_PORT') ?: 587);
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(
                getenv('MAIL_FROM') ?: 'no-responder@elahorro.pe',
                getenv('MAIL_FROM_NOMBRE') ?: 'EL AHORRO'
            );
            $mail->addAddress($destinatario);

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpoHtml;
            $mail->AltBody = strip_tags($cuerpoHtml);

            return $mail->send();
        } catch (Exception $e) {
            error_log('Mailer (PHPMailer): ' . $mail->ErrorInfo);
            return false;
        }
    }

    private static function enviarConMailNativo($destinatario, $asunto, $cuerpoHtml)
    {
        $desde = getenv('MAIL_FROM') ?: 'no-responder@elahorro.pe';

        $cabeceras  = "MIME-Version: 1.0\r\n";
        $cabeceras .= "Content-Type: text/html; charset=UTF-8\r\n";
        $cabeceras .= 'From: ' . (getenv('MAIL_FROM_NOMBRE') ?: 'EL AHORRO') . ' <' . $desde . ">\r\n";

        return @mail($destinatario, $asunto, $cuerpoHtml, $cabeceras);
    }
}
