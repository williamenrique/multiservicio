-- =============================================================================
-- SCRIPT DE MIGRACIÓN PARA PRODUCCIÓN
-- Agrega tablas de EMAILS y PRESUPUESTOS a base de datos existente
-- =============================================================================
-- Ejecutar este script en la base de datos de producción existente
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

-- =============================================================================
-- BLOQUE 10: EMAILS Y COMUNICACIONES
-- =============================================================================

-- Registro de emails enviados desde el sistema
CREATE TABLE IF NOT EXISTS `table_emails` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `tipo` enum('FACTURA','PRESUPUESTO','NOTIFICACION','RECUPERACION','OTRO') NOT NULL DEFAULT 'OTRO',
  `destinatario_email` varchar(150) NOT NULL,
  `destinatario_nombre` varchar(150) DEFAULT NULL,
  `asunto` varchar(255) NOT NULL,
  `cuerpo_html` longtext NOT NULL,
  `cuerpo_texto` longtext DEFAULT NULL,
  `adjuntos` json DEFAULT NULL COMMENT 'Array de rutas de archivos adjuntos',
  `referencia_tipo` enum('FACTURA','PRESUPUESTO','ORDEN','CLIENTE','NINGUNO') DEFAULT 'NINGUNO',
  `referencia_id` int(11) DEFAULT NULL,
  `estado` enum('ENVIADO','FALLIDO','PENDIENTE') NOT NULL DEFAULT 'PENDIENTE',
  `error_mensaje` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL COMMENT 'Usuario que envió el email',
  `fecha_envio` timestamp NULL DEFAULT NULL,
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`),
  INDEX (`tipo`),
  INDEX (`estado`),
  INDEX (`fecha_creacion`),
  INDEX (`referencia_tipo`, `referencia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Plantillas de email predefinidas
