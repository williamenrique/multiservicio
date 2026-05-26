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
    public function listarVentas() {
        $this->db->query("SELECT v.id, v.fecha, v.placa, v.modelo_vehiculo, v.total, v.status,
                          c.nombre as cliente_nombre, s.nombre as usuario_nombre
                          FROM table_ventas v
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE v.status IN ('COMPLETADO', 'CREDITO')
                          ORDER BY v.fecha DESC");
        return $this->db->resultSet();
    }

    /**
     * Obtiene los detalles completos de una venta específica.
     * @param int $ventaId ID de la venta.
     * @return object|false Objeto de venta con sus ítems, o false si no se encuentra.
     */
    public function obtenerDetalleVenta($ventaId) {
        $this->db->query("SELECT v.id, v.fecha, v.placa, v.modelo_vehiculo, v.subtotal, v.iva_monto, v.total, v.status,
                          v.pago_efectivo, v.pago_transferencia, v.saldo_pendiente,
                          c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.email as cliente_email,
                          s.nombre as usuario_nombre, s.cargo as usuario_cargo
                          FROM table_ventas v
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE v.id = :id AND v.status IN ('COMPLETADO', 'CREDITO')");
        $this->db->bind(':id', $ventaId);
        $venta = $this->db->single();

        if ($venta) {
            $this->db->query("SELECT vd.descripcion, vd.cantidad, vd.precio_unitario FROM table_ventas_detalle vd WHERE vd.venta_id = :vid");
            $this->db->bind(':vid', $ventaId);
            $venta->items = $this->db->resultSet();
        }
        return $venta;
    }
}