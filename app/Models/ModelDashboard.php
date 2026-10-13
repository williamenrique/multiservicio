<?php
/**
 * Modelo de Dashboard
 * Encargado de recopilar métricas y estadísticas clave del sistema.
 * 
 * v3.0 (2026-10-13) — REDISEÑO DEL DASHBOARD:
 *   • Nuevos métodos para comparativos de mes actual vs mes anterior:
 *       - getComparativoIngresos()
 *       - getComparativoGastos()
 *       - getComparativoUtilidad()
 *       - getClientesNuevosComparativo()
 *   • Nuevos métodos operativos:
 *       - getOrdenesSinMecanico()
 *       - getEntregasProximas($horas)
 *   • getFinancialHistory() ahora también calcula `profit` diario
 *     (ingresos − gastos) para la serie de utilidad neta.
 *   • getTopProducts() acepta parámetro $dias para variar el periodo.
 */
class ModelDashboard {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /* =====================================================================
     * INVENTARIO
     * ===================================================================== */

    public function getInventoryStats() {
        $this->db->query("SELECT 
            COALESCE(SUM(CASE WHEN stock > stock_minimo THEN 1 ELSE 0 END), 0) as ok,
            COALESCE(SUM(CASE WHEN stock <= stock_minimo AND stock > 0 THEN 1 ELSE 0 END), 0) as critico,
            COALESCE(SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END), 0) as agotado
            FROM table_inventario");
        return $this->db->single();
    }

