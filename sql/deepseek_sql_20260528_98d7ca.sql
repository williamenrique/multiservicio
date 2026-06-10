-- =====================================================
-- SCRIPT DE DATOS DE PRUEBA - MULTISERVICIO TALLER PRO
-- FECHAS: ABRIL Y MAYO 2024
-- TODO EN MAYUSCULAS SIN ACENTOS
-- =====================================================

USE `multiservicio`;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. LIMPIEZA DE DATOS DE PRUEBA (SIN AFECTAR ESTRUCTURA)
-- =====================================================
DELETE FROM `table_devoluciones`;
DELETE FROM `table_audit_logs`;
DELETE FROM `table_recuperaciones`;
DELETE FROM `table_abonos_clientes`;
DELETE FROM `table_compras_pagos`;
DELETE FROM `table_caja_movimientos`;
DELETE FROM `table_sesiones_caja`;
DELETE FROM `table_orden_checklist`;
DELETE FROM `table_orden_estados_log`;
DELETE FROM `table_ventas_detalle`;
DELETE FROM `table_compras_detalle`;
DELETE FROM `table_kardex`;
DELETE FROM `table_inventario_compatibilidad`;
DELETE FROM `table_gastos`;
DELETE FROM `table_ventas`;
DELETE FROM `table_compras`;
DELETE FROM `table_ordenes_servicio`;
DELETE FROM `table_vehiculos`;
DELETE FROM `table_clientes`;
DELETE FROM `table_proveedores`;
DELETE FROM `table_inventario`;

-- =====================================================
-- 2. PROVEEDORES (10)
-- =====================================================
INSERT INTO `table_proveedores` (`id`, `nombre`, `telefono`, `email`, `direccion`) VALUES
('PROV-001', 'AUTOREPUESTOS LA ECONOMIA', '04121234567', 'VENTAS@AUTOREPUESTOS.COM', 'AV PRINCIPAL LOCAL 1 CARACAS'),
('PROV-002', 'DISTRIBUIDORA DE ACEITES UNIDOS', '04127654321', 'ACEITES@DISTRIBUIDORA.COM', 'CALLE 2 URB INDUSTRIAL MARACAIBO'),
('PROV-003', 'REPUESTOS ORIGINALES C.A.', '04241234567', 'VENTAS@REPUESTOSORIGINALES.COM', 'AV FERROCARRIL VALENCIA'),
('PROV-004', 'LLANTAS Y NEUMATICOS DEL OESTE', '04143456789', 'LLANTAS@NEUMATICOSOESTE.COM', 'CARRETERA PANAMERICANA BARQUISIMETO'),
('PROV-005', 'ELECTRICIDAD AUTOMOTRIZ TOTAL', '04263456789', 'ELECTRICO@AUTOMOTRIZTOTAL.COM', 'CALLE COMERCIAL PUERTO LA CRUZ'),
('PROV-006', 'FRENOS Y SUSPENSIONES PRO', '04165432109', 'FRENOS@SUSPENSIONESPRO.COM', 'AV BOLIVAR SAN CRISTOBAL'),
('PROV-007', 'MOTORES Y PARTES DEL CENTRO', '04275432109', 'MOTORES@PARTESCENTRO.COM', 'CALLE PRINCIPAL BARCELONA'),
('PROV-008', 'LUBRICANTES INDUSTRIALES', '04168765432', 'LUBRICANTES@INDUSTRIALES.COM', 'AV ROMULO GALLEGOS CUMANA'),
('PROV-009', 'HERRAMIENTAS DEL TALLER', '04249876543', 'HERRAMIENTAS@TALLER.COM', 'CALLE 5 CIUDAD GUAYANA'),
('PROV-010', 'ACCESORIOS Y DECORACION AUTO', '04127654398', 'ACCESORIOS@DECORAUTO.COM', 'AV PRINCIPAL LOS TEQUES');

-- =====================================================
-- 3. CLIENTES (10)
-- =====================================================
INSERT INTO `table_clientes` (`id`, `nombre`, `email`, `telefono`, `direccion`) VALUES
('CLI-001', 'CARLOS PEREZ', 'CARLOS@GMAIL.COM', '04141234567', 'CALLE 10 URB LAS MERCEDES CARACAS'),
('CLI-002', 'MARIA GONZALEZ', 'MARIA@HOTMAIL.COM', '04147654321', 'AV PRINCIPAL BARQUISIMETO'),
('CLI-003', 'JOSE RODRIGUEZ', 'JOSE@GMAIL.COM', '04243456789', 'CALLE COMERCIAL VALENCIA'),
('CLI-004', 'ANA MARTINEZ', 'ANA@YAHOO.COM', '04149876543', 'URB LA FLORIDA MARACAIBO'),
('CLI-005', 'LUIS SANCHEZ', 'LUIS@GMAIL.COM', '04261234567', 'AV INTERCOMUNAL PUERTO LA CRUZ'),
('CLI-006', 'CARMEN DIAZ', 'CARMEN@HOTMAIL.COM', '04165432109', 'CALLE PRINCIPAL SAN CRISTOBAL'),
('CLI-007', 'PEDRO GOMEZ', 'PEDRO@GMAIL.COM', '04278765432', 'URB EL PARAISO BARCELONA'),
('CLI-008', 'LAURA FERNANDEZ', 'LAURA@YAHOO.COM', '04169876543', 'AV LIBERTADOR CUMANA'),
('CLI-009', 'JORGE LOPEZ', 'JORGE@GMAIL.COM', '04248765432', 'CALLE 8 CIUDAD GUAYANA'),
('CLI-010', 'SOFIA MORALES', 'SOFIA@HOTMAIL.COM', '04127654321', 'AV UNIVERSIDAD LOS TEQUES');

