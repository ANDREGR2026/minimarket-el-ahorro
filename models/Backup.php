<?php

require_once __DIR__ . '/../database/Conexion.php';

/**
 * Respaldo de la base de datos.
 *
 * Genera un volcado SQL propio (estructura + datos de cada tabla) sin
 * depender de mysqldump ni de ninguna herramienta externa: solo PDO, igual
 * que el resto del sistema. Los archivos se guardan en backups/ con marca
 * de tiempo y se aplica una política de retención configurable.
 */
class Backup
{
    private $conexion;
    private $directorio;

    public function __construct($directorio = null)
    {
        $this->conexion  = Conexion::conectar();
        $this->directorio = $directorio ?? (__DIR__ . '/../storage/backups');
    }

    public function directorio()
    {
        return $this->directorio;
    }

    /**
     * Lista los backups existentes, del más reciente al más antiguo.
     */
    public function listar()
    {
        $archivos = glob($this->directorio . '/*.sql') ?: [];

        $lista = array_map(function ($ruta) {
            $marcaTiempo = filemtime($ruta);

            return [
                'nombre'      => basename($ruta),
                'tamano'      => filesize($ruta),
                'fecha'       => date('Y-m-d H:i:s', $marcaTiempo),
                'marcaTiempo' => $marcaTiempo,
            ];
        }, $archivos);

        usort($lista, function ($a, $b) {
            return $b['marcaTiempo'] <=> $a['marcaTiempo'];
        });

        return $lista;
    }

    /**
     * Ruta absoluta de un backup a partir de su nombre de archivo.
     * basename() evita que un nombre con ../ escape del directorio.
     */
    public function ruta($nombreArchivo)
    {
        return $this->directorio . '/' . basename($nombreArchivo);
    }

    public function ultimoBackup()
    {
        $lista = $this->listar();

        return $lista[0] ?? null;
    }

    /**
     * Genera un volcado completo de la base de datos activa y aplica la
     * retención indicada (0 o null = no borrar ninguno).
     *
     * @return array{nombre: string, ruta: string}
     */
    public function generar($conservar = null)
    {
        if (!is_dir($this->directorio)) {
            mkdir($this->directorio, 0775, true);
        }

        $baseDatos     = $this->conexion->query('SELECT DATABASE()')->fetchColumn();
        $nombreArchivo = 'minimarket_' . date('Y-m-d_His') . '.sql';
        $ruta          = $this->ruta($nombreArchivo);

        $sql = "-- Respaldo de la base de datos '{$baseDatos}'\n"
             . '-- Generado el ' . date('Y-m-d H:i:s') . " por el sistema\n\n"
             . "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tablas = $this->conexion->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tablas as $tabla) {
            $sql .= $this->volcarTabla($tabla);
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        file_put_contents($ruta, $sql);

        if ($conservar !== null) {
            $this->aplicarRetencion((int) $conservar);
        }

        return ['nombre' => $nombreArchivo, 'ruta' => $ruta];
    }

    private function volcarTabla($tabla)
    {
        $creacion = $this->conexion->query("SHOW CREATE TABLE `{$tabla}`")->fetch();

        $sql = "-- ----------------------------------------------------------\n"
             . "-- Tabla: {$tabla}\n"
             . "-- ----------------------------------------------------------\n"
             . "DROP TABLE IF EXISTS `{$tabla}`;\n"
             . $creacion['Create Table'] . ";\n\n";

        $filas = $this->conexion->query("SELECT * FROM `{$tabla}`")->fetchAll();

        if (empty($filas)) {
            return $sql;
        }

        $columnas = array_map(function ($columna) {
            return "`{$columna}`";
        }, array_keys($filas[0]));

        $lineas = [];
        foreach ($filas as $fila) {
            $valores = array_map([$this, 'formatearValor'], $fila);
            $lineas[] = '(' . implode(', ', $valores) . ')';
        }

        $sql .= 'INSERT INTO `' . $tabla . '` (' . implode(', ', $columnas) . ") VALUES\n"
              . implode(",\n", $lineas) . ";\n\n";

        return $sql;
    }

    private function formatearValor($valor)
    {
        if ($valor === null) {
            return 'NULL';
        }

        return $this->conexion->quote((string) $valor);
    }

    /**
     * Conserva solo los $conservar backups más recientes y borra el resto.
     */
    public function aplicarRetencion($conservar)
    {
        if ($conservar <= 0) {
            return;
        }

        $lista = $this->listar();

        foreach (array_slice($lista, $conservar) as $antiguo) {
            @unlink($this->ruta($antiguo['nombre']));
        }
    }

    /**
     * Indica si, según la frecuencia configurada, ya toca generar un backup.
     * 'Desactivado' o cualquier valor no reconocido siempre responde false.
     */
    public function corresponde($frecuencia)
    {
        $intervalos = [
            'Diario'  => 60 * 60 * 24,
            'Semanal' => 60 * 60 * 24 * 7,
            'Mensual' => 60 * 60 * 24 * 30,
        ];

        if (!isset($intervalos[$frecuencia])) {
            return false;
        }

        $ultimo = $this->ultimoBackup();

        if ($ultimo === null) {
            return true;
        }

        return (time() - $ultimo['marcaTiempo']) >= $intervalos[$frecuencia];
    }
}