    public function getLowStockProducts() {
        $this->db->query("SELECT id, nombre, stock, stock_minimo FROM table_inventario 
                          WHERE stock <= stock_minimo 
                          ORDER BY stock ASC LIMIT 10");
        return $this->db->resultSet();
    }

    /* =====================================================================
     * INGRESOS Y GASTOS
     * ===================================================================== */

    public function getIncomeToday($usuarioId = null) {
        $sql = "SELECT COALESCE(SUM(total), 0) as total FROM table_facturas WHERE DATE(fecha) = CURDATE() AND status IN ('COMPLETADO', 'CREDITO')";
        if ($usuarioId) $sql .= " AND usuario_id = :uid";
        
        $this->db->query($sql);
        if ($usuarioId) $this->db->bind(':uid', $usuarioId);
        return $this->db->single()->total ?? 0;
    }

    public function getExpensesMonth() {
        $this->db->query("SELECT COALESCE(SUM(monto), 0) as total FROM table_gastos WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE())");
        return $this->db->single()->total ?? 0;
    }

    public function getRecentExpenses() {
        $this->db->query("SELECT * FROM table_gastos 
                          WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) 
                          AND YEAR(fecha) = YEAR(CURRENT_DATE()) 
                          ORDER BY fecha DESC 
                          LIMIT 6");
        return $this->db->resultSet();
    }

    /* =====================================================================
     * VENTAS Y BORRADORES
     * ===================================================================== */

    public function getRecentSales($usuarioId = null) {
        $sql = "SELECT v.*, c.nombre as cliente_nombre,
                       COALESCE(vh.placa, v.placa) as placa, 
                       COALESCE(vh.modelo, v.modelo_vehiculo, 'N/A') as modelo_vehiculo
                          FROM table_facturas v 
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id 
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                          WHERE v.status IN ('COMPLETADO', 'CREDITO')";
        if ($usuarioId) $sql .= " AND v.usuario_id = :uid";
        $sql .= " ORDER BY v.fecha DESC LIMIT 5";

        $this->db->query($sql);
        if ($usuarioId) $this->db->bind(':uid', $usuarioId);
        return $this->db->resultSet();
    }

    public function getPendingDrafts($usuarioId = null) {
        $sql = "SELECT v.id, CONCAT('FAC-', LPAD(v.id, 3, '0')) as id_formateado,
                       v.usuario_id, v.fecha, v.total,
                       COALESCE(vh.placa, v.placa, '---') as placa, 
                       COALESCE(vh.modelo, v.modelo_vehiculo, 'N/A') as modelo_vehiculo,
                       COALESCE(c.nombre, 'CLIENTE MOSTRADOR') as cliente_nombre,
                       CASE WHEN v.orden_id IS NOT NULL THEN 'OS' WHEN v.placa IS NOT NULL THEN 'TALLER' ELSE 'MOSTRADOR' END as tipo_procedencia,
                       COALESCE(sm.nombre, (SELECT s2.nombre FROM table_facturas_detalle vd2 JOIN table_staff s2 ON vd2.mecanico_id = s2.id WHERE vd2.factura_id = v.id AND vd2.mecanico_id IS NOT NULL LIMIT 1), su.nombre, u.username) as responsable_nombre 
                          FROM table_facturas v 
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id 
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff su ON u.staff_id = su.id
                          LEFT JOIN table_staff sm ON os.mecanico_id = sm.id
                          WHERE v.status = 'PENDIENTE'";
        if ($usuarioId) $sql .= " AND v.usuario_id = :uid";
        $sql .= " ORDER BY v.fecha DESC LIMIT 6";

        $this->db->query($sql);
        if ($usuarioId) $this->db->bind(':uid', $usuarioId);
        return $this->db->resultSet();
    }

    /* =====================================================================
     * PROVEEDORES
     * ===================================================================== */

    public function getSupplierDebtsSummary() {
       $this->db->query("SELECT p.id, p.nombre, p.telefono, SUM(c.total - c.pagado) as saldo_pendiente, MIN(c.fecha_vencimiento) as proximo_vencimiento
                          FROM table_compras c
                          INNER JOIN table_proveedores p ON c.proveedor_id = p.id
                          WHERE (c.total - c.pagado) > 0
                          GROUP BY p.id
                          ORDER BY saldo_pendiente DESC 
                          LIMIT 3");
        return $this->db->resultSet();
    }

    /* =====================================================================
     * TALLER
     * ===================================================================== */

    public function getServiceOrdersStatus() {
        $this->db->query("SELECT 
            COALESCE(SUM(CASE WHEN estado = 'RECIBIDO' THEN 1 ELSE 0 END), 0) as recibidos,
            COALESCE(SUM(CASE WHEN estado = 'EN_REPARACION' THEN 1 ELSE 0 END), 0) as reparacion,
            COALESCE(SUM(CASE WHEN estado = 'LISTO' THEN 1 ELSE 0 END), 0) as listos
            FROM table_ordenes_servicio
            WHERE estado != 'ENTREGADO'");
        return $this->db->single();
    }

    /**
     * v3.0: Lista de órdenes activas sin mecánico asignado.
     * Útil para el widget de cuello de botella operativo.
     */
    public function getOrdenesSinMecanico($limit = 5) {
        $this->db->query("SELECT os.id, os.placa, os.estado, os.fecha_ingreso,
                                 TIMESTAMPDIFF(HOUR, os.fecha_ingreso, NOW()) as horas_en_taller,
                                 v.marca, v.modelo,
                                 c.nombre as cliente_nombre,
                                 c.telefono as cliente_telefono
                          FROM table_ordenes_servicio os
                          LEFT JOIN table_vehiculos v ON os.placa = v.placa
                          LEFT JOIN table_clientes c ON os.cliente_id = c.id
                          WHERE os.mecanico_id IS NULL
                            AND os.estado NOT IN ('ENTREGADO', 'CANCELADO')
                          ORDER BY os.fecha_ingreso ASC
                          LIMIT :limit");
        $this->db->bind(':limit', (int)$limit);
        return $this->db->resultSet();
    }

    /**
     * v3.0: Órdenes con entrega estimada dentro de las próximas N horas.
     * Alerta para priorizar trabajos urgentes.
     */
    public function getEntregasProximas($horas = 48, $limit = 6) {
        $this->db->query("SELECT os.id, os.placa, os.estado, os.fecha_entrega_estimada,
                                 TIMESTAMPDIFF(MINUTE, NOW(), os.fecha_entrega_estimada) as minutos_restantes,
                                 v.marca, v.modelo,
                                 c.nombre as cliente_nombre,
                                 c.telefono as cliente_telefono,
                                 s.nombre as mecanico_nombre
                          FROM table_ordenes_servicio os
                          LEFT JOIN table_vehiculos v ON os.placa = v.placa
                          LEFT JOIN table_clientes c ON os.cliente_id = c.id
                          LEFT JOIN table_staff s ON os.mecanico_id = s.id
                          WHERE os.estado NOT IN ('ENTREGADO', 'CANCELADO')
                            AND os.fecha_entrega_estimada IS NOT NULL
                            AND os.fecha_entrega_estimada BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :horas HOUR)
                          ORDER BY os.fecha_entrega_estimada ASC
                          LIMIT :limit");
        $this->db->bind(':horas', (int)$horas);
        $this->db->bind(':limit', (int)$limit);
        return $this->db->resultSet();
    }

    /* =====================================================================
     * TOP PRODUCTOS
     * ===================================================================== */

    public function getTopSellingProducts() {
        $this->db->query("SELECT i.nombre, SUM(vd.cantidad) as total_vendido, i.categoria
                          FROM table_facturas_detalle vd
                          JOIN table_inventario i ON vd.producto_id = i.id
                          JOIN table_facturas v ON vd.factura_id = v.id
                          WHERE v.status IN ('COMPLETADO', 'CREDITO')
                          AND MONTH(v.fecha) = MONTH(CURRENT_DATE())
                          GROUP BY i.id 
                          ORDER BY total_vendido DESC LIMIT 5");
        return $this->db->resultSet();
    }

    /**
     * v3.0: Top productos con parámetro de días variable.
     */
    public function getTopProducts($dias = 30, $limit = 5) {
        $this->db->query("SELECT i.nombre, SUM(vd.cantidad) as total_vendido, i.categoria
                          FROM table_facturas_detalle vd
                          JOIN table_inventario i ON vd.producto_id = i.id
                          JOIN table_facturas v ON vd.factura_id = v.id
                          WHERE v.status IN ('COMPLETADO', 'CREDITO')
                          AND v.fecha >= DATE_SUB(NOW(), INTERVAL :dias DAY)
                          GROUP BY i.id 
                          ORDER BY total_vendido DESC 
                          LIMIT :limit");
        $this->db->bind(':dias', (int)$dias);
        $this->db->bind(':limit', (int)$limit);
        return $this->db->resultSet();
    }

    /* =====================================================================
     * HISTÓRICO FINANCIERO (gráfica)
     * ===================================================================== */

    /**
     * v3.0: Extendido con `profit` (utilidad neta diaria).
     */
    public function getFinancialHistory($days = 7, $usuarioId = null) {
        $dateStart = date('Y-m-d', strtotime("-" . ($days - 1) . " days"));

        // Ingresos agrupados por día
        $sql = "SELECT DATE(fecha) as date, SUM(total) as total FROM table_facturas 
                WHERE DATE(fecha) >= :start AND status IN ('COMPLETADO', 'CREDITO')";
        if ($usuarioId) $sql .= " AND usuario_id = :uid";
        $sql .= " GROUP BY DATE(fecha)";

        $this->db->query($sql);
        $this->db->bind(':start', $dateStart);
        if ($usuarioId) $this->db->bind(':uid', $usuarioId);
        $incomeRows = $this->db->resultSet();

        // Gastos agrupados por día
        $this->db->query("SELECT DATE(fecha) as date, SUM(monto) as total FROM table_gastos WHERE DATE(fecha) >= :start GROUP BY DATE(fecha)");
        $this->db->bind(':start', $dateStart);
        $expensesRows = $this->db->resultSet();

        $history = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $inc = 0;
            $exp = 0;

            foreach($incomeRows as $row) {
                if($row->date == $d) { $inc = (float)$row->total; break; }
            }
            foreach($expensesRows as $row) {
                if($row->date == $d) { $exp = (float)$row->total; break; }
            }

            $history[] = [
                'day' => date('d M', strtotime($d)),
                'income' => $inc,
                'expenses' => $exp,
                'profit' => $inc - $exp,
            ];
        }
        return $history;
    }

    /* =====================================================================
     * COMPARATIVOS MES ACTUAL vs MES ANTERIOR
     * ===================================================================== */

    /**
     * v3.0: Comparativos de ingresos.
     */
    public function getComparativoIngresos() {
        // Hoy vs Ayer
        $this->db->query("SELECT 
            COALESCE(SUM(CASE WHEN DATE(fecha) = CURDATE() THEN total ELSE 0 END), 0) as hoy,
            COALESCE(SUM(CASE WHEN DATE(fecha) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN total ELSE 0 END), 0) as ayer
            FROM table_facturas WHERE status IN ('COMPLETADO', 'CREDITO')");
        $row = $this->db->single();

        $hoy = (float)($row->hoy ?? 0);
        $ayer = (float)($row->ayer ?? 0);
        $variacionHoy = $ayer > 0.005 ? (($hoy - $ayer) / $ayer) * 100 : null;

        // Mes actual vs mes anterior
        $this->db->query("SELECT 
            COALESCE(SUM(CASE WHEN MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE()) THEN total ELSE 0 END), 0) as actual,
            COALESCE(SUM(CASE WHEN MONTH(fecha) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(fecha) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN total ELSE 0 END), 0) as anterior
            FROM table_facturas WHERE status IN ('COMPLETADO', 'CREDITO')");
        $row2 = $this->db->single();

        $actual = (float)($row2->actual ?? 0);
        $anterior = (float)($row2->anterior ?? 0);
        $variacionMes = $anterior > 0.005 ? (($actual - $anterior) / $anterior) * 100 : null;

        return [
            'hoy' => $hoy,
            'ayer' => $ayer,
            'variacion_hoy_pct' => $variacionHoy,
            'mes_actual' => $actual,
            'mes_anterior' => $anterior,
            'variacion_mes_pct' => $variacionMes,
        ];
    }

    /**
     * v3.0: Comparativos de gastos.
     */
    public function getComparativoGastos() {
        $this->db->query("SELECT 
            COALESCE(SUM(CASE WHEN MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE()) THEN monto ELSE 0 END), 0) as actual,
            COALESCE(SUM(CASE WHEN MONTH(fecha) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(fecha) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN monto ELSE 0 END), 0) as anterior
            FROM table_gastos");
        $row = $this->db->single();

        $actual = (float)($row->actual ?? 0);
        $anterior = (float)($row->anterior ?? 0);
        $variacion = $anterior > 0.005 ? (($actual - $anterior) / $anterior) * 100 : null;

        return [
            'actual' => $actual,
            'anterior' => $anterior,
            'variacion_pct' => $variacion,
        ];
    }

    /**
     * v3.0: Comparativos de utilidad bruta (ingresos − costos).
     * Usa los datos de facturas_detalle para calcular costo de repuestos.
     */
    public function getComparativoUtilidad() {
        // Mes actual
        $this->db->query("SELECT 
            COALESCE(SUM(vd.precio_unitario * vd.cantidad), 0) as ventas,
            COALESCE(SUM(vd.costo_unitario * vd.cantidad), 0) as costos
            FROM table_facturas_detalle vd
            JOIN table_facturas v ON vd.factura_id = v.id
            WHERE v.status IN ('COMPLETADO', 'CREDITO')
            AND MONTH(v.fecha) = MONTH(CURDATE()) 
            AND YEAR(v.fecha) = YEAR(CURDATE())");
        $actual = $this->db->single();
        $utilidadActual = (float)($actual->ventas ?? 0) - (float)($actual->costos ?? 0);

        // Mes anterior
        $this->db->query("SELECT 
            COALESCE(SUM(vd.precio_unitario * vd.cantidad), 0) as ventas,
            COALESCE(SUM(vd.costo_unitario * vd.cantidad), 0) as costos
            FROM table_facturas_detalle vd
            JOIN table_facturas v ON vd.factura_id = v.id
            WHERE v.status IN ('COMPLETADO', 'CREDITO')
            AND MONTH(v.fecha) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
            AND YEAR(v.fecha) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
        $anterior = $this->db->single();
        $utilidadAnterior = (float)($anterior->ventas ?? 0) - (float)($anterior->costos ?? 0);

        $variacion = $utilidadAnterior != 0 ? (($utilidadActual - $utilidadAnterior) / abs($utilidadAnterior)) * 100 : null;

        return [
            'actual' => $utilidadActual,
            'anterior' => $utilidadAnterior,
            'variacion_pct' => $variacion,
        ];
    }

    /**
     * v3.0: Clientes nuevos comparativo.
     */
    public function getClientesNuevosComparativo() {
        $this->db->query("SELECT 
            COALESCE(SUM(CASE WHEN MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE()) THEN 1 ELSE 0 END), 0) as actual,
            COALESCE(SUM(CASE WHEN MONTH(fecha_registro) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(fecha_registro) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) THEN 1 ELSE 0 END), 0) as anterior
            FROM table_clientes");
        $row = $this->db->single();

        $actual = (int)($row->actual ?? 0);
        $anterior = (int)($row->anterior ?? 0);
        $variacion = $anterior > 0 ? (($actual - $anterior) / $anterior) * 100 : null;

        return [
            'actual' => $actual,
            'anterior' => $anterior,
            'variacion_pct' => $variacion,
        ];
    }

    /* =====================================================================
     * OFERTAS
     * ===================================================================== */

    public function getProductsOnOffer($limit = 10) {
        $this->db->query("SELECT i.id, i.codigo, i.nombre, i.marca, i.categoria, i.precio, i.stock, i.imagen,
                                 i.oferta_porcentaje, i.oferta_fecha_inicio, i.oferta_fecha_fin,
                                 ROUND(i.precio * (1 - i.oferta_porcentaje / 100), 2) as precio_final
                          FROM table_inventario i
                          WHERE i.estado = 'ACTIVO'
                            AND i.oferta_activa = 1
                            AND i.oferta_porcentaje > 0
                            AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                            AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())
                          ORDER BY i.oferta_porcentaje DESC, i.nombre ASC
                          LIMIT :limit");
        $this->db->bind(':limit', (int)$limit);
        return $this->db->resultSet();
    }

    /* =====================================================================
     * MÉTODOS LEGACY (usados por NotificationChecker para el resumen mensual)
     * ===================================================================== */

    public function getVentasMesAnterior() {
        $this->db->query("SELECT COALESCE(SUM(total), 0) as total,
                                 COUNT(*) as cantidad
                          FROM table_facturas
                          WHERE status IN ('COMPLETADO', 'CREDITO')
                          AND MONTH(fecha) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)
                          AND YEAR(fecha) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)");
        return $this->db->single();
    }

    public function getGastosMesAnterior() {
        $this->db->query("SELECT COALESCE(SUM(monto), 0) as total,
                                 COUNT(*) as cantidad
                          FROM table_gastos
                          WHERE MONTH(fecha) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)
                          AND YEAR(fecha) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)");
        return $this->db->single();
    }

    public function getClientesMesAnterior() {
        $this->db->query("SELECT COUNT(*) as cantidad
                          FROM table_clientes
                          WHERE MONTH(fecha_registro) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)
                          AND YEAR(fecha_registro) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)");
        return $this->db->single();
    }

    public function getOrdenesMesAnterior() {
        $this->db->query("SELECT COUNT(*) as cantidad
                          FROM table_ordenes_servicio
                          WHERE estado = 'ENTREGADO'
                          AND MONTH(fecha_entrega_real) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)
                          AND YEAR(fecha_entrega_real) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)");
        return $this->db->single();
    }

    public function getTopProductosMesAnterior($limite = 5) {
        $this->db->query("SELECT i.nombre, SUM(vd.cantidad) as total_vendido
                          FROM table_facturas_detalle vd
                          JOIN table_inventario i ON vd.producto_id = i.id
                          JOIN table_facturas v ON vd.factura_id = v.id
                          WHERE v.status IN ('COMPLETADO', 'CREDITO')
                          AND MONTH(v.fecha) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)
                          AND YEAR(v.fecha) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)
                          GROUP BY i.id
                          ORDER BY total_vendido DESC LIMIT :limite");
        $this->db->bind(':limite', (int)$limite);
        return $this->db->resultSet();
    }

    public function getResumenInventario() {
        $this->db->query("SELECT
                            COUNT(*) as total_productos,
                            SUM(CASE WHEN stock <= stock_minimo AND stock > 0 THEN 1 ELSE 0 END) as criticos,
                            SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as agotados
                          FROM table_inventario");
        return $this->db->single();
    }
}