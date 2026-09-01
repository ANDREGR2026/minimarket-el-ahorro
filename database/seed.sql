-- =====================================================================
-- Sistema Web para la Gestion de un Minimarket
-- Datos de prueba
-- Ejecutar DESPUES de schema.sql
--
-- Credenciales de acceso:
--   SuperAdministrador -> usuario: superadmin  contrasena: super123
--   Administrador      -> usuario: admin       contrasena: admin123
--   Cajero              -> usuario: cajero      contrasena: cajero123
-- =====================================================================

USE minimarket;

-- ---------------------------------------------------------------------
-- Configuracion del negocio
-- ---------------------------------------------------------------------
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('razon_social', 'MINIMARKET EL AHORRO S.A.C.', 'Nombre legal del negocio'),
('nombre_comercial', 'EL AHORRO',               'Nombre comercial mostrado en el sistema'),
('ruc',          '20558877991',                  'RUC del negocio'),
('direccion',    'Av. Los Proceres 458, Lima',   'Direccion fiscal'),
('telefono',     '01 4567890',                   'Telefono de contacto'),
('igv',          '18',                           'Porcentaje de IGV vigente'),
('moneda',       'S/',                           'Simbolo de la moneda');

-- ---------------------------------------------------------------------
-- Usuarios
-- ---------------------------------------------------------------------
INSERT INTO usuarios (nombre, usuario, password, email, rol, estado) VALUES
('Super Administrador',  'superadmin', '$2y$10$GrD8fi73ASx43v7d0yiawu08SUw6s/CPc65bGXjEU93iBv5NW2ft2', 'superadmin@elahorro.pe', 'SuperAdministrador', 1),
('Andre Gutierrez Rojas', 'admin',  '$2y$10$/2UWllHyHOyQ8VlTBSZqIuGPnRYaX3T2y7yyfU7yD2dxqPcuKVTb2', 'admin@elahorro.pe', 'Administrador', 1),
('Lucia Ramos Peralta',   'cajero', '$2y$10$RM8/FmBegI8rJg9KidMa2ewcm6FiwrvUWGwpEtKk9idMnx0j80bPu', 'cajero@elahorro.pe', 'Cajero', 1);

-- ---------------------------------------------------------------------
-- Series de comprobante
-- ---------------------------------------------------------------------
INSERT INTO series_comprobante (tipo_comprobante, serie, ultimo_correlativo, estado) VALUES
('BOLETA',  'B001', 0, 1),
('FACTURA', 'F001', 0, 1);

-- ---------------------------------------------------------------------
-- Categorias
-- ---------------------------------------------------------------------
INSERT INTO categorias (nombre, descripcion, estado) VALUES
('Abarrotes',       'Arroz, azucar, aceite, fideos y menestras', 1),
('Bebidas',         'Gaseosas, aguas, jugos y energizantes',     1),
('Lacteos',         'Leche, yogurt, queso y mantequilla',        1),
('Snacks',          'Galletas, papas fritas y golosinas',        1),
('Limpieza',        'Detergentes, lejia y utiles de limpieza',   1),
('Cuidado Personal','Jabon, shampoo, pasta dental y papel',      1),
('Panaderia',       'Pan, pasteles y productos de panaderia',    1),
('Embutidos',       'Jamon, hot dog, chorizo y similares',       1);

