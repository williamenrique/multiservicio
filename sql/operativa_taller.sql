SET FOREIGN_KEY_CHECKS = 0;


-- Tabla de Vehículos
CREATE TABLE IF NOT EXISTS table_vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(20) UNIQUE NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    anio INT,
    color VARCHAR(30),
    cliente_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES table_clientes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de Órdenes de Servicio (O.S.)
CREATE TABLE IF NOT EXISTS table_ordenes_servicio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    usuario_id INT NOT NULL, -- Mecánico/Responsable
    kilometraje INT NOT NULL,
    nivel_combustible VARCHAR(20), -- Ej: 1/4, 1/2, Lleno
    observaciones_entrada TEXT,
    estado ENUM('RECIBIDO', 'DIAGNOSTICANDO', 'ESPERANDO_REPUESTOS', 'EN_REPARACION', 'LISTO', 'ENTREGADO') DEFAULT 'RECIBIDO',
    fecha_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_entrega_estimada DATETIME,
    total_estimado DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (vehiculo_id) REFERENCES table_vehiculos(id),
    FOREIGN KEY (usuario_id) REFERENCES table_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Checklist de Entrada
CREATE TABLE IF NOT EXISTS table_orden_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    item VARCHAR(100) NOT NULL, -- Ej: Llave de repuesto, Gato, Herramientas
    estado BOOLEAN DEFAULT FALSE,
    observacion VARCHAR(255),
    FOREIGN KEY (orden_id) REFERENCES table_ordenes_servicio(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Historial de Estados (Para trazabilidad)
CREATE TABLE IF NOT EXISTS table_orden_estados_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    estado_anterior VARCHAR(50),
    estado_nuevo VARCHAR(50),
    usuario_id INT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    comentario TEXT,
    FOREIGN KEY (orden_id) REFERENCES table_ordenes_servicio(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES table_usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;