-- =====================================================
-- 4. VEHICULOS (2 por cliente, 20 total)
-- =====================================================
INSERT INTO `table_vehiculos` (`placa`, `marca`, `modelo`, `anio`, `color`, `cliente_id`) VALUES
('ABC123', 'TOYOTA', 'COROLLA', 2020, 'BLANCO', 'CLI-001'),
('DEF456', 'FORD', 'FIESTA', 2019, 'GRIS', 'CLI-001'),
('GHI789', 'CHEVROLET', 'SPARK', 2021, 'ROJO', 'CLI-002'),
('JKL012', 'HYUNDAI', 'TUCSON', 2018, 'NEGRO', 'CLI-002'),
('MNO345', 'MAZDA', '3', 2022, 'AZUL', 'CLI-003'),
('PQR678', 'NISSAN', 'VERSA', 2020, 'PLATA', 'CLI-003'),
('STU901', 'VOLKSWAGEN', 'GOL', 2019, 'GRIS', 'CLI-004'),
('VWX234', 'RENAULT', 'LOGAN', 2021, 'BLANCO', 'CLI-004'),
('YZA567', 'KIA', 'SPORTAGE', 2018, 'NEGRO', 'CLI-005'),
('BCD890', 'CHEVROLET', 'CRUZE', 2022, 'ROJO', 'CLI-005'),
('EFG123', 'TOYOTA', 'HILUX', 2020, 'BLANCO', 'CLI-006'),
('HIJ456', 'FORD', 'RANGER', 2019, 'GRIS', 'CLI-006'),
('KLM789', 'HYUNDAI', 'ACCENT', 2021, 'AZUL', 'CLI-007'),
('NOP012', 'MAZDA', 'CX5', 2020, 'NEGRO', 'CLI-007'),
('QRS345', 'NISSAN', 'MARCH', 2022, 'PLATA', 'CLI-008'),
('TUV678', 'VOLKSWAGEN', 'POLO', 2021, 'ROJO', 'CLI-008'),
('WXY901', 'RENAULT', 'SANDERO', 2020, 'BLANCO', 'CLI-009'),
('ZAB234', 'KIA', 'RIO', 2019, 'GRIS', 'CLI-009'),
('CDE567', 'CHEVROLET', 'SILVERADO', 2018, 'NEGRO', 'CLI-010'),
('FGH890', 'TOYOTA', 'CAMRY', 2022, 'AZUL', 'CLI-010');

-- =====================================================
-- 5. INVENTARIO (30 ARTICULOS)
-- =====================================================
INSERT INTO `table_inventario` (`nombre`, `categoria`, `stock`, `stock_minimo`, `ultimo_costo`, `costo_promedio`, `precio`) VALUES
('ACEITE DE MOTOR 5W30 1L', 'LUBRICANTES', 50, 10, 8.50, 8.50, 15.00),
('ACEITE DE MOTOR 5W40 1L', 'LUBRICANTES', 45, 10, 9.00, 9.00, 16.00),
('FILTRO DE ACEITE', 'FILTROS', 30, 15, 5.00, 5.00, 9.00),
('FILTRO DE AIRE', 'FILTROS', 28, 15, 7.00, 7.00, 12.50),
('FILTRO DE COMBUSTIBLE', 'FILTROS', 25, 10, 6.50, 6.50, 11.00),
('PASTILLAS DE FRENO DELANTERAS', 'FRENOS', 20, 10, 25.00, 25.00, 45.00),
('PASTILLAS DE FRENO TRASERAS', 'FRENOS', 18, 10, 22.00, 22.00, 40.00),
('LIQUIDO DE FRENOS 500ML', 'FRENOS', 35, 15, 4.50, 4.50, 8.00),
('BATERIA 12V 60AH', 'ELECTRICO', 12, 5, 85.00, 85.00, 150.00),
('BATERIA 12V 75AH', 'ELECTRICO', 10, 5, 95.00, 95.00, 170.00),
('BUJIA IRIQUIM 4 UNIDADES', 'ELECTRICO', 40, 20, 12.00, 12.00, 22.00),
('CABLE DE BUJIA JUEGO', 'ELECTRICO', 15, 10, 18.00, 18.00, 32.00),
('NEUMATICO 185/65R15', 'LLANTAS', 25, 8, 45.00, 45.00, 80.00),
('NEUMATICO 195/55R16', 'LLANTAS', 20, 8, 50.00, 50.00, 90.00),
('NEUMATICO 205/55R16', 'LLANTAS', 18, 8, 55.00, 55.00, 100.00),
('AMORTIGUADOR DELANTERO', 'SUSPENSION', 14, 6, 40.00, 40.00, 75.00),
('AMORTIGUADOR TRASERO', 'SUSPENSION', 12, 6, 38.00, 38.00, 70.00),
('ROTULA DIRECCION', 'DIRECCION', 10, 5, 15.00, 15.00, 28.00),
('TERMINAL DE DIRECCION', 'DIRECCION', 12, 5, 12.00, 12.00, 22.00),
('CORREA DE DISTRIBUCION', 'MOTOR', 20, 10, 25.00, 25.00, 48.00),
('CORREA DE ACCESORIOS', 'MOTOR', 25, 10, 18.00, 18.00, 35.00),
('BOMBA DE AGUA', 'MOTOR', 8, 4, 55.00, 55.00, 100.00),
('TERMOSTATO', 'MOTOR', 15, 5, 12.00, 12.00, 22.00),
('REFACCION DE REFRIGERANTE 1L', 'MOTOR', 40, 15, 6.00, 6.00, 11.00),
('FARO DELANTERO DERECHO', 'ILUMINACION', 10, 5, 35.00, 35.00, 65.00),
('FARO DELANTERO IZQUIERDO', 'ILUMINACION', 10, 5, 35.00, 35.00, 65.00),
('BOMBILLO LED H4', 'ILUMINACION', 50, 20, 8.00, 8.00, 15.00),
('LIMPIADORES DE PARABRISAS JUEGO', 'ACCESORIOS', 30, 10, 10.00, 10.00, 18.00),
('ESPUMA LIMPIADORA DE MOTOR', 'QUIMICOS', 25, 10, 7.00, 7.00, 14.00),
('ADITIVO LIMPIADOR DE INYECTORES', 'QUIMICOS', 35, 15, 9.00, 9.00, 17.00);

