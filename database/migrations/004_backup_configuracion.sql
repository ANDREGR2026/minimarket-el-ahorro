-- =====================================================================
-- Migracion 004: configuracion del backup automatico
-- Ejecutar UNA SOLA VEZ sobre una base de datos existente. Si las claves
-- ya existen, INSERT IGNORE no las modifica.
-- =====================================================================

USE minimarket;

INSERT IGNORE INTO configuracion (clave, valor, descripcion) VALUES
('backup_frecuencia', 'Semanal', 'Frecuencia del backup automatico: Desactivado, Diario, Semanal o Mensual'),
('backup_conservar',  '10',      'Cantidad de backups a conservar antes de borrar el mas antiguo');
