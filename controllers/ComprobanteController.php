<?php

require_once __DIR__ . '/../lib/fpdf/fpdf.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Configuracion.php';

/**
 * Genera el PDF del comprobante en formato ticket de 80 mm.
 */
class ComprobanteController
{
    /** Ancho del papel del ticket, en milimetros */
    const ANCHO = 80;
    const MARGEN = 4;

    private $venta;
    private $config;

    public function __construct()
    {
        $this->venta  = new Venta();
        $this->config = new Configuracion();
    }

    /**
     * Arma el PDF y lo devuelve.
     *
     * @param int    $idVenta
     * @param string $destino 'I' muestra en el navegador, 'F' guarda en disco
     *
     * @return array ['ok' => bool, 'mensaje' => string, 'ruta' => string|null]
     */
    public function generar($idVenta, $destino = 'I')
    {
        $venta = $this->venta->obtenerPorId($idVenta);

        if (!$venta) {
            return ['ok' => false, 'mensaje' => 'El comprobante solicitado no existe.'];
        }

        $detalle = $this->venta->detalle($idVenta);
        $config  = $this->config->todos();

        $numero = Venta::numeroComprobante($venta['serie'], $venta['correlativo']);

        // La altura del ticket depende de cuántas líneas tiene la venta
        $alto = 120 + (count($detalle) * 8);

        $pdf = new FPDF('P', 'mm', [self::ANCHO, $alto]);
        $pdf->SetMargins(self::MARGEN, 5, self::MARGEN);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $ancho = self::ANCHO - (self::MARGEN * 2);

        // ---------------------------------------------------- encabezado
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->MultiCell($ancho, 5, $this->texto($config['razon_social'] ?? 'MINIMARKET'), 0, 'C');

        $pdf->SetFont('Helvetica', '', 7);
        $pdf->MultiCell($ancho, 3.5, $this->texto($config['direccion'] ?? ''), 0, 'C');
        $pdf->Cell($ancho, 3.5, $this->texto('RUC: ' . ($config['ruc'] ?? '')), 0, 1, 'C');
        $pdf->Cell($ancho, 3.5, $this->texto('Tel.: ' . ($config['telefono'] ?? '')), 0, 1, 'C');

        $pdf->Ln(2);
        $this->linea($pdf, $ancho);

        // ---------------------------------------------------- tipo y número
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($ancho, 5, $this->texto($venta['tipo_comprobante'] . ' DE VENTA ELECTRÓNICA'), 0, 1, 'C');
        $pdf->Cell($ancho, 5, $numero, 0, 1, 'C');

        if ($venta['estado'] === 'ANULADA') {
            $pdf->SetTextColor(200, 0, 0);
            $pdf->Cell($ancho, 5, '*** ANULADA ***', 0, 1, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }

        $this->linea($pdf, $ancho);

        // ---------------------------------------------------- datos de la venta
        $pdf->SetFont('Helvetica', '', 7);
        $this->campo($pdf, $ancho, 'Fecha:', date('d/m/Y H:i', strtotime($venta['fecha'])));
        $this->campo($pdf, $ancho, 'Cajero:', $venta['cajero']);

        if (!empty($venta['cliente'])) {
            $this->campo($pdf, $ancho, 'Cliente:', $venta['cliente']);
            $this->campo($pdf, $ancho, $venta['tipo_documento'] . ':', $venta['numero_documento']);

            if (!empty($venta['cliente_direccion'])) {
                $this->campo($pdf, $ancho, 'Dirección:', $venta['cliente_direccion']);
            }
        } else {
            $this->campo($pdf, $ancho, 'Cliente:', 'Cliente varios');
        }

        $this->campo($pdf, $ancho, 'Pago:', ucfirst(strtolower($venta['metodo_pago'])));

        $this->linea($pdf, $ancho);

        // ---------------------------------------------------- detalle
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(10, 4, 'CANT', 0, 0, 'L');
        $pdf->Cell($ancho - 34, 4, $this->texto('DESCRIPCIÓN'), 0, 0, 'L');
        $pdf->Cell(12, 4, 'P.U.', 0, 0, 'R');
        $pdf->Cell(12, 4, 'TOTAL', 0, 1, 'R');

        $this->linea($pdf, $ancho);

        $pdf->SetFont('Helvetica', '', 7);
        foreach ($detalle as $linea) {
            $pdf->Cell(10, 4, (int) $linea['cantidad'], 0, 0, 'L');
            $pdf->Cell($ancho - 34, 4, $this->texto($this->recortar($linea['producto'], 26)), 0, 0, 'L');
            $pdf->Cell(12, 4, number_format($linea['precio_unitario'], 2), 0, 0, 'R');
            $pdf->Cell(12, 4, number_format($linea['subtotal'], 2), 0, 1, 'R');
        }

        $this->linea($pdf, $ancho);

        // ---------------------------------------------------- totales
        $porcentajeIgv = (int) ($config['igv'] ?? 18);

        $pdf->SetFont('Helvetica', '', 8);
        $this->total($pdf, $ancho, 'Op. gravada:', $venta['subtotal']);
        $this->total($pdf, $ancho, 'IGV (' . $porcentajeIgv . '%):', $venta['igv']);

        $pdf->SetFont('Helvetica', 'B', 10);
        $this->total($pdf, $ancho, 'TOTAL:', $venta['total']);

        if ($venta['metodo_pago'] === 'EFECTIVO') {
            $pdf->SetFont('Helvetica', '', 8);
            $this->total($pdf, $ancho, 'Recibido:', $venta['monto_pagado']);
            $this->total($pdf, $ancho, 'Vuelto:', $venta['vuelto']);
        }

        $pdf->Ln(1);
        $pdf->SetFont('Helvetica', '', 6.5);
        $pdf->MultiCell($ancho, 3.2, $this->texto('SON: ' . $this->numeroALetras($venta['total'])), 0, 'L');

        $this->linea($pdf, $ancho);

        // ---------------------------------------------------- pie
        $pdf->SetFont('Helvetica', '', 6.5);
        $pdf->MultiCell($ancho, 3.2, $this->texto(
            '¡Gracias por su compra!' . "\n" .
            'Conserve este comprobante.' . "\n" .
            'Representación impresa del comprobante electrónico.'
        ), 0, 'C');

        // ---------------------------------------------------- salida
        if ($destino === 'F') {
            $ruta = BASE_PATH . '/storage/comprobantes/' . $numero . '.pdf';
            $pdf->Output('F', $ruta);

            return ['ok' => true, 'mensaje' => 'Comprobante generado.', 'ruta' => $ruta];
        }

        $pdf->Output('I', $numero . '.pdf');
        exit;
    }

    // ---------------------------------------------------------- utilidades

    /**
     * FPDF trabaja en ISO-8859-1: hay que convertir el texto UTF-8.
     */
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

    private function linea($pdf, $ancho)
    {
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell($ancho, 3, str_repeat('-', 48), 0, 1, 'C');
    }

    private function campo($pdf, $ancho, $etiqueta, $valor)
    {
        $pdf->Cell(18, 3.6, $this->texto($etiqueta), 0, 0, 'L');
        $pdf->Cell($ancho - 18, 3.6, $this->texto($valor), 0, 1, 'L');
    }

    private function total($pdf, $ancho, $etiqueta, $monto)
    {
        $pdf->Cell($ancho - 22, 4.5, $this->texto($etiqueta), 0, 0, 'R');
        $pdf->Cell(22, 4.5, 'S/ ' . number_format($monto, 2), 0, 1, 'R');
    }

    /**
     * Convierte un monto a su expresion en letras.
     * Ejemplo: 125.50 -> "CIENTO VEINTICINCO CON 50/100 SOLES"
     */
    public function numeroALetras($monto)
    {
        $monto     = round((float) $monto, 2);
        $entero    = (int) floor($monto);
        $centavos  = (int) round(($monto - $entero) * 100);

        $letras = $entero === 0 ? 'CERO' : $this->convertirEntero($entero);

        return trim($letras) . ' CON ' . str_pad($centavos, 2, '0', STR_PAD_LEFT) . '/100 SOLES';
    }

    private function convertirEntero($numero)
    {
        if ($numero === 0)   return '';
        if ($numero === 100) return 'CIEN';

        $unidades = [
            '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE',
            'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE',
            'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
            'VEINTE',
        ];

        $decenas = [
3 => 'TREINTA', 4 => 'CUARENTA', 5 => 'CINCUENTA',
            6 => 'SESENTA', 7 => 'SETENTA', 8 => 'OCHENTA', 9 => 'NOVENTA',
        ];

        $centenas = [
            1 => 'CIENTO', 2 => 'DOSCIENTOS', 3 => 'TRESCIENTOS',
            4 => 'CUATROCIENTOS', 5 => 'QUINIENTOS', 6 => 'SEISCIENTOS',
            7 => 'SETECIENTOS', 8 => 'OCHOCIENTOS', 9 => 'NOVECIENTOS',
        ];

        if ($numero <= 20) {
            return $unidades[$numero];
        }

        // Del 21 al 29 se escriben en una sola palabra
        $veintis = [
            21 => 'VEINTIUNO', 22 => 'VEINTIDÓS', 23 => 'VEINTITRÉS',
            24 => 'VEINTICUATRO', 25 => 'VEINTICINCO', 26 => 'VEINTISÉIS',
            27 => 'VEINTISIETE', 28 => 'VEINTIOCHO', 29 => 'VEINTINUEVE',
        ];

        if (isset($veintis[$numero])) {
            return $veintis[$numero];
        }

        if ($numero < 100) {
            $decena = (int) floor($numero / 10);
            $unidad = $numero % 10;

            return $decenas[$decena] . ($unidad > 0 ? ' Y ' . $unidades[$unidad] : '');
        }

        if ($numero < 1000) {
            $centena = (int) floor($numero / 100);
            $resto   = $numero % 100;

            return $centenas[$centena] . ($resto > 0 ? ' ' . $this->convertirEntero($resto) : '');
        }

        if ($numero < 1000000) {
            $miles = (int) floor($numero / 1000);
            $resto = $numero % 1000;

            $prefijo = $miles === 1 ? 'MIL' : $this->convertirEntero($miles) . ' MIL';

            return $prefijo . ($resto > 0 ? ' ' . $this->convertirEntero($resto) : '');
        }

        $millones = (int) floor($numero / 1000000);
        $resto    = $numero % 1000000;

        $prefijo = $millones === 1 ? 'UN MILLÓN' : $this->convertirEntero($millones) . ' MILLONES';

        return $prefijo . ($resto > 0 ? ' ' . $this->convertirEntero($resto) : '');
    }
}
