<?php

require_once __DIR__ . '/../models/Categoria.php';

/**
 * Reglas de negocio del mantenimiento de categorias.
 */
class CategoriaController
{
    private $modelo;

    public function __construct()
    {
        $this->modelo = new Categoria();
    }

    public function listar($busqueda = '')
    {
        return $this->modelo->listar($busqueda);
    }

    public function activas()
    {
        return $this->modelo->listar('', true);
    }

    public function obtener($id)
    {
        return $this->modelo->obtenerPorId($id);
    }

    /**
     * Procesa el formulario de alta o edicion.
     *
     * @return array ['ok' => bool, 'mensaje' => string]
     */
    public function guardar($datos)
    {
        $id          = !empty($datos['id_categoria']) ? (int) $datos['id_categoria'] : null;
        $nombre      = trim($datos['nombre'] ?? '');
        $descripcion = trim($datos['descripcion'] ?? '');
        $estado      = isset($datos['estado']) ? (int) $datos['estado'] : 1;

        if ($nombre === '') {
            return ['ok' => false, 'mensaje' => 'El nombre de la categoría es obligatorio.'];
        }

        if (mb_strlen($nombre) > 80) {
            return ['ok' => false, 'mensaje' => 'El nombre no puede superar los 80 caracteres.'];
        }

        if ($this->modelo->nombreExiste($nombre, $id)) {
            return ['ok' => false, 'mensaje' => 'Ya existe una categoría con ese nombre.'];
        }

        if ($id === null) {
            $this->modelo->crear($nombre, $descripcion);

            return ['ok' => true, 'mensaje' => 'Categoría registrada correctamente.'];
        }

        // No se puede desactivar una categoria que aun tiene productos activos
        if ($estado === 0 && $this->modelo->contarProductos($id) > 0) {
            return [
                'ok'      => false,
                'mensaje' => 'No se puede desactivar: la categoría todavía tiene productos activos.',
            ];
        }

        $this->modelo->actualizar($id, $nombre, $descripcion, $estado);

        return ['ok' => true, 'mensaje' => 'Categoría actualizada correctamente.'];
    }

    public function cambiarEstado($id, $estado)
    {
        $id     = (int) $id;
        $estado = (int) $estado;

        if ($estado === 0 && $this->modelo->contarProductos($id) > 0) {
            return [
                'ok'      => false,
                'mensaje' => 'No se puede desactivar: la categoría todavía tiene productos activos.',
            ];
        }

        $this->modelo->cambiarEstado($id, $estado);

        return [
            'ok'      => true,
            'mensaje' => $estado === 1 ? 'Categoría activada.' : 'Categoría desactivada.',
        ];
    }
}
