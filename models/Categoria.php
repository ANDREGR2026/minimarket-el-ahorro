<?php

require_once __DIR__ . '/../database/Conexion.php';

/**
 * Acceso a datos de las categorias de productos.
 */
class Categoria
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    /**
     * Lista las categorias con la cantidad de productos que tiene cada una.
     */
    public function listar($busqueda = '', $soloActivas = false)
    {
        $sql = "
            SELECT
                c.id_categoria,
                c.nombre,
                c.descripcion,
                c.estado,
                COUNT(p.id_producto) AS total_productos
            FROM categorias c
            LEFT JOIN productos p
                ON p.id_categoria = c.id_categoria AND p.estado = 1
            WHERE 1 = 1
        ";
        $parametros = [];

        if ($busqueda !== '') {
            $sql .= " AND c.nombre LIKE :busqueda";
            $parametros[':busqueda'] = '%' . $busqueda . '%';
        }

        if ($soloActivas) {
            $sql .= " AND c.estado = 1";
        }

        $sql .= " GROUP BY c.id_categoria, c.nombre, c.descripcion, c.estado
                  ORDER BY c.nombre ASC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conexion->prepare(
            "SELECT * FROM categorias WHERE id_categoria = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function nombreExiste($nombre, $idExcluir = null)
    {
        $sql = "SELECT COUNT(*) FROM categorias WHERE nombre = :nombre";
        $parametros = [':nombre' => $nombre];

        if ($idExcluir !== null) {
            $sql .= " AND id_categoria <> :id";
            $parametros[':id'] = $idExcluir;
        }

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function crear($nombre, $descripcion)
    {
        $stmt = $this->conexion->prepare("
            INSERT INTO categorias (nombre, descripcion, estado)
            VALUES (:nombre, :descripcion, 1)
        ");
        $stmt->execute([
            ':nombre'      => $nombre,
            ':descripcion' => $descripcion,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function actualizar($id, $nombre, $descripcion, $estado)
    {
        $stmt = $this->conexion->prepare("
            UPDATE categorias
            SET nombre = :nombre, descripcion = :descripcion, estado = :estado
            WHERE id_categoria = :id
        ");

        return $stmt->execute([
            ':nombre'      => $nombre,
            ':descripcion' => $descripcion,
            ':estado'      => $estado,
            ':id'          => $id,
        ]);
    }

    /**
     * Cuenta los productos activos asociados a la categoria.
     * Se usa para impedir desactivar una categoria que aun tiene productos.
     */
    public function contarProductos($id)
    {
        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*) FROM productos WHERE id_categoria = :id AND estado = 1"
        );
        $stmt->execute([':id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->conexion->prepare(
            "UPDATE categorias SET estado = :estado WHERE id_categoria = :id"
        );

        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }
}
