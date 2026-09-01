<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_PATH . '/controllers/ReporteController.php';

/**
 * Pruebas del controlador de reportes.
 *
 * Valida: ensamblado de datos del dashboard, del rango de reportes,
 * agrupación de la serie diaria y validación de fechas en rango().
 */
class ReporteControllerTest extends TestCase
{
    private function crearController($mockReporte = null, $mockProductos = null): ReporteController
    {
        $ref        = new ReflectionClass(ReporteController::class);
        $controller = $ref->newInstanceWithoutConstructor();

        $rep  = $mockReporte   ?? $this->createMock(Reporte::class);
        $prod = $mockProductos ?? $this->createMock(Producto::class);

        $propRep = $ref->getProperty('reporte');
        $propRep->setAccessible(true);
        $propRep->setValue($controller, $rep);

        $propProd = $ref->getProperty('productos');
        $propProd->setAccessible(true);
        $propProd->setValue($controller, $prod);

        return $controller;
    }

    // ─── dashboard() ─────────────────────────────────────────────────

    public function test_dashboard_calcula_ticket_promedio_del_mes(): void
    {
        $mockRep = $this->createMock(Reporte::class);
        $mockRep->method('indicadores')->willReturn([
            'venta_hoy' => 100, 'tickets_hoy' => 2,
            'venta_mes' => 500, 'tickets_mes' => 4,
            'venta_total' => 1000, 'tickets_total' => 10,
        ]);
        $mockRep->method('ventasPorDia')->willReturn([]);
        $mockRep->method('productosMasVendidos')->willReturn([]);
        $mockRep->method('ventasPorCategoria')->willReturn([]);
        $mockRep->method('ultimasVentas')->willReturn([]);

        $mockProd = $this->createMock(Producto::class);
        $mockProd->method('resumen')->willReturn(['total' => 5]);
        $mockProd->method('stockBajo')->willReturn([]);

        $controller = $this->crearController($mockRep, $mockProd);
        $resultado  = $controller->dashboard();

        $this->assertEquals(125.0, $resultado['indicadores']['ticket_promedio']); // 500 / 4
        $this->assertEquals(['total' => 5], $resultado['catalogo']);
    }

    public function test_dashboard_ticket_promedio_cero_sin_tickets(): void
    {
        $mockRep = $this->createMock(Reporte::class);
        $mockRep->method('indicadores')->willReturn([
            'venta_hoy' => 0, 'tickets_hoy' => 0,
            'venta_mes' => 0, 'tickets_mes' => 0,
            'venta_total' => 0, 'tickets_total' => 0,
        ]);
        $mockRep->method('ventasPorDia')->willReturn([]);
        $mockRep->method('productosMasVendidos')->willReturn([]);
        $mockRep->method('ventasPorCategoria')->willReturn([]);
        $mockRep->method('ultimasVentas')->willReturn([]);

        $mockProd = $this->createMock(Producto::class);
        $mockProd->method('resumen')->willReturn([]);
        $mockProd->method('stockBajo')->willReturn([]);

        $controller = $this->crearController($mockRep, $mockProd);
        $resultado  = $controller->dashboard();

        $this->assertEquals(0, $resultado['indicadores']['ticket_promedio']);
    }

    // ─── reportes() ──────────────────────────────────────────────────

    public function test_reportes_agrupa_serie_diaria_por_fecha(): void
    {
        $mockRep = $this->createMock(Reporte::class);
        $mockRep->method('resumenRango')->willReturn(['tickets' => 2]);
        $mockRep->method('detalleVentas')->willReturn([
            ['fecha' => '2026-08-25 10:00:00', 'total' => 50],
            ['fecha' => '2026-08-25 15:00:00', 'total' => 30],
            ['fecha' => '2026-08-26 09:00:00', 'total' => 20],
        ]);
        $mockRep->method('ventasPorCajero')->willReturn([]);
        $mockRep->method('ventasPorCategoria')->willReturn([]);
        $mockRep->method('ventasPorMetodoPago')->willReturn([]);
        $mockRep->method('productosMasVendidos')->willReturn([]);

        $mockProd = $this->createMock(Producto::class);
        $mockProd->method('stockBajo')->willReturn([]);

        $controller = $this->crearController($mockRep, $mockProd);
        $resultado  = $controller->reportes('2026-08-25', '2026-08-26');

        $this->assertCount(2, $resultado['porDia']);
        $this->assertEquals('2026-08-25', $resultado['porDia'][0]['dia']);
        $this->assertEquals(80.0, $resultado['porDia'][0]['total']);
        $this->assertEquals(2, $resultado['porDia'][0]['tickets']);
        $this->assertEquals('2026-08-26', $resultado['porDia'][1]['dia']);
        $this->assertEquals(20.0, $resultado['porDia'][1]['total']);
    }

    public function test_detalle_ventas_delega_al_modelo(): void
    {
        $mockRep = $this->createMock(Reporte::class);
        $mockRep->expects($this->once())
                ->method('detalleVentas')
                ->with('2026-08-01', '2026-08-27')
                ->willReturn([['id_venta' => 1]]);

        $controller = $this->crearController($mockRep);
        $resultado  = $controller->detalleVentas('2026-08-01', '2026-08-27');

        $this->assertEquals([['id_venta' => 1]], $resultado);
    }

    // ─── rango() ─────────────────────────────────────────────────────

    public function test_rango_usa_valores_por_defecto_si_faltan(): void
    {
        [$desde, $hasta] = ReporteController::rango(null, null);

        $this->assertEquals(date('Y-m-01'), $desde);
        $this->assertEquals(date('Y-m-d'), $hasta);
    }

    public function test_rango_rechaza_fecha_con_formato_invalido(): void
    {
        // "Hasta" es la fecha de hoy: siempre valida y siempre >= al primer
        // dia del mes actual, para que el fallback de "desde" no dispare la
        // inversion de fechas (ver test_rango_invierte_fechas_si_estan_al_reves).
        $hoy = date('Y-m-d');
        [$desde, $hasta] = ReporteController::rango('25-08-2026', $hoy);

        $this->assertEquals(date('Y-m-01'), $desde); // cae al default
        $this->assertEquals($hoy, $hasta);
    }

    public function test_rango_rechaza_fecha_inexistente(): void
    {
        $hoy = date('Y-m-d');
        [$desde, ] = ReporteController::rango('2026-02-30', $hoy);

        $this->assertEquals(date('Y-m-01'), $desde); // 30 de febrero no existe
    }

    public function test_rango_invierte_fechas_si_estan_al_reves(): void
    {
        [$desde, $hasta] = ReporteController::rango('2026-08-27', '2026-08-01');

        $this->assertEquals('2026-08-01', $desde);
        $this->assertEquals('2026-08-27', $hasta);
    }

    public function test_rango_conserva_fechas_validas_en_orden(): void
    {
        [$desde, $hasta] = ReporteController::rango('2026-08-01', '2026-08-27');

        $this->assertEquals('2026-08-01', $desde);
        $this->assertEquals('2026-08-27', $hasta);
    }
}
