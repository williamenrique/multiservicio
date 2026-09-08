-- =============================================================================
-- MIGRACIÓN: AGREGAR CAMPOS DE OFERTAS A TABLE_INVENTARIO
-- =============================================================================
-- Este script debe ejecutarse en bases de datos en producción para agregar
-- la funcionalidad de ofertas a los productos del inventario.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Agregar campos de oferta a table_inventario
ALTER TABLE `table_inventario` 
ADD COLUMN `oferta_activa` tinyint(1) DEFAULT 0 COMMENT '1 = en oferta, 0 = sin oferta' AFTER `estado`,
ADD COLUMN `oferta_porcentaje` decimal(5,2) DEFAULT 0.00 COMMENT 'Porcentaje de descuento (ej: 15.00 = 15%)' AFTER `oferta_activa`,
ADD COLUMN `oferta_fecha_inicio` date DEFAULT NULL COMMENT 'Fecha de inicio de la oferta' AFTER `oferta_porcentaje`,
ADD COLUMN `oferta_fecha_fin` date DEFAULT NULL COMMENT 'Fecha de fin de la oferta' AFTER `oferta_fecha_inicio`;

-- Agregar índice para filtrar ofertas activas eficientemente
ALTER TABLE `table_inventario` 
ADD INDEX `idx_oferta_activa` (`oferta_activa`);

-- Verificar que los campos se agregaron correctamente
SHOW COLUMNS FROM `table_inventario` LIKE 'oferta%';

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- NOTAS DE EJECUCIÓN:
-- =============================================================================
-- 1. Ejecutar este script en la base de datos de producción
-- 2. Los campos se agregan con valores por defecto seguros:
--    - oferta_activa = 0 (sin oferta por defecto)
--    - oferta_porcentaje = 0.00 (sin descuento)
--    - oferta_fecha_inicio = NULL
--    - oferta_fecha_fin = NULL
-- 3. No requiere migración de datos existentes
-- 4. Compatible con MySQL 5.7+ y MariaDB 10.2+
-- =============================================================================