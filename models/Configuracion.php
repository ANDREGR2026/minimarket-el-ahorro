<?php

require_once __DIR__ . '/../database/Conexion.php';

/**
 * Parametros globales del negocio: razon social, RUC, IGV, etc.
 */
class Configuracion
{
    private $conexion;
    private static $cache = null;

    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    /**
     * Devuelve todos los parametros como un arreglo clave => valor.
     * Se consulta una sola vez por peticion.
     */
    public function todos()
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $filas = $this->conexion->query("SELECT clave, valor FROM configuracion")->fetchAll();

        $valores = [];
        foreach ($filas as $fila) {
            $valores[$fila['clave']] = $fila['valor'];
        }

        self::$cache = $valores;

        return $valores;
    }

    public function obtener($clave, $porDefecto = '')
    {
        $valores = $this->todos();

        return $valores[$clave] ?? $porDefecto;
    }

    /**
     * Porcentaje de IGV vigente, por ejemplo 18.
     */
    public function igv()
    {
        return (float) $this->obtener('igv', 18);
    }

    /**
     * Factor para descomponer un precio que ya incluye IGV. Ejemplo: 1.18
     */
    public function factorIgv()
    {
        return 1 + ($this->igv() / 100);
    }

    public function guardar($clave, $valor)
    {
        $stmt = $this->conexion->prepare("
            INSERT INTO configuracion (clave, valor)
            VALUES (:clave, :valor)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)
        ");

        self::$cache = null;

        return $stmt->execute([':clave' => $clave, ':valor' => $valor]);
    }
}
