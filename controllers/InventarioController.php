<?php

require_once __DIR__ . '/../models/Inventario.php';
require_once __DIR__ . '/../models/Producto.php';

/**
 * Movimientos de stock hechos a mano: entradas, salidas y ajustes.
 */
class InventarioController
{
    private $inventario;
    private $productos;

    public function __construct()
    {
        $this->inventario = new Inventario();
        $this->productos  = new Producto();
    }

    public function movimientos($filtros = [], $limite = 100)
    {
        return $this->inventario->ultimosMovimientos($filtros, $limite);
    }

    public function kardex($idProducto)
    {
        return $this->inventario->kardex($idProducto);
    }

    /**
     * Procesa el formulario de movimiento de inventario.
     */
    public function registrar($datos, $idUsuario)
    {
        $idProducto = (int) ($datos['id_producto'] ?? 0);
        $tipo       = $datos['tipo'] ?? '';
        $cantidad   = (int) ($datos['cantidad'] ?? 0);
        $motivo     = trim($datos['motivo'] ?? '');

        if ($idProducto <= 0) {
            return ['ok' => false, 'mensaje' => 'Seleccione un producto.'];
        }

        if ($motivo === '') {
            return ['ok' => false, 'mensaje' => 'Indique el motivo del movimiento.'];
        }

        if (!in_array($tipo, ['ENTRADA', 'SALIDA', 'AJUSTE'], true)) {
            return ['ok' => false, 'mensaje' => 'Tipo de movimiento no válido.'];
        }

        if ($cantidad < 0) {
            return ['ok' => false, 'mensaje' => 'La cantidad no puede ser negativa.'];
        }

        switch ($tipo) {
            case 'ENTRADA':
                return $this->inventario->registrarEntrada($idProducto, $cantidad, $motivo, $idUsuario);

            case 'SALIDA':
                return $this->inventario->registrarSalida($idProducto, $cantidad, $motivo, $idUsuario);

            default:
                // En un ajuste la cantidad enviada es el stock contado fisicamente
                return $this->inventario->registrarAjuste($idProducto, $cantidad, $motivo, $idUsuario);
        }
    }

    /**
     * Productos activos, para llenar el selector del formulario.
     */
    public function productosActivos()
    {
        return $this->productos->listar(['solo_activos' => true]);
    }
}
