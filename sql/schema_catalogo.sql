-- Crear base de datos
CREATE DATABASE IF NOT EXISTS catalogo_repuestos;
USE catalogo_repuestos;

-- Tabla de repuestos
CREATE TABLE IF NOT EXISTS repuestos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    marca VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    imagen VARCHAR(255),
    stock INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_cliente VARCHAR(200) NOT NULL,
    cedula VARCHAR(20) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(50) DEFAULT 'pendiente'
);

-- Tabla de detalles de pedidos
CREATE TABLE IF NOT EXISTS pedido_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT,
    repuesto_id INT,
    cantidad INT,
    precio_unitario DECIMAL(10,2),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (repuesto_id) REFERENCES repuestos(id)
);

-- Insertar algunos repuestos de ejemplo
INSERT INTO repuestos (codigo, nombre, marca, descripcion, precio, stock) VALUES
('FIL-001', 'Filtro de Aire Premium', 'Bosch', 'Filtro de aire de alta eficiencia para motor', 25.99, 50),
('FIL-002', 'Filtro de Aceite', 'Mann', 'Filtro de aceite de calidad superior', 15.50, 80),
('BUI-001', 'Bujía Iridio', 'NGK', 'Bujía de iridio para mejor rendimiento', 12.75, 120),
('FR-001', 'Pastillas de Freno Delanteras', 'Brembo', 'Pastillas de freno de alto rendimiento', 45.90, 30),
('ACE-001', 'Aceite Motor 5W-30', 'Mobil', 'Aceite sintético de alta calidad', 35.00, 45);