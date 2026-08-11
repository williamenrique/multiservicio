-- =====================================================================
-- MIGRACIÓN: Sistema de Devoluciones Configurable
-- Aplica los cambios a una base de datos existente (multiservicio_2.0)
-- =====================================================================

-- 1. Añadir campo dias_garantia a table_inventario (garantía por repuesto)
ALTER TABLE `table_inventario`
  ADD COLUMN `dias_garantia` int(11) DEFAULT NULL COMMENT 'Días de garantía específicos (NULL = usar global)'
  AFTER `imagen`;

-- 2. Añadir campo dias_garantia_devolucion a table_company_settings (garantía global)
ALTER TABLE `table_company_settings`
  ADD COLUMN `dias_garantia_devolucion` int(11) DEFAULT 5 COMMENT 'Días globales de garantía para devoluciones'
  AFTER `logo`;

-- 3. Completar table_devoluciones con campos faltantes
-- descripcion ya debería existir si se creó manualmente, si no, se añade
ALTER TABLE `table_devoluciones`
  ADD COLUMN IF NOT EXISTS `descripcion` varchar(255) DEFAULT NULL AFTER `producto_id`;

ALTER TABLE `table_devoluciones`
  ADD COLUMN IF NOT EXISTS `motivo` varchar(255) DEFAULT NULL AFTER `destino`;

ALTER TABLE `table_devoluciones`
  ADD COLUMN IF NOT EXISTS `dias_garantia_aplicado` int(11) DEFAULT NULL AFTER `motivo`;

ALTER TABLE `table_devoluciones`
  ADD COLUMN IF NOT EXISTS `dias_transcurridos` int(11) DEFAULT NULL AFTER `dias_garantia_aplicado`;

-- Nota: Si MySQL no soporta ADD COLUMN IF NOT EXISTS (versiones < 8.0.13),
-- ejecutar manualmente verificando primero si la columna existe:
-- SHOW COLUMNS FROM table_devoluciones LIKE 'descripcion';
