
-- ---------------------------------------------------------------------
-- 1. usuarios : personal que opera el sistema
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario         INT AUTO_INCREMENT PRIMARY KEY,
    nombre             VARCHAR(100)  NOT NULL,
    usuario            VARCHAR(50)   NOT NULL,
    password           VARCHAR(255)  NOT NULL,
    email              VARCHAR(100)  NULL,
    rol                ENUM('SuperAdministrador','Administrador','Cajero','Almacenero') NOT NULL DEFAULT 'Cajero',
    estado             TINYINT(1)    NOT NULL DEFAULT 1,
    reset_token_hash   VARCHAR(255)  NULL,
    reset_token_expira DATETIME      NULL,
    created_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_usuarios_usuario UNIQUE (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. categorias : clasificacion de los productos
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(80)   NOT NULL,
    descripcion  VARCHAR(255)  NULL,
    estado       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_categorias_nombre UNIQUE (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. productos : catalogo del minimarket
--    precio_venta se almacena CON IGV incluido (regla de negocio RN-03)
-- ---------------------------------------------------------------------
CREATE TABLE productos (
    id_producto    INT AUTO_INCREMENT PRIMARY KEY,
    codigo_barras  VARCHAR(50)   NOT NULL,
    nombre         VARCHAR(150)  NOT NULL,
    descripcion    VARCHAR(255)  NULL,
    id_categoria   INT           NOT NULL,
    precio_compra  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    precio_venta   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock          INT           NOT NULL DEFAULT 0,
    stock_minimo   INT           NOT NULL DEFAULT 5,
    unidad_medida  VARCHAR(20)   NOT NULL DEFAULT 'UNIDAD',
    imagen         VARCHAR(255)  NULL,
    estado         TINYINT(1)    NOT NULL DEFAULT 1,
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_productos_codigo UNIQUE (codigo_barras),
    CONSTRAINT fk_productos_categoria
        FOREIGN KEY (id_categoria) REFERENCES categorias (id_categoria)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_productos_nombre (nombre),
    INDEX idx_productos_categoria (id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. clientes : personas naturales (DNI) y empresas (RUC)
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
    id_cliente       INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento   ENUM('DNI','RUC') NOT NULL DEFAULT 'DNI',
    numero_documento VARCHAR(11)   NOT NULL,
    nombres          VARCHAR(100)  NULL,
    apellidos        VARCHAR(100)  NULL,
    razon_social     VARCHAR(150)  NULL,
    telefono         VARCHAR(20)   NULL,
    email            VARCHAR(100)  NULL,
    direccion        VARCHAR(200)  NULL,
    estado           TINYINT(1)    NOT NULL DEFAULT 1,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_clientes_documento UNIQUE (numero_documento),
    INDEX idx_clientes_nombres (apellidos, nombres)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. series_comprobante : controla el correlativo de boletas y facturas
-- ---------------------------------------------------------------------
CREATE TABLE series_comprobante (
    id_serie           INT AUTO_INCREMENT PRIMARY KEY,
    tipo_comprobante   ENUM('BOLETA','FACTURA') NOT NULL,
    serie              VARCHAR(4)  NOT NULL,
    ultimo_correlativo INT         NOT NULL DEFAULT 0,
    estado             TINYINT(1)  NOT NULL DEFAULT 1,
    CONSTRAINT uq_series UNIQUE (tipo_comprobante, serie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. ventas : cabecera del comprobante
-- ---------------------------------------------------------------------
CREATE TABLE ventas (
    id_venta          INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario        INT           NOT NULL,
    id_cliente        INT           NULL,
    tipo_comprobante  ENUM('BOLETA','FACTURA') NOT NULL DEFAULT 'BOLETA',
    serie             VARCHAR(4)    NOT NULL,
    correlativo       INT           NOT NULL,
    fecha             DATETIME      NOT NULL,
    subtotal          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    igv               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    monto_pagado      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    vuelto            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    metodo_pago       ENUM('EFECTIVO','TARJETA','YAPE','PLIN') NOT NULL DEFAULT 'EFECTIVO',
    estado            ENUM('EMITIDA','ANULADA') NOT NULL DEFAULT 'EMITIDA',
    motivo_anulacion  VARCHAR(255)  NULL,
    fecha_anulacion   DATETIME      NULL,
    CONSTRAINT uq_ventas_comprobante UNIQUE (tipo_comprobante, serie, correlativo),
    CONSTRAINT fk_ventas_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ventas_cliente
        FOREIGN KEY (id_cliente) REFERENCES clientes (id_cliente)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_ventas_fecha (fecha),
    INDEX idx_ventas_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. detalle_venta : lineas del comprobante
-- ---------------------------------------------------------------------
CREATE TABLE detalle_venta (
    id_detalle      INT AUTO_INCREMENT PRIMARY KEY,
    id_venta        INT           NOT NULL,
    id_producto     INT           NOT NULL,
    cantidad        INT           NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detalle_venta
        FOREIGN KEY (id_venta) REFERENCES ventas (id_venta)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detalle_producto
        FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_detalle_venta (id_venta),
    INDEX idx_detalle_producto (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. movimientos_inventario : kardex de cada producto
-- ---------------------------------------------------------------------
CREATE TABLE movimientos_inventario (
    id_movimiento  INT AUTO_INCREMENT PRIMARY KEY,
    id_producto    INT          NOT NULL,
    tipo           ENUM('ENTRADA','SALIDA','AJUSTE') NOT NULL,
    cantidad       INT          NOT NULL,
    stock_anterior INT          NOT NULL,
    stock_nuevo    INT          NOT NULL,
    motivo         VARCHAR(255) NULL,
    id_venta       INT          NULL,
    id_usuario     INT          NOT NULL,
    fecha          DATETIME     NOT NULL,
    CONSTRAINT fk_movimientos_producto
        FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movimientos_venta
        FOREIGN KEY (id_venta) REFERENCES ventas (id_venta)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_movimientos_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_movimientos_producto (id_producto, fecha),
    INDEX idx_movimientos_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. configuracion : datos del negocio y parametros globales
-- ---------------------------------------------------------------------
CREATE TABLE configuracion (
    clave       VARCHAR(50)  PRIMARY KEY,
    valor       VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
