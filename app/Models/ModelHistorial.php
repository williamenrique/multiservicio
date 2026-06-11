<?php
class ModelHistorial {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Lista todas las ventas completadas con información básica.
     * @return array Array de objetos de venta.
     */
    public function listarVentas($limit = null, $offset = null, $search = null) {
        $sql = "SELECT v.id, v.fecha, COALESCE(vh.placa, v.placa) as placa, COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo, v.total, v.status,
                c.nombre as cliente_nombre, s.nombre as usuario_nombre
                FROM table_facturas v
                LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                LEFT JOIN table_clientes c ON v.cliente_id = c.id
                LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                LEFT JOIN table_staff s ON u.staff_id = s.id
                WHERE v.orden_id IS NULL AND (v.placa IS NULL OR v.placa = '') AND v.status IN ('COMPLETADO', 'CREDITO')";

        if ($search) {
            $sql .= " AND (v.id LIKE :search OR vh.placa LIKE :search OR v.placa LIKE :search OR c.nombre LIKE :search)";
        }

        $sql .= " ORDER BY v.fecha DESC";

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $this->db->query($sql);
        if ($search) $this->db->bind(':search', "%$search%");
        if ($limit !== null && $offset !== null) {
            $this->db->bind(':limit', (int)$limit);
            $this->db->bind(':offset', (int)$offset);
        }
        return $this->db->resultSet();
    }

    public function contarVentas($search = null) {
        $sql = "SELECT COUNT(*) as total 
                FROM table_facturas v 
                LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                LEFT JOIN table_clientes c ON v.cliente_id = c.id WHERE v.orden_id IS NULL AND (v.placa IS NULL OR v.placa = '') AND v.status IN ('COMPLETADO', 'CREDITO')";
        if ($search) $sql .= " AND (v.id LIKE :search OR vh.placa LIKE :search OR v.placa LIKE :search OR c.nombre LIKE :search)";
        $this->db->query($sql);
        if ($search) $this->db->bind(':search', "%$search%");
        return (int)$this->db->single()->total;
    }

    /**
     * Obtiene los detalles completos de una venta específica.
     * @param int $ventaId ID de la venta.
     * @return object|false Objeto de venta con sus ítems, o false si no se encuentra.
     */
    public function obtenerDetalleVenta($ventaId) {
        $this->db->query("SELECT v.id, v.fecha, COALESCE(vh.placa, v.placa) as placa, COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo, v.subtotal, v.iva_monto, v.total, v.status,
                          v.pago_efectivo, v.pago_transferencia, v.saldo_pendiente,
                          c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.email as cliente_email,
                          COALESCE(sm.nombre, (SELECT s2.nombre FROM table_facturas_detalle vd2 JOIN table_staff s2 ON vd2.mecanico_id = s2.id WHERE vd2.factura_id = v.id AND vd2.mecanico_id IS NOT NULL LIMIT 1)) as mecanico_nombre,
                          COALESCE(s.nombre, u.username) as usuario_nombre, s.cargo as usuario_cargo
                          FROM table_facturas v
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_staff sm ON os.mecanico_id = sm.id
                          LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE v.id = :id AND v.status IN ('COMPLETADO', 'CREDITO', 'PENDIENTE')");
        $this->db->bind(':id', $ventaId);
        $venta = $this->db->single();

        if ($venta) {
            $this->db->query("SELECT vd.descripcion, vd.cantidad, vd.precio_unitario FROM table_facturas_detalle vd WHERE vd.factura_id = :vid");
            $this->db->bind(':vid', $ventaId);
            $venta->items = $this->db->resultSet();
        }
        return $venta;
    }
}