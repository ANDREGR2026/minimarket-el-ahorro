-- =====================================================================
-- Migracion 006: normalizacion ortografica del catalogo inicial
-- =====================================================================

USE minimarket;

UPDATE categorias
SET nombre = CASE nombre
    WHEN 'Lacteos' THEN 'Lácteos'
    WHEN 'Panaderia' THEN 'Panadería'
    ELSE nombre
END,
descripcion = REPLACE(REPLACE(REPLACE(REPLACE(descripcion,
    'azucar', 'azúcar'), 'lejia', 'lejía'), 'utiles', 'útiles'),
    'Jabon', 'Jabón');

UPDATE categorias
SET descripcion = REPLACE(REPLACE(descripcion, 'Jamon', 'Jamón'), 'panaderia', 'panadería');

UPDATE productos
SET nombre = CASE nombre
    WHEN 'Arroz Costeno Extra 5 kg' THEN 'Arroz Costeño Extra 5 kg'
    WHEN 'Azucar Rubia Cartavio 1 kg' THEN 'Azúcar Rubia Cartavio 1 kg'
    WHEN 'Lentejas Costeno 500 g' THEN 'Lentejas Costeño 500 g'
    WHEN 'Atun Florida Filete 170 g' THEN 'Atún Florida Filete 170 g'
    WHEN 'Papas Lays Clasicas 145 g' THEN 'Papas Lays Clásicas 145 g'
    WHEN 'Lejia Clorox 1 L' THEN 'Lejía Clorox 1 L'
    WHEN 'Lavavajilla Ayudin 360 g' THEN 'Lavavajilla Ayudín 360 g'
    WHEN 'Detergente Bolivar 780 g' THEN 'Detergente Bolívar 780 g'
    WHEN 'Escoba Plastica Multiusos' THEN 'Escoba Plástica Multiusos'
    WHEN 'Papel Higienico Elite x 4' THEN 'Papel Higiénico Elite x 4'
    WHEN 'Jabon Protex 110 g' THEN 'Jabón Protex 110 g'
    WHEN 'Pan Frances (unidad)' THEN 'Pan Francés (unidad)'
    WHEN 'Leche Condensada Nestle 393 g' THEN 'Leche Condensada Nestlé 393 g'
    ELSE nombre
END,
descripcion = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(descripcion,
    'atun', 'atún'), 'mani', 'maní'), 'maiz', 'maíz'), 'sinteticas', 'sintéticas'),
    'higienico', 'higiénico'), 'fluor', 'flúor');

UPDATE clientes
SET nombres = CASE nombres
    WHEN 'Maria Elena' THEN 'María Elena'
    WHEN 'Jose Antonio' THEN 'José Antonio'
    WHEN 'Pedro Miguel' THEN 'Pedro Miguel'
    ELSE nombres
END,
apellidos = CASE apellidos
    WHEN 'Quispe Huaman' THEN 'Quispe Huamán'
    WHEN 'Chavez Rojas' THEN 'Chávez Rojas'
    ELSE apellidos
END,
razon_social = CASE razon_social
    WHEN 'DISTRIBUIDORA SAN MARTIN S.A.C.' THEN 'DISTRIBUIDORA SAN MARTÍN S.A.C.'
    ELSE razon_social
END,
direccion = REPLACE(REPLACE(direccion, 'Brena', 'Breña'), 'Tupac', 'Túpac');

UPDATE usuarios
SET nombre = CASE nombre
    WHEN 'Lucia Ramos Peralta' THEN 'Lucía Ramos Peralta'
    WHEN 'Marco Solis Vega' THEN 'Marco Solís Vega'
    ELSE nombre
END;
