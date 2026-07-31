-- ============================================================
-- MIGRACIÓN: Catálogo Público Unificado
-- ============================================================
-- 1. Agrega columnas faltantes a table_inventario
-- 2. Crea tablas de pedidos públicos
-- ============================================================

-- 1. Agregar columnas faltantes a table_inventario
ALTER TABLE table_inventario
ADD COLUMN IF NOT EXISTS codigo VARCHAR(50) NULL AFTER id,
ADD COLUMN IF NOT EXISTS marca VARCHAR(100) NULL AFTER nombre,
ADD COLUMN IF NOT EXISTS descripcion TEXT NULL AFTER marca;

-- Índice para búsquedas por código
ALTER TABLE table_inventario
ADD INDEX IF NOT EXISTS idx_codigo (codigo);

-- 2. Tabla de pedidos de clientes (público)
CREATE TABLE IF NOT EXISTS pedidos_clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_cliente VARCHAR(150) NOT NULL,
    cedula VARCHAR(20) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    direccion TEXT NULL,
    notas TEXT NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    iva DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    estado ENUM('PENDIENTE','CONFIRMADO','PROCESADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
    usuario_procesa INT NULL,
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_procesado TIMESTAMP NULL,
    FOREIGN KEY (usuario_procesa) REFERENCES table_usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de detalle del pedido
CREATE TABLE IF NOT EXISTS pedido_detalles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos_clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES table_inventario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;