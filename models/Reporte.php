<?php

require_once __DIR__ . '/../database/Conexion.php';

/**
 * Consultas agregadas para el dashboard y el módulo de reportes.
 * Las ventas anuladas nunca suman en los indicadores.
 */
class Reporte
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    /**
     * Indicadores principales del dashboard.
     */
    public function indicadores()
    {
        return $this->conexion->query("
            SELECT
                COALESCE(SUM(CASE WHEN DATE(fecha) = CURDATE() THEN total END), 0)     AS venta_hoy,
                COUNT(CASE WHEN DATE(fecha) = CURDATE() THEN 1 END)                    AS tickets_hoy,
                COALESCE(SUM(CASE WHEN YEAR(fecha) = YEAR(CURDATE())
                                   AND MONTH(fecha) = MONTH(CURDATE()) THEN total END), 0) AS venta_mes,
                COUNT(CASE WHEN YEAR(fecha) = YEAR(CURDATE())
                            AND MONTH(fecha) = MONTH(CURDATE()) THEN 1 END)           AS tickets_mes,
                COALESCE(SUM(total), 0)                                               AS venta_total,
                COUNT(*)                                                              AS tickets_total
            FROM ventas
            WHERE estado = 'EMITIDA'
        ")->fetch();
    }

    /**
     * Ventas de los últimos N días, incluyendo los días sin ventas.
     */
    public function ventasPorDia($dias = 7)
    {
        $stmt = $this->conexion->prepare("
            SELECT DATE(fecha) AS dia,
                   SUM(total)  AS total,
                   COUNT(*)    AS tickets
            FROM ventas
            WHERE estado = 'EMITIDA'
              AND fecha >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
            GROUP BY DATE(fecha)
        ");
        $stmt->bindValue(':dias', (int) $dias - 1, PDO::PARAM_INT);
        $stmt->execute();

        // Indexar por fecha para rellenar los días vacíos
        $porDia = [];
        foreach ($stmt->fetchAll() as $fila) {
            $porDia[$fila['dia']] = $fila;
        }

        $serie = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $dia = date('Y-m-d', strtotime("-$i day"));

            $serie[] = [
                'dia'     => $dia,
                'etiqueta' => date('d/m', strtotime($dia)),
                'total'   => (float) ($porDia[$dia]['total'] ?? 0),
                'tickets' => (int) ($porDia[$dia]['tickets'] ?? 0),
            ];
        }

        return $serie;
    }

    /**
     * Productos más vendidos en el rango indicado.
     */
    public function productosMasVendidos($desde = null, $hasta = null, $limite = 10)
    {
        $sql = "
            SELECT
                p.id_producto,
                p.nombre,
                c.nombre           AS categoria,
                SUM(d.cantidad)    AS unidades,
                SUM(d.subtotal)    AS importe
            FROM detalle_venta d
            INNER JOIN ventas v    ON v.id_venta = d.id_venta
            INNER JOIN productos p ON p.id_producto = d.id_producto
            INNER JOIN categorias c ON c.id_categoria = p.id_categoria
            WHERE v.estado = 'EMITIDA'
        ";
        $parametros = [];

        if ($desde) {
            $sql .= " AND v.fecha >= :desde";
            $parametros[':desde'] = $desde . ' 00:00:00';
        }

        if ($hasta) {
            $sql .= " AND v.fecha <= :hasta";
            $parametros[':hasta'] = $hasta . ' 23:59:59';
        }

        $sql .= " GROUP BY p.id_producto, p.nombre, c.nombre
                  ORDER BY unidades DESC
                  LIMIT " . (int) $limite;

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Importe vendido por categoría.
     */
    public function ventasPorCategoria($desde = null, $hasta = null)
    {
        $sql = "
            SELECT
                c.nombre        AS categoria,
                SUM(d.cantidad) AS unidades,
                SUM(d.subtotal) AS importe
            FROM detalle_venta d
            INNER JOIN ventas v     ON v.id_venta = d.id_venta
            INNER JOIN productos p  ON p.id_producto = d.id_producto
            INNER JOIN categorias c ON c.id_categoria = p.id_categoria
            WHERE v.estado = 'EMITIDA'
        ";
        $parametros = [];

        if ($desde) {
            $sql .= " AND v.fecha >= :desde";
            $parametros[':desde'] = $desde . ' 00:00:00';
        }

        if ($hasta) {
            $sql .= " AND v.fecha <= :hasta";
            $parametros[':hasta'] = $hasta . ' 23:59:59';
        }

        $sql .= " GROUP BY c.id_categoria, c.nombre ORDER BY importe DESC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Resumen de ventas por cajero.
     */
    public function ventasPorCajero($desde = null, $hasta = null)
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.nombre       AS cajero,
                COUNT(v.id_venta) AS tickets,
                COALESCE(SUM(v.total), 0) AS importe,
                COALESCE(AVG(v.total), 0) AS ticket_promedio
            FROM usuarios u
            LEFT JOIN ventas v
                ON v.id_usuario = u.id_usuario AND v.estado = 'EMITIDA'
        ";
        $parametros = [];

        if ($desde) {
            $sql .= " AND v.fecha >= :desde";
            $parametros[':desde'] = $desde . ' 00:00:00';
        }

        if ($hasta) {
            $sql .= " AND v.fecha <= :hasta";
            $parametros[':hasta'] = $hasta . ' 23:59:59';
        }

        $sql .= " GROUP BY u.id_usuario, u.nombre
                  HAVING tickets > 0
                  ORDER BY importe DESC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Totales del rango: importes, IGV, tickets y ticket promedio.
     */
    public function resumenRango($desde, $hasta)
    {
        $stmt = $this->conexion->prepare("
            SELECT
                COUNT(*)                  AS tickets,
                COALESCE(SUM(subtotal),0) AS subtotal,
                COALESCE(SUM(igv),0)      AS igv,
                COALESCE(SUM(total),0)    AS total,
                COALESCE(AVG(total),0)    AS ticket_promedio,
                COUNT(CASE WHEN tipo_comprobante = 'BOLETA'  THEN 1 END) AS boletas,
                COUNT(CASE WHEN tipo_comprobante = 'FACTURA' THEN 1 END) AS facturas
            FROM ventas
            WHERE estado = 'EMITIDA'
              AND fecha BETWEEN :desde AND :hasta
        ");

        $stmt->execute([
            ':desde' => $desde . ' 00:00:00',
            ':hasta' => $hasta . ' 23:59:59',
        ]);

        return $stmt->fetch();
    }

    /**
     * Importe vendido por método de pago.
     */
    public function ventasPorMetodoPago($desde, $hasta)
    {
        $stmt = $this->conexion->prepare("
            SELECT metodo_pago, COUNT(*) AS tickets, SUM(total) AS importe
            FROM ventas
            WHERE estado = 'EMITIDA'
              AND fecha BETWEEN :desde AND :hasta
            GROUP BY metodo_pago
            ORDER BY importe DESC
        ");

        $stmt->execute([
            ':desde' => $desde . ' 00:00:00',
            ':hasta' => $hasta . ' 23:59:59',
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Detalle de ventas del rango, para el reporte exportable.
     */
    public function detalleVentas($desde, $hasta)
    {
        $stmt = $this->conexion->prepare("
            SELECT
                v.id_venta,
                CONCAT(v.serie, '-', LPAD(v.correlativo, 6, '0')) AS comprobante,
                v.tipo_comprobante,
                v.fecha,
                v.subtotal, v.igv, v.total,
                v.metodo_pago,
                u.nombre AS cajero,
                TRIM(COALESCE(c.razon_social,
                     CONCAT(COALESCE(c.apellidos,''), ' ', COALESCE(c.nombres,'')))) AS cliente
            FROM ventas v
            INNER JOIN usuarios u ON u.id_usuario = v.id_usuario
            LEFT JOIN clientes c  ON c.id_cliente = v.id_cliente
            WHERE v.estado = 'EMITIDA'
              AND v.fecha BETWEEN :desde AND :hasta
            ORDER BY v.fecha ASC
        ");

        $stmt->execute([
            ':desde' => $desde . ' 00:00:00',
            ':hasta' => $hasta . ' 23:59:59',
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Últimas ventas registradas, para el panel del dashboard.
     */
    public function ultimasVentas($limite = 8)
    {
        $stmt = $this->conexion->prepare("
            SELECT
                v.id_venta,
                CONCAT(v.serie, '-', LPAD(v.correlativo, 6, '0')) AS comprobante,
                v.fecha, v.total, v.estado,
                u.nombre AS cajero
            FROM ventas v
            INNER JOIN usuarios u ON u.id_usuario = v.id_usuario
            ORDER BY v.fecha DESC, v.id_venta DESC
            LIMIT :limite
        ");

        $stmt->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
