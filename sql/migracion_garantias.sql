-- =============================================================================
-- MIGRACIÓN: MÓDULO DE GARANTÍAS
-- =============================================================================
-- Script seguro: no elimina datos existentes. Usa CREATE TABLE IF NOT EXISTS
-- y ALTER TABLE ... ADD COLUMN IF NOT EXISTS / MODIFY COLUMN para enums.
-- Ejecutar sobre la base de datos `multiservicio_2.0` ya poblada.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. NUEVAS TABLAS DE GARANTÍA
-- -----------------------------------------------------------------------------

-- Cabecera de garantías. Cada garantía anula una factura original y genera
-- una nueva factura de garantía (origen = GARANTIA).
CREATE TABLE IF NOT EXISTS `table_garantias` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `factura_original_id` int(11) NOT NULL COMMENT 'Factura que se anula',
  `factura_garantia_id` int(11) DEFAULT NULL COMMENT 'Nueva factura de garantía generada',
  `cliente_id` varchar(50) DEFAULT NULL COMMENT 'Referencia a table_clientes.id (varchar)',
  `placa` varchar(20) DEFAULT NULL,
  `modelo_vehiculo` varchar(100) DEFAULT NULL,
  `tipo_garantia` enum('SERVICIO','REPUESTO','MIXTO') NOT NULL DEFAULT 'SERVICIO' COMMENT 'Origen de la garantía',
  `motivo` varchar(255) NOT NULL COMMENT 'Razón de la garantía (mayúsculas)',
  `monto_mano_obra` decimal(15,2) DEFAULT 0.00 COMMENT 'Monto devuelto/aumentado de mano de obra',
  `monto_repuesto` decimal(15,2) DEFAULT 0.00 COMMENT 'Monto del repuesto involucrado',
  `monto_total` decimal(15,2) DEFAULT 0.00 COMMENT 'Monto total de la garantía',
  `destino_repuesto` enum('STOCK','DANADO','N/A') NOT NULL DEFAULT 'N/A' COMMENT 'Destino del repuesto si aplica',
  `dias_garantia_servicio` int(11) DEFAULT NULL COMMENT 'Días de garantía de mano de obra aplicados',
  `dias_garantia_repuesto` int(11) DEFAULT NULL COMMENT 'Días de garantía del repuesto aplicados',
  `dias_transcurridos` int(11) DEFAULT NULL COMMENT 'Días transcurridos desde la factura original',
  `usuario_id` int(11) COMMENT 'Usuario que procesó la garantía',
  `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`factura_original_id`) REFERENCES `table_facturas`(`id`),
  FOREIGN KEY (`factura_garantia_id`) REFERENCES `table_facturas`(`id`),
  FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`),
  INDEX (`factura_original_id`),
  INDEX (`tipo_garantia`),
  INDEX (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Detalle de items procesados en una garantía (servicios y repuestos).
CREATE TABLE IF NOT EXISTS `table_garantias_detalle` (
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
  `accion` enum('DEVOLVER','AUMENTAR','REEMPLAZAR') NOT NULL DEFAULT 'DEVOLVER' COMMENT 'Acción sobre mano de obra',
  `destino` enum('STOCK','DANADO','N/A') NOT NULL DEFAULT 'N/A' COMMENT 'Destino del repuesto',
  FOREIGN KEY (`garantia_id`) REFERENCES `table_garantias`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `table_inventario`(`id`),
  INDEX (`garantia_id`),
  INDEX (`tipo_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- 2. AMPLIACIÓN DE ENUMS EXISTENTES (sin perder datos)
-- -----------------------------------------------------------------------------

-- table_transacciones.categoria: agregar 'GARANTIA'
ALTER TABLE `table_transacciones`
  MODIFY COLUMN `categoria` enum('VENTA','GASTO','NOMINA','COMPRA_PROVEEDOR','ABONO_CLIENTE','ABONO_PROVEEDOR','DEVOLUCION','GARANTIA') DEFAULT NULL;

-- table_facturas.origen: agregar 'GARANTIA'
ALTER TABLE `table_facturas`
  MODIFY COLUMN `origen` enum('MOSTRADOR','CATALOGO','TALLER','GARANTIA') DEFAULT 'MOSTRADOR';

-- table_kardex.tipo_movimiento: agregar 'GARANTIA'
ALTER TABLE `table_kardex`
  MODIFY COLUMN `tipo_movimiento` enum('ENTRADA_COMPRA','SALIDA_VENTA','AJUSTE_MANUAL','DEVOLUCION','GARANTIA') NOT NULL;

-- -----------------------------------------------------------------------------
-- 3. NUEVO PARÁMETRO DE CONFIGURACIÓN GLOBAL
-- -----------------------------------------------------------------------------

-- Días de garantía por servicio (mano de obra). Por defecto 15 días.
-- Los lavados (categoria = 'LAVADO' en table_inventario) no aplican garantía de servicio.
ALTER TABLE `table_company_settings`
  ADD COLUMN IF NOT EXISTS `dias_garantia_servicio` int(11) NOT NULL DEFAULT 15 COMMENT 'Días de garantía por mano de obra (excepto lavados)';

-- =============================================================================
-- FIN DE LA MIGRACIÓN
-- =============================================================================
