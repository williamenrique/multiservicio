-- Tabla para el control de pagos y adelantos de empleados
CREATE TABLE IF NOT EXISTS `table_pagos_empleados` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` VARCHAR(50) NOT NULL,
  `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `monto` DECIMAL(15,2) NOT NULL,
  `tipo` ENUM('ADELANTO', 'PAGO_NOMINA') NOT NULL,
  `metodo_pago` ENUM('EFECTIVO', 'TRANSFERENCIA') NOT NULL DEFAULT 'EFECTIVO',
  `notas` TEXT,
  `usuario_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`staff_id`) REFERENCES `table_staff`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;