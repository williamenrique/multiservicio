<?php
/**
 * Modelo de Dashboard
 * Encargado de recopilar métricas y estadísticas clave del sistema.
 */
class ModelDashboard {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Obtiene el estado del inventario agrupado por criticidad
     */
    public function getInventoryStats() {
        $this->db->query("SELECT 
            SUM(CASE WHEN stock > stock_minimo THEN 1 ELSE 0 END) as ok,
            SUM(CASE WHEN stock <= stock_minimo AND stock > 0 THEN 1 ELSE 0 END) as critico,
            SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as agotado
            FROM table_inventario");
        return $this->db->single();
    }

    /**
     * Suma de ventas completadas en el día actual
     */
    public function getIncomeToday($usuarioId = null) {
        $sql = "SELECT SUM(total) as total FROM table_ventas WHERE DATE(fecha) = CURDATE() AND status IN ('COMPLETADO', 'CREDITO')";
        if ($usuarioId) $sql .= " AND usuario_id = :uid";
        
        $this->db->query($sql);
        if ($usuarioId) $this->db->bind(':uid', $usuarioId);
        return $this->db->single()->total ?? 0;
    }

    /**
     * Suma de gastos registrados en el mes actual
     */
    public function getExpensesMonth() {
        $this->db->query("SELECT SUM(monto) as total FROM table_gastos WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE())");
        return $this->db->single()->total ?? 0;
    }

    /**
     * Obtiene las últimas 5 ventas para el widget de actividad reciente
     */
    public function getRecentSales($usuarioId = null) {
        $sql = "SELECT v.*, c.nombre as cliente_nombre 
                          FROM table_ventas v 
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id 
                          WHERE v.status IN ('COMPLETADO', 'CREDITO')";
        if ($usuarioId) $sql .= " AND v.usuario_id = :uid";
        $sql .= " ORDER BY v.fecha DESC LIMIT 5";

        $this->db->query($sql);
        if ($usuarioId) $this->db->bind(':uid', $usuarioId);
        return $this->db->resultSet();
    }

    /**
     * Obtiene los borradores (ventas pendientes)
     */
    public function getPendingDrafts($usuarioId = null) {
        $sql = "SELECT v.id, v.usuario_id, v.fecha, v.placa, v.modelo_vehiculo, v.total, 
                                 c.nombre as cliente_nombre, s.nombre as responsable_nombre 
                          FROM table_ventas v 
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id 
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE v.status = 'PENDIENTE'";
        if ($usuarioId) $sql .= " AND v.usuario_id = :uid";
        $sql .= " ORDER BY v.fecha DESC LIMIT 6";

        $this->db->query($sql);
        if ($usuarioId) $this->db->bind(':uid', $usuarioId);
        return $this->db->resultSet();
    }

    /**
     * Obtiene un resumen de deudas con proveedores
     */
    public function getSupplierDebtsSummary() {
        $this->db->query("SELECT p.nombre, SUM(c.total - c.pagado) as saldo_pendiente
                          FROM table_compras c
                          INNER JOIN table_proveedores p ON c.proveedor_id = p.id
                          WHERE (c.total - c.pagado) > 0
                          GROUP BY p.id
                          ORDER BY saldo_pendiente DESC 
                          LIMIT 3");
        return $this->db->resultSet();
    }

    /**
     * Obtiene el historial financiero de los últimos N días para la gráfica de rendimiento
     */
    public function getFinancialHistory($days = 7, $usuarioId = null) {
        $dateStart = date('Y-m-d', strtotime("-" . ($days - 1) . " days"));

        // Ingresos agrupados por día
        $sql = "SELECT DATE(fecha) as date, SUM(total) as total FROM table_ventas 
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
                if($row->date == $d) { $inc = $row->total; break; }
            }
            foreach($expensesRows as $row) {
                if($row->date == $d) { $exp = $row->total; break; }
            }

            $history[] = [
                'day' => date('d M', strtotime($d)),
                'income' => (float)$inc,
                'expenses' => (float)$exp
            ];
        }
        return $history;
    }

    /**
     * Obtiene el listado detallado de los últimos gastos del mes actual
     */
    public function getRecentExpenses() {
        $this->db->query("SELECT * FROM table_gastos 
                          WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) 
                          AND YEAR(fecha) = YEAR(CURRENT_DATE()) 
                          ORDER BY fecha DESC 
                          LIMIT 6");
        return $this->db->resultSet();
    }

    /**
     * Obtiene lista detallada de productos bajo el stock mínimo para alertas
     */
    public function getLowStockProducts() {
        $this->db->query("SELECT id, nombre, stock, stock_minimo FROM table_inventario 
                          WHERE stock <= stock_minimo 
                          ORDER BY stock ASC LIMIT 10");
        return $this->db->resultSet();
    }
}