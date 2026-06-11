<?php
class ModelReportes {
    private $db;

    /**
     * Constructor del modelo
     * @param Database|null $db Instancia de base de datos compartida
     */
    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    public function obtenerFlujoCaja($desde, $hasta, $limit = null, $offset = null, $search = null) {
        // En la v2.0, table_transacciones es el origen centralizado de todo movimiento de caja.
        $sql = "SELECT t.*, 
                       t.monto as monto_pagado,
                       s.nombre as usuario_nombre,
                       f.orden_id,
                       COALESCE(vh.placa, f.placa, '---') as placa,
                       c.nombre as cliente_nombre,
                       p.nombre as proveedor_nombre,
                       st.nombre as empleado_nombre,
                       CASE 
                           WHEN t.tipo = 'INGRESO' THEN 'emerald' 
                           ELSE 'rose' 
                       END as tipo_color,
                       CASE 
                           WHEN t.categoria = 'VENTA' AND f.orden_id IS NOT NULL THEN CONCAT('VENTA O.S. #', f.orden_id)
                           WHEN t.categoria = 'VENTA' AND f.placa IS NULL THEN 'VENTA MOSTRADOR'
                           WHEN t.categoria = 'VENTA' THEN 'VENTA SERVICIO'
                           WHEN t.categoria = 'ABONO_CLIENTE' AND f.orden_id IS NOT NULL THEN CONCAT('ABONO O.S. #', f.orden_id)
                           WHEN t.categoria = 'ABONO_CLIENTE' AND f.placa IS NULL THEN 'ABONO MOSTRADOR'
                           WHEN t.categoria = 'ABONO_CLIENTE' THEN 'ABONO SERVICIO'
                           WHEN t.categoria = 'GASTO' THEN 'GASTO TALLER'
                           WHEN t.categoria = 'COMPRA_PROVEEDOR' THEN 'COMPRA REPUESTOS'
                           WHEN t.categoria = 'ABONO_PROVEEDOR' THEN 'PAGO PROVEEDOR'
                           WHEN t.categoria = 'NOMINA' THEN 'PAGO NÓMINA'
                           WHEN t.categoria = 'DEVOLUCION' THEN 'DEVOLUCIÓN'
                           ELSE t.categoria 
                       END as categoria_label
                FROM table_transacciones t
                LEFT JOIN table_usuarios u ON t.usuario_id = u.id
                LEFT JOIN table_staff s ON u.staff_id = s.id
                -- Joins para detalles según categoría, incluimos DEVOLUCION para ver placa/cliente
                LEFT JOIN table_facturas f ON (t.categoria IN ('VENTA', 'ABONO_CLIENTE', 'DEVOLUCION') AND t.referencia_id = f.id)
                LEFT JOIN table_ordenes_servicio os ON f.orden_id = os.id
                LEFT JOIN table_vehiculos vh ON (f.placa = vh.placa)
                LEFT JOIN table_clientes c ON f.cliente_id = c.id
                LEFT JOIN table_compras comp ON (t.categoria IN ('COMPRA_PROVEEDOR', 'ABONO_PROVEEDOR') AND t.referencia_id = comp.id)
                LEFT JOIN table_proveedores p ON comp.proveedor_id = p.id
                LEFT JOIN table_pagos_empleados pe ON (t.categoria = 'NOMINA' AND t.referencia_id = pe.id)
                LEFT JOIN table_staff st ON pe.staff_id = st.id
                WHERE DATE(t.fecha) BETWEEN :desde AND :hasta
                GROUP BY t.id";

        if ($search) {
            $sql .= " AND (t.descripcion LIKE :q OR vh.placa LIKE :q OR f.placa LIKE :q OR c.nombre LIKE :q OR p.nombre LIKE :q)";
        }

        $sql .= " ORDER BY t.fecha DESC";

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $this->db->query($sql);
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        if ($search) $this->db->bind(':q', "%$search%");
        if ($limit !== null && $offset !== null) {
            $this->db->bind(':limit', (int)$limit);
            $this->db->bind(':offset', (int)$offset);
        }

        $movimientos = $this->db->resultSet() ?: [];

        // Obtener total para paginación
        $this->db->query("SELECT COUNT(*) as total FROM table_transacciones WHERE DATE(fecha) BETWEEN :desde AND :hasta");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $totalMovimientos = (int)$this->db->single()->total;

        // Cálculos de Totales sin N+1
        $ingresoRepuestos = 0;
        $ingresoServicios = 0;

        // Agrupamos el peso de repuestos vs servicios de las facturas involucradas
        $this->db->query("SELECT 
                            SUM(CASE WHEN vd.producto_id IS NOT NULL THEN (vd.cantidad * vd.precio_unitario) ELSE 0 END) as val_repuestos,
                            SUM(CASE WHEN vd.producto_id IS NULL THEN (vd.cantidad * vd.precio_unitario) ELSE 0 END) as val_servicios,
                            v.id as factura_id
                          FROM table_facturas_detalle vd
                          JOIN table_facturas v ON vd.factura_id = v.id
                          WHERE v.id IN (SELECT referencia_id FROM table_transacciones WHERE categoria IN ('VENTA', 'ABONO_CLIENTE') AND DATE(fecha) BETWEEN :desde AND :hasta)
                          GROUP BY v.id");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $pesos = $this->db->resultSet();
        
        $mapPesos = [];
        foreach ($pesos as $p) {
            $total = (float)$p->val_repuestos + (float)$p->val_servicios;
            $mapPesos[$p->factura_id] = $total > 0 ? (float)$p->val_repuestos / $total : 1;
        }

        foreach ($movimientos as $m) {
            if ($m->tipo === 'INGRESO' && in_array($m->categoria, ['VENTA', 'ABONO_CLIENTE'])) {
                $ratio = $mapPesos[$m->referencia_id] ?? 0.5;
                $ingresoRepuestos += ((float)$m->monto * $ratio);
                $ingresoServicios += ((float)$m->monto * (1 - $ratio));
            } else {
                // Otros ingresos (si los hubiera)
            }
        }

        // Obtener Devoluciones del Periodo (dinero real que salió de caja)
        $this->db->query("SELECT COALESCE(SUM(monto_devuelto), 0) as total FROM table_devoluciones WHERE DATE(fecha) BETWEEN :desde AND :hasta");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $totalDevolucionesPeriodo = (float)$this->db->single()->total;

        $totalIngresosNetos = $ingresoRepuestos + $ingresoServicios;

        $this->db->query("SELECT COALESCE(SUM(monto), 0) as total FROM table_transacciones WHERE tipo = 'EGRESO' AND DATE(fecha) BETWEEN :desde AND :hasta");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $totalEgresosOperativos = (float)$this->db->single()->total;

        // Obtener la Deuda Total Global de Proveedores (Independiente del periodo seleccionado)
        $this->db->query("SELECT SUM(total - pagado) as deuda FROM table_compras WHERE status = 'PENDIENTE'");
        $resDeuda = $this->db->single();
        $totalDeuda = (float)($resDeuda->deuda ?? 0);

        return [
            'data' => $movimientos,
            'total' => $totalMovimientos,
            'totalFiltrados' => $totalMovimientos,
            'totales' => [
                'ingresos' => $totalIngresosNetos,
                'ingreso_repuestos' => $ingresoRepuestos,
                'ingreso_servicios' => $ingresoServicios,
                'egresos' => $totalEgresosOperativos,
                'devoluciones' => $totalDevolucionesPeriodo,
                'deuda' => $totalDeuda,
                'balance' => $totalIngresosNetos - $totalEgresosOperativos
            ]
        ];
    }

    public function obtenerReporteDetallado($desde, $hasta) {
        // 1. Detalle de Ventas (Vehículos + Items)
        $this->db->query("SELECT v.id, v.fecha, COALESCE(vh.placa, v.placa) as placa, COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo, vd.descripcion, vd.cantidad, vd.precio_unitario, 
                                 (vd.cantidad * vd.precio_unitario) as subtotal_item, 
                                 s.nombre as usuario_nombre,
                                 COALESCE(sm.nombre, (SELECT st2.nombre FROM table_facturas_detalle vd2 JOIN table_staff st2 ON vd2.mecanico_id = st2.id WHERE vd2.factura_id = v.id AND vd2.mecanico_id IS NOT NULL LIMIT 1)) as mecanico_nombre,
                                 c.nombre as cliente_nombre, c.telefono as cliente_telefono,
                                 v.subtotal, v.iva_monto, v.total, v.pago_efectivo, v.pago_transferencia, v.saldo_pendiente, v.status
                          FROM table_facturas v
                          JOIN table_facturas_detalle vd ON v.id = vd.factura_id
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          LEFT JOIN table_staff sm ON os.mecanico_id = sm.id
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE v.status IN ('COMPLETADO', 'CREDITO', 'PENDIENTE') AND (v.orden_id IS NOT NULL OR v.placa IS NOT NULL) AND DATE(v.fecha) BETWEEN :desde AND :hasta
                          ORDER BY v.fecha DESC");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $ventas = $this->db->resultSet() ?: [];

        // 2. Detalle de Compras (Proveedores + Items + Deuda)
        $this->db->query("SELECT c.id, c.fecha, p.nombre as proveedor, cd.descripcion, cd.cantidad, cd.costo_unitario, c.total as total_factura, c.pagado, (c.total - c.pagado) as deuda
                          FROM table_compras c
                          JOIN table_proveedores p ON c.proveedor_id = p.id
                          JOIN table_compras_detalle cd ON c.id = cd.compra_id
                          WHERE DATE(c.fecha) BETWEEN :desde AND :hasta
                          ORDER BY c.fecha DESC");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $compras = $this->db->resultSet() ?: [];

        // 3. Detalle de Gastos
        $this->db->query("SELECT * FROM table_gastos 
                          WHERE DATE(fecha) BETWEEN :desde AND :hasta
                          ORDER BY fecha DESC");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $gastos = $this->db->resultSet() ?: [];

        return [
            'ventas' => $ventas,
            'compras' => $compras,
            'gastos' => $gastos
        ];
    }

    public function obtenerReporteDevoluciones($desde, $hasta, $limit = null, $offset = null, $search = null) {
        $sql = "SELECT d.*, s.nombre as usuario_nombre, COALESCE(vh.placa, v.placa) as placa, c.nombre as cliente_nombre
                FROM table_devoluciones d
                JOIN table_facturas v ON d.factura_id = v.id
                LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                LEFT JOIN table_usuarios u ON d.usuario_id = u.id
                LEFT JOIN table_staff s ON u.staff_id = s.id
                LEFT JOIN table_clientes c ON v.cliente_id = c.id
                WHERE DATE(d.fecha) BETWEEN :desde AND :hasta";

        if ($search) {
            $sql .= " AND (COALESCE(vh.placa, v.placa) LIKE :search OR c.nombre LIKE :search OR d.descripcion LIKE :search)";
        }

        $sql .= " ORDER BY d.fecha DESC";

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $this->db->query($sql);
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        if ($search) $this->db->bind(':search', "%$search%");
        if ($limit !== null && $offset !== null) {
            $this->db->bind(':limit', (int)$limit);
            $this->db->bind(':offset', (int)$offset);
        }
        $results = $this->db->resultSet() ?: [];
        return $results;
    }

    public function contarDevoluciones($desde, $hasta, $search = null) {
        $sql = "SELECT COUNT(*) as total
                FROM table_devoluciones d 
                JOIN table_facturas v ON d.factura_id = v.id 
                LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                WHERE DATE(d.fecha) BETWEEN :desde AND :hasta";

        if ($search) $sql .= " AND (COALESCE(vh.placa, v.placa) LIKE :search OR d.descripcion LIKE :search)";
        $this->db->query($sql);
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        if ($search) $this->db->bind(':search', "%$search%");
        return (int)$this->db->single()->total;
    }

    /**
     * Obtiene el reporte de cartera clasificado por antigüedad de deuda.
     * Divide la deuda en rangos de 0-15, 16-30 y más de 30 días. 
     * Se usa DATE() para asegurar que deudas del mismo día (diff 0) sean incluidas.
     */
    public function obtenerCarteraPorEdades($desde = null, $hasta = null) {
        $where = "WHERE v.status = 'CREDITO' AND v.saldo_pendiente > 0.05";
        if ($desde && $hasta) {
            $where .= " AND DATE(v.fecha) BETWEEN :desde AND :hasta";
        }

        $this->db->query("SELECT 
                            c.nombre as cliente_nombre,
                            c.telefono as cliente_telefono,
                            SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(v.fecha)) <= 15 THEN v.saldo_pendiente ELSE 0 END) as rango_0_15,
                            SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(v.fecha)) > 15 AND DATEDIFF(CURDATE(), DATE(v.fecha)) <= 30 THEN v.saldo_pendiente ELSE 0 END) as rango_16_30,
                            SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(v.fecha)) > 30 THEN v.saldo_pendiente ELSE 0 END) as rango_30_mas,
                            SUM(v.saldo_pendiente) as total_deuda
                          FROM table_facturas v
                          JOIN table_clientes c ON v.cliente_id = c.id
                          $where
                          GROUP BY c.id
                          ORDER BY total_deuda ASC");
        
