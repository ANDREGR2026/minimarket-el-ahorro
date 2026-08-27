<?php

require_once __DIR__ . '/../database/Conexion.php';

/**
 * Movimientos de stock (kardex).
 *
 * Toda variacion de stock pasa por aqui o por el registro de una venta.
 * Asi el kardex siempre explica el stock actual de cada producto.
 */
class Inventario
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    /**
     * Registra una entrada de mercaderia: suma unidades al stock.
     *
     * @return array ['ok' => bool, 'mensaje' => string]
     */
    public function registrarEntrada($idProducto, $cantidad, $motivo, $idUsuario)
    {
        return $this->aplicarMovimiento($idProducto, 'ENTRADA', $cantidad, $motivo, $idUsuario);
    }

    /**
     * Registra una salida que no es una venta (merma, rotura, vencimiento).
     */
    public function registrarSalida($idProducto, $cantidad, $motivo, $idUsuario)
    {
        return $this->aplicarMovimiento($idProducto, 'SALIDA', $cantidad, $motivo, $idUsuario);
    }

    /**
     * Ajuste por conteo fisico: deja el stock en la cantidad indicada.
     */
    public function registrarAjuste($idProducto, $stockContado, $motivo, $idUsuario)
    {
        $conexion = $this->conexion;

        try {
            $conexion->beginTransaction();

            $stmt = $conexion->prepare(
                "SELECT stock FROM productos WHERE id_producto = :id FOR UPDATE"
            );
            $stmt->execute([':id' => $idProducto]);
            $stockAnterior = $stmt->fetchColumn();

            if ($stockAnterior === false) {
                $conexion->rollBack();
                return ['ok' => false, 'mensaje' => 'El producto no existe.'];
            }

            $stockAnterior = (int) $stockAnterior;
            $stockNuevo    = (int) $stockContado;
            $diferencia    = $stockNuevo - $stockAnterior;

            if ($diferencia === 0) {
                $conexion->rollBack();
                return ['ok' => false, 'mensaje' => 'El stock contado es igual al stock actual.'];
            }

            $conexion->prepare(
                "UPDATE productos SET stock = :stock WHERE id_producto = :id"
            )->execute([':stock' => $stockNuevo, ':id' => $idProducto]);

            $this->insertarMovimiento(
                $idProducto,
                'AJUSTE',
                abs($diferencia),
                $stockAnterior,
                $stockNuevo,
                $motivo,
                null,
                $idUsuario
            );

            $conexion->commit();

            return [
                'ok'      => true,
                'mensaje' => 'Ajuste registrado. El stock pasó de '
                    . $stockAnterior . ' a ' . $stockNuevo . ' unidades.',
            ];
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            return ['ok' => false, 'mensaje' => 'No se pudo registrar el ajuste: ' . $e->getMessage()];
        }
    }

    /**
     * Suma o resta unidades al stock dentro de una transaccion.
     */
    private function aplicarMovimiento($idProducto, $tipo, $cantidad, $motivo, $idUsuario)
    {
        $cantidad = (int) $cantidad;

        if ($cantidad <= 0) {
            return ['ok' => false, 'mensaje' => 'La cantidad debe ser mayor a cero.'];
        }

        $conexion = $this->conexion;

        try {
            $conexion->beginTransaction();

            $stmt = $conexion->prepare(
                "SELECT stock, nombre FROM productos WHERE id_producto = :id FOR UPDATE"
            );
            $stmt->execute([':id' => $idProducto]);
            $producto = $stmt->fetch();

            if (!$producto) {
                $conexion->rollBack();
                return ['ok' => false, 'mensaje' => 'El producto no existe.'];
            }

            $stockAnterior = (int) $producto['stock'];

            if ($tipo === 'SALIDA' && $cantidad > $stockAnterior) {
                $conexion->rollBack();
                return [
                    'ok'      => false,
                    'mensaje' => 'Stock insuficiente: solo hay ' . $stockAnterior
                        . ' unidades de ' . $producto['nombre'] . '.',
                ];
            }

            $stockNuevo = $tipo === 'ENTRADA'
                ? $stockAnterior + $cantidad
                : $stockAnterior - $cantidad;

            $conexion->prepare(
                "UPDATE productos SET stock = :stock WHERE id_producto = :id"
            )->execute([':stock' => $stockNuevo, ':id' => $idProducto]);

            $this->insertarMovimiento(
                $idProducto,
                $tipo,
                $cantidad,
                $stockAnterior,
                $stockNuevo,
                $motivo,
                null,
                $idUsuario
            );

            $conexion->commit();

            return [
                'ok'      => true,
                'mensaje' => 'Movimiento registrado. Stock actual: ' . $stockNuevo . ' unidades.',
            ];
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            return ['ok' => false, 'mensaje' => 'No se pudo registrar el movimiento: ' . $e->getMessage()];
        }
    }

    /**
     * Inserta la fila del kardex.
     * Es publico porque el registro de ventas tambien lo usa, dentro de
     * su propia transaccion.
     */
    public function insertarMovimiento(
        $idProducto,
        $tipo,
        $cantidad,
        $stockAnterior,
        $stockNuevo,
        $motivo,
        $idVenta,
        $idUsuario
    ) {
        $stmt = $this->conexion->prepare("
            INSERT INTO movimientos_inventario
                (id_producto, tipo, cantidad, stock_anterior, stock_nuevo,
                 motivo, id_venta, id_usuario, fecha)
            VALUES
                (:id_producto, :tipo, :cantidad, :stock_anterior, :stock_nuevo,
                 :motivo, :id_venta, :id_usuario, NOW())
        ");

        return $stmt->execute([
            ':id_producto'    => $idProducto,
            ':tipo'           => $tipo,
            ':cantidad'       => $cantidad,
            ':stock_anterior' => $stockAnterior,
            ':stock_nuevo'    => $stockNuevo,
            ':motivo'         => $motivo,
            ':id_venta'       => $idVenta,
            ':id_usuario'     => $idUsuario,
        ]);
    }

    /**
     * Kardex de un producto: todos sus movimientos, del mas reciente al mas antiguo.
     */
    public function kardex($idProducto, $limite = 100)
    {
        $stmt = $this->conexion->prepare("
            SELECT
                m.*,
                u.nombre AS usuario,
                v.serie, v.correlativo, v.tipo_comprobante
            FROM movimientos_inventario m
            INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
            LEFT JOIN ventas v ON v.id_venta = m.id_venta
            WHERE m.id_producto = :id
            ORDER BY m.fecha DESC, m.id_movimiento DESC
            LIMIT :limite
        ");

        $stmt->bindValue(':id', (int) $idProducto, PDO::PARAM_INT);
        $stmt->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Ultimos movimientos de todo el inventario.
     */
    public function ultimosMovimientos($filtros = [], $limite = 50)
    {
        $sql = "
            SELECT
                m.*,
                p.nombre AS producto,
                p.unidad_medida,
                u.nombre AS usuario
            FROM movimientos_inventario m
            INNER JOIN productos p ON p.id_producto = m.id_producto
            INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
            WHERE 1 = 1
        ";
        $parametros = [];

        if (!empty($filtros['tipo'])) {
            $sql .= " AND m.tipo = :tipo";
            $parametros[':tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['busqueda'])) {
            $sql .= " AND p.nombre LIKE :busqueda";
            $parametros[':busqueda'] = '%' . $filtros['busqueda'] . '%';
        }

        if (!empty($filtros['desde'])) {
            $sql .= " AND m.fecha >= :desde";
            $parametros[':desde'] = $filtros['desde'] . ' 00:00:00';
        }

        if (!empty($filtros['hasta'])) {
            $sql .= " AND m.fecha <= :hasta";
            $parametros[':hasta'] = $filtros['hasta'] . ' 23:59:59';
        }

        $sql .= " ORDER BY m.fecha DESC, m.id_movimiento DESC LIMIT " . (int) $limite;

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }
}
