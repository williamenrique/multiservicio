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
        // 1. Obtener Ventas (Pagos iniciales de facturas creadas en el periodo)
        // Restamos la suma de abonos del total pagado para obtener solo el pago inicial realizado en la fecha de la venta
        $this->db->query("SELECT v.id, v.fecha,
                          COALESCE(
                            (COALESCE(v.pago_efectivo, 0) + COALESCE(v.pago_transferencia, 0)) -
                            COALESCE((SELECT SUM(monto) FROM table_abonos_clientes WHERE factura_id = v.id), 0)
                          , 0) as monto_pagado, 
                          'VENTA' as tipo,
                          CASE WHEN v.orden_id IS NOT NULL THEN 'ORDEN DE TRABAJO' ELSE 'VENTA MOSTRADOR' END as categoria,
                          CONCAT(CASE WHEN v.orden_id IS NOT NULL THEN 'SERVICIO TÉCNICO' ELSE 'VENTA DE REPUESTOS' END) as descripcion,
                          s.nombre as usuario_nombre,
                          os.modelo_vehiculo, os.placa, c.nombre as cliente_nombre,
                          (SELECT COUNT(*) FROM table_facturas_detalle WHERE factura_id = v.id) as cantidad_items,
                          NULL as proveedor_nombre, v.saldo_pendiente
                          FROM table_facturas v
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id -- Placa desde O.S.
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE v.status IN ('COMPLETADO', 'CREDITO') 
                          AND DATE(v.fecha) BETWEEN :desde AND :hasta");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $ingresos = $this->db->resultSet() ?: [];

        // 2. Obtener Abonos (Dinero que entró de deudas antiguas en este periodo)
        $this->db->query("SELECT a.factura_id as id, a.fecha, COALESCE(a.monto, 0) as monto_pagado, 'ABONO' as tipo, 'ABONO CLIENTE' as categoria,
                          CONCAT('ABONO A FACTURA #', a.factura_id) as descripcion,
                          s.nombre as usuario_nombre,
                          os.placa, c.nombre as cliente_nombre
                          FROM table_abonos_clientes a
                          JOIN table_facturas v ON a.factura_id = v.id
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE DATE(a.fecha) BETWEEN :desde AND :hasta");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $abonos = $this->db->resultSet() ?: [];

        // 3. Obtener Gastos, Compras y Devoluciones (Egresos Reales)
        // Unificamos gastos generales y compras a proveedores
        $this->db->query("SELECT g.id, g.fecha, g.monto, COALESCE(g.monto, 0) as monto_pagado, 'GASTO' as tipo, g.categoria, 
                          g.descripcion, NULL as modelo_vehiculo, NULL as placa, NULL as cliente_nombre, 
                          0 as cantidad_items, NULL as proveedor_nombre, 0 as saldo_pendiente
                          FROM table_gastos g 
                          WHERE DATE(g.fecha) BETWEEN :desde AND :hasta
                          UNION ALL
                          SELECT c.id, c.fecha, c.total as monto, 
                          (COALESCE(c.pagado, 0) - COALESCE((SELECT SUM(monto) FROM table_transacciones WHERE referencia_id = c.id AND categoria = 'ABONO_PROVEEDOR'), 0)) as monto_pagado, 
                          'COMPRA' as tipo, 'MERCANCÍA' as categoria,
                          'COMPRA DE MERCANCIA' as descripcion, NULL as modelo_vehiculo, NULL as placa, NULL as cliente_nombre,
                          (SELECT COUNT(*) FROM table_compras_detalle WHERE compra_id = c.id) as cantidad_items,
                          p.nombre as proveedor_nombre, (c.total - c.pagado) as saldo_pendiente
                          FROM table_compras c
                          INNER JOIN table_proveedores p ON c.proveedor_id = p.id
                          WHERE DATE(c.fecha) BETWEEN :desde AND :hasta
                          UNION ALL
                          SELECT t.referencia_id as id, t.fecha, t.monto as monto, t.monto as monto_pagado, 'ABONO PROV' as tipo, 'PAGO PROVEEDOR' as categoria,
                          CONCAT('ABONO A COMPRA #', t.referencia_id) as descripcion, NULL as modelo_vehiculo, NULL as placa, NULL as cliente_nombre,
                          0 as cantidad_items, p.nombre as proveedor_nombre, 0 as saldo_pendiente
                          FROM table_transacciones t
                          JOIN table_compras c ON t.referencia_id = c.id
                          JOIN table_proveedores p ON c.proveedor_id = p.id
                          WHERE t.categoria = 'ABONO_PROVEEDOR' AND DATE(t.fecha) BETWEEN :desde AND :hasta
                          UNION ALL
                          SELECT d.id, d.fecha, d.monto_devuelto as monto, d.monto_devuelto as monto_pagado, 'DEVOLUCION' as tipo, 'DEVOLUCION' as categoria,
                          d.descripcion, NULL as modelo_vehiculo, v.placa, NULL as cliente_nombre,
                          1 as cantidad_items, NULL as proveedor_nombre, 0 as saldo_pendiente
                          FROM table_devoluciones d
                          JOIN table_facturas f ON d.factura_id = f.id
                          LEFT JOIN table_ordenes_servicio v ON f.orden_id = v.id
                          WHERE DATE(d.fecha) BETWEEN :desde AND :hasta");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $egresos = $this->db->resultSet() ?: [];

        // 4. Unificar movimientos para el listado
        $movimientos = array_merge($ingresos, $abonos, $egresos);
        
        if ($search) {
            $movimientos = array_filter($movimientos, function($m) use ($search) {
                $s = strtolower($search);
                return strpos(strtolower($m->id ?? ''), $s) !== false || 
                       strpos(strtolower($m->placa ?? ''), $s) !== false ||
                       strpos(strtolower($m->cliente_nombre ?? ''), $s) !== false ||
                       strpos(strtolower($m->descripcion ?? ''), $s) !== false;
            });
        }

        usort($movimientos, function($a, $b) {
            return strtotime($b->fecha) - strtotime($a->fecha);
        });

        $totalMovimientos = count($movimientos);

        // 5. Calcular División de Ingresos (Repuestos vs Servicios)
        $ingresoRepuestos = 0;
        $ingresoServicios = 0;
        $totalDevolucionesPeriodo = 0;

        // Analizamos cada venta que tuvo movimiento de dinero
        $todasLasEntradas = array_merge($ingresos, $abonos);
        foreach ($todasLasEntradas as $mov) {
            // Calculamos la proporción basada en los valores base
            $this->db->query("SELECT 
                (SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM table_facturas_detalle WHERE factura_id = :vid AND producto_id IS NOT NULL) as total_val_repuestos,
                (SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM table_facturas_detalle WHERE factura_id = :vid AND producto_id IS NULL) as total_val_servicios");
            
            $this->db->bind(':vid', $mov->id);
            $pesos = $this->db->single();
            
            $totalItems = (float)($pesos->total_val_repuestos ?? 0) + (float)($pesos->total_val_servicios ?? 0);
            $montoRecibido = (float)($mov->monto_pagado ?? 0);

            if ($totalItems > 0) {
                $porcentajeRepuestos = (float)$pesos->total_val_repuestos / $totalItems;
                $ingresoRepuestos += ($montoRecibido * $porcentajeRepuestos);
                $ingresoServicios += ($montoRecibido * (1 - $porcentajeRepuestos));
            } else {
                // Si no hay detalles (raro), lo sumamos a servicios
                $ingresoServicios += $montoRecibido;
            }
        }

        // Obtener Devoluciones del Periodo (dinero real que salió de caja)
        $this->db->query("SELECT COALESCE(SUM(monto_devuelto), 0) as total FROM table_devoluciones WHERE DATE(fecha) BETWEEN :desde AND :hasta");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $totalDevolucionesPeriodo = (float)$this->db->single()->total;

        // Los ingresos ya vienen "netos" porque ajustamos pago_efectivo en la venta al devolver
        // El total de devoluciones se muestra para fines informativos en el dashboard
        $totalIngresosNetos = $ingresoRepuestos + $ingresoServicios;

        // Egresos Operativos (Gastos y Compras - Excluimos devoluciones de aquí para evitar resta doble)
        $totalEgresosOperativos = array_reduce($egresos, function($acc, $item) { 
            if ($item->tipo === 'DEVOLUCION') return $acc;
            return $acc + (float)($item->monto_pagado ?? 0); 
        }, 0);

        // Obtener la Deuda Total Global de Proveedores (Independiente del periodo seleccionado)
        $this->db->query("SELECT SUM(total - pagado) as deuda FROM table_compras WHERE status = 'PENDIENTE'");
        $resDeuda = $this->db->single();
        $totalDeuda = (float)($resDeuda->deuda ?? 0);

        // Paginación en PHP para el set de datos unificado
        if ($limit !== null && $offset !== null) {
            $movimientos = array_slice($movimientos, $offset, $limit);
        }

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
        $this->db->query("SELECT v.id, v.fecha, os.placa, os.modelo_vehiculo, vd.descripcion, vd.cantidad, vd.precio_unitario, 
                                 (vd.cantidad * vd.precio_unitario) as subtotal_item, 
                                 s.nombre as usuario_nombre, c.nombre as cliente_nombre,
                                 v.subtotal, v.iva_monto, v.total, v.pago_efectivo, v.pago_transferencia, v.saldo_pendiente, v.status
                          FROM table_facturas v
                          JOIN table_facturas_detalle vd ON v.id = vd.factura_id
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE v.status IN ('COMPLETADO', 'CREDITO') AND DATE(v.fecha) BETWEEN :desde AND :hasta
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
        $sql = "SELECT d.*, s.nombre as usuario_nombre, os.placa, c.nombre as cliente_nombre
                FROM table_devoluciones d
                JOIN table_facturas v ON d.factura_id = v.id
                LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                LEFT JOIN table_usuarios u ON d.usuario_id = u.id
                LEFT JOIN table_staff s ON u.staff_id = s.id
                LEFT JOIN table_clientes c ON v.cliente_id = c.id
                WHERE DATE(d.fecha) BETWEEN :desde AND :hasta";

        if ($search) {
            $sql .= " AND (v.placa LIKE :search OR c.nombre LIKE :search OR d.descripcion LIKE :search)";
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
                WHERE DATE(d.fecha) BETWEEN :desde AND :hasta";

        if ($search) $sql .= " AND (os.placa LIKE :search OR d.descripcion LIKE :search)";
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
        $where = "WHERE v.status = 'CREDITO' AND v.saldo_pendiente > 0";
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
                          ORDER BY total_deuda DESC");
        
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
 
        $this->db->query("SELECT v.id as venta_id, v.fecha, v.placa, v.modelo_vehiculo, 
                                 vd.id as detalle_id, vd.descripcion, vd.cantidad, 
                                 vd.precio_unitario as monto_trabajo, vd.pago_nomina_id
                          FROM table_facturas v
                          JOIN table_facturas_detalle vd ON v.factura_id = v.id
                          WHERE (v.mecanico_id = :staff_id OR :staff_id_alt = '0')
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
        $this->db->query("SELECT p.*, s.nombre as staff_nombre, s.cedula as staff_cedula, s.cargo as staff_cargo, u.username as registrado_por
                          FROM table_pagos_empleados p
                          JOIN table_staff s ON p.staff_id = s.id
                          LEFT JOIN table_usuarios u ON p.usuario_id = u.id
                          WHERE p.id = :id");
        $this->db->bind(':id', $id);
        $pago = $this->db->single();

        if ($pago) {
            // Obtener los trabajos que fueron liquidados en este pago específico
            $this->db->query("SELECT v.id as venta_id, vd.descripcion, vd.cantidad, vd.precio_unitario, v.fecha, v.placa
                              FROM table_facturas_detalle vd
                              JOIN table_facturas v ON vd.factura_id = v.id
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
            $this->db->query("INSERT INTO table_pagos_empleados (staff_id, monto, monto_base, modo_calculo, factor_calculo, tipo, metodo_pago, notas, usuario_id) 
                              VALUES (:sid, :monto, :base, :modo, :factor, :tipo, :metodo, :notas, :uid)");
            
            $this->db->bind(':sid', $data['staff_id']);
            $this->db->bind(':monto', $data['monto']);
            $this->db->bind(':base', $data['monto_base']);
            $this->db->bind(':modo', $data['modo_calculo']);
            $this->db->bind(':factor', $data['factor_calculo']);
            $this->db->bind(':tipo', $data['tipo']);
            $this->db->bind(':metodo', $data['metodo_pago']);
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