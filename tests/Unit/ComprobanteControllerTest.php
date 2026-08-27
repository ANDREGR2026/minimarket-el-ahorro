<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/controllers/ComprobanteController.php';

/**
 * Pruebas del controlador de comprobantes: conversión de monto a letras
 * (numeroALetras), la única lógica pura y aislable del controlador
 * (el resto genera un PDF vía TCPDF).
 */
class ComprobanteControllerTest extends TestCase
{
    private function crearController(): ComprobanteController
    {
        $ref = new ReflectionClass(ComprobanteController::class);

        return $ref->newInstanceWithoutConstructor();
    }

    public function test_cero(): void
    {
        $controller = $this->crearController();

        $this->assertEquals('CERO CON 00/100 SOLES', $controller->numeroALetras(0));
    }

    public function test_unidades_y_centavos(): void
    {
        $controller = $this->crearController();

        $this->assertEquals('CINCO CON 50/100 SOLES', $controller->numeroALetras(5.5));
    }

    public function test_veintiuno_es_una_sola_palabra(): void
    {
        $controller = $this->crearController();

        $this->assertEquals('VEINTIUNO CON 00/100 SOLES', $controller->numeroALetras(21));
    }

    public function test_decenas_con_union_y(): void
    {
        $controller = $this->crearController();

        $this->assertEquals('TREINTA Y CINCO CON 00/100 SOLES', $controller->numeroALetras(35));
    }

    public function test_cien_exacto(): void
    {
        $controller = $this->crearController();

        $this->assertEquals('CIEN CON 00/100 SOLES', $controller->numeroALetras(100));
    }

    public function test_centenas_con_resto(): void
    {
        $controller = $this->crearController();

        $this->assertEquals('CIENTO VEINTICINCO CON 50/100 SOLES', $controller->numeroALetras(125.50));
    }

    public function test_mil_exacto(): void
    {
        $controller = $this->crearController();

        $this->assertEquals('MIL CON 00/100 SOLES', $controller->numeroALetras(1000));
    }

    public function test_miles_con_resto(): void
    {
        $controller = $this->crearController();

        $this->assertEquals('DOS MIL TRESCIENTOS CUARENTA Y CINCO CON 00/100 SOLES', $controller->numeroALetras(2345));
    }

    public function test_un_millon(): void
    {
        $controller = $this->crearController();

        $this->assertEquals('UN MILLÓN CON 00/100 SOLES', $controller->numeroALetras(1000000));
    }

    public function test_redondea_centavos_a_dos_decimales(): void
    {
        $controller = $this->crearController();

        $this->assertEquals('DIEZ CON 13/100 SOLES', $controller->numeroALetras(10.126));
    }
}
