-- =============================================================================
-- Migración: Ampliar ENUMs de table_emails y table_email_templates
-- Fecha: 2026-10-06
-- Motivo: Los controladores usan tipos ('ORDEN_SERVICIO', 'PEDIDO_CATALOGO',
--         'ALERTA_PROVEEDOR', 'RESUMEN_MENSUAL', 'GARANTIA') que no estaban
--         en el ENUM original y MySQL rechazaba el INSERT (perdiendo el log).
-- Ejecutar UNA sola vez sobre la base de datos multiservicio_2.0
-- =============================================================================

-- 1. Ampliar ENUM de table_emails
ALTER TABLE `table_emails`
  MODIFY COLUMN `tipo` ENUM(
    'FACTURA',
    'PRESUPUESTO',
    'NOTIFICACION',
    'RECUPERACION',
    'ORDEN_SERVICIO',
    'PEDIDO_CATALOGO',
    'RESUMEN_MENSUAL',
    'ALERTA_PROVEEDOR',
    'GARANTIA',
    'OTRO'
  ) NOT NULL DEFAULT 'OTRO',
  MODIFY COLUMN `referencia_tipo` ENUM(
    'FACTURA',
    'PRESUPUESTO',
    'ORDEN',
    'CLIENTE',
    'PEDIDO_CATALOGO',
    'PROVEEDOR',
    'GARANTIA',
    'NINGUNO'
  ) DEFAULT 'NINGUNO';

-- 2. Mismo cambio para table_email_templates (para poder guardar plantillas nuevas)
ALTER TABLE `table_email_templates`
  MODIFY COLUMN `tipo` ENUM(
    'FACTURA',
    'PRESUPUESTO',
    'NOTIFICACION',
    'RECUPERACION',
    'ORDEN_SERVICIO',
    'PEDIDO_CATALOGO',
    'RESUMEN_MENSUAL',
    'ALERTA_PROVEEDOR',
    'GARANTIA',
    'OTRO'
  ) NOT NULL DEFAULT 'OTRO';

-- 3. Índice para acelerar filtros por referencia (usado en la vista de historial)
ALTER TABLE `table_emails`
  ADD INDEX `idx_ref_orden` (`referencia_tipo`, `referencia_id`);

-- =============================================================================
-- Fin de la migración
-- =============================================================================