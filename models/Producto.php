<?php

require_once __DIR__ . '/../database/Conexion.php';

/**
 * Acceso a datos del catalogo de productos.
 *
 * Nota: precio_venta se guarda CON IGV incluido (regla de negocio RN-03).
 */
class Producto
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    /**
     * Lista los productos aplicando los filtros de la pantalla de catalogo.
     *
     * @param array $filtros busqueda, id_categoria, stock_bajo, solo_activos
     */
    public function listar($filtros = [])
    {
        $sql = "
            SELECT
                p.*,
                c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c ON c.id_categoria = p.id_categoria
            WHERE 1 = 1
        ";
        $parametros = [];

        if (!empty($filtros['busqueda'])) {
            // Cada placeholder debe ser único: las sentencias no son emuladas
            $sql .= " AND (p.nombre LIKE :busqueda_nombre OR p.codigo_barras LIKE :busqueda_codigo)";
            $parametros[':busqueda_nombre'] = '%' . $filtros['busqueda'] . '%';
            $parametros[':busqueda_codigo'] = '%' . $filtros['busqueda'] . '%';
        }

        if (!empty($filtros['id_categoria'])) {
            $sql .= " AND p.id_categoria = :id_categoria";
            $parametros[':id_categoria'] = $filtros['id_categoria'];
        }

        if (!empty($filtros['stock_bajo'])) {
            $sql .= " AND p.stock <= p.stock_minimo";
        }

        if (!empty($filtros['solo_activos'])) {
            $sql .= " AND p.estado = 1";
        }

        $sql .= " ORDER BY p.nombre ASC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conexion->prepare("
            SELECT p.*, c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c ON c.id_categoria = p.id_categoria
            WHERE p.id_producto = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    /**
     * Busca un producto activo por su codigo de barras exacto.
     * Es la consulta que usa el lector de codigo de barras del punto de venta.
     */
    public function obtenerPorCodigo($codigo)
    {
        $stmt = $this->conexion->prepare("
            SELECT p.*, c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c ON c.id_categoria = p.id_categoria
            WHERE p.codigo_barras = :codigo AND p.estado = 1
            LIMIT 1
        ");
        $stmt->execute([':codigo' => $codigo]);

        return $stmt->fetch();
    }

    /**
     * Busqueda rapida para el punto de venta: por nombre o por codigo.
     */
    public function buscar($termino, $limite = 12)
    {
        $stmt = $this->conexion->prepare("
            SELECT
                p.id_producto, p.codigo_barras, p.nombre, p.precio_venta,
                p.stock, p.unidad_medida, c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c ON c.id_categoria = p.id_categoria
            WHERE p.estado = 1
              AND (p.nombre LIKE :termino_nombre OR p.codigo_barras LIKE :termino_codigo)
            ORDER BY p.nombre ASC
            LIMIT :limite
        ");

        $stmt->bindValue(':termino_nombre', '%' . $termino . '%', PDO::PARAM_STR);
        $stmt->bindValue(':termino_codigo', '%' . $termino . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function codigoExiste($codigo, $idExcluir = null)
    {
        $sql = "SELECT COUNT(*) FROM productos WHERE codigo_barras = :codigo";
        $parametros = [':codigo' => $codigo];

        if ($idExcluir !== null) {
            $sql .= " AND id_producto <> :id";
            $parametros[':id'] = $idExcluir;
        }

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param array $datos codigo_barras, nombre, descripcion, id_categoria,
     *                     precio_compra, precio_venta, stock, stock_minimo,
     *                     unidad_medida, imagen
     */
    public function crear($datos)
    {
        $stmt = $this->conexion->prepare("
            INSERT INTO productos
                (codigo_barras, nombre, descripcion, id_categoria,
                 precio_compra, precio_venta, stock, stock_minimo,
                 unidad_medida, imagen, estado)
            VALUES
                (:codigo_barras, :nombre, :descripcion, :id_categoria,
                 :precio_compra, :precio_venta, :stock, :stock_minimo,
                 :unidad_medida, :imagen, 1)
        ");

        $stmt->execute([
            ':codigo_barras' => $datos['codigo_barras'],
            ':nombre'        => $datos['nombre'],
            ':descripcion'   => $datos['descripcion'],
            ':id_categoria'  => $datos['id_categoria'],
            ':precio_compra' => $datos['precio_compra'],
            ':precio_venta'  => $datos['precio_venta'],
            ':stock'         => $datos['stock'],
            ':stock_minimo'  => $datos['stock_minimo'],
            ':unidad_medida' => $datos['unidad_medida'],
            ':imagen'        => $datos['imagen'],
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    /**
     * Actualiza los datos del producto.
     * El stock NO se modifica aqui: solo cambia por ventas o por movimientos
     * de inventario, para que el kardex nunca quede descuadrado.
     */
    public function actualizar($id, $datos)
    {
        $sql = "
            UPDATE productos SET
                codigo_barras = :codigo_barras,
                nombre        = :nombre,
                descripcion   = :descripcion,
                id_categoria  = :id_categoria,
                precio_compra = :precio_compra,
                precio_venta  = :precio_venta,
                stock_minimo  = :stock_minimo,
                unidad_medida = :unidad_medida,
                estado        = :estado
        ";
        $parametros = [
            ':codigo_barras' => $datos['codigo_barras'],
            ':nombre'        => $datos['nombre'],
            ':descripcion'   => $datos['descripcion'],
            ':id_categoria'  => $datos['id_categoria'],
            ':precio_compra' => $datos['precio_compra'],
            ':precio_venta'  => $datos['precio_venta'],
            ':stock_minimo'  => $datos['stock_minimo'],
            ':unidad_medida' => $datos['unidad_medida'],
            ':estado'        => $datos['estado'],
            ':id'            => $id,
        ];

        // La imagen solo se toca si se subio una nueva
        if (!empty($datos['imagen'])) {
            $sql .= ", imagen = :imagen";
            $parametros[':imagen'] = $datos['imagen'];
        }

        $sql .= " WHERE id_producto = :id";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute($parametros);
    }

    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->conexion->prepare(
            "UPDATE productos SET estado = :estado WHERE id_producto = :id"
        );

        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    /**
     * Productos cuyo stock llego o bajo del minimo configurado.
     */
    public function stockBajo($limite = null)
    {
        $sql = "
            SELECT p.id_producto, p.nombre, p.stock, p.stock_minimo,
                   p.unidad_medida, c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c ON c.id_categoria = p.id_categoria
            WHERE p.estado = 1 AND p.stock <= p.stock_minimo
            ORDER BY (p.stock - p.stock_minimo) ASC, p.nombre ASC
        ";

        if ($limite !== null) {
            $stmt = $this->conexion->prepare($sql . " LIMIT :limite");
            $stmt->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        }

        return $this->conexion->query($sql)->fetchAll();
    }

    /**
     * Indicadores del catalogo usados en el dashboard.
     */
    public function resumen()
    {
        return $this->conexion->query("
            SELECT
                COUNT(*)                                          AS total,
                SUM(CASE WHEN stock <= stock_minimo THEN 1 ELSE 0 END) AS bajo_minimo,
                SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END)        AS agotados,
                COALESCE(SUM(stock * precio_compra), 0)           AS valor_inventario
            FROM productos
            WHERE estado = 1
        ")->fetch();
    }
}