CREATE TABLE IF NOT EXISTS `table_email_templates` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('FACTURA','PRESUPUESTO','NOTIFICACION','RECUPERACION','OTRO') NOT NULL DEFAULT 'OTRO',
  `asunto` varchar(255) NOT NULL,
  `cuerpo_html` longtext NOT NULL,
  `variables_disponibles` json DEFAULT NULL COMMENT 'Lista de variables que se pueden usar en la plantilla',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`tipo`),
  INDEX (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar plantillas base
INSERT INTO `table_email_templates` (`nombre`, `tipo`, `asunto`, `cuerpo_html`, `variables_disponibles`, `activo`) VALUES
('Factura de Venta', 'FACTURA', 'Factura {{numero_factura}} - {{empresa_nombre}}', 
'<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
  <h2 style="color: #1e40af;">Factura de Venta</h2>
  <p>Estimado/a {{cliente_nombre}},</p>
  <p>Adjunto encontrará la factura <strong>{{numero_factura}}</strong> por un total de <strong>{{total_formateado}}</strong>.</p>
  <p><strong>Detalles:</strong></p>
  <ul>
    <li>Fecha: {{fecha_factura}}</li>
    <li>Cliente: {{cliente_nombre}}</li>
    <li>Vehículo: {{vehiculo_placa}} {{vehiculo_marca}} {{vehiculo_modelo}}</li>
  </ul>
  <p>Gracias por su confianza en {{empresa_nombre}}.</p>
  <hr>
  <p style="font-size: 12px; color: #666;">{{empresa_nombre}} - {{empresa_nit}} - {{empresa_direccion}} - {{empresa_telefono}}</p>
</div>', 
'["numero_factura", "cliente_nombre", "total_formateado", "fecha_factura", "vehiculo_placa", "vehiculo_marca", "vehiculo_modelo", "empresa_nombre", "empresa_nit", "empresa_direccion", "empresa_telefono"]', 1),

('Presupuesto / Cotización', 'PRESUPUESTO', 'Presupuesto {{numero_presupuesto}} - {{empresa_nombre}}', 
'<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
  <h2 style="color: #1e40af;">Presupuesto / Cotización</h2>
  <p>Estimado/a {{cliente_nombre}},</p>
  <p>Le enviamos el presupuesto <strong>{{numero_presupuesto}}</strong> con validez hasta el <strong>{{fecha_vencimiento}}</strong>.</p>
  <p><strong>Total: {{total_formateado}}</strong></p>
  <p>Para aceptar este presupuesto, por favor contáctenos o responda a este correo.</p>
  <hr>
  <p style="font-size: 12px; color: #666;">{{empresa_nombre}} - {{empresa_nit}} - {{empresa_direccion}} - {{empresa_telefono}}</p>
</div>', 
'["numero_presupuesto", "cliente_nombre", "total_formateado", "fecha_vencimiento", "empresa_nombre", "empresa_nit", "empresa_direccion", "empresa_telefono"]', 1),

('Notificación General', 'NOTIFICACION', '{{asunto_personalizado}} - {{empresa_nombre}}', 
'<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
  <h2 style="color: #1e40af;">{{asunto_personalizado}}</h2>
  <p>Estimado/a {{cliente_nombre}},</p>
  <div>{{cuerpo_mensaje}}</div>
  <hr>
  <p style="font-size: 12px; color: #666;">{{empresa_nombre}} - {{empresa_nit}} - {{empresa_direccion}} - {{empresa_telefono}}</p>
</div>', 
'["asunto_personalizado", "cliente_nombre", "cuerpo_mensaje", "empresa_nombre", "empresa_nit", "empresa_direccion", "empresa_telefono"]', 1);

-- =============================================================================
-- BLOQUE 11: PRESUPUESTOS / COTIZACIONES
-- =============================================================================

-- Cabecera de presupuestos
CREATE TABLE IF NOT EXISTS `table_presupuestos` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `numero` varchar(20) NOT NULL COMMENT 'Formato: PRES-YYYY-XXXX',
  `cliente_id` varchar(50) DEFAULT NULL COMMENT 'Referencia a table_clientes.id (varchar)',
  `cliente_nombre` varchar(150) NOT NULL,
  `cliente_cedula` varchar(20) DEFAULT NULL,
  `cliente_telefono` varchar(20) DEFAULT NULL,
  `cliente_email` varchar(150) DEFAULT NULL,
  `cliente_direccion` text DEFAULT NULL,
  `vehiculo_placa` varchar(20) DEFAULT NULL,
  `vehiculo_marca` varchar(50) DEFAULT NULL,
  `vehiculo_modelo` varchar(100) DEFAULT NULL,
  `vehiculo_anio` int(4) DEFAULT NULL,
  `vehiculo_color` varchar(30) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `iva_monto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `iva_activo` tinyint(1) DEFAULT 1 COMMENT 'Si se aplica IVA',
  `tasa_iva` decimal(5,2) DEFAULT 19.00 COMMENT 'Porcentaje de IVA aplicado',
  `estado` enum('BORRADOR','ENVIADO','ACEPTADO','RECHAZADO','EXPIRADO','CONVERTIDO') DEFAULT 'BORRADOR',
  `validez_dias` int(11) DEFAULT 30 COMMENT 'Días de validez del presupuesto',
  `fecha_emision` date NOT NULL DEFAULT (CURRENT_DATE),
  `fecha_vencimiento` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `condiciones` text DEFAULT NULL COMMENT 'Términos y condiciones del presupuesto',
  `usuario_id` int(11) NOT NULL COMMENT 'Usuario que creó el presupuesto',
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`),
  UNIQUE KEY `uk_numero` (`numero`),
  INDEX (`cliente_id`),
  INDEX (`estado`),
  INDEX (`fecha_emision`),
  INDEX (`fecha_vencimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Detalle de items del presupuesto
CREATE TABLE IF NOT EXISTS `table_presupuestos_detalle` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `presupuesto_id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL COMMENT 'NULL si es servicio manual',
  `tipo_item` enum('PRODUCTO','SERVICIO') NOT NULL DEFAULT 'PRODUCTO',
  `descripcion` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(15,2) NOT NULL DEFAULT 0.00,
  `descuento_porcentaje` decimal(5,2) DEFAULT 0.00 COMMENT 'Descuento por item',
  `descuento_monto` decimal(15,2) DEFAULT 0.00 COMMENT 'Descuento en moneda',
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Cantidad * Precio - Descuento',
  `iva_porcentaje` decimal(5,2) DEFAULT 0.00 COMMENT 'IVA aplicado a este item',
  `iva_monto` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Subtotal + IVA',
  `orden_visual` int(11) DEFAULT 0 COMMENT 'Orden de visualización',
  `notas` text DEFAULT NULL COMMENT 'Notas específicas del item',
  FOREIGN KEY (`presupuesto_id`) REFERENCES `table_presupuestos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `table_inventario`(`id`),
  INDEX (`presupuesto_id`),
  INDEX (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- FIN DEL SCRIPT DE MIGRACIÓN
-- =============================================================================