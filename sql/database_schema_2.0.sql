-- ESTRUCTURA DE BASE DE DATOS MULTISERVICIO V2.0
-- =============================================================================
-- ESQUEMA DE BASE DE DATOS MULTISERVICIO V2.0 "TALLER PRO"
-- =============================================================================
-- Diseñado para:
-- 1. Separación Técnica (Órdenes) vs Contable (Facturas).
-- 2. Libro Mayor Centralizado (Ledger) para Flujo de Caja instantáneo.
-- 3. Atribución Dual (Quién hizo el trabajo vs Quién cobró).
-- 4. Valoración por Costo Promedio Ponderado (CPP).
-- 5. Compatibilidad Cross-Platform (Nombres en Snake Case).
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `multiservicio_2.0` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE `multiservicio_2.0`;

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"; -- Permite IDs en 0 si fuera necesario

-- =============================================================================
-- BLOQUE 1: IDENTIDAD Y SEGURIDAD
-- =============================================================================

-- Roles de usuario (ADMINISTRADOR, MECANICO, CAJERO)
CREATE TABLE `table_roles` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cuentas de acceso al sistema
CREATE TABLE `table_usuarios` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `staff_id` varchar(50),
  `username` varchar(50) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11),
  `estado` enum('ACTIVO', 'INACTIVO') DEFAULT 'ACTIVO',
  `fecha_registro` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`staff_id`) REFERENCES `table_staff`(`id`),
  FOREIGN KEY (`role_id`) REFERENCES `table_roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Control de sesiones activas (Single Session)
CREATE TABLE `table_usuario_sessions` (
  `usuario_id` int(11) PRIMARY KEY,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45),
  `usuario_agent` text,
  `last_activity` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `logo` varchar(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Maestro de vehículos vinculados a clientes
CREATE TABLE `table_vehiculos` (
  `placa` varchar(20) PRIMARY KEY,
  `cliente_id` varchar(50),
  `marca` varchar(50),
  `modelo` varchar(50),
  `anio` int(4),
  `color` varchar(30),
  FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Maestro de proveedores
CREATE TABLE `table_proveedores` (
  `id` varchar(50) PRIMARY KEY,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20),
  `email` varchar(100),
  `direccion` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- BLOQUE 3: INVENTARIO Y COSTEO (CPP)
-- =============================================================================

-- Productos y Servicios
CREATE TABLE `table_inventario` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `categoria` varchar(50),
  `stock` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 5,
  `ultimo_costo` decimal(15,2) DEFAULT 0.00,
  `costo_promedio` decimal(15,2) DEFAULT 0.00, -- Pilar para rentabilidad real
  `precio` decimal(15,2) NOT NULL DEFAULT 0.00,
  `imagen` varchar(255),
  `estado` enum('ACTIVO', 'INACTIVO') DEFAULT 'ACTIVO',
  INDEX (`nombre`),
  INDEX (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Historial de movimientos de stock (Kardex)
CREATE TABLE `table_kardex` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `producto_id` int(11),
  `tipo_movimiento` enum('ENTRADA_COMPRA', 'SALIDA_VENTA', 'AJUSTE_MANUAL', 'DEVOLUCION'),
  `cantidad` int(11) NOT NULL,
  `stock_anterior` int(11) NOT NULL,
  `stock_actual` int(11) NOT NULL,
  `referencia_id` varchar(50), -- ID de Factura o Compra
  `usuario_id` int(11), -- Quién realizó el movimiento
  `observacion` text DEFAULT NULL,
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`producto_id`) REFERENCES `table_inventario`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- BLOQUE 3: OPERACIONES DEL TALLER (TECNICO)
-- =============================================================================

-- Hoja de vida técnica del vehículo (No es factura aún)
CREATE TABLE `table_ordenes_servicio` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `cliente_id` varchar(50),
  `placa` varchar(10) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Checklist de Entrada (Accesorios y estado del vehículo)
CREATE TABLE `table_orden_checklist` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `orden_id` int(11) NOT NULL,
  `item` varchar(100) NOT NULL, -- Ej: Llaves, Gato, Herramientas
  `estado` tinyint(1) DEFAULT 0,
  `observacion` varchar(255) DEFAULT NULL,
  FOREIGN KEY (`orden_id`) REFERENCES `table_ordenes_servicio` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================================
-- BLOQUE 5: FINANZAS Y FACTURACION (CONTABLE)
-- =============================================================================

-- Catálogo de bancos y cajas
CREATE TABLE `table_cuentas_pago` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL, -- Ej: Caja Efectivo, Nequi, Bancolombia
  `tipo` enum('EFECTIVO', 'BANCO', 'VIRTUAL') DEFAULT 'EFECTIVO',
  `saldo_actual` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `observaciones` text DEFAULT NULL,
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`orden_id`) REFERENCES `table_ordenes_servicio`(`id`),
  FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  FOREIGN KEY (`factura_id`) REFERENCES `table_facturas`(`id`),
  FOREIGN KEY (`producto_id`) REFERENCES `table_inventario`(`id`),
  FOREIGN KEY (`mecanico_id`) REFERENCES `table_staff`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registro histórico de abonos a deudas de clientes
CREATE TABLE `table_abonos_clientes` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `factura_id` int(11),
  `monto` decimal(15,2) NOT NULL,
  `metodo_pago` enum('EFECTIVO', 'TRANSFERENCIA') NOT NULL,
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`factura_id`) REFERENCES `table_facturas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- BLOQUE 7: EL LIBRO MAYOR (TRANSACCIONES CENTRALIZADAS)
-- =============================================================================