        if ($desde && $hasta) {
            $this->db->bind(':desde', $desde);
            $this->db->bind(':hasta', $hasta);
        }

        $results = $this->db->resultSet() ?: [];
        return $results; // Devolvemos el array directo para evitar error .map() en JS
    }

    /**
     * Obtiene el reporte de cuentas por pagar (Proveedores) clasificado por antigüedad.
     */
    public function obtenerCarteraProveedoresPorEdades() {
        $this->db->query("SELECT 
                            p.nombre as proveedor_nombre,
                            p.telefono as proveedor_telefono,
                            SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(c.fecha)) <= 15 THEN (c.total - c.pagado) ELSE 0 END) as rango_0_15,
                            SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(c.fecha)) > 15 AND DATEDIFF(CURDATE(), DATE(c.fecha)) <= 30 THEN (c.total - c.pagado) ELSE 0 END) as rango_16_30,
                            SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(c.fecha)) > 30 THEN (c.total - c.pagado) ELSE 0 END) as rango_30_mas,
                            SUM(c.total - c.pagado) as total_deuda
                          FROM table_compras c
                          JOIN table_proveedores p ON c.proveedor_id = p.id
                          WHERE c.status = 'PENDIENTE' AND (c.total - c.pagado) > 0
                          GROUP BY p.id
                          ORDER BY total_deuda DESC");
        
        return $this->db->resultSet() ?: [];
    }

    /**
     * Obtiene el estado de cuenta detallado de un proveedor individual.
     */
    public function obtenerDetalleProveedor($id) {
        // 1. Información básica del proveedor
        $this->db->query("SELECT * FROM table_proveedores WHERE id = :id");
        $this->db->bind(':id', $id);
        $proveedor = $this->db->single();

        if ($proveedor) {
            // 2. Resumen de facturas de compra
            $this->db->query("SELECT * FROM table_compras WHERE proveedor_id = :id ORDER BY fecha DESC");
            $this->db->bind(':id', $id);
            $proveedor->compras = $this->db->resultSet() ?: [];

            // 3. Historial de abonos realizados a este proveedor
            $this->db->query("SELECT a.*, c.total as total_compra FROM table_abonos_proveedores a 
                              JOIN table_compras c ON a.compra_id = c.id 
                              WHERE c.proveedor_id = :id ORDER BY a.fecha DESC");
            $this->db->bind(':id', $id);
            $proveedor->abonos = $this->db->resultSet() ?: [];

            // 4. Totales acumulados
            $this->db->query("SELECT SUM(total) as total_compras, SUM(pagado) as total_pagado, SUM(total - pagado) as saldo_pendiente 
                              FROM table_compras WHERE proveedor_id = :id");
            $this->db->bind(':id', $id);
            $proveedor->resumen = $this->db->single();
        }
        return $proveedor;
    }

    /**
     * Calcula la rentabilidad comparando Repuestos vs Servicios en un periodo.
     */
    public function obtenerAnalisisRentabilidad($desde, $hasta) {
        $this->db->query("SELECT 
                            CASE WHEN vd.producto_id IS NULL THEN 'SERVICIO' ELSE 'REPUESTO' END as tipo,
                            SUM(vd.cantidad * vd.precio_unitario) as ingreso_total,
                            SUM(vd.cantidad * vd.costo_unitario) as costo_total,
                            SUM(vd.cantidad * (vd.precio_unitario - vd.costo_unitario)) as utilidad_bruta,
                            COUNT(DISTINCT v.id) as cantidad_operaciones
                          FROM table_facturas_detalle vd
                          JOIN table_facturas v ON vd.factura_id = v.id
                          WHERE v.status IN ('COMPLETADO', 'CREDITO') 
                          AND DATE(v.fecha) BETWEEN :desde AND :hasta
                          GROUP BY tipo");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        return $this->db->resultSet();
    }

    /**
     * Obtiene el listado de empleados para el selector de reportes.
     */
    public function obtenerStaffSimple() {
        $this->db->query("SELECT id, nombre, cargo FROM table_staff ORDER BY nombre ASC");
        return $this->db->resultSet();
    }

    /**
     * Obtiene los trabajos (servicios) y pagos de un empleado en un periodo.
     */
    public function obtenerNominaEmpleado($staff_id, $desde, $hasta) {
 
        $this->db->query("SELECT v.id as venta_id, v.fecha, COALESCE(vh.placa, v.placa) as placa, COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo, 
                                 vd.id as detalle_id, vd.descripcion, vd.cantidad, 
                                 vd.precio_unitario as monto_trabajo, vd.pago_nomina_id, COALESCE(NULLIF(vh.modelo, ''), NULLIF(v.modelo_vehiculo, ''), 'N/A') as modelo_vehiculo_display,
                                 CASE WHEN v.orden_id IS NOT NULL THEN 'OS' WHEN v.placa IS NOT NULL THEN 'TALLER' ELSE 'MOSTRADOR' END as tipo_procedencia
                          FROM table_facturas v
                          JOIN table_facturas_detalle vd ON vd.factura_id = v.id
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                          WHERE (vd.mecanico_id = :staff_id OR os.mecanico_id = :staff_id OR :staff_id_alt = '0')
                          AND vd.producto_id IS NULL 
                          AND v.status IN ('COMPLETADO', 'CREDITO')
                          AND DATE(v.fecha) BETWEEN :desde AND :hasta
                          ORDER BY v.fecha DESC");
        $this->db->bind(':staff_id', $staff_id);
        $this->db->bind(':staff_id_alt', $staff_id);
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $trabajos = $this->db->resultSet() ?: [];

        // 2. Pagos y Adelantos
        $this->db->query("SELECT p.*, u.username as registrado_por 
                          FROM table_pagos_empleados p
                          LEFT JOIN table_usuarios u ON p.usuario_id = u.id
                          WHERE (p.staff_id = :staff_id OR :staff_id = '0')
                          AND DATE(p.fecha) BETWEEN :desde AND :hasta
                          ORDER BY p.fecha DESC");
        $this->db->bind(':staff_id', $staff_id);
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $pagos = $this->db->resultSet() ?: [];

        return ['trabajos' => $trabajos, 'pagos' => $pagos];
    }

    /**
     * Obtiene el detalle completo de un pago para generar el recibo PDF
     */
    public function obtenerDetallePago($id) {
        $this->db->query("SELECT p.*, s.nombre as staff_nombre, s.cedula as staff_cedula, s.cargo as staff_cargo,
                                 COALESCE(s2.nombre, u.username) as pagador_nombre,
                                 s2.telefono as pagador_telefono,
                                 p.modo_calculo, p.factor_calculo
                          FROM table_pagos_empleados p
                          JOIN table_staff s ON p.staff_id = s.id
                          LEFT JOIN table_usuarios u ON p.usuario_id = u.id
                          LEFT JOIN table_staff s2 ON u.staff_id = s2.id
                          WHERE p.id = :id");
        $this->db->bind(':id', $id);
        $pago = $this->db->single();

        if ($pago) {
            // Obtener los trabajos que fueron liquidados en este pago específico con info de vehículo
            $this->db->query("SELECT v.id as venta_id, vd.descripcion, vd.cantidad, vd.precio_unitario, v.fecha, 
                                     COALESCE(vh.placa, v.placa) as placa, 
                                     COALESCE(NULLIF(vh.modelo, ''), NULLIF(v.modelo_vehiculo, ''), 'N/A') as modelo_vehiculo,
                                     CASE WHEN v.orden_id IS NOT NULL THEN 'OS' WHEN v.placa IS NOT NULL THEN 'TALLER' ELSE 'MOSTRADOR' END as tipo_procedencia
                              FROM table_facturas_detalle vd
                              JOIN table_facturas v ON vd.factura_id = v.id
                              LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                              WHERE vd.pago_nomina_id = :pid");
            $this->db->bind(':pid', $id);
            $pago->trabajos = $this->db->resultSet();
        }
        return $pago;
    }

    public function registrarPagoEmpleado($data) {
        try {
            $this->db->beginTransaction();

            // 1. Insertar el registro de nómina
            $this->db->query("INSERT INTO table_pagos_empleados (staff_id, monto, monto_base, tipo, metodo_pago, modo_calculo, factor_calculo, notas, usuario_id) 
                              VALUES (:sid, :monto, :base, :tipo, :metodo, :modo, :factor, :notas, :uid)");
            
            $this->db->bind(':sid', $data['staff_id']);
            $this->db->bind(':monto', $data['monto']);
            $this->db->bind(':base', $data['monto_base']);
            $this->db->bind(':tipo', $data['tipo']);
            $this->db->bind(':metodo', $data['metodo_pago']);
            $this->db->bind(':modo', $data['modo_calculo'] ?? 'FIJO');
            $this->db->bind(':factor', $data['factor_calculo'] ?? 0);
            $this->db->bind(':notas', $data['notas']);
            $this->db->bind(':uid', $data['usuario_id']);
            
            $this->db->execute();
            $pagoId = $this->db->lastInsertId();

            // 2. Marcar los trabajos como liquidados si existen en el array
            $detallesIds = $data['detalles_ids'] ?? [];
            if (!empty($detallesIds)) {
                foreach ($detallesIds as $id) {
                    $this->db->query("UPDATE table_facturas_detalle SET pago_nomina_id = :pid WHERE id = :id");
                    $this->db->bind(':pid', $pagoId);
                    $this->db->bind(':id', $id);
                    $this->db->execute();
                }
            }

            // 3. Registrar el egreso en el Libro Mayor (table_transacciones)
            $this->db->query("INSERT INTO table_transacciones (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id) 
                              VALUES (1, 'EGRESO', 'NOMINA', :monto, :ref, :desc, :uid)");
            $this->db->bind(':monto', $data['monto']);
            $this->db->bind(':ref', $pagoId);
            $prefix = ($data['tipo'] === 'ADELANTO') ? 'ADELANTO NÓMINA' : 'PAGO NÓMINA';
            $this->db->bind(':desc', "$prefix: " . ($data['notas'] ?: 'SIN OBSERVACIONES'));
            $this->db->bind(':uid', $data['usuario_id']);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db) $this->db->rollBack();
            error_log("Error registrando pago nómina: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el historial de pagos de nómina realizados.
     */
    public function obtenerHistorialPagosNomina($desde, $hasta) {
        $this->db->query("SELECT p.*, s.nombre as staff_nombre, s.cargo as staff_cargo, u.username as registrado_por 
                          FROM table_pagos_empleados p
                          JOIN table_staff s ON p.staff_id = s.id
                          LEFT JOIN table_usuarios u ON p.usuario_id = u.id
                          WHERE DATE(p.fecha) BETWEEN :desde AND :hasta
                          ORDER BY p.fecha DESC");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        return $this->db->resultSet() ?: [];
    }
}