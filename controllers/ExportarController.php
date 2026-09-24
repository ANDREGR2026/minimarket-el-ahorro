<?php

require_once __DIR__ . '/../lib/fpdf/fpdf.php';
require_once __DIR__ . '/../controllers/ReporteController.php';
require_once __DIR__ . '/../models/Configuracion.php';

/**
 * Exporta el reporte de ventas a PDF (A4) y a CSV para Excel.
 */
class ExportarController
{
    private $reportes;
    private $config;

    public function __construct()
    {
        $this->reportes = new ReporteController();
        $this->config   = new Configuracion();
    }

    /**
     * Descarga el reporte del rango en formato CSV.
     * Se usa punto y coma como separador: es lo que espera Excel en español.
     */
    public function csv($desde, $hasta)
    {
        $ventas  = $this->reportes->detalleVentas($desde, $hasta);
        $archivo = 'reporte_ventas_' . $desde . '_a_' . $hasta . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');

        $salida = fopen('php://output', 'w');

        // BOM para que Excel reconozca los acentos
        fwrite($salida, "\xEF\xBB\xBF");

        fputcsv($salida, [
            'Comprobante', 'Tipo', 'Fecha', 'Cliente', 'Cajero',
            'Metodo de pago', 'Op. gravada', 'IGV', 'Total',
        ], ';');

        $totales = ['subtotal' => 0, 'igv' => 0, 'total' => 0];

        foreach ($ventas as $venta) {
            fputcsv($salida, [
                $venta['comprobante'],
                $venta['tipo_comprobante'],
                date('d/m/Y H:i', strtotime($venta['fecha'])),
                $venta['cliente'] ?: 'Cliente varios',
                $venta['cajero'],
                $venta['metodo_pago'],
                number_format($venta['subtotal'], 2, '.', ''),
                number_format($venta['igv'], 2, '.', ''),
                number_format($venta['total'], 2, '.', ''),
            ], ';');

            $totales['subtotal'] += (float) $venta['subtotal'];
            $totales['igv']      += (float) $venta['igv'];
            $totales['total']    += (float) $venta['total'];
        }

        fputcsv($salida, [], ';');
        fputcsv($salida, [
            'TOTALES', '', '', '', '', '',
            number_format($totales['subtotal'], 2, '.', ''),
            number_format($totales['igv'], 2, '.', ''),
            number_format($totales['total'], 2, '.', ''),
        ], ';');

        fclose($salida);
        exit;
    }

    /**
     * Genera el reporte del rango en PDF tamaño A4.
     */
    public function pdf($desde, $hasta)
    {
        $datos   = $this->reportes->reportes($desde, $hasta);
        $ventas  = $this->reportes->detalleVentas($desde, $hasta);
        $config  = $this->config->todos();
        $resumen = $datos['resumen'];
        $sim     = ($config['moneda'] ?? 'S/') . ' ';

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // ---------------------------------------------------- encabezado
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->Cell(0, 7, $this->texto($config['razon_social'] ?? 'MINIMARKET'), 0, 1, 'C');

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, $this->texto('RUC: ' . ($config['ruc'] ?? '')), 0, 1, 'C');

