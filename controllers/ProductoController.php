<?php

require_once __DIR__ . '/../models/Producto.php';

/**
 * Reglas de negocio del catalogo de productos.
 */
class ProductoController
{
    /** Extensiones y tamano permitidos para la imagen del producto */
    const IMAGEN_EXTENSIONES = ['jpg', 'jpeg', 'png', 'webp'];
    const IMAGEN_TAMANO_MAX  = 2097152; // 2 MB

    private $modelo;

    public function __construct()
    {
        $this->modelo = new Producto();
    }

    public function listar($filtros = [])
    {
        return $this->modelo->listar($filtros);
    }

    public function obtener($id)
    {
        return $this->modelo->obtenerPorId($id);
    }

    public function buscar($termino)
    {
        return $this->modelo->buscar($termino);
    }

    public function stockBajo($limite = null)
    {
        return $this->modelo->stockBajo($limite);
    }

    public function resumen()
    {
        return $this->modelo->resumen();
    }

    /**
     * Valida y guarda el producto.
     *
     * @param array $datos   datos del formulario
     * @param array $archivo entrada de $_FILES para la imagen (opcional)
     */
    public function guardar($datos, $archivo = null)
    {
        $id = !empty($datos['id_producto']) ? (int) $datos['id_producto'] : null;

        $limpio = [
            'codigo_barras' => trim($datos['codigo_barras'] ?? ''),
            'nombre'        => trim($datos['nombre'] ?? ''),
            'descripcion'   => trim($datos['descripcion'] ?? ''),
            'id_categoria'  => (int) ($datos['id_categoria'] ?? 0),
            'precio_compra' => (float) ($datos['precio_compra'] ?? 0),
            'precio_venta'  => (float) ($datos['precio_venta'] ?? 0),
            'stock'         => (int) ($datos['stock'] ?? 0),
            'stock_minimo'  => (int) ($datos['stock_minimo'] ?? 0),
            'unidad_medida' => trim($datos['unidad_medida'] ?? 'UNIDAD'),
            'estado'        => isset($datos['estado']) ? (int) $datos['estado'] : 1,
            'imagen'        => null,
        ];

        $error = $this->validar($limpio, $id);
        if ($error !== null) {
            return ['ok' => false, 'mensaje' => $error];
        }

        // Imagen (opcional)
        if ($archivo && isset($archivo['error']) && $archivo['error'] === UPLOAD_ERR_OK) {
            $resultado = $this->guardarImagen($archivo);

            if (!$resultado['ok']) {
                return $resultado;
            }

            $limpio['imagen'] = $resultado['nombre'];
        }

        if ($id === null) {
            $idNuevo = $this->modelo->crear($limpio);

            // El stock inicial queda registrado en el kardex
            if ($limpio['stock'] > 0) {
                require_once __DIR__ . '/../models/Inventario.php';
                $inventario = new Inventario();
                $inventario->insertarMovimiento(
                    $idNuevo,
                    'ENTRADA',
                    $limpio['stock'],
                    0,
                    $limpio['stock'],
                    'Stock inicial del producto',
                    null,
                    $datos['id_usuario']
                );
            }

            return ['ok' => true, 'mensaje' => 'Producto registrado correctamente.'];
        }

        $this->modelo->actualizar($id, $limpio);

        return ['ok' => true, 'mensaje' => 'Producto actualizado correctamente.'];
    }

    /**
     * Devuelve el primer mensaje de error encontrado, o null si todo esta bien.
     */
    private function validar($datos, $id)
    {
        if ($datos['codigo_barras'] === '') {
            return 'El código de barras es obligatorio.';
        }

        if ($datos['nombre'] === '') {
            return 'El nombre del producto es obligatorio.';
        }

        if ($datos['id_categoria'] <= 0) {
            return 'Seleccione una categoría.';
        }

        if ($datos['precio_compra'] < 0 || $datos['precio_venta'] < 0) {
            return 'Los precios no pueden ser negativos.';
        }

        if ($datos['precio_venta'] <= 0) {
            return 'El precio de venta debe ser mayor a cero.';
        }

        if ($datos['precio_venta'] < $datos['precio_compra']) {
            return 'El precio de venta no puede ser menor que el precio de compra.';
        }

        if ($datos['stock'] < 0 || $datos['stock_minimo'] < 0) {
            return 'El stock no puede ser negativo.';
        }

        if ($this->modelo->codigoExiste($datos['codigo_barras'], $id)) {
            return 'Ya existe otro producto con ese código de barras.';
        }

        return null;
    }

    /**
     * Mueve la imagen subida a assets/img/productos con un nombre unico.
     */
    private function guardarImagen($archivo)
    {
        if ($archivo['size'] > self::IMAGEN_TAMANO_MAX) {
            return ['ok' => false, 'mensaje' => 'La imagen no debe pesar más de 2 MB.'];
        }

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, self::IMAGEN_EXTENSIONES, true)) {
            return ['ok' => false, 'mensaje' => 'La imagen debe ser JPG, PNG o WEBP.'];
        }

        // Verificar que el archivo sea realmente una imagen
        if (getimagesize($archivo['tmp_name']) === false) {
            return ['ok' => false, 'mensaje' => 'El archivo subido no es una imagen válida.'];
        }

        $nombre  = 'prod_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destino = BASE_PATH . '/assets/img/productos/' . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            return ['ok' => false, 'mensaje' => 'No se pudo guardar la imagen en el servidor.'];
        }

        return ['ok' => true, 'nombre' => $nombre];
    }

    public function cambiarEstado($id, $estado)
    {
        $this->modelo->cambiarEstado((int) $id, (int) $estado);

        return [
            'ok'      => true,
            'mensaje' => (int) $estado === 1 ? 'Producto activado.' : 'Producto desactivado.',
        ];
    }
}
