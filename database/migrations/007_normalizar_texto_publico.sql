-- =====================================================================
-- Migracion 007: correccion de acentos en la informacion publica
-- =====================================================================

USE minimarket;

UPDATE configuracion
SET valor = 'Av. Los Próceres 458, Lima'
WHERE clave = 'direccion' AND valor = 'Av. Los Proceres 458, Lima';
