-- 1. Actualizar tabla de ventas para soportar pagos parciales
ALTER TABLE `table_ventas` 
ADD COLUMN `pago_efectivo` DECIMAL(10,2) DEFAULT 0.00 AFTER `total`,
ADD COLUMN `pago_transferencia` DECIMAL(10,2) DEFAULT 0.00 AFTER `pago_efectivo`,
ADD COLUMN `saldo_pendiente` DECIMAL(10,2) DEFAULT 0.00 AFTER `pago_transferencia`;

-- 2. Crear tabla para el control de Cierres de Caja (Arqueos)
CREATE TABLE IF NOT EXISTS `table_cierres_caja` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `monto_esperado` DECIMAL(10,2) NOT NULL,
  `monto_real` DECIMAL(10,2) NOT NULL,
  `diferencia` DECIMAL(10,2) NOT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_cierre_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Crear tabla para Abonos de Clientes (Cuentas por Cobrar)
CREATE TABLE IF NOT EXISTS `table_abonos_clientes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `venta_id` INT NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL,
  `metodo_pago` ENUM('EFECTIVO', 'TRANSFERENCIA') NOT NULL,
  `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_abono_venta` FOREIGN KEY (`venta_id`) REFERENCES `table_ventas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Crear tabla para registrar pagos a proveedores (Egresos detallados)
CREATE TABLE IF NOT EXISTS `table_compras_pagos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `compra_id` INT NOT NULL,
  `monto_pagado` DECIMAL(10,2) NOT NULL,
  `metodo_pago` ENUM('EFECTIVO', 'TRANSFERENCIA') NOT NULL,
  `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pago_compra` FOREIGN KEY (`compra_id`) REFERENCES `table_compras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