-- ---------------------------------------------------------------------
-- Productos
-- precio_venta incluye IGV (RN-03)
-- ---------------------------------------------------------------------
INSERT INTO productos (codigo_barras, nombre, descripcion, id_categoria, precio_compra, precio_venta, stock, stock_minimo, unidad_medida, estado) VALUES
-- Abarrotes
('7750001000018', 'Arroz Costeno Extra 5 kg',        'Arroz superior embolsado',        1, 18.50, 23.90, 40,  8, 'BOLSA',  1),
('7750001000025', 'Azucar Rubia Cartavio 1 kg',      'Azucar rubia domestica',          1,  3.20,  4.50, 60, 10, 'BOLSA',  1),
('7750001000032', 'Aceite Primor 1 L',               'Aceite vegetal de soya',          1,  7.80,  9.90, 35,  8, 'UNIDAD', 1),
('7750001000049', 'Fideos Don Vittorio Spaghetti 500 g', 'Fideos de trigo',             1,  2.60,  3.80, 50, 10, 'BOLSA',  1),
('7750001000056', 'Lentejas Costeno 500 g',          'Menestra seleccionada',           1,  4.10,  5.60, 25,  6, 'BOLSA',  1),
('7750001000063', 'Atun Florida Filete 170 g',       'Conserva de atun en aceite',      1,  4.90,  6.50, 45, 10, 'LATA',   1),
('7750001000070', 'Sal Marina Emsal 1 kg',           'Sal yodada',                      1,  1.30,  2.20, 30,  8, 'BOLSA',  1),
-- Bebidas
('7751002000015', 'Inca Kola 1.5 L',                 'Gaseosa sabor original',          2,  4.80,  6.90, 48, 12, 'BOTELLA',1),
('7751002000022', 'Coca Cola 1.5 L',                 'Gaseosa cola',                    2,  5.00,  7.20, 42, 12, 'BOTELLA',1),
('7751002000039', 'Agua San Luis 625 ml',            'Agua mineral sin gas',            2,  1.10,  1.80, 80, 20, 'BOTELLA',1),
('7751002000046', 'Frugos Del Valle Durazno 1 L',    'Nectar de fruta',                 2,  3.90,  5.50, 30,  8, 'CAJA',   1),
('7751002000053', 'Red Bull 250 ml',                 'Bebida energizante',              2,  6.50,  9.00, 24,  6, 'LATA',   1),
('7751002000060', 'Cerveza Pilsen Callao 650 ml',    'Cerveza rubia',                   2,  5.20,  7.50, 36, 12, 'BOTELLA',1),
-- Lacteos
('7752003000012', 'Leche Gloria Evaporada 400 g',    'Leche evaporada entera',          3,  3.40,  4.60, 55, 12, 'LATA',   1),
('7752003000029', 'Yogurt Laive Fresa 1 L',          'Yogurt bebible',                  3,  5.60,  7.90, 28,  8, 'BOTELLA',1),
('7752003000036', 'Queso Fresco Bonle 500 g',        'Queso fresco pasteurizado',       3,  9.80, 13.50, 15,  5, 'UNIDAD', 1),
('7752003000043', 'Mantequilla Laive 200 g',         'Mantequilla con sal',             3,  6.20,  8.40, 18,  5, 'UNIDAD', 1),
('7752003000050', 'Huevos Pardos x 15 unidades',     'Huevos de gallina',               3,  9.00, 12.00, 20,  6, 'PAQUETE',1),
-- Snacks
('7753004000019', 'Galleta Soda Field 6 pack',       'Galleta de soda',                 4,  2.90,  4.20, 40, 10, 'PAQUETE',1),
('7753004000026', 'Papas Lays Clasicas 145 g',       'Hojuelas de papa',                4,  5.10,  7.00, 32,  8, 'BOLSA',  1),
('7753004000033', 'Chocolate Sublime 30 g',          'Chocolate con mani',              4,  1.20,  2.00, 70, 15, 'UNIDAD', 1),
('7753004000040', 'Chizitos Karinto 90 g',           'Snack de maiz con queso',         4,  2.30,  3.50, 26,  8, 'BOLSA',  1),
('7753004000057', 'Galleta Oreo 108 g',              'Galleta rellena de vainilla',     4,  2.70,  4.00, 38, 10, 'PAQUETE',1),
-- Limpieza
('7754005000016', 'Detergente Bolivar 780 g',        'Detergente en polvo',             5,  8.40, 11.50, 22,  6, 'BOLSA',  1),
('7754005000023', 'Lejia Clorox 1 L',                'Hipoclorito de sodio',            5,  3.60,  5.20, 24,  6, 'BOTELLA',1),
('7754005000030', 'Lavavajilla Ayudin 360 g',        'Crema lavavajilla',               5,  4.20,  6.00, 20,  6, 'UNIDAD', 1),
('7754005000047', 'Bolsas de Basura Grandes x 20',   'Bolsas de polietileno',           5,  4.80,  6.80, 16,  5, 'PAQUETE',1),
('7754005000054', 'Escoba Plastica Multiusos',       'Escoba de cerdas sinteticas',     5,  8.00, 12.00,  8,  3, 'UNIDAD', 1),
-- Cuidado personal
('7755006000013', 'Papel Higienico Elite x 4',       'Papel higienico doble hoja',      6,  5.40,  7.80, 34,  8, 'PAQUETE',1),
('7755006000020', 'Jabon Protex 110 g',              'Jabon antibacterial',             6,  2.40,  3.60, 42, 10, 'UNIDAD', 1),
('7755006000037', 'Shampoo Head & Shoulders 375 ml', 'Shampoo anticaspa',               6, 16.50, 22.90, 12,  4, 'BOTELLA',1),
('7755006000044', 'Pasta Dental Colgate 90 g',       'Crema dental con fluor',          6,  4.60,  6.50, 28,  8, 'UNIDAD', 1),
('7755006000051', 'Desodorante Rexona 150 ml',       'Desodorante en spray',            6, 11.20, 15.90, 14,  5, 'UNIDAD', 1),
-- Panaderia
('7756007000010', 'Pan Frances (unidad)',            'Pan del dia',                     7,  0.20,  0.40, 150, 30, 'UNIDAD',1),
('7756007000027', 'Pan de Molde Bimbo 500 g',        'Pan de molde blanco',             7,  5.80,  8.20, 18,  5, 'BOLSA',  1),
('7756007000034', 'Keke de Vainilla Individual',     'Keke envasado',                   7,  1.60,  2.50, 24,  6, 'UNIDAD', 1),
-- Embutidos
('7757008000017', 'Hot Dog Otto Kunz 8 unidades',    'Salchicha de pollo',              8,  7.40, 10.50, 20,  6, 'PAQUETE',1),
('7757008000024', 'Jamonada Laive 200 g',            'Jamonada de pollo',               8,  4.90,  6.90, 16,  5, 'PAQUETE',1),
('7757008000031', 'Chorizo Parrillero 500 g',        'Chorizo fresco',                  8, 10.20, 14.50,  9,  4, 'PAQUETE',1),
-- Productos ya bajo el stock minimo (para probar la alerta del dashboard)
('7750001000087', 'Leche Condensada Nestle 393 g',   'Leche condensada',                3,  6.10,  8.50,  3,  6, 'LATA',   1),
('7754005000061', 'Suavizante Downy 800 ml',         'Suavizante de ropa',              5,  9.30, 13.20,  2,  5, 'BOTELLA',1);

