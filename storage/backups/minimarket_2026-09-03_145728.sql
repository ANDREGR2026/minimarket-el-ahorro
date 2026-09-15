-- Respaldo de la base de datos 'minimarket'
-- Generado el 2026-09-03 14:57:28 por el sistema

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- Tabla: categorias
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `uq_categorias_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categorias` (`id_categoria`, `nombre`, `descripcion`, `estado`, `created_at`) VALUES
('1', 'Abarrotes', 'Arroz, azucar, aceite, fideos y menestras', '1', '2026-09-01 11:15:25'),
('2', 'Bebidas', 'Gaseosas, aguas, jugos y energizantes', '1', '2026-09-01 11:15:25'),
('3', 'Lacteos', 'Leche, yogurt, queso y mantequilla', '1', '2026-09-01 11:15:25'),
('4', 'Snacks', 'Galletas, papas fritas y golosinas', '1', '2026-09-01 11:15:25'),
('5', 'Limpieza', 'Detergentes, lejia y utiles de limpieza', '1', '2026-09-01 11:15:25'),
('6', 'Cuidado Personal', 'Jabon, shampoo, pasta dental y papel', '1', '2026-09-01 11:15:25'),
('7', 'Panaderia', 'Pan, pasteles y productos de panaderia', '1', '2026-09-01 11:15:25'),
('8', 'Embutidos', 'Jamon, hot dog, chorizo y similares', '1', '2026-09-01 11:15:25');

