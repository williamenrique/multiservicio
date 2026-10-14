-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         10.4.32-MariaDB - mariadb.org binary distribution
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.21.0.7344
-- --------------------------------------------------------
-- 
-- CAMBIOS v2.1.0 (2026-10-08):
--   • Se agregó la columna `estado_gestion` (ENUM) a table_facturas.
--
-- CAMBIOS v2.1.1 (2026-10-09):
--   • FIX CRÍTICO: Se agregó la columna `usuario_id` a table_abonos_clientes
--     con su índice (`idx_abonos_usuario`) y FK a table_usuarios.
--     Motivo: ModelFacturacion y ModelFacturas ya usaban esta columna en
--     JOINs, pero el dump base no la incluía, causando:
--         SQLSTATE[42S22]: Column not found: 1054 Unknown column 'a.usuario_id'
--
-- CAMBIOS v2.1.2 (2026-10-09):
--   • Se agregaron 5 índices adicionales para consultas frecuentes:
--       - table_facturas.idx_facturas_status          (WHERE status)
--       - table_compras.idx_compras_status            (WHERE status)
--       - table_abonos_clientes.idx_abonos_fecha      (ORDER BY fecha)
--       - table_devoluciones.idx_devoluciones_fecha   (WHERE fecha)
--       - table_kardex.idx_kardex_fecha               (ORDER BY fecha DESC)
--     Para BD existentes: ejecutar sql/migration_indices_faltantes.sql
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Volcando estructura para tabla multiservicio_2.0.pedido_detalles
CREATE TABLE IF NOT EXISTS `pedido_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `pedido_detalles_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos_clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pedido_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.pedido_detalles: ~0 rows (aproximadamente)
DELETE FROM `pedido_detalles`;

