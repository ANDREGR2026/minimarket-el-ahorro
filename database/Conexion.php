<?php

/**
 * Punto único de conexión a la base de datos MySQL mediante PDO.
 * Reutiliza la misma instancia durante toda la petición.
 */
class Conexion
{
    private static $instancia = null;

    public static function conectar()
    {
        if (self::$instancia !== null) {
            return self::$instancia;
        }

        try {
            $host = $_ENV['DB_HOST'] ?? (getenv('DB_HOST') ?: 'localhost');
            $db   = $_ENV['DB_NAME'] ?? (getenv('DB_NAME') ?: 'minimarket');
            $user = $_ENV['DB_USER'] ?? (getenv('DB_USER') ?: 'root');
            $pass = $_ENV['DB_PASS'] ?? (getenv('DB_PASS') ?: '');

            $conexion = new PDO(
                'mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8mb4',
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );

            self::$instancia = $conexion;

            return $conexion;
        } catch (PDOException $e) {
            die('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }
}
