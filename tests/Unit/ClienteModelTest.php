<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once ROOT_PATH . '/models/Cliente.php';

class ClienteModelTest extends DatabaseTestCase
{
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cliente = new Cliente();
    }

    public function test_crear_y_obtener_por_id(): void
    {
        $datos = [
            'tipo_documento'   => 'DNI',
            'numero_documento' => '12345678',
            'nombres'          => 'Juan',
            'apellidos'        => 'Perez',
            'razon_social'     => null,
            'telefono'         => '999888777',
            'email'            => 'juan@example.com',
            'direccion'        => 'Av Siempre Viva 123'
        ];

        $id = $this->cliente->crear($datos);
        $this->assertGreaterThan(0, $id);

        $clienteDB = $this->cliente->obtenerPorId($id);
        $this->assertEquals('Juan', $clienteDB['nombres']);
        $this->assertEquals('12345678', $clienteDB['numero_documento']);
    }

    public function test_obtener_por_documento(): void
    {
        $datos = [
            'tipo_documento'   => 'RUC',
            'numero_documento' => '20123456789',
            'nombres'          => null,
            'apellidos'        => null,
            'razon_social'     => 'Mi Empresa SAC',
            'telefono'         => null,
            'email'            => null,
            'direccion'        => null
        ];
        $this->cliente->crear($datos);

        $clienteDB = $this->cliente->obtenerPorDocumento('20123456789');
        $this->assertIsArray($clienteDB);
        $this->assertEquals('Mi Empresa SAC', $clienteDB['razon_social']);
    }

    public function test_documento_existe(): void
    {
        $this->cliente->crear([
            'tipo_documento' => 'DNI', 'numero_documento' => '11112222', 'nombres' => 'A', 'apellidos' => 'B',
            'razon_social' => null, 'telefono' => null, 'email' => null, 'direccion' => null
        ]);

        $this->assertTrue($this->cliente->documentoExiste('11112222'));
        $this->assertFalse($this->cliente->documentoExiste('00000000'));
    }

    public function test_buscar_cliente(): void
    {
        $this->cliente->crear([
            'tipo_documento' => 'DNI', 'numero_documento' => '87654321', 'nombres' => 'Maria', 'apellidos' => 'Gomez',
            'razon_social' => null, 'telefono' => null, 'email' => null, 'direccion' => null
        ]);

        // Búsqueda por número
        $resultados = $this->cliente->buscar('8765');
        $this->assertCount(1, $resultados);

        // Búsqueda por nombre
        $resultados = $this->cliente->buscar('Maria');
        $this->assertCount(1, $resultados);
    }

    public function test_actualizar_y_cambiar_estado(): void
    {
        $datos = [
            'tipo_documento' => 'DNI', 'numero_documento' => '12312312', 'nombres' => 'Carlos', 'apellidos' => 'Velez',
            'razon_social' => null, 'telefono' => null, 'email' => null, 'direccion' => null
        ];
        $id = $this->cliente->crear($datos);

        $datos['id_cliente'] = $id;
        $datos['nombres'] = 'Carlos Alberto';
        $datos['estado'] = 1;
        $this->cliente->actualizar($id, $datos);

        $clienteActualizado = $this->cliente->obtenerPorId($id);
        $this->assertEquals('Carlos Alberto', $clienteActualizado['nombres']);

        $this->cliente->cambiarEstado($id, 0);
        $clienteDesactivado = $this->cliente->obtenerPorId($id);
        $this->assertEquals(0, $clienteDesactivado['estado']);
    }
}
