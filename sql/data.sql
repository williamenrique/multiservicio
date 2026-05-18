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

