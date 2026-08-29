<?php

require_once __DIR__ . '/../models/Reporte.php';
require_once __DIR__ . '/../models/Producto.php';

/**
 * Indicadores del dashboard y reportes de gestión.
 */
class ReporteController
{
    private $reporte;
    private $productos;

    public function __construct()
    {
        $this->reporte   = new Reporte();
        $this->productos = new Producto();
    }

    /**
     * Todo lo que necesita la pantalla del dashboard.
     */
    public function dashboard()
    {
        $indicadores = $this->reporte->indicadores();
        $catalogo    = $this->productos->resumen();

        // Ticket promedio del mes
        $ticketsMes = (int) $indicadores['tickets_mes'];
        $indicadores['ticket_promedio'] = $ticketsMes > 0
            ? (float) $indicadores['venta_mes'] / $ticketsMes
            : 0;

        return [
            'indicadores'   => $indicadores,
            'catalogo'      => $catalogo,
            'stockBajo'     => $this->productos->stockBajo(8),
            'ultimasVentas' => $this->reporte->ultimasVentas(8),
        ];
    }

    /**
     * Datos del módulo de reportes para un rango de fechas.
     */
    public function reportes($desde, $hasta)
    {
        return [
            'resumen'      => $this->reporte->resumenRango($desde, $hasta),
            'porDia'       => $this->ventasPorDiaRango($desde, $hasta),
            'porCajero'    => $this->reporte->ventasPorCajero($desde, $hasta),
            'porCategoria' => $this->reporte->ventasPorCategoria($desde, $hasta),
            'porMetodo'    => $this->reporte->ventasPorMetodoPago($desde, $hasta),
            'masVendidos'  => $this->reporte->productosMasVendidos($desde, $hasta, 15),
            'stockBajo'    => $this->productos->stockBajo(),
        ];
    }

    /**
     * Serie diaria acotada al rango elegido por el usuario.
     */
    private function ventasPorDiaRango($desde, $hasta)
    {
        $detalle = $this->reporte->detalleVentas($desde, $hasta);

        $porDia = [];
        foreach ($detalle as $venta) {
            $dia = date('Y-m-d', strtotime($venta['fecha']));

            if (!isset($porDia[$dia])) {
                $porDia[$dia] = ['dia' => $dia, 'etiqueta' => date('d/m', strtotime($dia)),
                                 'total' => 0, 'tickets' => 0];
            }

            $porDia[$dia]['total']   += (float) $venta['total'];
            $porDia[$dia]['tickets'] += 1;
        }

        ksort($porDia);

        return array_values($porDia);
    }

    public function detalleVentas($desde, $hasta)
    {
        return $this->reporte->detalleVentas($desde, $hasta);
    }

    /**
     * Valida el rango recibido por GET y lo devuelve normalizado.
     */
    public static function rango($desde, $hasta)
    {
        $desde = self::fechaValida($desde) ? $desde : date('Y-m-01');
        $hasta = self::fechaValida($hasta) ? $hasta : date('Y-m-d');

        // Si el usuario invierte las fechas, se corrigen solas
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde, $hasta];
    }

    private static function fechaValida($fecha)
    {
        if (!is_string($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }

        [$anio, $mes, $dia] = explode('-', $fecha);

        return checkdate((int) $mes, (int) $dia, (int) $anio);
    }
}