-- =====================================================
-- 6. ORDENES DE SERVICIO (10)
-- =====================================================
INSERT INTO `table_ordenes_servicio` (`vehiculo_id`, `usuario_id`, `kilometraje`, `nivel_combustible`, `observaciones_entrada`, `estado`, `fecha_entrada`, `fecha_entrega_estimada`, `total_estimado`) VALUES
(1, 1, 25000, '1/2', 'REVISION GENERAL, RUIDO EN FRENOS', 'ENTREGADO', '2024-04-05 09:00:00', '2024-04-07 17:00:00', 85.00),
(2, 1, 18000, '3/4', 'CAMBIO DE ACEITE Y FILTROS', 'ENTREGADO', '2024-04-10 10:30:00', '2024-04-11 16:00:00', 45.00),
(3, 1, 32000, '1/4', 'REVISION DE SUSPENSION Y DIRECCION', 'ENTREGADO', '2024-04-15 08:15:00', '2024-04-18 18:00:00', 150.00),
(4, 1, 15000, '1/2', 'ALINEACION Y BALANCEO', 'ENTREGADO', '2024-04-20 11:00:00', '2024-04-20 17:00:00', 60.00),
(5, 1, 28000, '3/4', 'REVISION ELECTRICA, LUCES NO ENCIENDEN', 'ENTREGADO', '2024-04-25 14:00:00', '2024-04-27 16:00:00', 95.00),
(6, 1, 35000, '1/2', 'CAMBIO DE BATERIA Y BUJIAS', 'ENTREGADO', '2024-05-02 09:30:00', '2024-05-03 17:00:00', 200.00),
(7, 1, 22000, '1/4', 'REVISION DE MOTOR, RUIDO EXTRAÑO', 'ENTREGADO', '2024-05-08 08:00:00', '2024-05-12 18:00:00', 180.00),
(8, 1, 41000, '3/4', 'CAMBIO DE NEUMATICOS Y FRENOS', 'ENTREGADO', '2024-05-12 13:00:00', '2024-05-14 17:00:00', 350.00),
(9, 1, 19500, '1/2', 'MANTENIMIENTO PREVENTIVO', 'ENTREGADO', '2024-05-18 10:00:00', '2024-05-19 16:00:00', 70.00),
(10, 1, 52000, '1/2', 'REPARACION DE SISTEMA DE REFRIGERACION', 'ENTREGADO', '2024-05-25 15:00:00', '2024-05-30 18:00:00', 250.00);

-- =====================================================
-- 7. VENTAS (15 ventas)
-- =====================================================
-- Venta 1 - Abril 2024
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `orden_id`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-001', 'ABC123', 1, 'TOYOTA COROLLA', 50.00, 9.50, 59.50, 59.50, 0.00, 0.00, 1, 'COMPLETADO', '2024-04-07 16:30:00', '2024-04-07 16:30:00');

-- Venta 2 - Abril 2024
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `orden_id`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-002', 'GHI789', 3, 'CHEVROLET SPARK', 120.00, 22.80, 142.80, 142.80, 0.00, 0.00, 1, 'COMPLETADO', '2024-04-18 15:45:00', '2024-04-18 15:45:00');

-- Venta 3 - Abril 2024
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `orden_id`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-003', 'MNO345', 2, 'MAZDA 3', 38.00, 7.22, 45.22, 45.22, 0.00, 0.00, 1, 'COMPLETADO', '2024-04-11 14:20:00', '2024-04-11 14:20:00');

-- Venta 4 - Abril 2024
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `orden_id`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-004', 'STU901', 4, 'VOLKSWAGEN GOL', 50.00, 9.50, 59.50, 30.00, 0.00, 29.50, 1, 'CREDITO', '2024-04-20 16:00:00', NULL);

-- Venta 5 - Abril 2024
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `orden_id`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-005', 'YZA567', 5, 'KIA SPORTAGE', 80.00, 15.20, 95.20, 95.20, 0.00, 0.00, 1, 'COMPLETADO', '2024-04-27 17:30:00', '2024-04-27 17:30:00');

-- Venta 6 - Mayo 2024
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `orden_id`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-006', 'EFG123', 6, 'TOYOTA HILUX', 168.00, 31.92, 199.92, 100.00, 99.92, 0.00, 1, 'COMPLETADO', '2024-05-03 15:00:00', '2024-05-03 15:00:00');