-- ----------------------------------------------------------
-- Tabla: clientes
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `tipo_documento` enum('DNI','RUC') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DNI',
  `numero_documento` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombres` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apellidos` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `razon_social` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `uq_clientes_documento` (`numero_documento`),
  KEY `idx_clientes_nombres` (`apellidos`,`nombres`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clientes` (`id_cliente`, `tipo_documento`, `numero_documento`, `nombres`, `apellidos`, `razon_social`, `telefono`, `email`, `direccion`, `estado`, `created_at`, `updated_at`) VALUES
('1', 'DNI', '10245678', 'Maria Elena', 'Quispe Huaman', NULL, '987654321', 'maria.quispe@gmail.com', 'Jr. Union 231, Lima', '1', '2026-09-01 11:15:25', NULL),
('2', 'DNI', '45678123', 'Jose Antonio', 'Ramirez Soto', NULL, '956231478', 'jose.ramirez@gmail.com', 'Av. Brasil 1204, Brena', '1', '2026-09-01 11:15:25', NULL),
('3', 'DNI', '72345891', 'Carmen Rosa', 'Flores Diaz', NULL, '921456783', NULL, 'Calle Los Olivos 45, SMP', '1', '2026-09-01 11:15:25', NULL),
('4', 'DNI', '08765432', 'Luis Alberto', 'Mendoza Ccahua', NULL, '998745612', 'lmendoza@hotmail.com', 'Av. Tupac Amaru 890, Comas', '1', '2026-09-01 11:15:25', NULL),
('5', 'DNI', '46123789', 'Rosa Maria', 'Castillo Vega', NULL, '934567812', NULL, 'Jr. Ayacucho 567, Lima', '1', '2026-09-01 11:15:25', NULL),
('6', 'DNI', '71234567', 'Pedro Miguel', 'Chavez Rojas', NULL, '945123678', 'pchavez@gmail.com', 'Av. Colonial 2310, Callao', '1', '2026-09-01 11:15:25', NULL),
('7', 'RUC', '20512345678', NULL, NULL, 'DISTRIBUIDORA SAN MARTIN S.A.C.', '014785236', 'ventas@dsanmartin.com', 'Av. Argentina 3200, Callao', '1', '2026-09-01 11:15:25', NULL),
('8', 'RUC', '20487965123', NULL, NULL, 'BODEGAS UNIDAS DEL NORTE E.I.R.L.', '013698521', 'contacto@bun.pe', 'Av. Universitaria 1450, SMP', '1', '2026-09-01 11:15:25', NULL),
('9', 'RUC', '10452367891', NULL, NULL, 'INVERSIONES LA ESQUINA S.R.L.', '015874123', NULL, 'Jr. Puno 780, Lima', '1', '2026-09-01 11:15:25', NULL),
('10', 'DNI', '00000000', 'Cliente', 'Varios', NULL, NULL, NULL, NULL, '1', '2026-09-01 11:15:25', NULL);

-- ----------------------------------------------------------
-- Tabla: configuracion
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE `configuracion` (
  `clave` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('backup_conservar', '10', 'Cantidad de backups a conservar antes de borrar el mas antiguo'),
('backup_frecuencia', 'Semanal', 'Frecuencia del backup automatico: Desactivado, Diario, Semanal o Mensual'),
('direccion', 'Av. Los Proceres 458, Lima', 'Direccion fiscal'),
('igv', '18', 'Porcentaje de IGV vigente'),
('moneda', 'S/', 'Simbolo de la moneda'),
('nombre_comercial', 'EL AHORRO', 'Nombre comercial mostrado en el sistema'),
('razon_social', 'MINIMARKET EL AHORRO S.A.C.', 'Nombre legal del negocio'),
('ruc', '20558877991', 'RUC del negocio'),
('telefono', '01 4567890', 'Telefono de contacto');

-- ----------------------------------------------------------
-- Tabla: detalle_venta
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `detalle_venta`;
CREATE TABLE `detalle_venta` (
  `id_detalle` int NOT NULL AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `idx_detalle_venta` (`id_venta`),
  KEY `idx_detalle_producto` (`id_producto`),
  CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `detalle_venta` (`id_detalle`, `id_venta`, `id_producto`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
('7', '8', '34', '3', '0.40', '1.20');

-- ----------------------------------------------------------
-- Tabla: movimientos_inventario
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `movimientos_inventario`;
CREATE TABLE `movimientos_inventario` (
  `id_movimiento` int NOT NULL AUTO_INCREMENT,
  `id_producto` int NOT NULL,
  `tipo` enum('ENTRADA','SALIDA','AJUSTE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int NOT NULL,
  `stock_anterior` int NOT NULL,
  `stock_nuevo` int NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_venta` int DEFAULT NULL,
  `id_usuario` int NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id_movimiento`),
  KEY `fk_movimientos_venta` (`id_venta`),
  KEY `fk_movimientos_usuario` (`id_usuario`),
  KEY `idx_movimientos_producto` (`id_producto`,`fecha`),
  KEY `idx_movimientos_fecha` (`fecha`),
  CONSTRAINT `fk_movimientos_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_movimientos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_movimientos_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: productos
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `codigo_barras` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_categoria` int NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL DEFAULT '0.00',
  `precio_venta` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock` int NOT NULL DEFAULT '0',
  `stock_minimo` int NOT NULL DEFAULT '5',
  `unidad_medida` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNIDAD',
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `uq_productos_codigo` (`codigo_barras`),
  KEY `idx_productos_nombre` (`nombre`),
  KEY `idx_productos_categoria` (`id_categoria`),
  CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `productos` (`id_producto`, `codigo_barras`, `nombre`, `descripcion`, `id_categoria`, `precio_compra`, `precio_venta`, `stock`, `stock_minimo`, `unidad_medida`, `imagen`, `estado`, `created_at`, `updated_at`) VALUES
('1', '7750001000018', 'Arroz Costeno Extra 5 kg', 'Arroz superior embolsado', '1', '18.50', '23.90', '40', '8', 'BOLSA', NULL, '1', '2026-09-01 11:15:25', NULL),
('2', '7750001000025', 'Azucar Rubia Cartavio 1 kg', 'Azucar rubia domestica', '1', '3.20', '4.50', '60', '10', 'BOLSA', NULL, '1', '2026-09-01 11:15:25', NULL),
('3', '7750001000032', 'Aceite Primor 1 L', 'Aceite vegetal de soya', '1', '7.80', '9.90', '35', '8', 'UNIDAD', NULL, '1', '2026-09-01 11:15:25', NULL),
('4', '7750001000049', 'Fideos Don Vittorio Spaghetti 500 g', 'Fideos de trigo', '1', '2.60', '3.80', '50', '10', 'BOLSA', NULL, '1', '2026-09-01 11:15:25', NULL),
('5', '7750001000056', 'Lentejas Costeno 500 g', 'Menestra seleccionada', '1', '4.10', '5.60', '25', '6', 'BOLSA', NULL, '1', '2026-09-01 11:15:25', NULL),
('6', '7750001000063', 'Atun Florida Filete 170 g', 'Conserva de atun en aceite', '1', '4.90', '6.50', '45', '10', 'LATA', NULL, '1', '2026-09-01 11:15:25', NULL),
('7', '7750001000070', 'Sal Marina Emsal 1 kg', 'Sal yodada', '1', '1.30', '2.20', '30', '8', 'BOLSA', NULL, '1', '2026-09-01 11:15:25', NULL),
('8', '7751002000015', 'Inca Kola 1.5 L', 'Gaseosa sabor original', '2', '4.80', '6.90', '48', '12', 'BOTELLA', NULL, '1', '2026-09-01 11:15:25', NULL),
('9', '7751002000022', 'Coca Cola 1.5 L', 'Gaseosa cola', '2', '5.00', '7.20', '42', '12', 'BOTELLA', NULL, '1', '2026-09-01 11:15:25', NULL),
('10', '7751002000039', 'Agua San Luis 625 ml', 'Agua mineral sin gas', '2', '1.10', '1.80', '80', '20', 'BOTELLA', NULL, '1', '2026-09-01 11:15:25', NULL),
('11', '7751002000046', 'Frugos Del Valle Durazno 1 L', 'Nectar de fruta', '2', '3.90', '5.50', '30', '8', 'CAJA', NULL, '1', '2026-09-01 11:15:25', NULL),
('12', '7751002000053', 'Red Bull 250 ml', 'Bebida energizante', '2', '6.50', '9.00', '24', '6', 'LATA', NULL, '1', '2026-09-01 11:15:25', NULL),
('13', '7751002000060', 'Cerveza Pilsen Callao 650 ml', 'Cerveza rubia', '2', '5.20', '7.50', '36', '12', 'BOTELLA', NULL, '1', '2026-09-01 11:15:25', NULL),
('14', '7752003000012', 'Leche Gloria Evaporada 400 g', 'Leche evaporada entera', '3', '3.40', '4.60', '55', '12', 'LATA', NULL, '1', '2026-09-01 11:15:25', NULL),
('15', '7752003000029', 'Yogurt Laive Fresa 1 L', 'Yogurt bebible', '3', '5.60', '7.90', '28', '8', 'BOTELLA', NULL, '1', '2026-09-01 11:15:25', NULL),
('16', '7752003000036', 'Queso Fresco Bonle 500 g', 'Queso fresco pasteurizado', '3', '9.80', '13.50', '15', '5', 'UNIDAD', NULL, '1', '2026-09-01 11:15:25', NULL),
('17', '7752003000043', 'Mantequilla Laive 200 g', 'Mantequilla con sal', '3', '6.20', '8.40', '18', '5', 'UNIDAD', NULL, '1', '2026-09-01 11:15:25', NULL),
('18', '7752003000050', 'Huevos Pardos x 15 unidades', 'Huevos de gallina', '3', '9.00', '12.00', '20', '6', 'PAQUETE', NULL, '1', '2026-09-01 11:15:25', NULL),
('19', '7753004000019', 'Galleta Soda Field 6 pack', 'Galleta de soda', '4', '2.90', '4.20', '40', '10', 'PAQUETE', NULL, '1', '2026-09-01 11:15:25', NULL),
('20', '7753004000026', 'Papas Lays Clasicas 145 g', 'Hojuelas de papa', '4', '5.10', '7.00', '32', '8', 'BOLSA', NULL, '1', '2026-09-01 11:15:25', NULL),
('21', '7753004000033', 'Chocolate Sublime 30 g', 'Chocolate con mani', '4', '1.20', '2.00', '70', '15', 'UNIDAD', NULL, '1', '2026-09-01 11:15:25', NULL),
('22', '7753004000040', 'Chizitos Karinto 90 g', 'Snack de maiz con queso', '4', '2.30', '3.50', '26', '8', 'BOLSA', NULL, '1', '2026-09-01 11:15:25', NULL),
('23', '7753004000057', 'Galleta Oreo 108 g', 'Galleta rellena de vainilla', '4', '2.70', '4.00', '38', '10', 'PAQUETE', NULL, '1', '2026-09-01 11:15:25', NULL),
('24', '7754005000016', 'Detergente Bolivar 780 g', 'Detergente en polvo', '5', '8.40', '11.50', '22', '6', 'BOLSA', NULL, '1', '2026-09-01 11:15:25', NULL),
('25', '7754005000023', 'Lejia Clorox 1 L', 'Hipoclorito de sodio', '5', '3.60', '5.20', '24', '6', 'BOTELLA', NULL, '1', '2026-09-01 11:15:25', NULL),
('26', '7754005000030', 'Lavavajilla Ayudin 360 g', 'Crema lavavajilla', '5', '4.20', '6.00', '20', '6', 'UNIDAD', NULL, '1', '2026-09-01 11:15:25', NULL),
('27', '7754005000047', 'Bolsas de Basura Grandes x 20', 'Bolsas de polietileno', '5', '4.80', '6.80', '16', '5', 'PAQUETE', NULL, '1', '2026-09-01 11:15:25', NULL),
('28', '7754005000054', 'Escoba Plastica Multiusos', 'Escoba de cerdas sinteticas', '5', '8.00', '12.00', '8', '3', 'UNIDAD', NULL, '1', '2026-09-01 11:15:25', NULL),
('29', '7755006000013', 'Papel Higienico Elite x 4', 'Papel higienico doble hoja', '6', '5.40', '7.80', '34', '8', 'PAQUETE', NULL, '1', '2026-09-01 11:15:25', NULL),
('30', '7755006000020', 'Jabon Protex 110 g', 'Jabon antibacterial', '6', '2.40', '3.60', '42', '10', 'UNIDAD', NULL, '1', '2026-09-01 11:15:25', NULL),
('31', '7755006000037', 'Shampoo Head & Shoulders 375 ml', 'Shampoo anticaspa', '6', '16.50', '22.90', '12', '4', 'BOTELLA', NULL, '1', '2026-09-01 11:15:25', NULL),
('32', '7755006000044', 'Pasta Dental Colgate 90 g', 'Crema dental con fluor', '6', '4.60', '6.50', '28', '8', 'UNIDAD', NULL, '1', '2026-09-01 11:15:25', NULL),
('33', '7755006000051', 'Desodorante Rexona 150 ml', 'Desodorante en spray', '6', '11.20', '15.90', '14', '5', 'UNIDAD', NULL, '1', '2026-09-01 11:15:25', NULL),
('34', '7756007000010', 'Pan Frances (unidad)', 'Pan del dia', '7', '0.20', '0.40', '147', '30', 'UNIDAD', NULL, '1', '2026-09-01 11:15:25', '2026-09-03 14:49:31'),
('35', '7756007000027', 'Pan de Molde Bimbo 500 g', 'Pan de molde blanco', '7', '5.80', '8.20', '18', '5', 'BOLSA', NULL, '1', '2026-09-01 11:15:25', NULL),
('36', '7756007000034', 'Keke de Vainilla Individual', 'Keke envasado', '7', '1.60', '2.50', '24', '6', 'UNIDAD', NULL, '1', '2026-09-01 11:15:25', NULL),
('37', '7757008000017', 'Hot Dog Otto Kunz 8 unidades', 'Salchicha de pollo', '8', '7.40', '10.50', '20', '6', 'PAQUETE', NULL, '1', '2026-09-01 11:15:25', NULL),
('38', '7757008000024', 'Jamonada Laive 200 g', 'Jamonada de pollo', '8', '4.90', '6.90', '16', '5', 'PAQUETE', NULL, '1', '2026-09-01 11:15:25', NULL),
('39', '7757008000031', 'Chorizo Parrillero 500 g', 'Chorizo fresco', '8', '10.20', '14.50', '9', '4', 'PAQUETE', NULL, '1', '2026-09-01 11:15:25', NULL),
('40', '7750001000087', 'Leche Condensada Nestle 393 g', 'Leche condensada', '3', '6.10', '8.50', '3', '6', 'LATA', NULL, '1', '2026-09-01 11:15:25', NULL),
('41', '7754005000061', 'Suavizante Downy 800 ml', 'Suavizante de ropa', '5', '9.30', '13.20', '2', '5', 'BOTELLA', NULL, '1', '2026-09-01 11:15:25', NULL);

-- ----------------------------------------------------------
-- Tabla: series_comprobante
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `series_comprobante`;
CREATE TABLE `series_comprobante` (
  `id_serie` int NOT NULL AUTO_INCREMENT,
  `tipo_comprobante` enum('BOLETA','FACTURA') COLLATE utf8mb4_unicode_ci NOT NULL,
  `serie` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ultimo_correlativo` int NOT NULL DEFAULT '0',
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_serie`),
  UNIQUE KEY `uq_series` (`tipo_comprobante`,`serie`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `series_comprobante` (`id_serie`, `tipo_comprobante`, `serie`, `ultimo_correlativo`, `estado`) VALUES
('1', 'BOLETA', 'B001', '1', '1'),
('2', 'FACTURA', 'F001', '0', '1');

-- ----------------------------------------------------------
-- Tabla: usuarios
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol` enum('SuperAdministrador','Administrador','Cajero','Almacenero') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Cajero',
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `reset_token_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expira` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uq_usuarios_usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `usuario`, `password`, `email`, `rol`, `estado`, `reset_token_hash`, `reset_token_expira`, `created_at`, `updated_at`) VALUES
('1', 'Super Administrador', 'superadmin', '$2y$10$GrD8fi73ASx43v7d0yiawu08SUw6s/CPc65bGXjEU93iBv5NW2ft2', 'superadmin@elahorro.pe', 'SuperAdministrador', '1', NULL, NULL, '2026-09-01 11:15:25', NULL),
('2', 'Andre Gutierrez Rojas', 'admin', '$2y$10$/2UWllHyHOyQ8VlTBSZqIuGPnRYaX3T2y7yyfU7yD2dxqPcuKVTb2', 'admin@elahorro.pe', 'Administrador', '1', NULL, NULL, '2026-09-01 11:15:25', NULL),
('3', 'Lucia Ramos Peralta', 'cajero', '$2y$10$RM8/FmBegI8rJg9KidMa2ewcm6FiwrvUWGwpEtKk9idMnx0j80bPu', 'cajero@elahorro.pe', 'Cajero', '1', NULL, NULL, '2026-09-01 11:15:25', NULL),
('4', 'Marco Solis Vega', 'almacen', '$2y$10$TX6jHugKh07pK0Vg2kGoWeYTCTYVgLtHNANumlcVXZdiSeUMkdcyK', 'almacen@elahorro.pe', 'Almacenero', '1', NULL, NULL, '2026-09-01 16:18:57', NULL);

-- ----------------------------------------------------------
-- Tabla: ventas
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `ventas`;
CREATE TABLE `ventas` (
  `id_venta` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_cliente` int DEFAULT NULL,
  `tipo_comprobante` enum('BOLETA','FACTURA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BOLETA',
  `serie` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correlativo` int NOT NULL,
  `fecha` datetime NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `igv` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_pagado` decimal(10,2) NOT NULL DEFAULT '0.00',
  `vuelto` decimal(10,2) NOT NULL DEFAULT '0.00',
  `metodo_pago` enum('EFECTIVO','TARJETA','YAPE','PLIN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EFECTIVO',
  `estado` enum('EMITIDA','ANULADA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EMITIDA',
  `motivo_anulacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_anulacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_venta`),
  UNIQUE KEY `uq_ventas_comprobante` (`tipo_comprobante`,`serie`,`correlativo`),
  KEY `fk_ventas_usuario` (`id_usuario`),
  KEY `fk_ventas_cliente` (`id_cliente`),
  KEY `idx_ventas_fecha` (`fecha`),
  KEY `idx_ventas_estado` (`estado`),
  CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ventas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ventas` (`id_venta`, `id_usuario`, `id_cliente`, `tipo_comprobante`, `serie`, `correlativo`, `fecha`, `subtotal`, `igv`, `total`, `monto_pagado`, `vuelto`, `metodo_pago`, `estado`, `motivo_anulacion`, `fecha_anulacion`) VALUES
('8', '3', NULL, 'BOLETA', 'B001', '1', '2026-09-03 14:49:31', '1.02', '0.18', '1.20', '10.00', '8.80', 'EFECTIVO', 'EMITIDA', NULL, NULL);

SET FOREIGN_KEY_CHECKS = 1;
