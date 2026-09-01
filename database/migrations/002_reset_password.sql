-- =====================================================================
-- Migracion 002: recuperacion de contrasena por correo
-- Ejecutar sobre una base de datos existente creada con una version
-- anterior de schema.sql (que no tenia estas columnas en "usuarios").
-- Idempotente: seguro de correr aunque falte solo alguna columna.
-- =====================================================================

USE minimarket;

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL AFTER password,
    ADD COLUMN IF NOT EXISTS reset_token_hash VARCHAR(255) NULL AFTER estado,
    ADD COLUMN IF NOT EXISTS reset_token_expira DATETIME NULL AFTER reset_token_hash;

-- Emails de ejemplo para las cuentas de prueba del seed (ajustar en produccion)
UPDATE usuarios SET email = 'superadmin@elahorro.pe' WHERE usuario = 'superadmin' AND email IS NULL;
UPDATE usuarios SET email = 'admin@elahorro.pe'      WHERE usuario = 'admin'      AND email IS NULL;
UPDATE usuarios SET email = 'cajero@elahorro.pe'     WHERE usuario = 'cajero'     AND email IS NULL;
