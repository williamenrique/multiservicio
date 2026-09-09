-- =============================================================================
-- MIGRACIÓN: Tabla de Servicios/Revisiones para Órdenes de Servicio
-- =============================================================================
-- Ejecutar en producción para agregar la nueva funcionalidad
-- Fecha: 2026-09-09
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

-- Crear tabla table_orden_servicios si no existe
CREATE TABLE IF NOT EXISTS `table_orden_servicios` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `orden_id` int(11) NOT NULL,
  `descripcion` varchar(255) NOT NULL, -- Descripción del servicio/revisión
  `estado` enum('PENDIENTE', 'EN_PROCESO', 'COMPLETADO', 'CANCELADO') DEFAULT 'PENDIENTE',
  `orden_visual` int(11) DEFAULT 0, -- Orden de visualización (1, 2, 3...)
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`orden_id`) REFERENCES `table_ordenes_servicio` (`id`) ON DELETE CASCADE,
  INDEX (`orden_id`),
  INDEX (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verificar si la tabla se creó correctamente
SELECT 'Tabla table_orden_servicios creada/verificada correctamente' as resultado;

SET FOREIGN_KEY_CHECKS = 1;