-- Volcando estructura para tabla multiservicio_2.0.pedidos_clientes
CREATE TABLE IF NOT EXISTS `pedidos_clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_cliente` varchar(150) NOT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `iva` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `estado` enum('PENDIENTE','PROCESADO','CANCELADO') DEFAULT 'PENDIENTE',
  `usuario_procesa` int(11) DEFAULT NULL,
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_procesado` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `estado` (`estado`),
  KEY `fecha_pedido` (`fecha_pedido`),
  KEY `usuario_procesa` (`usuario_procesa`),
  CONSTRAINT `pedidos_clientes_ibfk_1` FOREIGN KEY (`usuario_procesa`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.pedidos_clientes: ~0 rows (aproximadamente)
DELETE FROM `pedidos_clientes`;

-- -----------------------------------------------------------------------------
-- v2.1.1: se agregó `usuario_id` + idx_abonos_usuario + FK.
-- v2.1.2: se agregó idx_abonos_fecha (fecha).
-- -----------------------------------------------------------------------------
-- Volcando estructura para tabla multiservicio_2.0.table_abonos_clientes
CREATE TABLE IF NOT EXISTS `table_abonos_clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) DEFAULT NULL,
  `monto` decimal(15,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO','TRANSFERENCIA') NOT NULL,
  `usuario_id` int(11) DEFAULT NULL COMMENT 'Usuario que registró el abono',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `factura_id` (`factura_id`),
  KEY `idx_abonos_usuario` (`usuario_id`),
  KEY `idx_abonos_fecha` (`fecha`),
  CONSTRAINT `table_abonos_clientes_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `table_facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_abonos_clientes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_abonos_clientes: ~0 rows (aproximadamente)
DELETE FROM `table_abonos_clientes`;

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

-- Volcando datos para la tabla multiservicio_2.0.table_abonos_proveedores: ~0 rows (aproximadamente)
DELETE FROM `table_abonos_proveedores`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_audit_logs: ~0 rows (aproximadamente)
DELETE FROM `table_audit_logs`;

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

-- Volcando datos para la tabla multiservicio_2.0.table_clientes: ~9 rows (aproximadamente)
DELETE FROM `table_clientes`;
INSERT INTO `table_clientes` (`id`, `nombre`, `telefono`, `email`, `direccion`, `fecha_registro`) VALUES
	('10332211', 'PEDRO PABLO', '04142752211', 'pedropablo@yahoo.com', 'LAS TAPIAS', '2026-10-07 18:45:35'),
	('13302132', 'KARLA MARTINEZ', '0412521226', 'karlamartinez@gmail.com', 'URB LA ASCENCION VEREDA 3', '2026-10-07 12:04:26'),
	('13332120', 'JOSE PEREZ', '04125121125', '', '', '2026-10-07 18:30:56'),
	('15769775', 'YBET NACARI', '04120586489', 'ybet.naca@gmail.com', '', '2026-10-07 11:51:29'),
	('17303158', 'MIGUEL ROMERO', '041251236523', 'miguelro@gmail.com', 'URB LA ASCENCION', '2026-10-07 15:35:40'),
	('18303158', 'JOSE RENGEL', '0412152122', 'joserengel@gmail.com', 'URACHICHE', '2026-10-07 18:10:51'),
	('19323150', 'NELSON JIMENEZ', '04125151236', 'nelson@gmail.com', 'CAÑAVERAL', '2026-10-07 20:45:44'),
	('30303158', 'JOSE ANTONIO', '04125122363', 'joseantonio@gmail.com', 'URB SAN MIGUEL , LA CEDEÑO', '2026-10-07 19:09:05'),
	('7303152', 'PEDRO CAMEJO', '04125181622', 'pedrocamejo@gmail.com', 'LAS TAPIAS', '2026-10-07 12:06:58');

-- Volcando estructura para tabla multiservicio_2.0.table_company_settings
CREATE TABLE IF NOT EXISTS `table_company_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(100) NOT NULL,
  `nit` varchar(50) DEFAULT NULL,
  `iva` decimal(5,2) DEFAULT 19.00,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `dias_garantia_devolucion` int(11) DEFAULT 5,
  `dias_garantia_servicio` int(11) NOT NULL DEFAULT 15,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_company_settings: ~1 rows (aproximadamente)
DELETE FROM `table_company_settings`;
INSERT INTO `table_company_settings` (`id`, `name`, `nit`, `iva`, `direccion`, `telefono`, `logo`, `dias_garantia_devolucion`, `dias_garantia_servicio`) VALUES
	(1, 'TALLER PRO', 'J-00000000-0', 19.00, 'DIRECCIÓN DE LA EMPRESA', '000-0000000', NULL, 5, 15);

-- -----------------------------------------------------------------------------
-- v2.1.2: se agregó KEY idx_compras_status (status).
-- -----------------------------------------------------------------------------
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
  KEY `idx_compras_status` (`status`),
  CONSTRAINT `table_compras_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `table_proveedores` (`id`),
  CONSTRAINT `table_compras_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_compras: ~0 rows (aproximadamente)
DELETE FROM `table_compras`;

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

-- Volcando datos para la tabla multiservicio_2.0.table_compras_detalle: ~0 rows (aproximadamente)
DELETE FROM `table_compras_detalle`;

-- Volcando estructura para tabla multiservicio_2.0.table_cuentas_pago
CREATE TABLE IF NOT EXISTS `table_cuentas_pago` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `tipo` enum('EFECTIVO','BANCO','VIRTUAL') DEFAULT 'EFECTIVO',
  `saldo_actual` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_cuentas_pago: ~2 rows (aproximadamente)
DELETE FROM `table_cuentas_pago`;
INSERT INTO `table_cuentas_pago` (`id`, `nombre`, `tipo`, `saldo_actual`) VALUES
	(1, 'CAJA GENERAL EFECTIVO', 'EFECTIVO', 0.00),
	(2, 'CUENTA BANCO', 'VIRTUAL', 0.00);

-- -----------------------------------------------------------------------------
-- v2.1.2: se agregó KEY idx_devoluciones_fecha (fecha).
-- -----------------------------------------------------------------------------
-- Volcando estructura para tabla multiservicio_2.0.table_devoluciones
CREATE TABLE IF NOT EXISTS `table_devoluciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `monto_devuelto` decimal(15,2) DEFAULT NULL,
  `destino` enum('STOCK','DANADO') DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `dias_garantia_aplicado` int(11) DEFAULT NULL,
  `dias_transcurridos` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `factura_id` (`factura_id`),
  KEY `producto_id` (`producto_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_devoluciones_fecha` (`fecha`),
  CONSTRAINT `table_devoluciones_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `table_facturas` (`id`),
  CONSTRAINT `table_devoluciones_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`),
  CONSTRAINT `table_devoluciones_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_devoluciones: ~0 rows (aproximadamente)
DELETE FROM `table_devoluciones`;

-- Volcando estructura para tabla multiservicio_2.0.table_email_templates
CREATE TABLE IF NOT EXISTS `table_email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('FACTURA','PRESUPUESTO','NOTIFICACION','RECUPERACION','ORDEN_SERVICIO','PEDIDO_CATALOGO','RESUMEN_MENSUAL','ALERTA_PROVEEDOR','GARANTIA','OTRO') NOT NULL DEFAULT 'OTRO',
  `asunto` varchar(255) NOT NULL,
  `cuerpo_html` longtext NOT NULL,
  `variables_disponibles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON con variables usables en la plantilla' CHECK (json_valid(`variables_disponibles`)),
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tipo` (`tipo`),
  KEY `activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_email_templates: ~0 rows (aproximadamente)
DELETE FROM `table_email_templates`;

-- Volcando estructura para tabla multiservicio_2.0.table_emails
CREATE TABLE IF NOT EXISTS `table_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('FACTURA','PRESUPUESTO','NOTIFICACION','RECUPERACION','ORDEN_SERVICIO','PEDIDO_CATALOGO','RESUMEN_MENSUAL','ALERTA_PROVEEDOR','GARANTIA','OTRO') NOT NULL DEFAULT 'OTRO',
  `destinatario_email` varchar(150) NOT NULL,
  `destinatario_nombre` varchar(150) DEFAULT NULL,
  `asunto` varchar(255) NOT NULL,
  `cuerpo_html` longtext NOT NULL,
  `cuerpo_texto` longtext DEFAULT NULL,
  `adjuntos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array JSON de rutas de archivos adjuntos' CHECK (json_valid(`adjuntos`)),
  `referencia_tipo` enum('FACTURA','PRESUPUESTO','ORDEN','CLIENTE','PEDIDO_CATALOGO','PROVEEDOR','GARANTIA','NINGUNO') DEFAULT 'NINGUNO',
  `referencia_id` int(11) DEFAULT NULL,
  `estado` enum('ENVIADO','FALLIDO','PENDIENTE') NOT NULL DEFAULT 'PENDIENTE',
  `error_mensaje` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL COMMENT 'Usuario que envió el email',
  `fecha_envio` timestamp NULL DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `tipo` (`tipo`),
  KEY `estado` (`estado`),
  KEY `fecha_creacion` (`fecha_creacion`),
  KEY `referencia_tipo` (`referencia_tipo`,`referencia_id`),
  CONSTRAINT `table_emails_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_emails: ~0 rows (aproximadamente)
DELETE FROM `table_emails`;

-- -----------------------------------------------------------------------------
-- CAMBIO v2.0.2: se agregó `presupuesto_activo_id` y su índice.
-- CAMBIO v2.0.3: se agregó 'PRESUPUESTO' al ENUM de `origen`.
-- CAMBIO v2.1.0: se agregó `estado_gestion` (ENUM) y su índice.
-- CAMBIO v2.1.2: se agregó KEY idx_facturas_status (status).
-- -----------------------------------------------------------------------------
-- Volcando estructura para tabla multiservicio_2.0.table_facturas
CREATE TABLE IF NOT EXISTS `table_facturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orden_id` int(11) DEFAULT NULL,
  `presupuesto_activo_id` int(11) DEFAULT NULL,
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
  `estado_gestion` enum('NUEVO','GESTIONADO','PROMETIDO','ACUERDO_PAGO','JUDICIAL') DEFAULT 'NUEVO' COMMENT 'Estado de gestión de cobranza para el semáforo de cartera',
  `origen` enum('MOSTRADOR','CATALOGO','TALLER','GARANTIA','PRESUPUESTO') DEFAULT 'MOSTRADOR',
  `observaciones` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  KEY `presupuesto_activo_id` (`presupuesto_activo_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_estado_gestion` (`estado_gestion`),
  KEY `idx_facturas_status` (`status`),
  CONSTRAINT `table_facturas_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `table_ordenes_servicio` (`id`),
  CONSTRAINT `table_facturas_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes` (`id`),
  CONSTRAINT `table_facturas_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_facturas: ~0 rows (aproximadamente)
DELETE FROM `table_facturas`;

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
  CONSTRAINT `table_facturas_detalle_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `table_facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_facturas_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`),
  CONSTRAINT `table_facturas_detalle_ibfk_3` FOREIGN KEY (`mecanico_id`) REFERENCES `table_staff` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_facturas_detalle: ~0 rows (aproximadamente)
DELETE FROM `table_facturas_detalle`;

-- Volcando estructura para tabla multiservicio_2.0.table_garantias
CREATE TABLE IF NOT EXISTS `table_garantias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_original_id` int(11) NOT NULL COMMENT 'Factura que se anula',
  `factura_garantia_id` int(11) DEFAULT NULL COMMENT 'Nueva factura de garantía generada',
  `cliente_id` varchar(50) DEFAULT NULL COMMENT 'Referencia a table_clientes.id (varchar)',
  `placa` varchar(20) DEFAULT NULL,
  `marca_vehiculo` varchar(50) DEFAULT NULL,
  `modelo_vehiculo` varchar(100) DEFAULT NULL,
  `tipo_garantia` enum('SERVICIO','REPUESTO','MIXTO') NOT NULL DEFAULT 'SERVICIO',
  `motivo` varchar(255) NOT NULL COMMENT 'Razón de la garantía (mayúsculas)',
  `monto_mano_obra` decimal(15,2) DEFAULT 0.00,
  `monto_repuesto` decimal(15,2) DEFAULT 0.00,
  `monto_total` decimal(15,2) DEFAULT 0.00,
  `destino_repuesto` enum('STOCK','DANADO','N/A') NOT NULL DEFAULT 'N/A',
  `dias_garantia_servicio` int(11) DEFAULT NULL,
  `dias_garantia_repuesto` int(11) DEFAULT NULL,
  `dias_transcurridos` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `factura_garantia_id` (`factura_garantia_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `factura_original_id` (`factura_original_id`),
  KEY `tipo_garantia` (`tipo_garantia`),
  KEY `fecha` (`fecha`),
  CONSTRAINT `table_garantias_ibfk_1` FOREIGN KEY (`factura_original_id`) REFERENCES `table_facturas` (`id`),
  CONSTRAINT `table_garantias_ibfk_2` FOREIGN KEY (`factura_garantia_id`) REFERENCES `table_facturas` (`id`),
  CONSTRAINT `table_garantias_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes` (`id`),
  CONSTRAINT `table_garantias_ibfk_4` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_garantias: ~0 rows (aproximadamente)
DELETE FROM `table_garantias`;

-- Volcando estructura para tabla multiservicio_2.0.table_garantias_detalle
CREATE TABLE IF NOT EXISTS `table_garantias_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `garantia_id` int(11) NOT NULL,
  `factura_detalle_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(15,2) NOT NULL DEFAULT 0.00,
  `monto_base` decimal(15,2) NOT NULL DEFAULT 0.00,
  `monto_iva` decimal(15,2) NOT NULL DEFAULT 0.00,
  `monto_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tipo_item` enum('SERVICIO','REPUESTO') NOT NULL DEFAULT 'SERVICIO',
  `accion` enum('DEVOLVER','AUMENTAR','REEMPLAZAR') NOT NULL DEFAULT 'DEVOLVER',
  `destino` enum('STOCK','DANADO','N/A') NOT NULL DEFAULT 'N/A',
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  KEY `garantia_id` (`garantia_id`),
  KEY `tipo_item` (`tipo_item`),
  CONSTRAINT `table_garantias_detalle_ibfk_1` FOREIGN KEY (`garantia_id`) REFERENCES `table_garantias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_garantias_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_garantias_detalle: ~0 rows (aproximadamente)
DELETE FROM `table_garantias_detalle`;

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

-- Volcando datos para la tabla multiservicio_2.0.table_gastos: ~0 rows (aproximadamente)
DELETE FROM `table_gastos`;

-- Volcando estructura para tabla multiservicio_2.0.table_inventario
CREATE TABLE IF NOT EXISTS `table_inventario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 5,
  `ultimo_costo` decimal(15,2) DEFAULT 0.00,
  `costo_promedio` decimal(15,2) DEFAULT 0.00,
  `precio` decimal(15,2) NOT NULL DEFAULT 0.00,
  `imagen` varchar(255) DEFAULT NULL,
  `dias_garantia` int(11) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
  `oferta_activa` tinyint(1) DEFAULT 0,
  `oferta_porcentaje` decimal(5,2) DEFAULT 0.00,
  `oferta_fecha_inicio` date DEFAULT NULL,
  `oferta_fecha_fin` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo_marca` (`codigo`,`marca`),
  KEY `nombre` (`nombre`),
  KEY `categoria` (`categoria`),
  KEY `oferta_activa` (`oferta_activa`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_inventario: ~11 rows (aproximadamente)
DELETE FROM `table_inventario`;
INSERT INTO `table_inventario` (`id`, `codigo`, `nombre`, `marca`, `descripcion`, `categoria`, `stock`, `stock_minimo`, `ultimo_costo`, `costo_promedio`, `precio`, `imagen`, `dias_garantia`, `estado`, `oferta_activa`, `oferta_porcentaje`, `oferta_fecha_inicio`, `oferta_fecha_fin`) VALUES
	(1, 'FUS-1', 'FUSIBLES STANDART', 'GENERICO', 'FUSIBLES STARNDRT UNVERSALES DE DIFERENTES AMPERAJES', 'REPUESTOS', 94, 20, 0.00, 0.00, 1.00, 'uploads/inventario/prod_1791372836_814cd4f1.jpg', NULL, 'ACTIVO', 0, 0.00, NULL, NULL),
	(2, 'FUS-2', 'FUSIBLES PEQUEÑOS', 'GENERICO', 'FUSIBLES PEQUEÑOS DE DIFERENTES AMPERAJES', 'REPUESTOS', 100, 20, 0.00, 0.00, 1.50, 'uploads/inventario/prod_1791372889_189803d5.jpg', NULL, 'ACTIVO', 0, 0.00, NULL, NULL),
	(3, 'FUS-4', 'FUSIBLES CUADRADOS', 'GENERICO', 'FUSIBLES CUADRADOS D EDIFERENTES CALIBRES', 'REPUESTOS', 100, 20, 0.00, 0.00, 2.00, 'uploads/inventario/prod_1791372937_321430c6.jpg', NULL, 'ACTIVO', 0, 0.00, NULL, NULL),
	(4, 'FUS-3', 'FUSIBLES GRANDES', 'GENERICO', 'FUSIBLES DE DIFERENTES AMPERAJES GRANDES PROTEJE TU VEHICULO', 'REPUESTOS', 80, 20, 0.00, 0.00, 2.50, 'uploads/inventario/prod_1791372988_f86084d1.jpg', NULL, 'ACTIVO', 0, 0.00, NULL, NULL),
	(5, 'BAT-750', 'BATERIA 750 AMP', 'DUNCAN', 'BATERIA LBRE MANTENIMIENTO PARA EL USO DIARIO', 'ELECTRICIDAD', 9, 5, 0.00, 0.00, 110.00, 'uploads/inventario/prod_1791373643_135abf18.jpg', NULL, 'ACTIVO', 0, 0.00, NULL, NULL),
	(6, 'BAT-1000', 'BATERIA 1000AMP', 'DUNCAN', 'LA MEJOR BATERIA PARA CARGA DURA DIARIA', 'ELECTRICIDAD', 7, 5, 0.00, 0.00, 115.00, 'uploads/inventario/prod_1791373599_98da5d9f.jpg', NULL, 'ACTIVO', 0, 0.00, NULL, NULL),
	(7, 'ACE-SEMI', 'ACEITE 15W/40 SEMI SINTETICO', 'INCA', 'ACEITE DE EXCELENTE CALIDAD SEMI SINTETICO PARA EL CUIDADO DE TU MOTOR', 'LUBRICANTES', 24, 5, 0.00, 0.00, 12.00, 'uploads/inventario/prod_1791373444_0d099b15.jpg', NULL, 'ACTIVO', 0, 0.00, NULL, NULL),
	(8, 'ACE-SEMI', 'ACEITE SEMI SINTETICO 15W/40', 'SKY', 'ACEITE DE LATO RENDIMIENTO SEMI SINTETICO PARA TU MOTOR LO MEJOR', 'MECANICA', 24, 5, 0.00, 0.00, 12.00, 'uploads/inventario/prod_1791373490_a176aef3.jpg', NULL, 'ACTIVO', 0, 0.00, NULL, NULL),
	(9, 'ACE-SEMI', 'ACEITE SE MI SINTETICO15W/40', 'VALVOLINE', 'ACEITE DE LTO FLUJO SEMI SINTETICO', 'LUBRICANTES', 48, 5, 0.00, 0.00, 12.00, 'uploads/inventario/prod_1791373563_ded9e5dc.jpg', NULL, 'ACTIVO', 0, 0.00, NULL, NULL),
	(10, 'LED-H4', 'BOMBILLO H4 LED 30000 LUMENES', 'HAMMET', 'MEJOR ILUMNACION PARA LAS NOCHES OSCURAS LED DE 30000 LUMENES', 'ELECTRICIDAD', 6, 5, 0.00, 0.00, 25.00, 'uploads/inventario/prod_1791373694_ffcd8568.jpg', NULL, 'ACTIVO', 0, 0.00, NULL, NULL),
	(11, 'STR-CHEV', 'STATOR DELCO CHEVROLET', 'DELCO', 'ESTATOR DELCO CHEVROLET 600AMP', 'MECANICA', 10, 4, 0.00, 0.00, 80.00, 'uploads/inventario/prod_1791373762_1cbdacb0.jpg', NULL, 'ACTIVO', 0, 0.00, NULL, NULL);

-- -----------------------------------------------------------------------------
-- v2.1.2: se agregó KEY idx_kardex_fecha (fecha).
-- -----------------------------------------------------------------------------
-- Volcando estructura para tabla multiservicio_2.0.table_kardex
CREATE TABLE IF NOT EXISTS `table_kardex` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('ENTRADA_COMPRA','SALIDA_VENTA','AJUSTE_MANUAL','DEVOLUCION','GARANTIA') DEFAULT NULL,
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
  KEY `idx_kardex_fecha` (`fecha`),
  CONSTRAINT `table_kardex_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_kardex_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_kardex: ~0 rows (aproximadamente)
DELETE FROM `table_kardex`;

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

-- Volcando datos para la tabla multiservicio_2.0.table_orden_checklist: ~0 rows (aproximadamente)
DELETE FROM `table_orden_checklist`;

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

-- Volcando datos para la tabla multiservicio_2.0.table_orden_estados_log: ~0 rows (aproximadamente)
DELETE FROM `table_orden_estados_log`;

-- Volcando estructura para tabla multiservicio_2.0.table_orden_servicios
CREATE TABLE IF NOT EXISTS `table_orden_servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orden_id` int(11) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `estado` enum('PENDIENTE','EN_PROCESO','COMPLETADO','CANCELADO') DEFAULT 'PENDIENTE',
  `orden_visual` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  KEY `estado` (`estado`),
  CONSTRAINT `table_orden_servicios_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `table_ordenes_servicio` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_orden_servicios: ~0 rows (aproximadamente)
DELETE FROM `table_orden_servicios`;

-- Volcando estructura para tabla multiservicio_2.0.table_ordenes_servicio
CREATE TABLE IF NOT EXISTS `table_ordenes_servicio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` varchar(50) DEFAULT NULL,
  `placa` varchar(20) NOT NULL,
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

-- Volcando datos para la tabla multiservicio_2.0.table_ordenes_servicio: ~0 rows (aproximadamente)
DELETE FROM `table_ordenes_servicio`;

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

-- Volcando datos para la tabla multiservicio_2.0.table_pagos_empleados: ~0 rows (aproximadamente)
DELETE FROM `table_pagos_empleados`;

-- Volcando estructura para tabla multiservicio_2.0.table_presupuestos
CREATE TABLE IF NOT EXISTS `table_presupuestos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(20) NOT NULL COMMENT 'Formato: PRES-YYYY-XXXX',
  `cliente_id` varchar(50) DEFAULT NULL,
  `cliente_nombre` varchar(150) NOT NULL,
  `cliente_cedula` varchar(20) DEFAULT NULL,
  `cliente_telefono` varchar(20) DEFAULT NULL,
  `cliente_email` varchar(150) DEFAULT NULL,
  `cliente_direccion` text DEFAULT NULL,
  `vehiculo_placa` varchar(20) DEFAULT NULL,
  `vehiculo_marca` varchar(50) DEFAULT NULL,
  `vehiculo_modelo` varchar(100) DEFAULT NULL,
  `vehiculo_anio` int(4) DEFAULT NULL,
  `vehiculo_color` varchar(30) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `iva_monto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `iva_activo` tinyint(1) DEFAULT 1,
  `tasa_iva` decimal(5,2) DEFAULT 19.00,
  `estado` enum('BORRADOR','ENVIADO','ACTIVO','EN_PROCESO','ANEXADO','ACEPTADO','RECHAZADO','EXPIRADO','CONVERTIDO') DEFAULT 'BORRADOR',
  `validez_dias` int(11) DEFAULT 30,
  `fecha_emision` date NOT NULL DEFAULT curdate(),
  `fecha_vencimiento` date DEFAULT NULL,
  `fecha_activacion` datetime DEFAULT NULL,
  `usuario_activacion_id` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `condiciones` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero` (`numero`),
  KEY `usuario_id` (`usuario_id`),
  KEY `usuario_activacion_id` (`usuario_activacion_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `estado` (`estado`),
  KEY `fecha_emision` (`fecha_emision`),
  KEY `fecha_vencimiento` (`fecha_vencimiento`),
  CONSTRAINT `table_presupuestos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes` (`id`),
  CONSTRAINT `table_presupuestos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`),
  CONSTRAINT `table_presupuestos_ibfk_3` FOREIGN KEY (`usuario_activacion_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_presupuestos: ~0 rows (aproximadamente)
DELETE FROM `table_presupuestos`;

-- Volcando estructura para tabla multiservicio_2.0.table_presupuestos_detalle
CREATE TABLE IF NOT EXISTS `table_presupuestos_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `presupuesto_id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `tipo_item` enum('PRODUCTO','SERVICIO') NOT NULL DEFAULT 'PRODUCTO',
  `descripcion` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(15,2) NOT NULL DEFAULT 0.00,
  `descuento_porcentaje` decimal(5,2) DEFAULT 0.00,
  `descuento_monto` decimal(15,2) DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `iva_porcentaje` decimal(5,2) DEFAULT 0.00,
  `iva_monto` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `orden_visual` int(11) DEFAULT 0,
  `notas` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `presupuesto_id` (`presupuesto_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `table_presupuestos_detalle_ibfk_1` FOREIGN KEY (`presupuesto_id`) REFERENCES `table_presupuestos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_presupuestos_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_presupuestos_detalle: ~0 rows (aproximadamente)
DELETE FROM `table_presupuestos_detalle`;

-- Volcando estructura para tabla multiservicio_2.0.table_presupuestos_reservas
CREATE TABLE IF NOT EXISTS `table_presupuestos_reservas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `presupuesto_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad_reservada` int(11) NOT NULL DEFAULT 0,
  `cantidad_liberada` int(11) DEFAULT 0,
  `estado` enum('RESERVADA','LIBERADA','FACTURADA') DEFAULT 'RESERVADA',
  `fecha_reserva` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_liberacion` datetime DEFAULT NULL,
  `usuario_liberacion_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_liberacion_id` (`usuario_liberacion_id`),
  KEY `presupuesto_id` (`presupuesto_id`),
  KEY `producto_id` (`producto_id`),
  KEY `estado` (`estado`),
  CONSTRAINT `table_presupuestos_reservas_ibfk_1` FOREIGN KEY (`presupuesto_id`) REFERENCES `table_presupuestos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_presupuestos_reservas_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`),
  CONSTRAINT `table_presupuestos_reservas_ibfk_3` FOREIGN KEY (`usuario_liberacion_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_presupuestos_reservas: ~0 rows (aproximadamente)
DELETE FROM `table_presupuestos_reservas`;

-- =============================================================================
-- ALTER: FK de table_facturas.presupuesto_activo_id → table_presupuestos
-- =============================================================================
ALTER TABLE `table_facturas`
  ADD CONSTRAINT `table_facturas_ibfk_4`
  FOREIGN KEY (`presupuesto_activo_id`) REFERENCES `table_presupuestos` (`id`) ON DELETE SET NULL;

-- Volcando estructura para tabla multiservicio_2.0.table_proveedores
CREATE TABLE IF NOT EXISTS `table_proveedores` (
  `id` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_proveedores: ~3 rows (aproximadamente)
DELETE FROM `table_proveedores`;
INSERT INTO `table_proveedores` (`id`, `nombre`, `telefono`, `email`, `direccion`) VALUES
	('J-3245125-7', 'LUBRICANTES DEL CENTRO', '0412512545', 'lubricantesdelcentro@gmail.com', 'AV INTERCOMUNAL SECTOR SABANETA'),
	('J-70254125-8', 'BATERIAS JUAN', '04142125125', 'bateriasjuan@hotmail.com', 'AV INTERCOMUNAL SECTOR LAS TAPIAS'),
	('J10452122-5', 'MULTISERVICIO LA 13', '04125212563', 'multiserviciola13@gmail.com', 'CALLE 13 CON AV 3 SAN FELIPE');

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

-- Volcando datos para la tabla multiservicio_2.0.table_recuperaciones: ~0 rows (aproximadamente)
DELETE FROM `table_recuperaciones`;

-- Volcando estructura para tabla multiservicio_2.0.table_roles
CREATE TABLE IF NOT EXISTS `table_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_roles: ~3 rows (aproximadamente)
DELETE FROM `table_roles`;
INSERT INTO `table_roles` (`id`, `nombre_rol`, `descripcion`) VALUES
	(1, 'ADMINISTRADOR', 'CONTROL TOTAL DEL SISTEMA'),
	(2, 'MECANICO', 'GESTION DE ORDENES Y TRABAJOS'),
	(3, 'CAJERO', 'GESTION DE FACTURACION Y CAJA');

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

-- Volcando datos para la tabla multiservicio_2.0.table_staff: ~4 rows (aproximadamente)
DELETE FROM `table_staff`;
INSERT INTO `table_staff` (`id`, `cedula`, `nombre`, `cargo`, `telefono`, `email`, `direccion`, `foto`, `foto_frente`, `estado`, `fecha_creacion`) VALUES
	('MEC-001', '11279254', 'CARLOS ALBERTO', 'MECANICO', '04121542155', 'carlos@gmail.com', 'URB LAS TAPIAS', 'img/default.png', 'img/default.png', 'ACTIVO', '2026-10-07 11:30:52'),
	('MEC-002', '10965236', 'PEDRO JOSE LINAREZ', 'MECANICO', '041451212563', 'pedrojose@gmail.com', 'LA PADERA', 'img/default.png', 'img/default.png', 'ACTIVO', '2026-10-07 11:31:30'),
	('STAFF-001', 'V-00000000', 'ADMINISTRADOR', 'ADMINISTRADOR', NULL, NULL, NULL, 'img/default.png', 'img/default.png', 'ACTIVO', '2026-10-07 11:26:03'),
	('STAFF-002', '14607920', 'WILLIAM ENRIQUE INFANTE', 'ADMINISTRADOR', '04125181629', 'william21enrique@gmail.com', 'URB VISTA ALEGRE CALLE 2 CASA 2', 'img/default.png', 'img/default.png', 'ACTIVO', '2026-10-07 11:29:22');

-- Volcando estructura para tabla multiservicio_2.0.table_transacciones
CREATE TABLE IF NOT EXISTS `table_transacciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cuenta_id` int(11) DEFAULT NULL,
  `tipo` enum('INGRESO','EGRESO') NOT NULL,
  `categoria` enum('VENTA','GASTO','NOMINA','COMPRA_PROVEEDOR','ABONO_CLIENTE','ABONO_PROVEEDOR','DEVOLUCION','GARANTIA') NOT NULL,
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

-- Volcando datos para la tabla multiservicio_2.0.table_transacciones: ~0 rows (aproximadamente)
DELETE FROM `table_transacciones`;

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

-- Volcando datos para la tabla multiservicio_2.0.table_usuario_sessions: ~0 rows (aproximadamente)
DELETE FROM `table_usuario_sessions`;

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
  CONSTRAINT `table_usuarios_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `table_staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_usuarios_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `table_roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla multiservicio_2.0.table_usuarios: ~2 rows (aproximadamente)
DELETE FROM `table_usuarios`;
INSERT INTO `table_usuarios` (`id`, `staff_id`, `username`, `password`, `role_id`, `estado`, `fecha_registro`) VALUES
	(1, 'STAFF-001', 'admin', '$2y$10$rRwmfLEGjGKnq13Yr6JYb.o.ZCemuwaWhtskpvb8TO77ZPlj1lTWO', 1, 'ACTIVO', '2026-10-07 11:26:03'),
	(2, 'STAFF-002', 'WILL', '$2y$10$NYX6F5ObBfaGNK714UHNHOMXg/JCdLYxm7hO8Grn3X7H2tg.yQina', 1, 'ACTIVO', '2026-10-07 11:29:22');

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

-- Volcando datos para la tabla multiservicio_2.0.table_vehiculos: ~7 rows (aproximadamente)
DELETE FROM `table_vehiculos`;
INSERT INTO `table_vehiculos` (`placa`, `cliente_id`, `marca`, `modelo`, `anio`, `color`) VALUES
	('BBG04P', '15769775', 'CHEVROLET', 'CORSA', 2004, 'AMARILLO'),
	('F22RT', '10332211', 'TOYOTA', 'SUPRA', 2000, 'ROJO'),
	('FDC7YI', '19323150', 'TOYOTA', 'STARLET', 2000, 'AZUL'),
	('RAB303R', '13302132', 'TOYOTA', 'YARIS', 2010, 'BLANCO'),
	('RAB30ET', '18303158', 'CHEVROLET', 'CORSA', 2002, 'AZUL'),
	('RAB56TR', '30303158', 'CHEVROLET', 'AVEO', 2006, 'BALNCO'),
	('YBT56T', '17303158', 'TOYOTA', 'YARIS', 2012, 'ROJO');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;