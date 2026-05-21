CREATE TABLE table_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    modulo VARCHAR(50) NOT NULL, -- Ej: 'AUTH', 'PERSONAL', 'INVENTARIO'
    accion VARCHAR(50) NOT NULL, -- Ej: 'LOGIN', 'CREATE', 'UPDATE', 'DELETE'
    descripcion TEXT,            -- Detalles del cambio
    ip_address VARCHAR(45),
    fecha DATETIME NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES table_usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
