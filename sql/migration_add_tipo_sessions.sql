-- =============================================================================
-- Migration: Agregar columna `tipo` (WEB/APP) a table_usuario_sessions
-- Permite que un usuario tenga 1 sesión WEB + 1 sesión APP simultáneamente.
-- =============================================================================
-- Fecha: 2026-07-22
-- =============================================================================

-- 1. Agregar columna tipo para diferenciar origen de la petición
ALTER TABLE `table_usuario_sessions`
    ADD COLUMN `tipo` ENUM('WEB', 'APP') NOT NULL DEFAULT 'APP' AFTER `usuario_id`;

-- 2. Eliminar la PK actual (usuario_id) porque ahora habrá múltiples filas por usuario
ALTER TABLE `table_usuario_sessions` DROP PRIMARY KEY;

-- 3. Agregar un id auto-incremental como nueva PK
ALTER TABLE `table_usuario_sessions`
    ADD COLUMN `id` INT PRIMARY KEY AUTO_INCREMENT FIRST;

-- 4. Unique Key compuesta: solo 1 sesión por usuario por tipo (WEB o APP)
ALTER TABLE `table_usuario_sessions`
    ADD UNIQUE KEY `uk_usuario_tipo` (`usuario_id`, `tipo`);

-- 5. Asignar tipo 'WEB' a las sesiones existentes (retrocompatibilidad)
--    Se asume que las sesiones previas eran de navegador web
UPDATE `table_usuario_sessions` SET `tipo` = 'WEB' WHERE `tipo` = 'APP';