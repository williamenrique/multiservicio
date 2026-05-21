

ALTER TABLE `table_staff` 
ADD COLUMN `foto` TEXT NULL DEFAULT NULL AFTER `direccion`,
ADD COLUMN `foto_frente` TEXT NULL DEFAULT NULL AFTER `foto`;

ALTER TABLE `table_company_settings` ADD COLUMN `markup_default` DECIMAL(5,2) DEFAULT 30.00 AFTER `iva`;

ALTER TABLE `table_inventario` 
ADD COLUMN `ultimo_costo` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `stock`;

CREATE TABLE IF NOT EXISTS `table_recuperaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `tipo` VARCHAR(50) NOT NULL DEFAULT 'RECUPERACION',
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  -- Relación con la tabla de usuarios para integridad de datos
  CONSTRAINT `fk_recuperaciones_usuario` 
    FOREIGN KEY (`usuario_id`) 
    REFERENCES `table_usuarios` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
