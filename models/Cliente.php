<?php

require_once __DIR__ . '/../database/Conexion.php';

/**
 * Acceso a datos de los clientes.
 * Personas naturales se identifican con DNI y las empresas con RUC.
 */
class Cliente
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    /**
     * Nombre a mostrar: razon social para RUC, nombres y apellidos para DNI.
     */
    private function expresionNombre($alias = 'c')
    {
        return "TRIM(COALESCE({$alias}.razon_social,
                CONCAT(COALESCE({$alias}.apellidos, ''), ' ',
                       COALESCE({$alias}.nombres, ''))))";
    }

    public function listar($filtros = [])
    {
        $nombre = $this->expresionNombre();

        $sql = "
            SELECT
                c.*,
                {$nombre} AS nombre_completo,
                COUNT(v.id_venta) AS total_compras,
                COALESCE(SUM(CASE WHEN v.estado = 'EMITIDA' THEN v.total ELSE 0 END), 0) AS monto_comprado
            FROM clientes c
            LEFT JOIN ventas v ON v.id_cliente = c.id_cliente
            WHERE 1 = 1
        ";
        $parametros = [];

        if (!empty($filtros['busqueda'])) {
            // Cada placeholder debe ser único: las sentencias no son emuladas
            $sql .= " AND (c.numero_documento LIKE :busqueda_doc
                        OR c.nombres LIKE :busqueda_nom
                        OR c.apellidos LIKE :busqueda_ape
                        OR c.razon_social LIKE :busqueda_raz)";
            $valor = '%' . $filtros['busqueda'] . '%';
            $parametros[':busqueda_doc'] = $valor;
            $parametros[':busqueda_nom'] = $valor;
            $parametros[':busqueda_ape'] = $valor;
            $parametros[':busqueda_raz'] = $valor;
        }

        if (!empty($filtros['tipo_documento'])) {
            $sql .= " AND c.tipo_documento = :tipo";
            $parametros[':tipo'] = $filtros['tipo_documento'];
        }

        if (!empty($filtros['solo_activos'])) {
            $sql .= " AND c.estado = 1";
        }

        $sql .= " GROUP BY c.id_cliente ORDER BY {$nombre} ASC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    public function obtenerPorId($id)
    {
        $nombre = $this->expresionNombre();

        $stmt = $this->conexion->prepare("
            SELECT c.*, {$nombre} AS nombre_completo
            FROM clientes c
            WHERE c.id_cliente = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function obtenerPorDocumento($documento)
    {
        $nombre = $this->expresionNombre();

        $stmt = $this->conexion->prepare("
            SELECT c.*, {$nombre} AS nombre_completo
            FROM clientes c
            WHERE c.numero_documento = :documento AND c.estado = 1
            LIMIT 1
        ");
        $stmt->execute([':documento' => $documento]);

        return $stmt->fetch();
    }

    /**
     * Busqueda usada por el punto de venta.
     */
    public function buscar($termino, $limite = 10)
    {
        $nombre = $this->expresionNombre();

        $stmt = $this->conexion->prepare("
            SELECT c.id_cliente, c.tipo_documento, c.numero_documento,
                   {$nombre} AS nombre_completo
            FROM clientes c
            WHERE c.estado = 1
              AND (c.numero_documento LIKE :termino_doc
                OR c.nombres LIKE :termino_nom
                OR c.apellidos LIKE :termino_ape
                OR c.razon_social LIKE :termino_raz)
            ORDER BY {$nombre} ASC
            LIMIT :limite
        ");

        $valor = '%' . $termino . '%';
        $stmt->bindValue(':termino_doc', $valor, PDO::PARAM_STR);
        $stmt->bindValue(':termino_nom', $valor, PDO::PARAM_STR);
        $stmt->bindValue(':termino_ape', $valor, PDO::PARAM_STR);
        $stmt->bindValue(':termino_raz', $valor, PDO::PARAM_STR);
        $stmt->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function documentoExiste($documento, $idExcluir = null)
    {
        $sql = "SELECT COUNT(*) FROM clientes WHERE numero_documento = :documento";
        $parametros = [':documento' => $documento];

        if ($idExcluir !== null) {
            $sql .= " AND id_cliente <> :id";
            $parametros[':id'] = $idExcluir;
        }

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function crear($datos)
    {
        $stmt = $this->conexion->prepare("
            INSERT INTO clientes
                (tipo_documento, numero_documento, nombres, apellidos,
                 razon_social, telefono, email, direccion, estado)
            VALUES
                (:tipo_documento, :numero_documento, :nombres, :apellidos,
                 :razon_social, :telefono, :email, :direccion, 1)
        ");

        $stmt->execute([
            ':tipo_documento'   => $datos['tipo_documento'],
            ':numero_documento' => $datos['numero_documento'],
            ':nombres'          => $datos['nombres'],
            ':apellidos'        => $datos['apellidos'],
            ':razon_social'     => $datos['razon_social'],
            ':telefono'         => $datos['telefono'],
            ':email'            => $datos['email'],
            ':direccion'        => $datos['direccion'],
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function actualizar($id, $datos)
    {
        $stmt = $this->conexion->prepare("
            UPDATE clientes SET
                tipo_documento   = :tipo_documento,
                numero_documento = :numero_documento,
                nombres          = :nombres,
                apellidos        = :apellidos,
                razon_social     = :razon_social,
                telefono         = :telefono,
                email            = :email,
                direccion        = :direccion,
                estado           = :estado
            WHERE id_cliente = :id
        ");

        return $stmt->execute([
            ':tipo_documento'   => $datos['tipo_documento'],
            ':numero_documento' => $datos['numero_documento'],
            ':nombres'          => $datos['nombres'],
            ':apellidos'        => $datos['apellidos'],
            ':razon_social'     => $datos['razon_social'],
            ':telefono'         => $datos['telefono'],
            ':email'            => $datos['email'],
            ':direccion'        => $datos['direccion'],
            ':estado'           => $datos['estado'],
            ':id'               => $id,
        ]);
    }

    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->conexion->prepare(
            "UPDATE clientes SET estado = :estado WHERE id_cliente = :id"
        );

        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    /**
     * Historial de compras del cliente.
     */
    public function historialCompras($id, $limite = 20)
    {
        $stmt = $this->conexion->prepare("
            SELECT id_venta, tipo_comprobante, serie, correlativo,
                   fecha, total, estado
            FROM ventas
            WHERE id_cliente = :id
            ORDER BY fecha DESC
            LIMIT :limite
        ");

        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
