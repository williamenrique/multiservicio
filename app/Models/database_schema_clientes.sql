-- Tabla de Clientes
CREATE TABLE IF NOT EXISTS `table_clientes` (
    `id` VARCHAR(50) PRIMARY KEY, -- Formato 'CLI-001' o similar
    `nombre` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100),
    `telefono` VARCHAR(20),
    `direccion` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Opcional: Indexar el nombre para búsquedas rápidas en facturación
CREATE INDEX idx_cliente_nombre ON table_clientes(nombre);