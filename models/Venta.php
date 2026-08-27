<?php

require_once __DIR__ . '/../database/Conexion.php';
require_once __DIR__ . '/Inventario.php';
require_once __DIR__ . '/Configuracion.php';

/**
 * Registro y consulta de ventas.
 *
 * El registro y la anulacion se hacen dentro de una transaccion: o se graba
 * todo (comprobante, detalle, stock y kardex) o no se graba nada.
 */
class Venta
{
    private $conexion;
    private $inventario;
    private $config;

    public function __construct()
    {
        $this->conexion   = Conexion::conectar();
        $this->inventario = new Inventario();
        $this->config     = new Configuracion();
    }

    /**
     * Registra una venta completa.
     *
     * @param array $items    [['id_producto' => int, 'cantidad' => int], ...]
     * @param array $datos    id_cliente, tipo_comprobante, metodo_pago,
     *                        monto_pagado, id_usuario
     *
     * @return array ['ok' => bool, 'mensaje' => string, 'id_venta' => int|null,
     *                'comprobante' => string|null]
     */
    public function registrar($items, $datos)
    {
        if (empty($items)) {
            return ['ok' => false, 'mensaje' => 'La venta no tiene productos.'];
        }

        $tipoComprobante = $datos['tipo_comprobante'] === 'FACTURA' ? 'FACTURA' : 'BOLETA';

        // Una factura siempre necesita un cliente con RUC
        if ($tipoComprobante === 'FACTURA') {
            $validacion = $this->validarClienteFactura($datos['id_cliente'] ?? null);
            if (!$validacion['ok']) {
                return $validacion;
            }
        }

        $conexion = $this->conexion;

        try {
            $conexion->beginTransaction();

            // 1. Bloquear cada producto y verificar su stock con el precio real de la BD
            $lineas = [];
            $total  = 0.0;

            foreach ($items as $item) {
                $idProducto = (int) ($item['id_producto'] ?? 0);
                $cantidad   = (int) ($item['cantidad'] ?? 0);

                if ($idProducto <= 0 || $cantidad <= 0) {
                    $conexion->rollBack();
                    return ['ok' => false, 'mensaje' => 'Hay una línea de venta inválida.'];
                }

                $stmt = $conexion->prepare("
                    SELECT id_producto, nombre, precio_venta, stock
                    FROM productos
                    WHERE id_producto = :id AND estado = 1
                    FOR UPDATE
                ");
                $stmt->execute([':id' => $idProducto]);
                $producto = $stmt->fetch();

                if (!$producto) {
                    $conexion->rollBack();
                    return ['ok' => false, 'mensaje' => 'Un producto de la venta ya no está disponible.'];
                }

                if ((int) $producto['stock'] < $cantidad) {
                    $conexion->rollBack();
                    return [
                        'ok'      => false,
                        'mensaje' => 'Stock insuficiente de "' . $producto['nombre'] . '": quedan '
                            . (int) $producto['stock'] . ' unidades y se piden ' . $cantidad . '.',
                    ];
                }

                // El precio SIEMPRE sale de la base de datos, nunca del navegador
                $precio            = (float) $producto['precio_venta'];
                $subtotalLinea     = round($precio * $cantidad, 2);
                $total            += $subtotalLinea;

                $lineas[] = [
                    'id_producto'     => $idProducto,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal'        => $subtotalLinea,
                    'stock_anterior'  => (int) $producto['stock'],
                ];
            }

            $total = round($total, 2);

            // 2. Descomponer el IGV hacia atrás (el precio ya lo incluye: RN-03)
            $factor   = $this->config->factorIgv();
            $subtotal = round($total / $factor, 2);
            $igv      = round($total - $subtotal, 2);

            // 3. Validar el pago recibido
            $montoPagado = round((float) ($datos['monto_pagado'] ?? 0), 2);
            $metodoPago  = $datos['metodo_pago'] ?? 'EFECTIVO';

            if ($metodoPago === 'EFECTIVO') {
                if ($montoPagado < $total) {
                    $conexion->rollBack();
                    return [
                        'ok'      => false,
                        'mensaje' => 'El monto recibido (' . number_format($montoPagado, 2)
                            . ') es menor que el total de la venta (' . number_format($total, 2) . ').',
                    ];
                }
            } else {
                // Con tarjeta o billetera se cobra el importe exacto
                $montoPagado = $total;
            }

            $vuelto = round($montoPagado - $total, 2);

            // 4. Tomar el siguiente correlativo bloqueando la fila de la serie
            $comprobante = $this->siguienteCorrelativo($tipoComprobante);

            if (!$comprobante['ok']) {
                $conexion->rollBack();
                return $comprobante;
            }

            // 5. Cabecera de la venta
            $stmt = $conexion->prepare("
                INSERT INTO ventas
                    (id_usuario, id_cliente, tipo_comprobante, serie, correlativo,
                     fecha, subtotal, igv, total, monto_pagado, vuelto,
                     metodo_pago, estado)
                VALUES
                    (:id_usuario, :id_cliente, :tipo_comprobante, :serie, :correlativo,
                     NOW(), :subtotal, :igv, :total, :monto_pagado, :vuelto,
                     :metodo_pago, 'EMITIDA')
            ");

            $stmt->execute([
                ':id_usuario'       => $datos['id_usuario'],
                ':id_cliente'       => !empty($datos['id_cliente']) ? $datos['id_cliente'] : null,
                ':tipo_comprobante' => $tipoComprobante,
                ':serie'            => $comprobante['serie'],
                ':correlativo'      => $comprobante['correlativo'],
                ':subtotal'         => $subtotal,
                ':igv'              => $igv,
                ':total'            => $total,
                ':monto_pagado'     => $montoPagado,
                ':vuelto'           => $vuelto,
                ':metodo_pago'      => $metodoPago,
            ]);

            $idVenta = (int) $conexion->lastInsertId();

            // 6. Detalle, descuento de stock y kardex
            $stmtDetalle = $conexion->prepare("
                INSERT INTO detalle_venta
                    (id_venta, id_producto, cantidad, precio_unitario, subtotal)
                VALUES
                    (:id_venta, :id_producto, :cantidad, :precio_unitario, :subtotal)
            ");

            $stmtStock = $conexion->prepare(
                "UPDATE productos SET stock = stock - :cantidad WHERE id_producto = :id"
            );

            foreach ($lineas as $linea) {
                $stmtDetalle->execute([
                    ':id_venta'        => $idVenta,
                    ':id_producto'     => $linea['id_producto'],
                    ':cantidad'        => $linea['cantidad'],
                    ':precio_unitario' => $linea['precio_unitario'],
                    ':subtotal'        => $linea['subtotal'],
                ]);

                $stmtStock->execute([
                    ':cantidad' => $linea['cantidad'],
                    ':id'       => $linea['id_producto'],
                ]);

                $this->inventario->insertarMovimiento(
                    $linea['id_producto'],
                    'SALIDA',
                    $linea['cantidad'],
                    $linea['stock_anterior'],
                    $linea['stock_anterior'] - $linea['cantidad'],
                    'Venta ' . $comprobante['numero'],
                    $idVenta,
                    $datos['id_usuario']
                );
            }

            $conexion->commit();

            return [
                'ok'          => true,
                'mensaje'     => 'Venta registrada correctamente.',
                'id_venta'    => $idVenta,
                'comprobante' => $comprobante['numero'],
                'total'       => $total,
                'vuelto'      => $vuelto,
            ];
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            return ['ok' => false, 'mensaje' => 'No se pudo registrar la venta: ' . $e->getMessage()];
        }
    }

    /**
     * Reserva el siguiente numero de comprobante.
     * Debe llamarse dentro de una transaccion ya iniciada.
     */
    private function siguienteCorrelativo($tipoComprobante)
    {
        $stmt = $this->conexion->prepare("
            SELECT id_serie, serie, ultimo_correlativo
            FROM series_comprobante
            WHERE tipo_comprobante = :tipo AND estado = 1
            ORDER BY id_serie ASC
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([':tipo' => $tipoComprobante]);
        $serie = $stmt->fetch();

        if (!$serie) {
            return [
                'ok'      => false,
                'mensaje' => 'No hay una serie configurada para ' . $tipoComprobante . '.',
            ];
        }

        $correlativo = (int) $serie['ultimo_correlativo'] + 1;

        $this->conexion->prepare("
            UPDATE series_comprobante
            SET ultimo_correlativo = :correlativo
            WHERE id_serie = :id
        ")->execute([':correlativo' => $correlativo, ':id' => $serie['id_serie']]);

        return [
            'ok'          => true,
            'serie'       => $serie['serie'],
            'correlativo' => $correlativo,
            'numero'      => $serie['serie'] . '-' . str_pad($correlativo, 6, '0', STR_PAD_LEFT),
        ];
    }

    /**
     * Una factura exige un cliente identificado con RUC.
     */
    private function validarClienteFactura($idCliente)
    {
        if (empty($idCliente)) {
            return ['ok' => false, 'mensaje' => 'Para emitir una factura debe seleccionar un cliente con RUC.'];
        }

        $stmt = $this->conexion->prepare(
            "SELECT tipo_documento FROM clientes WHERE id_cliente = :id LIMIT 1"
        );
        $stmt->execute([':id' => $idCliente]);
        $tipo = $stmt->fetchColumn();

        if ($tipo !== 'RUC') {
            return ['ok' => false, 'mensaje' => 'El cliente seleccionado no tiene RUC: emita una boleta.'];
        }

        return ['ok' => true];
    }

    /**
     * Anula una venta y devuelve las unidades al stock.
     */
    public function anular($idVenta, $motivo, $idUsuario)
    {
        $conexion = $this->conexion;

        try {
            $conexion->beginTransaction();

            $stmt = $conexion->prepare("
                SELECT id_venta, estado, serie, correlativo
                FROM ventas
                WHERE id_venta = :id
                FOR UPDATE
            ");
            $stmt->execute([':id' => $idVenta]);
            $venta = $stmt->fetch();

            if (!$venta) {
                $conexion->rollBack();
                return ['ok' => false, 'mensaje' => 'La venta no existe.'];
            }

            if ($venta['estado'] === 'ANULADA') {
                $conexion->rollBack();
                return ['ok' => false, 'mensaje' => 'La venta ya estaba anulada.'];
            }

            $numero = $venta['serie'] . '-' . str_pad($venta['correlativo'], 6, '0', STR_PAD_LEFT);

            // Devolver cada producto al stock
            $stmt = $conexion->prepare(
                "SELECT id_producto, cantidad FROM detalle_venta WHERE id_venta = :id"
            );
            $stmt->execute([':id' => $idVenta]);
            $detalles = $stmt->fetchAll();

            foreach ($detalles as $detalle) {
                $stmtStock = $conexion->prepare(
                    "SELECT stock FROM productos WHERE id_producto = :id FOR UPDATE"
                );
                $stmtStock->execute([':id' => $detalle['id_producto']]);
                $stockAnterior = (int) $stmtStock->fetchColumn();

                $stockNuevo = $stockAnterior + (int) $detalle['cantidad'];

                $conexion->prepare(
                    "UPDATE productos SET stock = :stock WHERE id_producto = :id"
                )->execute([':stock' => $stockNuevo, ':id' => $detalle['id_producto']]);

                $this->inventario->insertarMovimiento(
                    $detalle['id_producto'],
                    'ENTRADA',
                    (int) $detalle['cantidad'],
                    $stockAnterior,
                    $stockNuevo,
                    'Anulación de la venta ' . $numero,
                    $idVenta,
                    $idUsuario
                );
            }

            $conexion->prepare("
                UPDATE ventas
                SET estado = 'ANULADA',
                    motivo_anulacion = :motivo,
                    fecha_anulacion = NOW()
                WHERE id_venta = :id
            ")->execute([':motivo' => $motivo, ':id' => $idVenta]);

            $conexion->commit();

            return ['ok' => true, 'mensaje' => 'La venta ' . $numero . ' fue anulada y el stock se restituyó.'];
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            return ['ok' => false, 'mensaje' => 'No se pudo anular la venta: ' . $e->getMessage()];
        }
    }

    /**
     * Historial de ventas con filtros.
     */
    public function listar($filtros = [], $limite = 200)
    {
        $sql = "
            SELECT
                v.*,
                u.nombre AS cajero,
                TRIM(COALESCE(c.razon_social,
                     CONCAT(COALESCE(c.apellidos,''), ' ', COALESCE(c.nombres,'')))) AS cliente,
                c.numero_documento,
                (SELECT COUNT(*) FROM detalle_venta d WHERE d.id_venta = v.id_venta) AS items
            FROM ventas v
            INNER JOIN usuarios u ON u.id_usuario = v.id_usuario
            LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
            WHERE 1 = 1
        ";
        $parametros = [];

        if (!empty($filtros['desde'])) {
            $sql .= " AND v.fecha >= :desde";
            $parametros[':desde'] = $filtros['desde'] . ' 00:00:00';
        }

        if (!empty($filtros['hasta'])) {
            $sql .= " AND v.fecha <= :hasta";
            $parametros[':hasta'] = $filtros['hasta'] . ' 23:59:59';
        }

        if (!empty($filtros['estado'])) {
            $sql .= " AND v.estado = :estado";
            $parametros[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['tipo_comprobante'])) {
            $sql .= " AND v.tipo_comprobante = :tipo";
            $parametros[':tipo'] = $filtros['tipo_comprobante'];
        }

        if (!empty($filtros['id_usuario'])) {
            $sql .= " AND v.id_usuario = :id_usuario";
            $parametros[':id_usuario'] = $filtros['id_usuario'];
        }

        if (!empty($filtros['busqueda'])) {
            // Cada placeholder debe ser único: las sentencias no son emuladas
            $sql .= " AND (CONCAT(v.serie, '-', LPAD(v.correlativo, 6, '0')) LIKE :busqueda_numero
                        OR c.numero_documento LIKE :busqueda_doc)";
            $parametros[':busqueda_numero'] = '%' . $filtros['busqueda'] . '%';
            $parametros[':busqueda_doc']    = '%' . $filtros['busqueda'] . '%';
        }

        $sql .= " ORDER BY v.fecha DESC, v.id_venta DESC LIMIT " . (int) $limite;

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function obtenerPorId($idVenta)
    {
        $stmt = $this->conexion->prepare("
            SELECT
                v.*,
                u.nombre AS cajero,
                c.tipo_documento, c.numero_documento, c.direccion AS cliente_direccion,
                TRIM(COALESCE(c.razon_social,
                     CONCAT(COALESCE(c.apellidos,''), ' ', COALESCE(c.nombres,'')))) AS cliente
            FROM ventas v
            INNER JOIN usuarios u ON u.id_usuario = v.id_usuario
            LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
            WHERE v.id_venta = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $idVenta]);

        return $stmt->fetch();
    }

    public function detalle($idVenta)
    {
        $stmt = $this->conexion->prepare("
            SELECT
                d.*,
                p.nombre AS producto,
                p.codigo_barras,
                p.unidad_medida
            FROM detalle_venta d
            INNER JOIN productos p ON p.id_producto = d.id_producto
            WHERE d.id_venta = :id
            ORDER BY d.id_detalle ASC
        ");
        $stmt->execute([':id' => $idVenta]);

        return $stmt->fetchAll();
    }

    /**
     * Numero de comprobante formateado, por ejemplo B001-000001.
     */
    public static function numeroComprobante($serie, $correlativo)
    {
        return $serie . '-' . str_pad($correlativo, 6, '0', STR_PAD_LEFT);
    }
}
