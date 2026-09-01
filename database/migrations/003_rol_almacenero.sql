-- =====================================================================
-- Migracion 003: rol Almacenero
-- Ejecutar UNA SOLA VEZ sobre una base de datos existente creada con una
-- version anterior de schema.sql (que no tenia "Almacenero" en el ENUM
-- de "usuarios.rol"). Si el valor ya existe en el ENUM, no vuelva a
-- correr este script.
-- =====================================================================

USE minimarket;

ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('SuperAdministrador','Administrador','Cajero','Almacenero')
    NOT NULL DEFAULT 'Cajero';

-- Usuario de prueba opcional para el nuevo rol (ajustar o quitar en produccion)
INSERT INTO usuarios (nombre, usuario, password, email, rol, estado) VALUES
('Marco Solis Vega', 'almacen', '$2y$10$TX6jHugKh07pK0Vg2kGoWeYTCTYVgLtHNANumlcVXZdiSeUMkdcyK', 'almacen@elahorro.pe', 'Almacenero', 1)
ON DUPLICATE KEY UPDATE usuario = usuario;