-- Venta 7 - Mayo 2024
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `orden_id`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-007', 'KLM789', 7, 'HYUNDAI ACCENT', 151.00, 28.69, 179.69, 179.69, 0.00, 0.00, 1, 'COMPLETADO', '2024-05-12 17:15:00', '2024-05-12 17:15:00');

-- Venta 8 - Mayo 2024
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `orden_id`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-008', 'QRS345', 8, 'NISSAN MARCH', 294.00, 55.86, 349.86, 349.86, 0.00, 0.00, 1, 'COMPLETADO', '2024-05-14 18:00:00', '2024-05-14 18:00:00');

-- Venta 9 - Mayo 2024
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `orden_id`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-009', 'WXY901', 9, 'RENAULT SANDERO', 58.80, 11.17, 69.97, 69.97, 0.00, 0.00, 1, 'COMPLETADO', '2024-05-19 14:45:00', '2024-05-19 14:45:00');

-- Venta 10 - Mayo 2024
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `orden_id`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-010', 'CDE567', 10, 'CHEVROLET SILVERADO', 210.00, 39.90, 249.90, 150.00, 0.00, 99.90, 1, 'CREDITO', '2024-05-30 16:30:00', NULL);

-- Venta 11 - Mayo 2024 (Venta mostrador sin orden)
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-001', 'DEF456', 'FORD FIESTA', 45.00, 8.55, 53.55, 53.55, 0.00, 0.00, 1, 'COMPLETADO', '2024-05-05 11:20:00', '2024-05-05 11:20:00');

-- Venta 12 - Mayo 2024 (Venta mostrador)
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-003', 'PQR678', 'NISSAN VERSA', 65.00, 12.35, 77.35, 0.00, 77.35, 0.00, 1, 'COMPLETADO', '2024-05-15 10:00:00', '2024-05-15 10:00:00');

-- Venta 13 - Mayo 2024
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-005', 'BCD890', 'CHEVROLET CRUZE', 90.00, 17.10, 107.10, 107.10, 0.00, 0.00, 1, 'COMPLETADO', '2024-05-20 15:30:00', '2024-05-20 15:30:00');

-- Venta 14 - Mayo 2024 (Crédito)
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-007', 'NOP012', 'MAZDA CX5', 75.00, 14.25, 89.25, 40.00, 0.00, 49.25, 1, 'CREDITO', '2024-05-22 12:00:00', NULL);

-- Venta 15 - Mayo 2024
INSERT INTO `table_ventas` (`cliente_id`, `placa`, `modelo_vehiculo`, `subtotal`, `iva_monto`, `total`, `pago_efectivo`, `pago_transferencia`, `saldo_pendiente`, `usuario_id`, `status`, `fecha`, `fecha_cierre`) VALUES
('CLI-009', 'ZAB234', 'KIA RIO', 42.00, 7.98, 49.98, 49.98, 0.00, 0.00, 1, 'COMPLETADO', '2024-05-28 09:45:00', '2024-05-28 09:45:00');

-- =====================================================
-- 8. DETALLE DE VENTAS
-- =====================================================
-- Venta 1
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(1, 3, 'FILTRO DE ACEITE', 2, 9.00, 5.00),
(1, 6, 'PASTILLAS DE FRENO DELANTERAS', 1, 45.00, 25.00);

-- Venta 2
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(2, 16, 'AMORTIGUADOR DELANTERO', 2, 75.00, 40.00);

-- Venta 3
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(3, 1, 'ACEITE DE MOTOR 5W30 1L', 4, 15.00, 8.50);

-- Venta 4
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(4, 20, 'CORREA DE DISTRIBUCION', 1, 48.00, 25.00);

-- Venta 5
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(5, 18, 'ROTULA DIRECCION', 2, 28.00, 15.00),
(5, 19, 'TERMINAL DE DIRECCION', 2, 22.00, 12.00);

-- Venta 6
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(6, 9, 'BATERIA 12V 60AH', 1, 150.00, 85.00),
(6, 11, 'BUJIA IRIQUIM 4 UNIDADES', 1, 22.00, 12.00),
(6, 8, 'LIQUIDO DE FRENOS 500ML', 2, 8.00, 4.50);

-- Venta 7
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(7, 21, 'CORREA DE ACCESORIOS', 1, 35.00, 18.00),
(7, 22, 'BOMBA DE AGUA', 1, 100.00, 55.00),
(7, 24, 'REFACCION DE REFRIGERANTE 1L', 2, 11.00, 6.00);

-- Venta 8
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(8, 13, 'NEUMATICO 185/65R15', 2, 80.00, 45.00),
(8, 6, 'PASTILLAS DE FRENO DELANTERAS', 1, 45.00, 25.00),
(8, 7, 'PASTILLAS DE FRENO TRASERAS', 1, 40.00, 22.00);

-- Venta 9
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(9, 2, 'ACEITE DE MOTOR 5W40 1L', 5, 16.00, 9.00);

-- Venta 10
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(10, 15, 'NEUMATICO 205/55R16', 2, 100.00, 55.00);

-- Venta 11
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(11, 4, 'FILTRO DE AIRE', 2, 12.50, 7.00),
(11, 3, 'FILTRO DE ACEITE', 1, 9.00, 5.00),
(11, 30, 'ADITIVO LIMPIADOR DE INYECTORES', 1, 17.00, 9.00);

-- Venta 12
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(12, 25, 'FARO DELANTERO DERECHO', 1, 65.00, 35.00);

