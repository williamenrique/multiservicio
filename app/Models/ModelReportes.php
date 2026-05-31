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
                            COALESCE((SELECT SUM(monto) FROM table_abonos_clientes WHERE venta_id = v.id), 0)
                          , 0) as monto_pagado, 
                          'VENTA' as tipo, 'VENTA' as categoria,
                          v.modelo_vehiculo, v.placa, c.nombre as cliente_nombre,
                          (SELECT COUNT(*) FROM table_ventas_detalle WHERE venta_id = v.id) as cantidad_items,
                          NULL as proveedor_nombre, v.saldo_pendiente
                          FROM table_ventas v
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE v.status IN ('COMPLETADO', 'CREDITO') 
                          AND DATE(v.fecha) BETWEEN :desde AND :hasta");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $ingresos = $this->db->resultSet() ?: [];

        // 2. Obtener Abonos (Dinero que entró de deudas antiguas en este periodo)
        $this->db->query("SELECT a.venta_id as id, a.fecha, COALESCE(a.monto, 0) as monto_pagado, 'ABONO' as tipo, 'ABONO CLIENTE' as categoria,
                          v.placa, c.nombre as cliente_nombre
                          FROM table_abonos_clientes a
                          JOIN table_ventas v ON a.venta_id = v.id
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
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
                          SELECT c.id, c.fecha, c.total as monto, c.pagado as monto_pagado, 'COMPRA' as tipo, 'MERCANCÍA' as categoria,
                          'COMPRA DE MERCANCIA' as descripcion, NULL as modelo_vehiculo, NULL as placa, NULL as cliente_nombre,
                          (SELECT COUNT(*) FROM table_compras_detalle WHERE compra_id = c.id) as cantidad_items,
                          p.nombre as proveedor_nombre, (c.total - c.pagado) as saldo_pendiente
                          FROM table_compras c
                          INNER JOIN table_proveedores p ON c.proveedor_id = p.id
                          WHERE DATE(c.fecha) BETWEEN :desde AND :hasta
                          UNION ALL
                          SELECT d.id, d.fecha, d.monto_devuelto as monto, d.monto_devuelto as monto_pagado, 'DEVOLUCION' as tipo, 'DEVOLUCION' as categoria,
                          d.descripcion, NULL as modelo_vehiculo, v.placa, NULL as cliente_nombre,
                          1 as cantidad_items, NULL as proveedor_nombre, 0 as saldo_pendiente
                          FROM table_devoluciones d
                          JOIN table_ventas v ON d.venta_id = v.id
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
                (SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM table_ventas_detalle WHERE venta_id = :vid AND producto_id IS NOT NULL) as total_val_repuestos,
                (SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM table_ventas_detalle WHERE venta_id = :vid AND producto_id IS NULL) as total_val_servicios");
            
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
        $this->db->query("SELECT v.id, v.fecha, v.placa, v.modelo_vehiculo, vd.descripcion, vd.cantidad, vd.precio_unitario, 
                                 (vd.cantidad * vd.precio_unitario) as subtotal_item, s.nombre as usuario_nombre, c.nombre as cliente_nombre,
                                 v.subtotal, v.iva_monto, v.total, v.pago_efectivo, v.pago_transferencia, v.saldo_pendiente, v.status
                          FROM table_ventas v
                          JOIN table_ventas_detalle vd ON v.id = vd.venta_id
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
        $sql = "SELECT d.*, s.nombre as usuario_nombre, v.placa, c.nombre as cliente_nombre
                FROM table_devoluciones d
                LEFT JOIN table_ventas v ON d.venta_id = v.id
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
                LEFT JOIN table_ventas v ON d.venta_id = v.id 
                WHERE DATE(d.fecha) BETWEEN :desde AND :hasta";

        if ($search) $sql .= " AND (v.placa LIKE :search OR d.descripcion LIKE :search)";
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
    public function obtenerCarteraPorEdades() {
        $this->db->query("SELECT 
                            c.nombre as cliente_nombre,
                            c.telefono as cliente_telefono,
                            SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(v.fecha)) <= 15 THEN v.saldo_pendiente ELSE 0 END) as rango_0_15,
                            SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(v.fecha)) > 15 AND DATEDIFF(CURDATE(), DATE(v.fecha)) <= 30 THEN v.saldo_pendiente ELSE 0 END) as rango_16_30,
                            SUM(CASE WHEN DATEDIFF(CURDATE(), DATE(v.fecha)) > 30 THEN v.saldo_pendiente ELSE 0 END) as rango_30_mas,
                            SUM(v.saldo_pendiente) as total_deuda
                          FROM table_ventas v
                          JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE v.status = 'CREDITO' AND v.saldo_pendiente > 0
                          GROUP BY c.id
                          ORDER BY total_deuda DESC");
        
        $results = $this->db->resultSet() ?: [];
        return $results; // Devolvemos el array directo para evitar error .map() en JS
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
                          FROM table_ventas_detalle vd
                          JOIN table_ventas v ON vd.venta_id = v.id
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
                          FROM table_ventas v
                          JOIN table_ventas_detalle vd ON v.id = vd.venta_id
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

    public function registrarPagoEmpleado($data) {
        try {
            $this->db->beginTransaction();

            // 1. Extraer los IDs de los trabajos para marcar como pagados
            $detallesIds = $data['detalles_ids'] ?? [];

            // 2. Insertar el registro de nómina con los nuevos campos de cálculo
            $this->db->query("INSERT INTO table_pagos_empleados (staff_id, monto, monto_base, modo_calculo, factor_calculo, tipo, metodo_pago, notas, usuario_id) 
                              VALUES (:sid, :monto, :base, :modo, :factor, :tipo, :metodo, :notas, :uid)");
            $this->db->bind(':sid', $data['staff_id']);
            $this->db->bind(':monto', $data['monto']);
            $this->db->bind(':base', $data['monto_base'] ?? 0);
            $this->db->bind(':modo', $data['modo_calculo'] ?? 'FIJO');
            $this->db->bind(':factor', $data['factor_calculo'] ?? 0);
            $this->db->bind(':tipo', $data['tipo'] ?? 'ADELANTO');
            $this->db->bind(':metodo', $data['metodo_pago'] ?? 'EFECTIVO');
            $this->db->bind(':notas', $data['notas'] ?? '');
            $this->db->bind(':uid', $data['usuario_id']);
            $this->db->execute();
            
            $pagoId = $this->db->lastInsertId();

            // 3. Vincular los ítems de mano de obra a este pago para el historial (cambian a Gris)
            if ($pagoId && !empty($detallesIds)) {
                foreach ($detallesIds as $detId) {
                    $this->db->query("UPDATE table_ventas_detalle SET pago_nomina_id = :pid WHERE id = :did");
                    $this->db->bind(':pid', $pagoId);
                    $this->db->bind(':did', $detId);
                    $this->db->execute();
                }
            }

            $this->db->commit();
            return $pagoId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}