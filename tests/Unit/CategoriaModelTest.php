<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once ROOT_PATH . '/models/Categoria.php';

class CategoriaModelTest extends DatabaseTestCase
{
    private Categoria $categoria;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categoria = new Categoria();
    }

    public function test_crear_y_obtener_categoria(): void
    {
        $id = $this->categoria->crear('Dulces', 'Golosinas y chocolates');
        $this->assertGreaterThan(0, $id);

        $cat = $this->categoria->obtenerPorId($id);
        $this->assertIsArray($cat);
        $this->assertEquals('Dulces', $cat['nombre']);
        $this->assertEquals('Golosinas y chocolates', $cat['descripcion']);
        $this->assertEquals(1, $cat['estado']);
    }

    public function test_listar_categorias_con_busqueda(): void
    {
        $this->categoria->crear('Limpieza', 'Productos de limpieza');
        $this->categoria->crear('Bebidas', 'Jugos y gaseosas');

        $resultados = $this->categoria->listar('limp', false);
        
        $this->assertCount(1, $resultados);
        $this->assertEquals('Limpieza', $resultados[0]['nombre']);
    }

    public function test_listar_solo_activas(): void
    {
        $id1 = $this->categoria->crear('Cat 1', 'Desc');
        $id2 = $this->categoria->crear('Cat 2', 'Desc');

        $this->categoria->cambiarEstado($id2, 0); // Desactivar Cat 2

        $resultados = $this->categoria->listar('', true);
        
        $nombres = array_column($resultados, 'nombre');
        $this->assertContains('Cat 1', $nombres);
        $this->assertNotContains('Cat 2', $nombres);
    }

    public function test_nombre_existe(): void
    {
        $this->categoria->crear('Abarrotes', 'Abarrotes en general');

        $this->assertTrue($this->categoria->nombreExiste('Abarrotes'));
        $this->assertFalse($this->categoria->nombreExiste('No existe'));
    }

    public function test_nombre_existe_ignorando_id(): void
    {
        $id = $this->categoria->crear('Cereales', 'Cereales varios');

        // Al editarse a sí mismo, el nombre "Cereales" no debería dar conflicto si ignoramos su propio ID
        $this->assertFalse($this->categoria->nombreExiste('Cereales', $id));
    }

    public function test_actualizar_categoria(): void
    {
        $id = $this->categoria->crear('Panaderia', 'Panes');
        
        $this->categoria->actualizar($id, 'Panadería y Pastelería', 'Panes dulces y salados', 0);

        $cat = $this->categoria->obtenerPorId($id);
        $this->assertEquals('Panadería y Pastelería', $cat['nombre']);
        $this->assertEquals('Panes dulces y salados', $cat['descripcion']);
        $this->assertEquals(0, $cat['estado']);
    }
}