        $pdf->Ln(3);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 7, $this->texto('REPORTE DE VENTAS'), 0, 1, 'C');

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(0, 5, $this->texto(
            'Período: ' . date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta))
        ), 0, 1, 'C');
        $pdf->Cell(0, 5, $this->texto('Generado el ' . date('d/m/Y H:i')), 0, 1, 'C');

        $pdf->Ln(4);

        // ---------------------------------------------------- resumen
        $this->titulo($pdf, 'RESUMEN DEL PERÍODO');

        $pdf->SetFont('Helvetica', '', 9);
        $this->filaResumen($pdf, 'Comprobantes emitidos', (int) $resumen['tickets']);
        $this->filaResumen($pdf, 'Boletas / Facturas',
            (int) $resumen['boletas'] . ' / ' . (int) $resumen['facturas']);
        $this->filaResumen($pdf, 'Operación gravada', $sim . number_format($resumen['subtotal'], 2));
        $this->filaResumen($pdf, 'IGV recaudado', $sim . number_format($resumen['igv'], 2));
        $this->filaResumen($pdf, 'Total vendido', $sim . number_format($resumen['total'], 2));
        $this->filaResumen($pdf, 'Ticket promedio', $sim . number_format($resumen['ticket_promedio'], 2));

        $pdf->Ln(4);

        // ---------------------------------------------------- por cajero
        if (!empty($datos['porCajero'])) {
            $this->titulo($pdf, 'VENTAS POR CAJERO');
            $this->cabecera($pdf, [
                ['Cajero', 80, 'L'], ['Comprobantes', 35, 'C'],
                ['Importe', 35, 'R'], ['Ticket prom.', 36, 'R'],
            ]);

            $pdf->SetFont('Helvetica', '', 9);
            foreach ($datos['porCajero'] as $fila) {
                $pdf->Cell(80, 6, $this->texto($fila['cajero']), 'B', 0, 'L');
                $pdf->Cell(35, 6, (int) $fila['tickets'], 'B', 0, 'C');
                $pdf->Cell(35, 6, $sim . number_format($fila['importe'], 2), 'B', 0, 'R');
                $pdf->Cell(36, 6, $sim . number_format($fila['ticket_promedio'], 2), 'B', 1, 'R');
            }

            $pdf->Ln(4);
        }

        // ---------------------------------------------------- por categoría
        if (!empty($datos['porCategoria'])) {
            $this->titulo($pdf, 'VENTAS POR CATEGORÍA');
            $this->cabecera($pdf, [
                ['Categoría', 100, 'L'], ['Unidades', 40, 'C'], ['Importe', 46, 'R'],
            ]);

            $pdf->SetFont('Helvetica', '', 9);
            foreach ($datos['porCategoria'] as $fila) {
                $pdf->Cell(100, 6, $this->texto($fila['categoria']), 'B', 0, 'L');
                $pdf->Cell(40, 6, (int) $fila['unidades'], 'B', 0, 'C');
                $pdf->Cell(46, 6, $sim . number_format($fila['importe'], 2), 'B', 1, 'R');
            }

            $pdf->Ln(4);
        }

        // ---------------------------------------------------- más vendidos
        if (!empty($datos['masVendidos'])) {
            $this->titulo($pdf, 'PRODUCTOS MÁS VENDIDOS');
            $this->cabecera($pdf, [
                ['#', 10, 'C'], ['Producto', 90, 'L'],
                ['Unidades', 40, 'C'], ['Importe', 46, 'R'],
            ]);

            $pdf->SetFont('Helvetica', '', 9);
            foreach ($datos['masVendidos'] as $indice => $fila) {
                $pdf->Cell(10, 6, $indice + 1, 'B', 0, 'C');
                $pdf->Cell(90, 6, $this->texto($this->recortar($fila['nombre'], 45)), 'B', 0, 'L');
                $pdf->Cell(40, 6, (int) $fila['unidades'], 'B', 0, 'C');
                $pdf->Cell(46, 6, $sim . number_format($fila['importe'], 2), 'B', 1, 'R');
            }

            $pdf->Ln(4);
        }

        // ---------------------------------------------------- detalle
        $pdf->AddPage();
        $this->titulo($pdf, 'DETALLE DE COMPROBANTES');
        $this->cabecera($pdf, [
            ['Comprobante', 28, 'L'], ['Fecha', 28, 'L'], ['Cliente', 55, 'L'],
            ['Cajero', 35, 'L'], ['IGV', 22, 'R'], ['Total', 18, 'R'],
        ]);

        $pdf->SetFont('Helvetica', '', 8);

        if (empty($ventas)) {
            $pdf->Cell(0, 8, $this->texto('No hay comprobantes emitidos en el período.'), 0, 1, 'C');
        }

        foreach ($ventas as $venta) {
            $pdf->Cell(28, 5.5, $venta['comprobante'], 'B', 0, 'L');
            $pdf->Cell(28, 5.5, date('d/m/Y H:i', strtotime($venta['fecha'])), 'B', 0, 'L');
            $pdf->Cell(55, 5.5, $this->texto($this->recortar($venta['cliente'] ?: 'Cliente varios', 30)), 'B', 0, 'L');
            $pdf->Cell(35, 5.5, $this->texto($this->recortar($venta['cajero'], 20)), 'B', 0, 'L');
            $pdf->Cell(22, 5.5, number_format($venta['igv'], 2), 'B', 0, 'R');
            $pdf->Cell(18, 5.5, number_format($venta['total'], 2), 'B', 1, 'R');
        }

        // Totales del detalle
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(146, 7, $this->texto('TOTAL DEL PERÍODO'), 0, 0, 'R');
        $pdf->Cell(22, 7, number_format($resumen['igv'], 2), 0, 0, 'R');
        $pdf->Cell(18, 7, number_format($resumen['total'], 2), 0, 1, 'R');

        $pdf->Output('I', 'reporte_ventas_' . $desde . '_a_' . $hasta . '.pdf');
        exit;
    }

    // ---------------------------------------------------------- utilidades

    private function texto($cadena)
    {
        return mb_convert_encoding((string) $cadena, 'ISO-8859-1', 'UTF-8');
    }

    private function recortar($cadena, $largo)
    {
        return mb_strlen($cadena) > $largo
            ? mb_substr($cadena, 0, $largo - 1) . '.'
            : $cadena;
    }

    private function titulo($pdf, $texto)
    {
        $pdf->SetFillColor(37, 122, 235);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(0, 7, ' ' . $this->texto($texto), 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
    }

    private function cabecera($pdf, $columnas)
    {
        $pdf->SetFillColor(240, 244, 248);
        $pdf->SetFont('Helvetica', 'B', 8.5);

        $ultima = count($columnas) - 1;
        foreach ($columnas as $indice => $columna) {
            $pdf->Cell($columna[1], 6, $this->texto($columna[0]), 'B',
                $indice === $ultima ? 1 : 0, $columna[2], true);
        }
    }

    private function filaResumen($pdf, $etiqueta, $valor)
    {
        $pdf->Cell(70, 6, $this->texto($etiqueta), 'B', 0, 'L');
        $pdf->Cell(116, 6, $this->texto($valor), 'B', 1, 'R');
    }
}
