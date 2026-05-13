-- Desactivar revisión de llaves foráneas
SET FOREIGN_KEY_CHECKS = 0;

-- 1. TABLA DE ROLES (Niveles de acceso)
CREATE TABLE IF NOT EXISTS `table_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL UNIQUE, -- 'admin', 'recepcion', 'mecanico'
  `descripcion` varchar(255),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TABLA DE USUARIOS (Relacionada con Roles)
CREATE TABLE IF NOT EXISTS `table_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`role_id`) REFERENCES `table_roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABLA DE LA EMPRESA
CREATE TABLE IF NOT EXISTS `table_empresa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_taller` varchar(150) NOT NULL,
  `rif_dni` varchar(20) NOT NULL,
  `direccion` text,
  `telefono` varchar(50),
  `email` varchar(100),
  `logo` varchar(255) DEFAULT 'logo_default.png',
  `moneda_simbolo` varchar(5) DEFAULT '$',
  `iva_porcentaje` decimal(5,2) DEFAULT 16.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. TABLA DE PERSONAL
CREATE TABLE IF NOT EXISTS `table_personal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `cedula_id` varchar(20) NOT NULL UNIQUE,
  `nombre` varchar(100) NOT NULL,
  `cargo` varchar(50),
  `telefono` varchar(20),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_personal_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. TABLA DE CLIENTES
CREATE TABLE IF NOT EXISTS `table_clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc_identidad` varchar(20) NOT NULL UNIQUE,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(20),
  `email` varchar(100),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. TABLA DE PROVEEDORES
CREATE TABLE IF NOT EXISTS `table_proveedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rif_dni` varchar(20) NOT NULL UNIQUE,
  `razon_social` varchar(150) NOT NULL,
  `telefono` varchar(20),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activar llaves foráneas
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- INSERCIÓN DE DATOS INICIALES (SEEDER)
-- ==========================================================

-- Insertar Roles
INSERT INTO `table_roles` (`id`, `nombre_rol`, `descripcion`) VALUES
(1, 'admin', 'Administrador con acceso total'),
(2, 'recepcion', 'Atención al cliente y facturación'),
(3, 'mecanico', 'Gestión de órdenes de trabajo');

-- Insertar Empresa
INSERT INTO `table_empresa` (`id`, `nombre_taller`, `rif_dni`) 
VALUES (1, 'Mi Multiservicio Pro', 'J-12345678-0');

-- Insertar Súper Usuario (Relacionado al rol 1: admin)
-- Password: 'admin123'
INSERT INTO `table_usuarios` (`role_id`, `nombre`, `email`, `password`) 
VALUES (1, 'Administrador del Sistema', 'admin@taller.com', '$2y$10$8W3T8.fQ.Z6Z/n7k6b3P.OqFhQzX6Q/IuG6G6G6G6G6G6G6G6G6');
