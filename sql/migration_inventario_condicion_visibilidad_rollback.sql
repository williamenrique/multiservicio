-- =============================================================================
-- ROLLBACK: Condición (NUEVO/USADO) + Visibilidad en catálogo público
-- =============================================================================
-- ⚠️ ADVERTENCIA:
--   Elimina las columnas condicion, visible_catalogo, condicion_item.
--   Se PIERDEN los datos de estas columnas. Ejecutar solo si hay que revertir.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Quitar índices + columnas de table_inventario
SET @idx_existe = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'table_inventario'
    AND INDEX_NAME = 'idx_condicion'
);
SET @sql = IF(@idx_existe > 0,
  "ALTER TABLE `table_inventario` DROP INDEX `idx_condicion`",
  "SELECT 'INFO: idx_condicion no existe' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_existe = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'table_inventario'
    AND INDEX_NAME = 'idx_visible_catalogo'
);
SET @sql = IF(@idx_existe > 0,
  "ALTER TABLE `table_inventario` DROP INDEX `idx_visible_catalogo`",
  "SELECT 'INFO: idx_visible_catalogo no existe' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'table_inventario'
    AND COLUMN_NAME = 'visible_catalogo'
);
SET @sql = IF(@col_existe > 0,
  "ALTER TABLE `table_inventario` DROP COLUMN `visible_catalogo`",
  "SELECT 'INFO: visible_catalogo no existe' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'table_inventario'
    AND COLUMN_NAME = 'condicion'
);
SET @sql = IF(@col_existe > 0,
  "ALTER TABLE `table_inventario` DROP COLUMN `condicion`",
  "SELECT 'INFO: condicion no existe' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Snapshot histórico
SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'table_facturas_detalle'
    AND COLUMN_NAME = 'condicion_item'
);
SET @sql = IF(@col_existe > 0,
  "ALTER TABLE `table_facturas_detalle` DROP COLUMN `condicion_item`",
  "SELECT 'INFO: condicion_item no existe en facturas_detalle' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'table_presupuestos_detalle'
    AND COLUMN_NAME = 'condicion_item'
);
SET @sql = IF(@col_existe > 0,
  "ALTER TABLE `table_presupuestos_detalle` DROP COLUMN `condicion_item`",
  "SELECT 'INFO: condicion_item no existe en presupuestos_detalle' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;