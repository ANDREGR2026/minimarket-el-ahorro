-- =====================================================================
-- Migracion 002: recuperacion de contrasena por correo
-- Ejecutar UNA SOLA VEZ sobre una base de datos existente creada con una
-- version anterior de schema.sql (que no tenia estas columnas en
-- "usuarios"). Si alguna columna ya existe, quite esa linea antes de
-- volver a correr el script.
-- =====================================================================

USE minimarket;

ALTER TABLE usuarios ADD COLUMN email VARCHAR(100) NULL AFTER password;
ALTER TABLE usuarios ADD COLUMN reset_token_hash VARCHAR(255) NULL AFTER estado;
ALTER TABLE usuarios ADD COLUMN reset_token_expira DATETIME NULL AFTER reset_token_hash;

-- Emails de ejemplo para las cuentas de prueba del seed (ajustar en produccion)
UPDATE usuarios SET email = 'superadmin@elahorro.pe' WHERE usuario = 'superadmin' AND email IS NULL;
UPDATE usuarios SET email = 'admin@elahorro.pe'      WHERE usuario = 'admin'      AND email IS NULL;
UPDATE usuarios SET email = 'cajero@elahorro.pe'     WHERE usuario = 'cajero'     AND email IS NULL;
