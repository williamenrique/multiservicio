-- =============================================================================
-- MIGRACIÓN: Condición (NUEVO/USADO) + Visibilidad en catálogo público
-- =============================================================================
-- Versión: 3.1.0
-- Fecha:   2026-10-21
-- Autor:   Sistema Multiservicio
--
-- PROPÓSITO:
--   Agregar a table_inventario los campos necesarios para distinguir:
--     • condicion        → NUEVO / USADO (informativo y para reglas de garantía)
--     • visible_catalogo → 1 = se publica en el catálogo público, 0 = solo interno
--
--   Regla de negocio:
--     - El catálogo público SOLO muestra productos con estado='ACTIVO' AND visible_catalogo=1
--     - Taller / Facturación / Presupuesto ven TODOS los productos ACTIVO (sin filtro)
--     - Los repuestos USADO tienen garantía fija de 5 días (lógica en aplicación)
--
-- CAMBIOS EN TABLAS RELACIONADAS:
--     • table_facturas_detalle    → + condicion_item (snapshot histórico)
--     • table_presupuestos_detalle→ + condicion_item (snapshot histórico)
--
-- IDEMPOTENTE: Se puede ejecutar varias veces sin efectos secundarios.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

-- =============================================================================
-- PASO 1: Agregar columnas a table_inventario
-- =============================================================================

-- 1.a condicion (NUEVO/USADO)
SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'table_inventario'
    AND COLUMN_NAME = 'condicion'
);
SET @sql = IF(@col_existe = 0,
  "ALTER TABLE `table_inventario`
     ADD COLUMN `condicion` ENUM('NUEVO','USADO') NOT NULL DEFAULT 'NUEVO' AFTER `categoria`,
     ADD KEY `idx_condicion` (`condicion`)",
  "SELECT 'INFO: columna condicion ya existe en table_inventario' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.b visible_catalogo (toggle manual)
SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'table_inventario'
    AND COLUMN_NAME = 'visible_catalogo'
);
SET @sql = IF(@col_existe = 0,
  "ALTER TABLE `table_inventario`
     ADD COLUMN `visible_catalogo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `condicion`,
     ADD KEY `idx_visible_catalogo` (`visible_catalogo`)",
  "SELECT 'INFO: columna visible_catalogo ya existe en table_inventario' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- PASO 2: Snapshot histórico de condición en detalle de factura y presupuesto
-- =============================================================================

-- 2.a table_facturas_detalle.condicion_item
SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'table_facturas_detalle'
    AND COLUMN_NAME = 'condicion_item'
);
SET @sql = IF(@col_existe = 0,
  "ALTER TABLE `table_facturas_detalle`
     ADD COLUMN `condicion_item` ENUM('NUEVO','USADO','SERVICIO') DEFAULT NULL
     AFTER `costo_unitario`",
  "SELECT 'INFO: columna condicion_item ya existe en table_facturas_detalle' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2.b table_presupuestos_detalle.condicion_item
SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'table_presupuestos_detalle'
    AND COLUMN_NAME = 'condicion_item'
);
SET @sql = IF(@col_existe = 0,
  "ALTER TABLE `table_presupuestos_detalle`
     ADD COLUMN `condicion_item` ENUM('NUEVO','USADO','SERVICIO') DEFAULT NULL
     AFTER `tipo_item`",
  "SELECT 'INFO: columna condicion_item ya existe en table_presupuestos_detalle' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- PASO 3: Migración de datos existentes
-- =============================================================================
--
-- 3.a Todos los productos existentes se marcan como NUEVO por defecto
--     (esto lo hace el DEFAULT del ADD COLUMN, pero por claridad lo hacemos explícito)
UPDATE `table_inventario`
   SET `condicion` = 'NUEVO'
 WHERE `condicion` IS NULL;

-- 3.b Publicar en catálogo los productos que YA se mostraban antes:
--     Criterio: estado='ACTIVO' AND stock > 0.
--     Esto preserva el comportamiento actual del catálogo público,
--     que hoy muestra todos los activos.
--     El admin puede despublicar producto por producto después.
UPDATE `table_inventario`
   SET `visible_catalogo` = 1
 WHERE `estado` = 'ACTIVO'
   AND `stock` > 0;

-- 3.c Backfill de snapshot histórico:
--     Rellenar condicion_item en facturas y presupuestos existentes
--     usando la condición actual del producto (aproximación razonable).
UPDATE `table_facturas_detalle` fd
  INNER JOIN `table_inventario` i ON fd.producto_id = i.id
   SET fd.`condicion_item` = i.`condicion`
 WHERE fd.`condicion_item` IS NULL
   AND fd.`producto_id` IS NOT NULL;

-- Los items de factura sin producto_id son SERVICIO
UPDATE `table_facturas_detalle`
   SET `condicion_item` = 'SERVICIO'
 WHERE `condicion_item` IS NULL
   AND `producto_id` IS NULL;

UPDATE `table_presupuestos_detalle` pd
  INNER JOIN `table_inventario` i ON pd.producto_id = i.id
   SET pd.`condicion_item` = i.`condicion`
 WHERE pd.`condicion_item` IS NULL
   AND pd.`producto_id` IS NOT NULL;

UPDATE `table_presupuestos_detalle`
   SET `condicion_item` = 'SERVICIO'
 WHERE `condicion_item` IS NULL
   AND (`producto_id` IS NULL OR `tipo_item` = 'SERVICIO');

-- =============================================================================
-- PASO 4: Verificación final
-- =============================================================================

SELECT
  'table_inventario' AS tabla,
  SUM(CASE WHEN condicion='NUEVO' THEN 1 ELSE 0 END) AS nuevos,
  SUM(CASE WHEN condicion='USADO' THEN 1 ELSE 0 END) AS usados,
  SUM(CASE WHEN visible_catalogo=1 THEN 1 ELSE 0 END) AS publicados,
  COUNT(*) AS total
FROM table_inventario;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- FIN DE LA MIGRACIÓN
-- =============================================================================