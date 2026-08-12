-- =============================================================================
-- ESQUEMA DE BASE DE DATOS MULTISERVICIO V2.0 "TALLER PRO"
-- =============================================================================
-- Diseñado para:
-- 1. Separación Técnica (Órdenes) vs Contable (Facturas).
-- 2. Libro Mayor Centralizado (Ledger) para Flujo de Caja instantáneo.
-- 3. Atribución Dual (Quién hizo el trabajo vs Quién cobró).
-- 4. Valoración por Costo Promedio Ponderado (CPP).
-- 5. Compatibilidad Cross-Platform (Nombres en Snake Case).
-- 6. Módulo de Garantías (anula factura original y genera factura de garantía).
-- =============================================================================
-- SEMILLA ÚNICA: 1 usuario ADMINISTRADOR (admin / 123) para arrancar el sistema.
-- =============================================================================

/*CREATE DATABASE IF NOT EXISTS `multiservicio_2.0`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE `multiservicio_2.0`;
*/
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

-- =============================================================================
-- BLOQUE 1: IDENTIDAD Y SEGURIDAD
-- =============================================================================

-- Roles de usuario (ADMINISTRADOR, MECANICO, CAJERO)
CREATE TABLE `table_roles` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Maestro de personal (Datos reales de los empleados)
CREATE TABLE `table_staff` (
  `id` varchar(50) PRIMARY KEY, -- Ej: STAFF-001
  `cedula` varchar(20) UNIQUE NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cargo` varchar(50),
  `telefono` varchar(20),
  `email` varchar(100),
  `direccion` text,
  `foto` varchar(255) DEFAULT 'img/default.png',
  `foto_frente` varchar(255) DEFAULT 'img/default.png',
  `estado` enum('ACTIVO', 'INACTIVO') DEFAULT 'ACTIVO',
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Cuentas de acceso al sistema
CREATE TABLE `table_usuarios` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `staff_id` varchar(50),
  `username` varchar(50) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11),
  `estado` enum('ACTIVO', 'INACTIVO') DEFAULT 'ACTIVO',
  `fecha_registro` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`staff_id`) REFERENCES `table_staff`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `table_roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Control de sesiones activas (Single Session)
CREATE TABLE `table_usuario_sessions` (
  `usuario_id` int(11) PRIMARY KEY,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45),
  `usuario_agent` text,
  `last_activity` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 2: ENTIDADES MAESTRAS
-- =============================================================================

-- Configuración global de la empresa e impuestos
CREATE TABLE `table_company_settings` (
  `id` int(11) PRIMARY KEY DEFAULT 1,
  `name` varchar(100) NOT NULL,
  `nit` varchar(50),
  `iva` decimal(5,2) DEFAULT 19.00,
  `direccion` text,
  `telefono` varchar(50),
  `logo` varchar(255),
  `dias_garantia_devolucion` int(11) DEFAULT 5, -- Días globales de garantía para devoluciones de repuestos
  `dias_garantia_servicio` int(11) NOT NULL DEFAULT 15 -- Días de garantía por mano de obra (excepto lavados)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Maestro de clientes
CREATE TABLE `table_clientes` (
  `id` varchar(50) PRIMARY KEY, -- Cédula o NIT
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20),
  `email` varchar(100),
  `direccion` text,
  `fecha_registro` timestamp DEFAULT CURRENT_TIMESTAMP,
  INDEX (`nombre`),
  INDEX (`telefono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Maestro de vehículos vinculados a clientes
CREATE TABLE `table_vehiculos` (
  `placa` varchar(20) PRIMARY KEY,
  `cliente_id` varchar(50),
  `marca` varchar(50),
  `modelo` varchar(50),
  `anio` int(4),
  `color` varchar(30),
  FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Maestro de proveedores
CREATE TABLE `table_proveedores` (
  `id` varchar(50) PRIMARY KEY,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20),
  `email` varchar(100),
  `direccion` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 3: INVENTARIO Y COSTEO (CPP)
-- =============================================================================

-- Productos y Servicios
CREATE TABLE `table_inventario` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 5,
  `ultimo_costo` decimal(15,2) DEFAULT 0.00,
  `costo_promedio` decimal(15,2) DEFAULT 0.00, -- Pilar para rentabilidad real
  `precio` decimal(15,2) NOT NULL DEFAULT 0.00,
  `imagen` varchar(255) DEFAULT NULL,
  `dias_garantia` int(11) DEFAULT NULL, -- Días de garantía específicos para este repuesto (NULL = usar global)
  `estado` enum('ACTIVO', 'INACTIVO') DEFAULT 'ACTIVO',
  INDEX (`nombre`),
  INDEX (`categoria`),
  UNIQUE KEY `uk_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Historial de movimientos de stock (Kardex)
CREATE TABLE `table_kardex` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `producto_id` int(11),
  `tipo_movimiento` enum('ENTRADA_COMPRA', 'SALIDA_VENTA', 'AJUSTE_MANUAL', 'DEVOLUCION', 'GARANTIA'),
  `cantidad` int(11) NOT NULL,
  `stock_anterior` int(11) NOT NULL,
  `stock_actual` int(11) NOT NULL,
  `referencia_id` varchar(50), -- ID de Factura o Compra
  `usuario_id` int(11), -- Quién realizó el movimiento
  `observacion` text DEFAULT NULL,
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`producto_id`) REFERENCES `table_inventario`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 4: OPERACIONES DEL TALLER (TÉCNICO)
-- =============================================================================

-- Hoja de vida técnica del vehículo (No es factura aún)
CREATE TABLE `table_ordenes_servicio` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `cliente_id` varchar(50),
  `placa` varchar(20) NOT NULL,
  `mecanico_id` varchar(50), -- Técnico asignado
  `kilometraje` varchar(20),
  `nivel_combustible` varchar(20),
  `diagnostico_entrada` text,
  `diagnostico_salida` text DEFAULT NULL,
  `observaciones` text,
  `estado` enum('RECIBIDO', 'DIAGNOSTICANDO', 'EN_REPARACION', 'LISTO', 'ENTREGADO', 'CANCELADO') DEFAULT 'RECIBIDO',
  `fecha_ingreso` timestamp DEFAULT CURRENT_TIMESTAMP,
  `fecha_entrega_estimada` datetime DEFAULT NULL,
  `fecha_entrega_real` datetime DEFAULT NULL,
  FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes`(`id`),
  FOREIGN KEY (`placa`) REFERENCES `table_vehiculos`(`placa`),
  FOREIGN KEY (`mecanico_id`) REFERENCES `table_staff`(`id`),
  INDEX (`placa`),
  INDEX (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Checklist de Entrada (Accesorios y estado del vehículo)
CREATE TABLE `table_orden_checklist` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `orden_id` int(11) NOT NULL,
  `item` varchar(100) NOT NULL, -- Ej: Llaves, Gato, Herramientas
  `estado` tinyint(1) DEFAULT 0,
  `observacion` varchar(255) DEFAULT NULL,
  FOREIGN KEY (`orden_id`) REFERENCES `table_ordenes_servicio` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Historial de estados de la orden
CREATE TABLE `table_orden_estados_log` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `orden_id` int(11) NOT NULL,
  `estado_anterior` varchar(50),
  `estado_nuevo` varchar(50) NOT NULL,
  `usuario_id` int(11),
  `comentario` text,
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`orden_id`) REFERENCES `table_ordenes_servicio`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 5: FINANZAS Y FACTURACIÓN (CONTABLE)
-- =============================================================================

-- Catálogo de bancos y cajas
CREATE TABLE `table_cuentas_pago` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL, -- Ej: Caja Efectivo, Nequi, Bancolombia
  `tipo` enum('EFECTIVO', 'BANCO', 'VIRTUAL') DEFAULT 'EFECTIVO',
  `saldo_actual` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Registro contable de ventas
CREATE TABLE `table_facturas` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `orden_id` int(11) NULL, -- Referencia a OS si aplica
  `cliente_id` varchar(50),
  `placa` varchar(20) DEFAULT NULL, -- Para ventas de mostrador sin O.S.
  `modelo_vehiculo` varchar(100) DEFAULT NULL,
  `usuario_id` int(11), -- El que cobró (Administrador)
  `subtotal` decimal(15,2) NOT NULL,
  `iva_monto` decimal(15,2) DEFAULT 0,
  `total` decimal(15,2) NOT NULL,
  `pago_efectivo` decimal(15,2) DEFAULT 0,
  `pago_transferencia` decimal(15,2) DEFAULT 0,
  `saldo_pendiente` decimal(15,2) DEFAULT 0,
  `status` enum('COMPLETADO', 'CREDITO', 'ANULADO', 'PENDIENTE') DEFAULT 'COMPLETADO',
  `origen` enum('MOSTRADOR','CATALOGO','TALLER','GARANTIA') DEFAULT 'MOSTRADOR',
  `observaciones` text DEFAULT NULL,
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`orden_id`) REFERENCES `table_ordenes_servicio`(`id`),
  FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Detalle granular con atribución al mecánico por cada ítem
CREATE TABLE `table_facturas_detalle` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `factura_id` int(11),
  `producto_id` int(11) NULL,
  `mecanico_id` varchar(50), -- Quién ejecutó este trabajo específico
  `descripcion` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(15,2) NOT NULL,
  `costo_unitario` decimal(15,2) NOT NULL, -- "Congela" el CPP al vender
  `pago_nomina_id` int(11) NULL, -- Enlace para liquidación
  FOREIGN KEY (`factura_id`) REFERENCES `table_facturas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `table_inventario`(`id`),
  FOREIGN KEY (`mecanico_id`) REFERENCES `table_staff`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Registro histórico de abonos a deudas de clientes
CREATE TABLE `table_abonos_clientes` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `factura_id` int(11),
  `monto` decimal(15,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO', 'TRANSFERENCIA') NOT NULL,
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`factura_id`) REFERENCES `table_facturas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 6: COMPRAS Y EGRESOS
-- =============================================================================

-- Cabecera de compra a proveedores
CREATE TABLE `table_compras` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `proveedor_id` varchar(50),
  `total` decimal(15,2) NOT NULL,
  `pagado` decimal(15,2) DEFAULT 0,
  `fecha_vencimiento` date,
  `status` enum('PENDIENTE', 'PAGADO', 'ANULADO') DEFAULT 'PENDIENTE',
  `usuario_id` int(11),
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`proveedor_id`) REFERENCES `table_proveedores`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Detalle de compra para actualización de stock y CPP
CREATE TABLE `table_compras_detalle` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `compra_id` int(11),
  `producto_id` int(11),
  `descripcion` varchar(255),
  `cantidad` int(11) NOT NULL,
  `costo_unitario` decimal(15,2) NOT NULL,
  FOREIGN KEY (`compra_id`) REFERENCES `table_compras`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `table_inventario`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Historial de abonos realizados a proveedores
CREATE TABLE `table_abonos_proveedores` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `compra_id` int(11) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO', 'TRANSFERENCIA') DEFAULT 'EFECTIVO',
  `usuario_id` int(11),
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`compra_id`) REFERENCES `table_compras`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 7: EL LIBRO MAYOR (TRANSACCIONES CENTRALIZADAS)
-- =============================================================================

-- Origen único para reportes de flujo de caja
CREATE TABLE `table_transacciones` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `cuenta_id` int(11), -- Caja o banco específico
  `tipo` enum('INGRESO', 'EGRESO') NOT NULL,
  `categoria` enum('VENTA', 'GASTO', 'NOMINA', 'COMPRA_PROVEEDOR', 'ABONO_CLIENTE', 'ABONO_PROVEEDOR', 'DEVOLUCION', 'GARANTIA') NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `referencia_id` int(11), -- ID del documento origen
  `descripcion` varchar(255),
  `usuario_id` int(11), -- El cajero que registró
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cuenta_id`) REFERENCES `table_cuentas_pago`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`),
  INDEX (`categoria`),
  INDEX (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Gastos fijos y variables del taller
CREATE TABLE `table_gastos` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `categoria` varchar(50),
  `descripcion` varchar(255),
  `monto` decimal(15,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT 'EFECTIVO',
  `usuario_id` int(11),
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Registro de pagos a empleados
CREATE TABLE `table_pagos_empleados` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `staff_id` varchar(50),
  `monto` decimal(15,2) NOT NULL,
  `monto_base` decimal(15,2), -- Base de cálculo
  `tipo` enum('ADELANTO', 'PAGO_NOMINA') DEFAULT 'PAGO_NOMINA',
  `metodo_pago` varchar(50),
  `modo_calculo` varchar(30) DEFAULT 'FIJO',
  `factor_calculo` decimal(15,2) DEFAULT 0.00,
  `notas` text,
  `usuario_id` int(11),
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`staff_id`) REFERENCES `table_staff`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 8: AUDITORÍA Y SISTEMA
-- =============================================================================

-- Bitácora de acciones de seguridad
CREATE TABLE `table_audit_logs` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `usuario_id` int(11),
  `modulo` varchar(50),
  `accion` varchar(50),
  `descripcion` text,
  `ip_address` varchar(45),
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Solicitudes de recuperación de clave
CREATE TABLE `table_recuperaciones` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `usuario_id` int(11),
  `tipo` varchar(50) DEFAULT 'RECUPERACION',
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Historial de devoluciones de productos
CREATE TABLE `table_devoluciones` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `factura_id` int(11),
  `producto_id` int(11),
  `descripcion` varchar(255) DEFAULT NULL,
  `cantidad` int(11),
  `monto_devuelto` decimal(15,2),
  `destino` enum('STOCK', 'DANADO'),
  `motivo` varchar(255) DEFAULT NULL,
  `dias_garantia_aplicado` int(11) DEFAULT NULL,
  `dias_transcurridos` int(11) DEFAULT NULL,
  `usuario_id` int(11),
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`factura_id`) REFERENCES `table_facturas`(`id`),
  FOREIGN KEY (`producto_id`) REFERENCES `table_inventario`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 9: GARANTÍAS (MÓDULO NUEVO)
-- =============================================================================
-- Las garantías anulan una factura original (status = ANULADO) y generan
-- una nueva factura de garantía (origen = GARANTIA). Cubren tanto la mano
-- de obra (15 días excepto lavados) como los repuestos (dias_garantia propio).

-- Cabecera de garantías
CREATE TABLE `table_garantias` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `factura_original_id` int(11) NOT NULL COMMENT 'Factura que se anula',
  `factura_garantia_id` int(11) DEFAULT NULL COMMENT 'Nueva factura de garantía generada',
  `cliente_id` varchar(50) DEFAULT NULL COMMENT 'Referencia a table_clientes.id (varchar)',
  `placa` varchar(20) DEFAULT NULL,
  `marca_vehiculo` varchar(50) DEFAULT NULL,
  `modelo_vehiculo` varchar(100) DEFAULT NULL,
  `tipo_garantia` enum('SERVICIO','REPUESTO','MIXTO') NOT NULL DEFAULT 'SERVICIO',
  `motivo` varchar(255) NOT NULL COMMENT 'Razón de la garantía (mayúsculas)',
  `monto_mano_obra` decimal(15,2) DEFAULT 0.00 COMMENT 'Monto devuelto/aumentado de mano de obra',
  `monto_repuesto` decimal(15,2) DEFAULT 0.00 COMMENT 'Monto del repuesto involucrado',
  `monto_total` decimal(15,2) DEFAULT 0.00 COMMENT 'Monto total de la garantía',
  `destino_repuesto` enum('STOCK','DANADO','N/A') NOT NULL DEFAULT 'N/A',
  `dias_garantia_servicio` int(11) DEFAULT NULL,
  `dias_garantia_repuesto` int(11) DEFAULT NULL,
  `dias_transcurridos` int(11) DEFAULT NULL,
  `usuario_id` int(11),
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`factura_original_id`) REFERENCES `table_facturas`(`id`),
  FOREIGN KEY (`factura_garantia_id`) REFERENCES `table_facturas`(`id`),
  FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`),
  INDEX (`factura_original_id`),
  INDEX (`tipo_garantia`),
  INDEX (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Detalle de items procesados en una garantía
CREATE TABLE `table_garantias_detalle` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `garantia_id` int(11) NOT NULL,
  `factura_detalle_id` int(11) COMMENT 'Detalle original de la factura',
  `producto_id` int(11) DEFAULT NULL COMMENT 'NULL si es solo mano de obra',
  `descripcion` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(15,2) NOT NULL DEFAULT 0.00,
  `monto_base` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Base sin IVA',
  `monto_iva` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'IVA proporcional',
  `monto_total` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total con IVA',
  `tipo_item` enum('SERVICIO','REPUESTO') NOT NULL DEFAULT 'SERVICIO',
  `accion` enum('DEVOLVER','AUMENTAR','REEMPLAZAR') NOT NULL DEFAULT 'DEVOLVER',
  `destino` enum('STOCK','DANADO','N/A') NOT NULL DEFAULT 'N/A',
  FOREIGN KEY (`garantia_id`) REFERENCES `table_garantias`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `table_inventario`(`id`),
  INDEX (`garantia_id`),
  INDEX (`tipo_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 10: CATÁLOGO PÚBLICO Y PEDIDOS EN LÍNEA
-- =============================================================================

-- Pedidos realizados desde el catálogo público
CREATE TABLE `pedidos_clientes` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
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
  `fecha_pedido` timestamp DEFAULT CURRENT_TIMESTAMP,
  `fecha_procesado` datetime DEFAULT NULL,
  INDEX (`estado`),
  INDEX (`fecha_pedido`),
  FOREIGN KEY (`usuario_procesa`) REFERENCES `table_usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Detalle de productos en cada pedido
CREATE TABLE `pedido_detalles` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos_clientes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `table_inventario`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- SEMILLAS (DATOS INICIALES)
-- =============================================================================
-- Único usuario administrador para arrancar el sistema limpio.
-- Usuario: admin  |  Clave: 123  (hash bcrypt)
-- El sistema también soporta texto plano con auto-migración a bcrypt.
-- =============================================================================

-- Roles básicos
INSERT INTO `table_roles` (`id`, `nombre_rol`, `descripcion`) VALUES
(1, 'ADMINISTRADOR', 'CONTROL TOTAL DEL SISTEMA'),
(2, 'MECANICO', 'GESTION DE ORDENES Y TRABAJOS'),
(3, 'CAJERO', 'GESTION DE FACTURACION Y CAJA');

-- Único empleado inicial (Administrador)
INSERT INTO `table_staff` (`id`, `cedula`, `nombre`, `cargo`, `telefono`, `email`, `direccion`, `foto`, `foto_frente`, `estado`) VALUES
('STAFF-001', 'V-00000000', 'ADMINISTRADOR', 'ADMINISTRADOR', NULL, NULL, NULL, 'img/default.png', 'img/default.png', 'ACTIVO');

-- Único usuario inicial (admin / 123) — clave hasheada con bcrypt
INSERT INTO `table_usuarios` (`staff_id`, `username`, `password`, `role_id`, `estado`) VALUES
('STAFF-001', 'admin', '$2y$10$BiaxfHWn6k0voBm6yp9NOuvgXlGCvO1oKr86tFxq07epy7Jll5pt6', 1, 'ACTIVO');

-- Configuración inicial de empresa
INSERT INTO `table_company_settings` (`id`, `name`, `nit`, `iva`) VALUES
(1, 'TALLER PRO', 'J-00000000-0', 19.00);

-- Cuentas de caja base
INSERT INTO `table_cuentas_pago` (`nombre`, `tipo`, `saldo_actual`) VALUES
('CAJA GENERAL EFECTIVO', 'EFECTIVO', 0.00),
('CUENTA BANCO', 'VIRTUAL', 0.00);

SET FOREIGN_KEY_CHECKS = 1;
-- Fin del esquema 2.0
-- Listo para ejecutar sin errores en un sitio nuevo.