-- Origen único para reportes de flujo de caja
CREATE TABLE `table_transacciones` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `cuenta_id` int(11), -- Caja o Banco específico
  `tipo` enum('INGRESO', 'EGRESO') NOT NULL,
  `categoria` enum('VENTA', 'GASTO', 'NOMINA', 'COMPRA_PROVEEDOR', 'ABONO_CLIENTE', 'ABONO_PROVEEDOR') NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `referencia_id` int(11), -- ID del documento origen
  `descripcion` varchar(255),
  `usuario_id` int(11), -- El cajero que registró
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cuenta_id`) REFERENCES `table_cuentas_pago`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`),
  INDEX (`categoria`),
  INDEX (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gastos fijos y variables del taller
CREATE TABLE `table_gastos` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `categoria` varchar(50),
  `descripcion` varchar(255),
  `monto` decimal(15,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT 'EFECTIVO',
  `usuario_id` int(11),
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Solicitudes de recuperación de clave
CREATE TABLE `table_recuperaciones` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `usuario_id` int(11),
  `tipo` varchar(50) DEFAULT 'RECUPERACION',
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Historial de devoluciones de productos
CREATE TABLE `table_devoluciones` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `factura_id` int(11),
  `producto_id` int(11),
  `cantidad` int(11),
  `monto_devuelto` decimal(15,2),
  `destino` enum('STOCK', 'DANADO'),
  `usuario_id` int(11),
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`factura_id`) REFERENCES `table_facturas`(`id`),
  FOREIGN KEY (`producto_id`) REFERENCES `table_inventario`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SEMILLAS (DATOS INICIALES)
-- =====================================================

-- Roles básicos
INSERT INTO `table_roles` (`id`, `nombre_rol`, `descripcion`) VALUES 
(1, 'ADMINISTRADOR', 'CONTROL TOTAL DEL SISTEMA'),
(2, 'MECANICO', 'GESTION DE ORDENES Y TRABAJOS'),
(3, 'CAJERO', 'GESTION DE FACTURACION Y CAJA');

-- Empleado Administrador Inicial
INSERT IGNORE INTO `table_staff` (`id`, `cedula`, `nombre`, `cargo`, `telefono`, `email`, `direccion`, `foto`, `foto_frente`, `estado`, `fecha_creacion`) VALUES
  ('MEC-001', '12512563', 'ALBERTO JOSE', 'MECANICO', '0412125123', 'alberto@gmail.com', 'LAS TAPIAS CALLE 2', 'img/default.png', 'img/default.png', 'ACTIVO', '2026-06-11 13:58:48'),
  ('MEC-002', '112021362', 'CARLOS LUIS', 'MECANICO', '0412125236', 'carlos@gmail.com', 'LA PRADERA, COCOROTE', 'img/default.png', 'img/default.png', 'ACTIVO', '2026-06-11 13:59:39'),
  ('STAFF-001', 'V-00000000', 'ADMINISTRADOR', 'GERENTE', NULL, NULL, NULL, 'img/default.png', 'img/default.png', 'ACTIVO', '2026-06-11 13:53:39'),
  ('STAFF-002', '14607920', 'WILLIAM ENRIQUE', 'ADMINISTRADOR', '04125181629', 'william21enrique@gmail.com', 'AV PRINCIPAL CALLE 2 URB VISTA ALEGRE', 'img/default.png', 'img/default.png', 'ACTIVO', '2026-06-11 13:57:55');


-- Usuario Admin Inicial (User: admin / Pass: admin)
-- NOTA: El sistema hashea la clave automáticamente al primer login
INSERT INTO `table_usuarios` (`staff_id`, `username`, `password`, `role_id`, `estado`) VALUES 
('STAFF-001', 'admin', 'admin', 1, 'ACTIVO'),
('STAFF-002', 'WILL', 'QQ', 1, 'ACTIVO');

-- Configuración inicial de empresa
INSERT INTO `table_company_settings` (`id`, `name`, `nit`, `iva`) VALUES 
(1, 'TALLER PRO', 'J-14607920-9', 19.00);

-- Cuentas de caja base
INSERT INTO `table_cuentas_pago` (`nombre`, `tipo`, `saldo_actual`) VALUES 
('CAJA GENERAL EFECTIVO', 'EFECTIVO', 0.00),
('CUENTA BANCO', 'VIRTUAL', 0.00);

-- PROVEEDORSES PRUEBA
INSERT IGNORE INTO `table_proveedores` (`id`, `nombre`, `telefono`, `email`, `direccion`) VALUES
  ('J-632563-7', 'LUBRICANTES DEL CENTRO', '041251256', 'lubricantes@gmail.com', 'AV LOS LEONES EDO LARA'),
  ('J-954155-5', 'BATERIAS JUAN', '0414125212', 'bateriasjuan@gmail.com', 'AV INTERCOMUNAL SECTOR LAS TAPIAS');

SET FOREIGN_KEY_CHECKS = 1;
-- Fin del esquema 2.0
-- Listo para ejecutar sin errores.