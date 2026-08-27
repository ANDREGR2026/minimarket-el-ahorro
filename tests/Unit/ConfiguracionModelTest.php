<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once ROOT_PATH . '/models/Configuracion.php';

class ConfiguracionModelTest extends DatabaseTestCase
{
    private Configuracion $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new Configuracion();
    }

    public function test_obtener_y_guardar_configuracion(): void
    {
        $this->config->guardar('nombre_empresa', 'Mi Empresa de Prueba');
        $this->assertEquals('Mi Empresa de Prueba', $this->config->obtener('nombre_empresa'));
    }

    public function test_obtener_valor_por_defecto(): void
    {
        $this->assertEquals('Default', $this->config->obtener('clave_inexistente', 'Default'));
    }

    public function test_igv_y_factor_igv(): void
    {
        $this->config->guardar('igv', '18');
        $this->assertEquals(18.0, $this->config->igv());
        $this->assertEquals(1.18, $this->config->factorIgv());

        $this->config->guardar('igv', '10');
        $this->assertEquals(10.0, $this->config->igv());
        $this->assertEquals(1.10, $this->config->factorIgv());
    }

    public function test_todos_con_cache(): void
    {
        $this->config->guardar('moneda', 'USD');
        
        $todos = $this->config->todos();
        $this->assertIsArray($todos);
        $this->assertEquals('USD', $todos['moneda']);
    }
}