-- Venta 13
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(13, 28, 'LIMPIADORES DE PARABRISAS JUEGO', 2, 18.00, 10.00),
(13, 29, 'ESPUMA LIMPIADORA DE MOTOR', 2, 14.00, 7.00),
(13, 27, 'BOMBILLO LED H4', 3, 15.00, 8.00);

-- Venta 14
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(14, 17, 'AMORTIGUADOR TRASERO', 1, 70.00, 38.00);

-- Venta 15
INSERT INTO `table_ventas_detalle` (`venta_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `costo_unitario`) VALUES
(15, 5, 'FILTRO DE COMBUSTIBLE', 1, 11.00, 6.50),
(15, 1, 'ACEITE DE MOTOR 5W30 1L', 2, 15.00, 8.50);

-- =====================================================
-- 9. ABONOS DE CLIENTES (Para ventas en crédito)
-- =====================================================
-- Abono para venta 4 (30 pesos pagados en efectivo)
INSERT INTO `table_abonos_clientes` (`venta_id`, `monto`, `metodo_pago`, `fecha`) VALUES
(4, 30.00, 'EFECTIVO', '2024-04-25 10:00:00');

-- Abono para venta 10 (150 pesos pagados en efectivo)
INSERT INTO `table_abonos_clientes` (`venta_id`, `monto`, `metodo_pago`, `fecha`) VALUES
(10, 150.00, 'EFECTIVO', '2024-06-01 14:30:00');

-- Abono para venta 14 (40 pesos pagados en efectivo)
INSERT INTO `table_abonos_clientes` (`venta_id`, `monto`, `metodo_pago`, `fecha`) VALUES
(14, 40.00, 'EFECTIVO', '2024-05-25 09:15:00');

-- =====================================================
-- 10. COMPRAS A PROVEEDORES (5 compras)
-- =====================================================
INSERT INTO `table_compras` (`proveedor_id`, `total`, `pagado`, `fecha_vencimiento`, `status`, `usuario_id`, `fecha`) VALUES
('PROV-001', 450.00, 450.00, '2024-05-15', 'PAGADO', 1, '2024-04-20 10:00:00'),
('PROV-003', 780.00, 400.00, '2024-05-30', 'PENDIENTE', 1, '2024-04-25 14:30:00'),
('PROV-005', 350.00, 350.00, '2024-06-05', 'PAGADO', 1, '2024-05-05 09:00:00'),
('PROV-007', 920.00, 0.00, '2024-06-15', 'PENDIENTE', 1, '2024-05-15 11:45:00'),
('PROV-010', 275.00, 275.00, '2024-06-20', 'PAGADO', 1, '2024-05-20 15:30:00');

-- =====================================================
-- 11. DETALLE DE COMPRAS
-- =====================================================
INSERT INTO `table_compras_detalle` (`compra_id`, `producto_id`, `descripcion`, `cantidad`, `costo_unitario`) VALUES
(1, 1, 'ACEITE DE MOTOR 5W30 1L', 30, 8.50),
(1, 2, 'ACEITE DE MOTOR 5W40 1L', 20, 9.00);

INSERT INTO `table_compras_detalle` (`compra_id`, `producto_id`, `descripcion`, `cantidad`, `costo_unitario`) VALUES
(2, 6, 'PASTILLAS DE FRENO DELANTERAS', 15, 25.00),
(2, 7, 'PASTILLAS DE FRENO TRASERAS', 12, 22.00);

INSERT INTO `table_compras_detalle` (`compra_id`, `producto_id`, `descripcion`, `cantidad`, `costo_unitario`) VALUES
(3, 11, 'BUJIA IRIQUIM 4 UNIDADES', 25, 12.00),
(3, 12, 'CABLE DE BUJIA JUEGO', 8, 18.00);

INSERT INTO `table_compras_detalle` (`compra_id`, `producto_id`, `descripcion`, `cantidad`, `costo_unitario`) VALUES
(4, 22, 'BOMBA DE AGUA', 10, 55.00),
(4, 20, 'CORREA DE DISTRIBUCION', 15, 25.00);

INSERT INTO `table_compras_detalle` (`compra_id`, `producto_id`, `descripcion`, `cantidad`, `costo_unitario`) VALUES
(5, 28, 'LIMPIADORES DE PARABRISAS JUEGO', 20, 10.00),
(5, 30, 'ADITIVO LIMPIADOR DE INYECTORES', 15, 9.00);

-- =====================================================
-- 12. PAGOS A PROVEEDORES
-- =====================================================
INSERT INTO `table_compras_pagos` (`compra_id`, `monto_pagado`, `metodo_pago`, `fecha`) VALUES
(2, 400.00, 'EFECTIVO', '2024-05-10 14:00:00');

-- =====================================================
-- 13. GASTOS (10 gastos)
-- =====================================================
INSERT INTO `table_gastos` (`fecha`, `descripcion`, `categoria`, `monto`, `metodo_pago`, `usuario_id`) VALUES
('2024-04-03', 'PAGO DE ALQUILER LOCAL', 'ALQUILER', 350.00, 'TRANSFERENCIA', 1),
('2024-04-10', 'COMPRA DE HERRAMIENTAS', 'HERRAMIENTAS', 120.00, 'EFECTIVO', 1),
('2024-04-18', 'PAGO SERVICIO ELECTRICO', 'SERVICIOS', 85.00, 'TRANSFERENCIA', 1),
('2024-04-25', 'COMPRA DE EPP SEGURIDAD', 'SEGURIDAD', 45.00, 'EFECTIVO', 1),
('2024-05-02', 'PAGO DE SUELDOS', 'NOMINA', 1200.00, 'TRANSFERENCIA', 1),
('2024-05-08', 'MANTENIMIENTO DE EQUIPOS', 'MANTENIMIENTO', 150.00, 'EFECTIVO', 1),
('2024-05-15', 'COMPRA DE MATERIAL DE LIMPIEZA', 'LIMPIEZA', 35.00, 'EFECTIVO', 1),
('2024-05-20', 'PAGO SERVICIO DE AGUA', 'SERVICIOS', 40.00, 'TRANSFERENCIA', 1),
('2024-05-25', 'COMPRA DE SUMINISTROS OFICINA', 'OFICINA', 60.00, 'EFECTIVO', 1),
('2024-05-28', 'PAGO INTERNET Y TELEFONIA', 'SERVICIOS', 55.00, 'TRANSFERENCIA', 1);

-- =====================================================
-- 14. KARDEX (Movimientos de inventario)
-- =====================================================
-- Stock iniciales ya definidos en INSERT de inventario
-- Entradas por compras
INSERT INTO `table_kardex` (`producto_id`, `tipo_movimiento`, `cantidad`, `stock_anterior`, `stock_actual`, `referencia_id`, `usuario_id`, `observaciones`, `fecha`) VALUES
(1, 'ENTRADA_COMPRA', 30, 20, 50, 'COMP-1', 1, 'COMPRA #1 A AUTOREPUESTOS', '2024-04-20 10:00:00'),
(2, 'ENTRADA_COMPRA', 20, 25, 45, 'COMP-1', 1, 'COMPRA #1 A AUTOREPUESTOS', '2024-04-20 10:00:00'),
(6, 'ENTRADA_COMPRA', 15, 5, 20, 'COMP-2', 1, 'COMPRA #2 A REPUESTOS ORIGINALES', '2024-04-25 14:30:00'),
(7, 'ENTRADA_COMPRA', 12, 6, 18, 'COMP-2', 1, 'COMPRA #2 A REPUESTOS ORIGINALES', '2024-04-25 14:30:00'),
(11, 'ENTRADA_COMPRA', 25, 15, 40, 'COMP-3', 1, 'COMPRA #3 A ELECTRICIDAD AUTOMOTRIZ', '2024-05-05 09:00:00'),
(12, 'ENTRADA_COMPRA', 8, 7, 15, 'COMP-3', 1, 'COMPRA #3 A ELECTRICIDAD AUTOMOTRIZ', '2024-05-05 09:00:00'),
(22, 'ENTRADA_COMPRA', 10, -2, 8, 'COMP-4', 1, 'COMPRA #4 A MOTORES Y PARTES', '2024-05-15 11:45:00'),
(20, 'ENTRADA_COMPRA', 15, 5, 20, 'COMP-4', 1, 'COMPRA #4 A MOTORES Y PARTES', '2024-05-15 11:45:00'),
(28, 'ENTRADA_COMPRA', 20, 10, 30, 'COMP-5', 1, 'COMPRA #5 A ACCESORIOS AUTO', '2024-05-20 15:30:00'),
(30, 'ENTRADA_COMPRA', 15, 20, 35, 'COMP-5', 1, 'COMPRA #5 A ACCESORIOS AUTO', '2024-05-20 15:30:00');

-- Salidas por ventas (actualización de stock según detalle)
UPDATE `table_inventario` SET `stock` = `stock` - 2 WHERE `id` = 3;
UPDATE `table_inventario` SET `stock` = `stock` - 1 WHERE `id` = 6;
UPDATE `table_inventario` SET `stock` = `stock` - 2 WHERE `id` = 16;
UPDATE `table_inventario` SET `stock` = `stock` - 4 WHERE `id` = 1;
UPDATE `table_inventario` SET `stock` = `stock` - 1 WHERE `id` = 20;
UPDATE `table_inventario` SET `stock` = `stock` - 2 WHERE `id` = 18;
UPDATE `table_inventario` SET `stock` = `stock` - 2 WHERE `id` = 19;
UPDATE `table_inventario` SET `stock` = `stock` - 1 WHERE `id` = 9;
UPDATE `table_inventario` SET `stock` = `stock` - 1 WHERE `id` = 11;
UPDATE `table_inventario` SET `stock` = `stock` - 2 WHERE `id` = 8;
UPDATE `table_inventario` SET `stock` = `stock` - 1 WHERE `id` = 21;
UPDATE `table_inventario` SET `stock` = `stock` - 1 WHERE `id` = 22;
UPDATE `table_inventario` SET `stock` = `stock` - 2 WHERE `id` = 24;
UPDATE `table_inventario` SET `stock` = `stock` - 2 WHERE `id` = 13;
UPDATE `table_inventario` SET `stock` = `stock` - 1 WHERE `id` = 7;
UPDATE `table_inventario` SET `stock` = `stock` - 5 WHERE `id` = 2;
UPDATE `table_inventario` SET `stock` = `stock` - 2 WHERE `id` = 15;
UPDATE `table_inventario` SET `stock` = `stock` - 2 WHERE `id` = 4;
UPDATE `table_inventario` SET `stock` = `stock` - 1 WHERE `id` = 30;
UPDATE `table_inventario` SET `stock` = `stock` - 1 WHERE `id` = 25;
UPDATE `table_inventario` SET `stock` = `stock` - 2 WHERE `id` = 28;
UPDATE `table_inventario` SET `stock` = `stock` - 2 WHERE `id` = 29;
UPDATE `table_inventario` SET `stock` = `stock` - 3 WHERE `id` = 27;
UPDATE `table_inventario` SET `stock` = `stock` - 1 WHERE `id` = 17;
UPDATE `table_inventario` SET `stock` = `stock` - 1 WHERE `id` = 5;

-- =====================================================
-- 15. SESION DE CAJA (Apertura y cierre)
-- =====================================================
INSERT INTO `table_sesiones_caja` (`usuario_id`, `fecha_apertura`, `fecha_cierre`, `monto_inicial`, `monto_final_esperado`, `monto_final_real`, `diferencia`, `estado`) VALUES
(1, '2024-04-01 08:00:00', '2024-04-30 18:00:00', 500.00, 962.72, 962.72, 0.00, 'CERRADA'),
(1, '2024-05-01 08:00:00', '2024-05-31 18:00:00', 500.00, 1417.70, 1417.70, 0.00, 'CERRADA');

-- =====================================================
-- 16. MOVIMIENTOS DE CAJA
-- =====================================================
-- Abril - Ingresos por ventas (Efectivo y Transferencia)
INSERT INTO `table_caja_movimientos` (`sesion_id`, `tipo`, `monto`, `metodo_pago`, `referencia_id`, `concepto`, `fecha`) VALUES
(1, 'INGRESO', 59.50, 'EFECTIVO', 1, 'VENTA #1 - CLIENTE CARLOS PEREZ', '2024-04-07 16:30:00'),
(1, 'INGRESO', 142.80, 'EFECTIVO', 2, 'VENTA #2 - CLIENTE MARIA GONZALEZ', '2024-04-18 15:45:00'),
(1, 'INGRESO', 45.22, 'EFECTIVO', 3, 'VENTA #3 - CLIENTE JOSE RODRIGUEZ', '2024-04-11 14:20:00'),
(1, 'INGRESO', 30.00, 'EFECTIVO', 4, 'ABONO VENTA #4 - CLIENTE ANA MARTINEZ', '2024-04-25 10:00:00'),
(1, 'INGRESO', 95.20, 'EFECTIVO', 5, 'VENTA #5 - CLIENTE LUIS SANCHEZ', '2024-04-27 17:30:00');

-- Abril - Egresos por gastos
INSERT INTO `table_caja_movimientos` (`sesion_id`, `tipo`, `monto`, `metodo_pago`, `concepto`, `fecha`) VALUES
(1, 'EGRESO', 350.00, 'TRANSFERENCIA', 'PAGO ALQUILER ABRIL', '2024-04-03 09:00:00'),
(1, 'EGRESO', 120.00, 'EFECTIVO', 'COMPRA DE HERRAMIENTAS', '2024-04-10 11:00:00'),
(1, 'EGRESO', 85.00, 'TRANSFERENCIA', 'PAGO SERVICIO ELECTRICO', '2024-04-18 13:30:00'),
(1, 'EGRESO', 45.00, 'EFECTIVO', 'COMPRA DE EPP SEGURIDAD', '2024-04-25 15:00:00');

-- Mayo - Ingresos por ventas
INSERT INTO `table_caja_movimientos` (`sesion_id`, `tipo`, `monto`, `metodo_pago`, `referencia_id`, `concepto`, `fecha`) VALUES
(2, 'INGRESO', 100.00, 'EFECTIVO', 6, 'VENTA #6 - CLIENTE CARMEN DIAZ', '2024-05-03 15:00:00'),
(2, 'INGRESO', 99.92, 'TRANSFERENCIA', 6, 'VENTA #6 - CLIENTE CARMEN DIAZ (TRANSFERENCIA)', '2024-05-03 15:00:00'),
(2, 'INGRESO', 179.69, 'EFECTIVO', 7, 'VENTA #7 - CLIENTE PEDRO GOMEZ', '2024-05-12 17:15:00'),
(2, 'INGRESO', 349.86, 'EFECTIVO', 8, 'VENTA #8 - CLIENTE LAURA FERNANDEZ', '2024-05-14 18:00:00'),
(2, 'INGRESO', 69.97, 'EFECTIVO', 9, 'VENTA #9 - CLIENTE JORGE LOPEZ', '2024-05-19 14:45:00'),
(2, 'INGRESO', 150.00, 'EFECTIVO', 10, 'ABONO VENTA #10 - CLIENTE SOFIA MORALES', '2024-06-01 14:30:00'),
(2, 'INGRESO', 53.55, 'EFECTIVO', 11, 'VENTA #11 - CLIENTE CARLOS PEREZ', '2024-05-05 11:20:00'),
(2, 'INGRESO', 77.35, 'TRANSFERENCIA', 12, 'VENTA #12 - CLIENTE JOSE RODRIGUEZ', '2024-05-15 10:00:00'),
(2, 'INGRESO', 107.10, 'EFECTIVO', 13, 'VENTA #13 - CLIENTE LUIS SANCHEZ', '2024-05-20 15:30:00'),
(2, 'INGRESO', 40.00, 'EFECTIVO', 14, 'ABONO VENTA #14 - CLIENTE PEDRO GOMEZ', '2024-05-25 09:15:00'),
(2, 'INGRESO', 49.98, 'EFECTIVO', 15, 'VENTA #15 - CLIENTE JORGE LOPEZ', '2024-05-28 09:45:00');

-- Mayo - Egresos por gastos
INSERT INTO `table_caja_movimientos` (`sesion_id`, `tipo`, `monto`, `metodo_pago`, `concepto`, `fecha`) VALUES
(2, 'EGRESO', 1200.00, 'TRANSFERENCIA', 'PAGO DE SUELDOS MAYO', '2024-05-02 08:30:00'),
(2, 'EGRESO', 150.00, 'EFECTIVO', 'MANTENIMIENTO DE EQUIPOS', '2024-05-08 10:00:00'),
(2, 'EGRESO', 35.00, 'EFECTIVO', 'COMPRA DE MATERIAL DE LIMPIEZA', '2024-05-15 09:00:00'),
(2, 'EGRESO', 40.00, 'TRANSFERENCIA', 'PAGO SERVICIO AGUA', '2024-05-20 11:30:00'),
(2, 'EGRESO', 60.00, 'EFECTIVO', 'COMPRA SUMINISTROS OFICINA', '2024-05-25 14:00:00'),
(2, 'EGRESO', 55.00, 'TRANSFERENCIA', 'PAGO INTERNET Y TELEFONIA', '2024-05-28 16:00:00');

-- =====================================================
-- 17. AUDIT LOGS (Registro de actividades)
-- =====================================================
INSERT INTO `table_audit_logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `fecha`) VALUES
(1, 'INVENTARIO', 'INSERCION', 'CARGA INICIAL DE 30 PRODUCTOS', '192.168.1.1', '2024-04-01 08:00:00'),
(1, 'CLIENTES', 'INSERCION', 'CARGA DE 10 CLIENTES', '192.168.1.1', '2024-04-01 08:15:00'),
(1, 'PROVEEDORES', 'INSERCION', 'CARGA DE 10 PROVEEDORES', '192.168.1.1', '2024-04-01 08:30:00'),
(1, 'VENTAS', 'INSERCION', 'REGISTRO DE VENTA #1', '192.168.1.1', '2024-04-07 16:30:00'),
(1, 'VENTAS', 'INSERCION', 'REGISTRO DE VENTA #2', '192.168.1.1', '2024-04-18 15:45:00'),
(1, 'COMPRAS', 'INSERCION', 'REGISTRO DE COMPRA #1', '192.168.1.1', '2024-04-20 10:00:00'),
(1, 'GASTOS', 'INSERCION', 'REGISTRO DE GASTOS ABRIL', '192.168.1.1', '2024-04-03 09:00:00'),
(1, 'CAJA', 'APERTURA', 'APERTURA DE CAJA MES ABRIL', '192.168.1.1', '2024-04-01 08:00:00'),
(1, 'CAJA', 'CIERRE', 'CIERRE DE CAJA MES ABRIL', '192.168.1.1', '2024-04-30 18:00:00'),
(1, 'CAJA', 'APERTURA', 'APERTURA DE CAJA MES MAYO', '192.168.1.1', '2024-05-01 08:00:00'),
(1, 'VENTAS', 'INSERCION', 'REGISTRO DE VENTA #6', '192.168.1.1', '2024-05-03 15:00:00'),
(1, 'COMPRAS', 'INSERCION', 'REGISTRO DE COMPRA #3', '192.168.1.1', '2024-05-05 09:00:00'),
(1, 'VENTAS', 'INSERCION', 'REGISTRO DE VENTA #7', '192.168.1.1', '2024-05-12 17:15:00'),
(1, 'VENTAS', 'INSERCION', 'REGISTRO DE VENTA #8', '192.168.1.1', '2024-05-14 18:00:00'),
(1, 'COMPRAS', 'INSERCION', 'REGISTRO DE COMPRA #4', '192.168.1.1', '2024-05-15 11:45:00'),
(1, 'VENTAS', 'INSERCION', 'REGISTRO DE VENTA #12', '192.168.1.1', '2024-05-15 10:00:00'),
(1, 'VENTAS', 'INSERCION', 'REGISTRO DE VENTA #13', '192.168.1.1', '2024-05-20 15:30:00'),
(1, 'VENTAS', 'INSERCION', 'REGISTRO DE VENTA #15', '192.168.1.1', '2024-05-28 09:45:00'),
(1, 'GASTOS', 'INSERCION', 'REGISTRO DE GASTOS MAYO', '192.168.1.1', '2024-05-02 08:30:00'),
(1, 'CAJA', 'CIERRE', 'CIERRE DE CAJA MES MAYO', '192.168.1.1', '2024-05-31 18:00:00');

-- =====================================================
-- 18. REACTIVAR FOREIGN KEYS
-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- RESUMEN DE DATOS INSERTADOS
-- =====================================================
SELECT 'DATOS DE PRUEBA CARGADOS EXITOSAMENTE' AS MENSAJE;
SELECT '10 PROVEEDORES' AS TABLA, COUNT(*) AS CANTIDAD FROM `table_proveedores`
UNION SELECT '10 CLIENTES', COUNT(*) FROM `table_clientes`
UNION SELECT '20 VEHICULOS', COUNT(*) FROM `table_vehiculos`
UNION SELECT '30 PRODUCTOS', COUNT(*) FROM `table_inventario`
UNION SELECT '10 ORDENES SERVICIO', COUNT(*) FROM `table_ordenes_servicio`
UNION SELECT '15 VENTAS', COUNT(*) FROM `table_ventas`
UNION SELECT '5 COMPRAS', COUNT(*) FROM `table_compras`
UNION SELECT '10 GASTOS', COUNT(*) FROM `table_gastos`;