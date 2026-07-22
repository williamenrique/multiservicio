-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         10.4.32-MariaDB - mariadb.org binary distribution
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.20.0.7320
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para multiservicio_2.0
CREATE DATABASE IF NOT EXISTS `multiservicio_2.0` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci */;
USE `multiservicio_2.0`;

-- Volcando estructura para tabla multiservicio_2.0.table_abonos_clientes
CREATE TABLE IF NOT EXISTS `table_abonos_clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) DEFAULT NULL,
  `monto` decimal(15,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO','TRANSFERENCIA') NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `factura_id` (`factura_id`),
  CONSTRAINT `table_abonos_clientes_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `table_facturas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_abonos_proveedores
CREATE TABLE IF NOT EXISTS `table_abonos_proveedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compra_id` int(11) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO','TRANSFERENCIA') DEFAULT 'EFECTIVO',
  `usuario_id` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `compra_id` (`compra_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `table_abonos_proveedores_ibfk_1` FOREIGN KEY (`compra_id`) REFERENCES `table_compras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_abonos_proveedores_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_audit_logs
CREATE TABLE IF NOT EXISTS `table_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_clientes
CREATE TABLE IF NOT EXISTS `table_clientes` (
  `id` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `nombre` (`nombre`),
  KEY `telefono` (`telefono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_company_settings
CREATE TABLE IF NOT EXISTS `table_company_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(100) NOT NULL,
  `nit` varchar(50) DEFAULT NULL,
  `iva` decimal(5,2) DEFAULT 19.00,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_compras
CREATE TABLE IF NOT EXISTS `table_compras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_id` varchar(50) DEFAULT NULL,
  `total` decimal(15,2) NOT NULL,
  `pagado` decimal(15,2) DEFAULT 0.00,
  `fecha_vencimiento` date DEFAULT NULL,
  `status` enum('PENDIENTE','PAGADO','ANULADO') DEFAULT 'PENDIENTE',
  `usuario_id` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `proveedor_id` (`proveedor_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `table_compras_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `table_proveedores` (`id`),
  CONSTRAINT `table_compras_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_compras_detalle
CREATE TABLE IF NOT EXISTS `table_compras_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compra_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `costo_unitario` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `compra_id` (`compra_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `table_compras_detalle_ibfk_1` FOREIGN KEY (`compra_id`) REFERENCES `table_compras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_compras_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_cuentas_pago
CREATE TABLE IF NOT EXISTS `table_cuentas_pago` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `tipo` enum('EFECTIVO','BANCO','VIRTUAL') DEFAULT 'EFECTIVO',
  `saldo_actual` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_devoluciones
CREATE TABLE IF NOT EXISTS `table_devoluciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `monto_devuelto` decimal(15,2) DEFAULT NULL,
  `destino` enum('STOCK','DANADO') DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `factura_id` (`factura_id`),
  KEY `producto_id` (`producto_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `table_devoluciones_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `table_facturas` (`id`),
  CONSTRAINT `table_devoluciones_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`),
  CONSTRAINT `table_devoluciones_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_facturas
CREATE TABLE IF NOT EXISTS `table_facturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orden_id` int(11) DEFAULT NULL,
  `cliente_id` varchar(50) DEFAULT NULL,
  `placa` varchar(20) DEFAULT NULL,
  `modelo_vehiculo` varchar(100) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `iva_monto` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL,
  `pago_efectivo` decimal(15,2) DEFAULT 0.00,
  `pago_transferencia` decimal(15,2) DEFAULT 0.00,
  `saldo_pendiente` decimal(15,2) DEFAULT 0.00,
  `status` enum('COMPLETADO','CREDITO','ANULADO','PENDIENTE') DEFAULT 'COMPLETADO',
  `observaciones` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `table_facturas_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `table_ordenes_servicio` (`id`),
  CONSTRAINT `table_facturas_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes` (`id`),
  CONSTRAINT `table_facturas_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_facturas_detalle
CREATE TABLE IF NOT EXISTS `table_facturas_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `mecanico_id` varchar(50) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(15,2) NOT NULL,
  `costo_unitario` decimal(15,2) NOT NULL,
  `pago_nomina_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `factura_id` (`factura_id`),
  KEY `producto_id` (`producto_id`),
  KEY `mecanico_id` (`mecanico_id`),
  CONSTRAINT `table_facturas_detalle_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `table_facturas` (`id`),
  CONSTRAINT `table_facturas_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`),
  CONSTRAINT `table_facturas_detalle_ibfk_3` FOREIGN KEY (`mecanico_id`) REFERENCES `table_staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_gastos
CREATE TABLE IF NOT EXISTS `table_gastos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria` varchar(50) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `monto` decimal(15,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT 'EFECTIVO',
  `usuario_id` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_inventario
CREATE TABLE IF NOT EXISTS `table_inventario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 5,
  `ultimo_costo` decimal(15,2) DEFAULT 0.00,
  `costo_promedio` decimal(15,2) DEFAULT 0.00,
  `precio` decimal(15,2) NOT NULL DEFAULT 0.00,
  `imagen` varchar(255) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  PRIMARY KEY (`id`),
  KEY `nombre` (`nombre`),
  KEY `categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_kardex
CREATE TABLE IF NOT EXISTS `table_kardex` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('ENTRADA_COMPRA','SALIDA_VENTA','AJUSTE_MANUAL','DEVOLUCION') DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `stock_anterior` int(11) NOT NULL,
  `stock_actual` int(11) NOT NULL,
  `referencia_id` varchar(50) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `table_kardex_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_kardex_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_orden_checklist
CREATE TABLE IF NOT EXISTS `table_orden_checklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orden_id` int(11) NOT NULL,
  `item` varchar(100) NOT NULL,
  `estado` tinyint(1) DEFAULT 0,
  `observacion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  CONSTRAINT `table_orden_checklist_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `table_ordenes_servicio` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_orden_estados_log
CREATE TABLE IF NOT EXISTS `table_orden_estados_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orden_id` int(11) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `table_orden_estados_log_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `table_ordenes_servicio` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_orden_estados_log_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_ordenes_servicio
CREATE TABLE IF NOT EXISTS `table_ordenes_servicio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` varchar(50) DEFAULT NULL,
  `placa` varchar(10) NOT NULL,
  `mecanico_id` varchar(50) DEFAULT NULL,
  `kilometraje` varchar(20) DEFAULT NULL,
  `nivel_combustible` varchar(20) DEFAULT NULL,
  `diagnostico_entrada` text DEFAULT NULL,
  `diagnostico_salida` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('RECIBIDO','DIAGNOSTICANDO','EN_REPARACION','LISTO','ENTREGADO','CANCELADO') DEFAULT 'RECIBIDO',
  `fecha_ingreso` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_entrega_estimada` datetime DEFAULT NULL,
  `fecha_entrega_real` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `mecanico_id` (`mecanico_id`),
  KEY `placa` (`placa`),
  KEY `estado` (`estado`),
  CONSTRAINT `table_ordenes_servicio_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes` (`id`),
  CONSTRAINT `table_ordenes_servicio_ibfk_2` FOREIGN KEY (`placa`) REFERENCES `table_vehiculos` (`placa`),
  CONSTRAINT `table_ordenes_servicio_ibfk_3` FOREIGN KEY (`mecanico_id`) REFERENCES `table_staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_pagos_empleados
CREATE TABLE IF NOT EXISTS `table_pagos_empleados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(50) DEFAULT NULL,
  `monto` decimal(15,2) NOT NULL,
  `monto_base` decimal(15,2) DEFAULT NULL,
  `tipo` enum('ADELANTO','PAGO_NOMINA') DEFAULT 'PAGO_NOMINA',
  `metodo_pago` varchar(50) DEFAULT NULL,
  `modo_calculo` varchar(30) DEFAULT 'FIJO',
  `factor_calculo` decimal(15,2) DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `table_pagos_empleados_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `table_staff` (`id`),
  CONSTRAINT `table_pagos_empleados_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_proveedores
CREATE TABLE IF NOT EXISTS `table_proveedores` (
  `id` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_recuperaciones
CREATE TABLE IF NOT EXISTS `table_recuperaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT 'RECUPERACION',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `table_recuperaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_roles
CREATE TABLE IF NOT EXISTS `table_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_staff
CREATE TABLE IF NOT EXISTS `table_staff` (
  `id` varchar(50) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cargo` varchar(50) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'img/default.png',
  `foto_frente` varchar(255) DEFAULT 'img/default.png',
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_transacciones
CREATE TABLE IF NOT EXISTS `table_transacciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cuenta_id` int(11) DEFAULT NULL,
  `tipo` enum('INGRESO','EGRESO') NOT NULL,
  `categoria` enum('VENTA','GASTO','NOMINA','COMPRA_PROVEEDOR','ABONO_CLIENTE','ABONO_PROVEEDOR') NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cuenta_id` (`cuenta_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `categoria` (`categoria`),
  KEY `fecha` (`fecha`),
  CONSTRAINT `table_transacciones_ibfk_1` FOREIGN KEY (`cuenta_id`) REFERENCES `table_cuentas_pago` (`id`),
  CONSTRAINT `table_transacciones_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_usuario_sessions
CREATE TABLE IF NOT EXISTS `table_usuario_sessions` (
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('WEB','APP') NOT NULL DEFAULT 'APP',
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `usuario_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`usuario_id`,`tipo`),
  UNIQUE KEY `uk_usuario_tipo` (`usuario_id`,`tipo`),
  KEY `idx_sessions_tipo` (`tipo`),
  CONSTRAINT `table_usuario_sessions_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_usuarios
CREATE TABLE IF NOT EXISTS `table_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `staff_id` (`staff_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `table_usuarios_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `table_staff` (`id`),
  CONSTRAINT `table_usuarios_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `table_roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla multiservicio_2.0.table_vehiculos
CREATE TABLE IF NOT EXISTS `table_vehiculos` (
  `placa` varchar(20) NOT NULL,
  `cliente_id` varchar(50) DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `anio` int(4) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`placa`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `table_vehiculos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