-- ---------------------------------------------------------------------
-- Clientes
-- ---------------------------------------------------------------------
INSERT INTO clientes (tipo_documento, numero_documento, nombres, apellidos, razon_social, telefono, email, direccion, estado) VALUES
('DNI', '10245678', 'Maria Elena',  'Quispe Huaman',   NULL, '987654321', 'maria.quispe@gmail.com',  'Jr. Union 231, Lima',            1),
('DNI', '45678123', 'Jose Antonio', 'Ramirez Soto',    NULL, '956231478', 'jose.ramirez@gmail.com',  'Av. Brasil 1204, Brena',         1),
('DNI', '72345891', 'Carmen Rosa',  'Flores Diaz',     NULL, '921456783', NULL,                      'Calle Los Olivos 45, SMP',       1),
('DNI', '08765432', 'Luis Alberto', 'Mendoza Ccahua',  NULL, '998745612', 'lmendoza@hotmail.com',    'Av. Tupac Amaru 890, Comas',     1),
('DNI', '46123789', 'Rosa Maria',   'Castillo Vega',   NULL, '934567812', NULL,                      'Jr. Ayacucho 567, Lima',         1),
('DNI', '71234567', 'Pedro Miguel', 'Chavez Rojas',    NULL, '945123678', 'pchavez@gmail.com',       'Av. Colonial 2310, Callao',      1),
('RUC', '20512345678', NULL, NULL, 'DISTRIBUIDORA SAN MARTIN S.A.C.', '014785236', 'ventas@dsanmartin.com', 'Av. Argentina 3200, Callao', 1),
('RUC', '20487965123', NULL, NULL, 'BODEGAS UNIDAS DEL NORTE E.I.R.L.', '013698521', 'contacto@bun.pe', 'Av. Universitaria 1450, SMP',  1),
('RUC', '10452367891', NULL, NULL, 'INVERSIONES LA ESQUINA S.R.L.', '015874123', NULL,               'Jr. Puno 780, Lima',             1),
('DNI', '00000000', 'Cliente',      'Varios',          NULL, NULL, NULL, NULL, 1);
