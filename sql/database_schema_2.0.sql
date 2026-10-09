-- =============================================================================
-- ESQUEMA DE BASE DE DATOS MULTISERVICIO V2.1.1 "TALLER PRO"
-- =============================================================================
-- Este script crea TODAS las tablas del sistema en el orden correcto de
-- dependencias (foreign keys) y termina con los datos mínimos para arrancar:
--   • 3 Roles (ADMINISTRADOR, MECANICO, CAJERO)
--   • 1 Empleado/Staff administrador
--   • 1 Usuario admin (admin / admin123)
--   • 1 Configuración de empresa inicial ("TALLER PRO")
--   • 2 Cuentas de pago base (Caja y Banco)
--
-- USO:
--   Ejecutar UNA sola vez sobre una base de datos VACÍA.
--   El sistema auto-migra la clave en texto plano a bcrypt al primer login.
--
-- CAMBIOS v2.0.1 (2026-10-07):
--   • Se agregó el estado 'ANEXADO' al ENUM de table_presupuestos.estado.
--     Motivo: al anexar un presupuesto a una OS o Factura, el sistema
--     marcaba estado = 'ANEXADO' pero MySQL lo guardaba como '' (vacío),
--     causando que el presupuesto siguiera apareciendo como disponible.
--
-- CAMBIOS v2.0.2 (2026-10-08):
--   • Se agregó la columna `presupuesto_activo_id` a table_facturas para
--     vincular el borrador de factura con el presupuesto que se le anexó.
--     Permite que el POS muestre el panel verde "Presupuesto #X anexado"
--     incluso cuando la factura se creó desde una Orden de Servicio.
--   • La FK `table_facturas_ibfk_4` se crea al final del script (después
--     de table_presupuestos) mediante ALTER TABLE, porque table_facturas
--     se define antes en el orden de dependencias.
--
-- CAMBIOS v2.0.3 (2026-10-08):
--   • Se agregó 'PRESUPUESTO' al ENUM de table_facturas.origen. Motivo: al
--     convertir un presupuesto a venta directamente (botón "Convertir a
--     Venta"), el sistema marca origen = 'PRESUPUESTO', pero como el ENUM
--     no lo incluía, MySQL guardaba '' silenciosamente.
--
-- CAMBIOS v2.1.0 (2026-10-08):
--   • Se agregó la columna `estado_gestion` (ENUM) a table_facturas para el
--     semáforo de gestión de cobranza. Valores posibles:
--         NUEVO, GESTIONADO, PROMETIDO, ACUERDO_PAGO, JUDICIAL
--     Default: 'NUEVO'. Se agrega el índice `idx_estado_gestion` para
--     filtrados rápidos.
--     Motivo: el módulo de Cartera por Edades ahora muestra por cada factura
--     un selector con el estado de gestión (llamado, promesa de pago, etc.)
--     que se persiste en esta columna.
--
-- CAMBIOS v2.1.1 (2026-10-09):
--   • FIX CRÍTICO: Se agregó la columna `usuario_id` a table_abonos_clientes
--     junto con su índice (`idx_abonos_usuario`) y FK a table_usuarios
--     (`table_abonos_clientes_ibfk_2` con ON DELETE SET NULL).
--     Motivo: ModelFacturacion y ModelFacturas ya usaban esta columna en
--     JOINs (para mostrar "Registrado por: X" en el detalle de factura y en
--     el PDF del recibo), pero el schema base no la incluía, causando:
--         SQLSTATE[42S22]: Column not found: 1054 Unknown column 'a.usuario_id'
--     al abrir /facturas/ver/X cuando la factura tenía abonos.
--
--     Para BD existentes: ejecutar sql/migration_abonos_usuario_id.sql
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';
SET NAMES utf8mb4;

-- =============================================================================
-- BLOQUE 1: IDENTIDAD Y SEGURIDAD
-- =============================================================================

CREATE TABLE IF NOT EXISTS `table_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- =============================================================================
-- BLOQUE 2: ENTIDADES MAESTRAS
-- =============================================================================

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

CREATE TABLE IF NOT EXISTS `table_proveedores` (
  `id` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 3: INVENTARIO Y COSTEO (CPP)
-- =============================================================================

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  CONSTRAINT `table_kardex_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_kardex_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 4: OPERACIONES DEL TALLER
-- =============================================================================

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

-- =============================================================================
-- BLOQUE 5: FINANZAS Y FACTURACIÓN
-- =============================================================================

CREATE TABLE IF NOT EXISTS `table_cuentas_pago` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `tipo` enum('EFECTIVO','BANCO','VIRTUAL') DEFAULT 'EFECTIVO',
  `saldo_actual` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Tabla: table_facturas
-- Propósito: Cabecera de facturas (registro contable de ventas).
-- 
-- NOTA SOBRE presupuesto_activo_id:
--   Almacena el ID del presupuesto que fue anexado a esta factura (cuando
--   se procesa una venta desde el POS o se crea una O.S. con presupuesto).
--   Permite mostrar el panel verde "Presupuesto #X anexado" en el POS y
--   saber qué presupuesto marcar como CONVERTIDO al facturar.
-- 
-- NOTA SOBRE origen:
--   Incluye 'PRESUPUESTO' para facturas creadas desde el botón
--   "Convertir a Venta" en el módulo de presupuestos. Los presupuestos
--   anexados a OS/POS conservan origen 'TALLER' o 'MOSTRADOR'.
-- 
-- NOTA SOBRE estado_gestion (v2.1.0):
--   Semáforo de gestión de cobranza. El módulo de "Cartera por Edades"
--   muestra un selector por factura para marcar el avance de la cobranza.
--   Valores: NUEVO, GESTIONADO, PROMETIDO, ACUERDO_PAGO, JUDICIAL.
-- 
--   La FK `table_facturas_ibfk_4` se crea DESPUÉS del bloque 11
--   (ALTER TABLE) porque table_presupuestos se define más adelante.
-- -----------------------------------------------------------------------------
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
  CONSTRAINT `table_facturas_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `table_ordenes_servicio` (`id`),
  CONSTRAINT `table_facturas_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes` (`id`),
  CONSTRAINT `table_facturas_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Tabla: table_abonos_clientes
-- Propósito: Historial de abonos/pagos parciales que los clientes hacen
--            a sus facturas a crédito.
--
-- CAMBIO v2.1.1 (2026-10-09):
--   Se agregó la columna `usuario_id` con su índice y FK.
--   Motivo: ModelFacturacion::registrarAbono() la inserta desde el principio,
--   y ModelFacturas::obtenerPorId + ModelFacturacion::obtenerAbonosPorFactura
--   + obtenerReciboAbono la usan en JOINs para mostrar "Registrado por: X".
--   Sin esta columna, /facturas/ver/X fallaba con error 1054 al abrir
--   cualquier factura con abonos.
--
-- ON DELETE SET NULL: los abonos son registros históricos. Si se elimina
-- un usuario, los abonos NO deben desaparecer (aparecerán como "SISTEMA").
-- -----------------------------------------------------------------------------
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
  CONSTRAINT `table_abonos_clientes_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `table_facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `table_abonos_clientes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 6: COMPRAS Y EGRESOS A PROVEEDORES
-- =============================================================================

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

-- =============================================================================
-- BLOQUE 7: LIBRO MAYOR CENTRALIZADO
-- =============================================================================

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

-- =============================================================================
-- BLOQUE 8: AUDITORÍA Y SISTEMA
-- =============================================================================

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

CREATE TABLE IF NOT EXISTS `table_recuperaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT 'RECUPERACION',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `table_recuperaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  CONSTRAINT `table_devoluciones_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `table_facturas` (`id`),
  CONSTRAINT `table_devoluciones_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`),
  CONSTRAINT `table_devoluciones_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- BLOQUE 9: GARANTÍAS
-- =============================================================================

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

-- =============================================================================
-- BLOQUE 10: EMAILS Y COMUNICACIONES
-- =============================================================================

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

-- =============================================================================
-- BLOQUE 11: PRESUPUESTOS / COTIZACIONES
-- =============================================================================

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

-- =============================================================================
-- ALTER: FK de table_facturas.presupuesto_activo_id
-- =============================================================================
-- Se ejecuta aquí porque table_presupuestos se creó en el bloque anterior.
-- Vincula cada borrador de factura con el presupuesto que se le anexó.
-- ON DELETE SET NULL: si se borra el presupuesto, la factura queda sin vínculo
-- pero NO se elimina (la factura ya está emitida o en proceso).
-- =============================================================================

ALTER TABLE `table_facturas`
  ADD CONSTRAINT `table_facturas_ibfk_4`
  FOREIGN KEY (`presupuesto_activo_id`) REFERENCES `table_presupuestos` (`id`) ON DELETE SET NULL;

-- =============================================================================
-- BLOQUE 12: CATÁLOGO PÚBLICO Y PEDIDOS EN LÍNEA
-- =============================================================================

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

-- =============================================================================
-- SEMILLAS (DATOS INICIALES PARA ARRANCAR EL SISTEMA)
-- =============================================================================

INSERT INTO `table_roles` (`id`, `nombre_rol`, `descripcion`) VALUES
(1, 'ADMINISTRADOR', 'CONTROL TOTAL DEL SISTEMA'),
(2, 'MECANICO',      'GESTION DE ORDENES Y TRABAJOS'),
(3, 'CAJERO',        'GESTION DE FACTURACION Y CAJA');

INSERT INTO `table_staff` (`id`, `cedula`, `nombre`, `cargo`, `estado`) VALUES
('STAFF-001', 'V-00000000', 'ADMINISTRADOR', 'ADMINISTRADOR', 'ACTIVO');

INSERT INTO `table_usuarios` (`staff_id`, `username`, `password`, `role_id`, `estado`) VALUES
('STAFF-001', 'admin', 'admin123', 1, 'ACTIVO');

INSERT INTO `table_company_settings` (`id`, `name`, `nit`, `iva`, `direccion`, `telefono`) VALUES
(1, 'TALLER PRO', 'J-00000000-0', 19.00, 'DIRECCIÓN DE LA EMPRESA', '000-0000000');

INSERT INTO `table_cuentas_pago` (`nombre`, `tipo`, `saldo_actual`) VALUES
('CAJA GENERAL EFECTIVO', 'EFECTIVO', 0.00),
('CUENTA BANCO',          'VIRTUAL',  0.00);

-- =============================================================================
-- FIN DEL SCRIPT
-- =============================================================================
-- Después de ejecutar este archivo, la base de datos queda lista para
-- iniciar el sistema con el usuario:
--     Usuario:  admin
--     Clave:    admin123
-- Se recomienda cambiar la clave tras el primer inicio de sesión.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 1;