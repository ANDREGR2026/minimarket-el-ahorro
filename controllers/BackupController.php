<?php

require_once __DIR__ . '/../models/Backup.php';
require_once __DIR__ . '/../models/Configuracion.php';

/**
 * Orquesta la generación, el listado, la descarga y la política de
 * retención de los backups de la base de datos. Solo Administrador.
 */
class BackupController
{
    const FRECUENCIAS_VALIDAS = ['Desactivado', 'Diario', 'Semanal', 'Mensual'];

    private $modelo;
    private $configuracion;

    public function __construct($modelo = null, $configuracion = null)
    {
        $this->modelo        = $modelo ?? new Backup();
        $this->configuracion = $configuracion ?? new Configuracion();
    }

    public function listar()
    {
        return $this->modelo->listar();
    }

    public function configuracionActual()
    {
        return [
            'frecuencia' => $this->configuracion->obtener('backup_frecuencia', 'Semanal'),
            'conservar'  => (int) $this->configuracion->obtener('backup_conservar', 10),
        ];
    }

    /**
     * @param array $datos ['frecuencia' => ..., 'conservar' => ...]
     */
    public function guardarConfiguracion($datos)
    {
        $frecuencia = $datos['frecuencia'] ?? 'Desactivado';
        $conservar  = (int) ($datos['conservar'] ?? 0);

        if (!in_array($frecuencia, self::FRECUENCIAS_VALIDAS, true)) {
            return ['ok' => false, 'mensaje' => 'La frecuencia indicada no es válida.'];
        }

        if ($conservar < 1 || $conservar > 365) {
            return ['ok' => false, 'mensaje' => 'La cantidad de backups a conservar debe estar entre 1 y 365.'];
        }

        $this->configuracion->guardar('backup_frecuencia', $frecuencia);
        $this->configuracion->guardar('backup_conservar', (string) $conservar);

        return ['ok' => true, 'mensaje' => 'Configuración de backups actualizada.'];
    }

    public function generarManual()
    {
        $conservar = (int) $this->configuracion->obtener('backup_conservar', 10);

        try {
            $resultado = $this->modelo->generar($conservar);
        } catch (Exception $e) {
            return ['ok' => false, 'mensaje' => 'No se pudo generar el backup: ' . $e->getMessage()];
        }

        return ['ok' => true, 'mensaje' => 'Backup generado: ' . $resultado['nombre']];
    }

    /**
     * Se llama en cada pantalla del Administrador: si según la frecuencia
     * configurada ya corresponde, genera el backup en silencio. Cualquier
     * error se ignora para no interrumpir la navegación normal.
     */
    public function ejecutarSiCorresponde()
    {
        $frecuencia = $this->configuracion->obtener('backup_frecuencia', 'Desactivado');

        if (!$this->modelo->corresponde($frecuencia)) {
            return;
        }

        try {
            $conservar = (int) $this->configuracion->obtener('backup_conservar', 10);
            $this->modelo->generar($conservar);
        } catch (Exception $e) {
            // Backup silencioso: una falla aquí no debe romper la pantalla
            // que el administrador quería ver.
        }
    }

    /**
     * Envía un backup existente como descarga y termina el script. Devuelve
     * false (sin terminar el script) si el archivo no existe.
     */
    public function descargar($nombreArchivo)
    {
        $ruta = $this->modelo->ruta($nombreArchivo);

        if (!is_file($ruta)) {
            return false;
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($ruta) . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }
}
