-- =====================================================================
-- MIGRACIÓN: Sistema de Devoluciones Configurable (PostgreSQL / Supabase)
-- Aplica los cambios a una base de datos existente (schema: multiservicio_2.0)
-- Idempotente: se puede ejecutar múltiples veces sin error.
-- =====================================================================

-- 1. Añadir campo dias_garantia a table_inventario (garantía por repuesto)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = 'table_inventario'
          AND column_name = 'dias_garantia'
    ) THEN
        ALTER TABLE table_inventario
            ADD COLUMN dias_garantia integer DEFAULT NULL;
        COMMENT ON COLUMN table_inventario.dias_garantia IS 'Días de garantía específicos (NULL = usar global)';
    END IF;
END $$;

-- 2. Añadir campo dias_garantia_devolucion a table_company_settings (garantía global)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = 'table_company_settings'
          AND column_name = 'dias_garantia_devolucion'
    ) THEN
        ALTER TABLE table_company_settings
            ADD COLUMN dias_garantia_devolucion integer DEFAULT 5;
        COMMENT ON COLUMN table_company_settings.dias_garantia_devolucion IS 'Días globales de garantía para devoluciones';
    END IF;
END $$;

-- 3. Completar table_devoluciones con campos faltantes
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = 'table_devoluciones'
          AND column_name = 'descripcion'
    ) THEN
        ALTER TABLE table_devoluciones ADD COLUMN descripcion varchar(255) DEFAULT NULL;
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = 'table_devoluciones'
          AND column_name = 'motivo'
    ) THEN
        ALTER TABLE table_devoluciones ADD COLUMN motivo varchar(255) DEFAULT NULL;
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = 'table_devoluciones'
          AND column_name = 'dias_garantia_aplicado'
    ) THEN
        ALTER TABLE table_devoluciones ADD COLUMN dias_garantia_aplicado integer DEFAULT NULL;
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = 'table_devoluciones'
          AND column_name = 'dias_transcurridos'
    ) THEN
        ALTER TABLE table_devoluciones ADD COLUMN dias_transcurridos integer DEFAULT NULL;
    END IF;
END $$;

-- 4. Añadir valor 'DEVOLUCION' al enum de categorías de transacciones
-- (PG 12+ soporta ADD VALUE IF NOT EXISTS dentro de un bloque transaccional)
ALTER TYPE table_transacciones_categoria_t ADD VALUE IF NOT EXISTS 'DEVOLUCION';

-- Nota: table_kardex_tipo_movimiento_t ya incluye 'DEVOLUCION' en el schema base.
-- =====================================================================
-- FIN DE LA MIGRACIÓN
-- =====================================================================
