<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/database/Conexion.php';

abstract class DatabaseTestCase extends TestCase
{
    protected PDO $conexion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conexion = Conexion::conectar();
        
        // Iniciar transacción para aislar las pruebas
        if (!$this->conexion->inTransaction()) {
            $this->conexion->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        // Revertir cualquier cambio hecho durante la prueba
        if ($this->conexion->inTransaction()) {
            $this->conexion->rollBack();
        }
        parent::tearDown();
    }
}
