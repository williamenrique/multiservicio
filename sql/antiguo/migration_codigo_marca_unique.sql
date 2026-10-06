-- =============================================================================
-- MIGRACIÓN: Cambiar constraint único de código a código+marca
-- =============================================================================
-- Permite tener el mismo código para diferentes marcas de repuestos
-- =============================================================================

USE `multiservicio_2.0`;

-- Eliminar la clave única existente solo en 'codigo'
ALTER TABLE `table_inventario` DROP INDEX `uk_codigo`;

-- Agregar nueva clave única compuesta en (codigo, marca)
-- Esto permite el mismo código para diferentes marcas
ALTER TABLE `table_inventario` ADD UNIQUE KEY `uk_codigo_marca` (`codigo`, `marca`);

-- Verificar el cambio
SHOW INDEX FROM `table_inventario` WHERE Key_name = 'uk_codigo_marca';