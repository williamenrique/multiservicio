-- --------------------------------------------------------
-- Estructura de Base de Datos para Multiservicio "Taller Pro"
-- Unificada: Roles, Personal, Usuarios, Clientes, Proveedores, Empresa, Inventario, Ventas, Gastos y Compras
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS `multiservicio` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci */;
USE `multiservicio`;

-- 1. Tabla de Roles (Independiente)
CREATE TABLE IF NOT EXISTS `table_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Tabla de Staff / Personal (Independiente)
CREATE TABLE IF NOT EXISTS `table_staff` (
  `id` varchar(50) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Tabla de Usuarios (Depende de Roles y Staff)
CREATE TABLE IF NOT EXISTS `table_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_id` (`staff_id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_user_role` (`role_id`),
  CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `table_roles` (`id`),
  CONSTRAINT `fk_user_staff` FOREIGN KEY (`staff_id`) REFERENCES `table_staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Tabla de Sesiones (Depende de Usuarios)
CREATE TABLE IF NOT EXISTS `table_usuario_sessions` (
  `usuario_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `usuario_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Tabla de Clientes
CREATE TABLE IF NOT EXISTS `table_clientes` (
  `id` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cliente_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Tabla de Proveedores
CREATE TABLE IF NOT EXISTS `table_proveedores` (
  `id` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Tabla de ConfiguraciOn de la Empresa
CREATE TABLE IF NOT EXISTS `table_company_settings` (
  `id` INT(11) NOT NULL DEFAULT 1, -- Siempre serA 1, para asegurar una única fila
  `name` VARCHAR(100) NOT NULL,
  `nit` VARCHAR(50) DEFAULT NULL,
  `iva` DECIMAL(5,2) DEFAULT 0.00,
  `logo` TEXT DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  `updated_at` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8. Tabla de Inventario (Repuestos y Servicios)
CREATE TABLE IF NOT EXISTS `table_inventario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `precio` decimal(15,2) NOT NULL DEFAULT 0.00,
  `imagen` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_inv_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9. Tabla de Ventas (Historial)
CREATE TABLE IF NOT EXISTS `table_ventas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` varchar(50) DEFAULT NULL, -- Corregido de 20 a 50 para coincidir con table_clientes.id
  `placa` varchar(20) DEFAULT NULL,
  `modelo_vehiculo` varchar(100) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `iva_monto` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `status` enum('PENDIENTE','COMPLETADO','CANCELADO') DEFAULT 'PENDIENTE',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_venta_cliente` (`cliente_id`),
  KEY `fk_venta_usuario` (`usuario_id`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- 10. Detalle de Ventas
CREATE TABLE IF NOT EXISTS `table_ventas_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalle_venta` (`venta_id`),
  CONSTRAINT `fk_detalle_venta` FOREIGN KEY (`venta_id`) REFERENCES `table_ventas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 11. Tabla de Gastos (Egresos Operativos del Taller)
DROP TABLE IF EXISTS `table_gastos`;
CREATE TABLE IF NOT EXISTS `table_gastos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT 'EFECTIVO', -- Efectivo, Transferencia, Tarjeta
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_gasto_usuario` (`usuario_id`),
  CONSTRAINT `fk_gasto_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 12. Tabla de Compras (Entrada de Mercancía y Deudas)
CREATE TABLE IF NOT EXISTS `table_compras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_id` varchar(50) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `pagado` decimal(15,2) DEFAULT 0.00,
  `fecha_vencimiento` date DEFAULT NULL, -- Fecha de cobro/corte
  `status` enum('PENDIENTE', 'PAGADO') DEFAULT 'PENDIENTE',
  `usuario_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_compra_proveedor` (`proveedor_id`),
  KEY `fk_compra_usuario` (`usuario_id`),
  CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `table_proveedores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 13. Detalle de Compras
CREATE TABLE IF NOT EXISTS `table_compras_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compra_id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL, -- NULL si es producto nuevo en esta compra
  `descripcion` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `costo_unitario` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalle_compra` (`compra_id`),
  KEY `fk_detalle_producto_compra` (`producto_id`),
  CONSTRAINT `fk_detalle_compra` FOREIGN KEY (`compra_id`) REFERENCES `table_compras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_detalle_producto_compra` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- INSERCION DE DATOS INICIALES (SEMILLAS)
-- --------------------------------------------------------

-- Limpiar tablas antes de insertar (Orden inverso de FK)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `table_usuario_sessions`;
TRUNCATE TABLE `table_usuarios`;
TRUNCATE TABLE `table_roles`;
TRUNCATE TABLE `table_staff`;
TRUNCATE TABLE `table_company_settings`;
SET FOREIGN_KEY_CHECKS = 1;

-- Roles del sistema
INSERT INTO `table_roles` (`nombre_rol`) VALUES 
('ADMINISTRADOR'), 
('MECANICO'), 
('EMPLEADO');

-- Empleado inicial para el Administrador
INSERT INTO `table_staff` (`id`, `cedula`, `nombre`, `cargo`, `telefono`, `email`, `direccion`) 
VALUES 
('STAFF-1', 'V-00000000', 'ADMINISTRADOR DEL SISTEMA', 'ADMINISTRADOR', '0000000000', 'admin@tallerpro.com', 'SEDE PRINCIPAL');

-- Usuario administrador inicial (Usuario: admin / Password: admin)
INSERT INTO `table_usuarios` (`staff_id`, `username`, `password`, `role_id`, `estado`) 
VALUES 
('STAFF-1', 'admin', 'admin', 1, 1);

-- Datos iniciales para la configuraciOn de la empresa
INSERT INTO `table_company_settings` (`id`, `name`, `nit`, `iva`, `address`) VALUES
(1, 'TALLER PRO', '0000000000', 19.00, 'DIRECCION PRINCIPAL DEL TALLER');

-- Restaurar configuraciones originales
/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;