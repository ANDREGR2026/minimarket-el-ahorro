-- =====================================================================
-- Migracion 005: correccion de texto historico en movimientos
-- Repara registros importados con el caracter "?" en lugar de "ó".
-- =====================================================================

USE minimarket;

UPDATE movimientos_inventario
SET motivo = REPLACE(motivo, 'Anulaci?n', 'Anulación')
WHERE motivo LIKE '%Anulaci?n%';
