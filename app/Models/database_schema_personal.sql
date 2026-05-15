-- Tabla de Personal (Staff)
CREATE TABLE IF NOT EXISTS `table_staff` (
    `id` VARCHAR(50) PRIMARY KEY, -- ID Interno (ej: STAFF-001)
    `cedula` VARCHAR(20) NOT NULL UNIQUE, -- Cédula de Identidad
    `nombre` VARCHAR(100) NOT NULL,
    `cargo` VARCHAR(50) NOT NULL, -- Ej: Mecánico, Administrador
    `telefono` VARCHAR(20),
    `email` VARCHAR(100),
    `direccion` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;