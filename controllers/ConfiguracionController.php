<?php
require_once __DIR__ . '/../models/Configuracion.php';
require_once __DIR__ . '/../middleware/Auth.php';

class ConfiguracionController
{
    public static function validar(array $datos): array
    {
        $valores = [];

        // Campos de texto simples
        foreach ([
            'nombre_comercial' => 80,
            'razon_social'     => 150,
            'telefono'         => 30,
            'direccion'        => 250,
            'email_contacto'   => 150,
            'moneda'           => 5,
            'color_principal'  => 7,
            'color_oscuro'     => 7,
        ] as $clave => $maximo) {
            if (!is_string($datos[$clave] ?? null)) {
                throw new InvalidArgumentException('Complete todos los campos.');
            }
            $valor = trim($datos[$clave]);
            if ($valor === '' || mb_strlen($valor) > $maximo || preg_match('/[\x00-\x1f]/', $valor)) {
                throw new InvalidArgumentException('Revise los campos vacíos o demasiado largos.');
            }
            $valores[$clave] = $valor;
        }

        // RUC: 11 dígitos numéricos
        $ruc = trim($datos['ruc'] ?? '');
        if (!preg_match('/^\d{11}$/', $ruc)) {
            throw new InvalidArgumentException('El RUC debe tener exactamente 11 dígitos numéricos.');
        }
        $valores['ruc'] = $ruc;

        if (!preg_match('/^[+0-9 ()-]{6,30}$/D', $valores['telefono'])) {
            throw new InvalidArgumentException('Ingrese un teléfono válido.');
        }
        if (!filter_var($valores['email_contacto'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Ingrese un correo de contacto válido.');
        }
        foreach (['color_principal', 'color_oscuro'] as $clave) {
            if (!preg_match('/^#[a-f0-9]{6}$/iD', $valores[$clave])) {
                throw new InvalidArgumentException('Seleccione colores válidos.');
            }
            $valores[$clave] = strtolower($valores[$clave]);
        }

        // IGV: entero entre 0 y 99
        $igv = (int) ($datos['igv'] ?? 18);
        if ($igv < 0 || $igv > 99) {
            throw new InvalidArgumentException('El IGV debe estar entre 0% y 99%.');
        }
        $valores['igv'] = (string) $igv;

        // Límite de ventas: 50-2000
        $lim = (int) ($datos['ventas_limite'] ?? 200);
        if ($lim < 50 || $lim > 2000) {
            throw new InvalidArgumentException('El límite del historial debe estar entre 50 y 2 000.');
        }
        $valores['ventas_limite'] = (string) $lim;

        // Días de anulación: 0-365
        $dias = (int) ($datos['dias_anulacion'] ?? 0);
        if ($dias < 0 || $dias > 365) {
            throw new InvalidArgumentException('Los días para anular deben estar entre 0 y 365.');
        }
        $valores['dias_anulacion'] = (string) $dias;

        // Stock mínimo por defecto: 1-999
        $stock = (int) ($datos['stock_minimo_def'] ?? 5);
        if ($stock < 1 || $stock > 999) {
            throw new InvalidArgumentException('El stock mínimo por defecto debe estar entre 1 y 999.');
        }
        $valores['stock_minimo_def'] = (string) $stock;

        return $valores;
    }

    public function guardar(array $datos, array $archivo): array
    {
        if (!Auth::esAdministrador()) {
            return ['ok' => false, 'mensaje' => 'No tiene permiso para cambiar la configuración.'];
        }
        $nuevo = null;
        try {
            $valores = self::validar($datos);
            if (!empty($datos['quitar_logo'])) $valores['logo'] = '';
            $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($error !== UPLOAD_ERR_NO_FILE) {
                if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'] ?? '') || filesize($archivo['tmp_name']) > 2 * 1024 * 1024) {
                    throw new InvalidArgumentException('El logo no pudo cargarse. Use una imagen de hasta 2 MB.');
                }
                $info = @getimagesize($archivo['tmp_name']);
                $tipos = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
                if (!$info || !isset($tipos[$mime]) || $info[0] > 4096 || $info[1] > 4096 || $info['mime'] !== $mime) {
                    throw new InvalidArgumentException('Use un logo PNG, JPG o WebP de hasta 4096 × 4096 píxeles.');
                }
                $carpeta = ROOT_PATH . '/assets/img/marca';
                if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) throw new RuntimeException('No se pudo crear la carpeta.');
                $ruta = 'assets/img/marca/' . bin2hex(random_bytes(16)) . '.' . $tipos[$mime];
                $nuevo = ROOT_PATH . '/' . $ruta;
                if (!move_uploaded_file($archivo['tmp_name'], $nuevo)) throw new RuntimeException('No se pudo guardar el logo.');
                $valores['logo'] = $ruta;
            }
            (new Configuracion())->guardarVarios($valores);
            return ['ok' => true, 'mensaje' => 'Configuración guardada. Los cambios ya están disponibles en la web y el sistema.'];
        } catch (Throwable $error) {
            if ($nuevo && is_file($nuevo)) unlink($nuevo);
            return ['ok' => false, 'mensaje' => $error instanceof InvalidArgumentException ? $error->getMessage() : 'No se pudo guardar la configuración. Inténtelo nuevamente.'];
        }
    }
}
