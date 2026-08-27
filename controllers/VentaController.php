<?php

require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Configuracion.php';

/**
 * Punto de venta e historial de comprobantes.
 */
class VentaController
{
    private $modelo;
    private $productos;
    private $clientes;
    private $config;

    public function __construct()
    {
        $this->modelo    = new Venta();
        $this->productos = new Producto();
        $this->clientes  = new Cliente();
        $this->config    = new Configuracion();
    }

    /**
     * Registra la venta enviada desde el punto de venta.
     *
     * Los precios NUNCA se toman del navegador: el modelo los relee de la
     * base de datos dentro de la transacción.
     */
    public function registrar($carrito, $datos, $idUsuario)
    {
        if (!is_array($carrito) || empty($carrito)) {
            return ['ok' => false, 'mensaje' => 'Agregue al menos un producto a la venta.'];
        }

        // Consolidar líneas repetidas del mismo producto
        $items = [];
        foreach ($carrito as $linea) {
            $idProducto = (int) ($linea['id_producto'] ?? 0);
            $cantidad   = (int) ($linea['cantidad'] ?? 0);

            if ($idProducto <= 0 || $cantidad <= 0) {
                return ['ok' => false, 'mensaje' => 'La venta contiene una línea inválida.'];
            }

            if (isset($items[$idProducto])) {
                $items[$idProducto]['cantidad'] += $cantidad;
            } else {
                $items[$idProducto] = ['id_producto' => $idProducto, 'cantidad' => $cantidad];
            }
        }

        $metodoPago = $datos['metodo_pago'] ?? 'EFECTIVO';

        if (!in_array($metodoPago, ['EFECTIVO', 'TARJETA', 'YAPE', 'PLIN'], true)) {
            return ['ok' => false, 'mensaje' => 'Método de pago no válido.'];
        }

        return $this->modelo->registrar(array_values($items), [
            'id_usuario'       => $idUsuario,
            'id_cliente'       => !empty($datos['id_cliente']) ? (int) $datos['id_cliente'] : null,
            'tipo_comprobante' => $datos['tipo_comprobante'] ?? 'BOLETA',
            'metodo_pago'      => $metodoPago,
            'monto_pagado'     => (float) ($datos['monto_pagado'] ?? 0),
        ]);
    }

    public function anular($idVenta, $motivo, $idUsuario)
    {
        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 5) {
            return ['ok' => false, 'mensaje' => 'Indique el motivo de la anulación (mínimo 5 caracteres).'];
        }

        return $this->modelo->anular((int) $idVenta, $motivo, $idUsuario);
    }

    public function listar($filtros = [])
    {
        return $this->modelo->listar($filtros);
    }

    public function obtener($idVenta)
    {
        return $this->modelo->obtenerPorId($idVenta);
    }

    public function detalle($idVenta)
    {
        return $this->modelo->detalle($idVenta);
    }

    public function buscarProductos($termino)
    {
        return $this->productos->buscar($termino);
    }

    public function productoPorCodigo($codigo)
    {
        return $this->productos->obtenerPorCodigo($codigo);
    }

    public function buscarClientes($termino)
    {
        return $this->clientes->buscar($termino);
    }

    public function configuracion()
    {
        return $this->config->todos();
    }
}
