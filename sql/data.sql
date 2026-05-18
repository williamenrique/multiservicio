CREATE TABLE `table_ventas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` varchar(20) DEFAULT NULL,
  `placa` varchar(20) DEFAULT NULL,
  `modelo_vehiculo` varchar(100) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `iva_monto` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `status` enum('PENDIENTE','COMPLETADO','CANCELADO') DEFAULT 'PENDIENTE',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_venta_cliente` (`cliente_id`),
  KEY `fk_venta_usuario` (`usuario_id`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `table_clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Tabla de Compras (Entrada de Mercancía y Deudas)
CREATE TABLE IF NOT EXISTS `table_compras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor_id` varchar(50) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `pagado` decimal(15,2) DEFAULT 0.00,
  `fecha_vencimiento` date DEFAULT NULL, -- Fecha de cobro/corte
  `status` enum('PENDIENTE', 'PAGADO') DEFAULT 'PENDIENTE',
  `usuario_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_compra_proveedor` (`proveedor_id`),
  KEY `fk_compra_usuario` (`usuario_id`),
  CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `table_proveedores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 13. Detalle de Compras
CREATE TABLE IF NOT EXISTS `table_compras_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compra_id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL, -- NULL si es producto nuevo en esta compra
  `descripcion` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `costo_unitario` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalle_compra` (`compra_id`),
  KEY `fk_detalle_producto_compra` (`producto_id`),
  CONSTRAINT `fk_detalle_compra` FOREIGN KEY (`compra_id`) REFERENCES `table_compras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_detalle_producto_compra` FOREIGN KEY (`producto_id`) REFERENCES `table_inventario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
