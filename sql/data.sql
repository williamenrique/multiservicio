CREATE TABLE IF NOT EXISTS `table_gastos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT 'EFECTIVO', -- Efectivo, Transferencia, Tarjeta
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_gasto_usuario` (`usuario_id`),
  CONSTRAINT `fk_gasto_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `table_company_settings` 
ADD COLUMN `logo` TEXT DEFAULT NULL AFTER `iva`;
