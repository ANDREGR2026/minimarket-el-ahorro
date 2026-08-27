<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once ROOT_PATH . '/models/Producto.php';
require_once ROOT_PATH . '/models/Categoria.php';

class ProductoModelTest extends DatabaseTestCase
{
    private Producto $producto;
    private int $idCategoria;

    protected function setUp(): void
    {
        parent::setUp();
        $this->producto = new Producto();
        $cat = new Categoria();
        $this->idCategoria = $cat->crear('Cat Test', 'Desc');
    }

    public function test_crear_y_obtener_por_id_y_codigo(): void
    {
        $datos = [
            'codigo_barras' => '7751234567890',
            'nombre'        => 'Galletas Test',
            'descripcion'   => null,
            'id_categoria'  => $this->idCategoria,
            'precio_compra' => 1.50,
            'precio_venta'  => 2.50,
            'stock_minimo'  => 5,
            'stock'         => 0,
            'unidad_medida' => 'UNIDAD',
            'imagen'        => null
        ];

        $id = $this->producto->crear($datos);
        $this->assertGreaterThan(0, $id);

        $prodDB = $this->producto->obtenerPorId($id);
        $this->assertEquals('Galletas Test', $prodDB['nombre']);
        $this->assertEquals(1.50, $prodDB['precio_compra']);
        $this->assertEquals($this->idCategoria, $prodDB['id_categoria']);

        $prodByCode = $this->producto->obtenerPorCodigo('7751234567890');
        $this->assertEquals($id, $prodByCode['id_producto']);
    }

    public function test_codigo_existe(): void
    {
        $this->producto->crear([
            'codigo_barras' => 'COD123', 'nombre' => 'Prod 1', 'descripcion' => null,
            'id_categoria' => $this->idCategoria, 'precio_compra' => 1, 'precio_venta' => 2,
            'stock_minimo' => 1, 'stock' => 0, 'unidad_medida' => 'UNIDAD', 'imagen' => null
        ]);

        $this->assertTrue($this->producto->codigoExiste('COD123'));
        $this->assertFalse($this->producto->codigoExiste('COD_FALSO'));
    }

    public function test_buscar_producto(): void
    {
        $this->producto->crear([
            'codigo_barras' => 'BUSCAR123', 'nombre' => 'Arroz Costeno', 'descripcion' => null,
            'id_categoria' => $this->idCategoria, 'precio_compra' => 1, 'precio_venta' => 2,
            'stock_minimo' => 1, 'stock' => 0, 'unidad_medida' => 'KG', 'imagen' => null
        ]);

        $resultados = $this->producto->buscar('Coste');
        $this->assertCount(1, $resultados);

        $resultadosCode = $this->producto->buscar('BUSCAR');
        $this->assertCount(1, $resultadosCode);
    }

    public function test_actualizar_y_cambiar_estado(): void
    {
        $datos = [
            'codigo_barras' => 'ACTUALIZAR', 'nombre' => 'Fideos', 'descripcion' => null,
            'id_categoria' => $this->idCategoria, 'precio_compra' => 1, 'precio_venta' => 2,
            'stock_minimo' => 1, 'stock' => 0, 'unidad_medida' => 'UNIDAD', 'imagen' => null
        ];
        $id = $this->producto->crear($datos);

        $datos['id_producto'] = $id;
        $datos['nombre'] = 'Fideos Largos';
        $datos['estado'] = 1;
        $this->producto->actualizar($id, $datos);

        $prodActualizado = $this->producto->obtenerPorId($id);
        $this->assertEquals('Fideos Largos', $prodActualizado['nombre']);

        $this->producto->cambiarEstado($id, 0);
        $prodDesactivado = $this->producto->obtenerPorId($id);
        $this->assertEquals(0, $prodDesactivado['estado']);
    }
}
