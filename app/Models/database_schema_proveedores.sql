-- Tabla de Proveedores
CREATE TABLE IF NOT EXISTS `table_proveedores` (
    `id` VARCHAR(50) PRIMARY KEY, -- NIT o Cédula Jurídica
    `nombre` VARCHAR(100) NOT NULL,
    `telefono` VARCHAR(20),
    `email` VARCHAR(100),
    `direccion` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;