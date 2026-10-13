<?php
/**
 * Modelo de Facturas
 * 
 * v2.1 (2026-10-08):
 *   • listar() ahora acepta filtro por `estado` (COMPLETADO/CREDITO/ANULADO)
 *     y por `cliente_id`. Incluye `ultimo_abono` y `estado_gestion`.
 *   • contarFiltrados() refleja los mismos filtros.
 *   • obtenerPorId() incluye `estado_gestion` y datos del último abono.
 */
class ModelFacturas {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    /**
     * Lista facturas con filtros: búsqueda, rango de fechas, estado y cliente.
     */
    public function listar($limit = null, $offset = null, $search = null, $desde = null, $hasta = null, $estado = null, $clienteId = null) {
        $sql = "SELECT v.*, 
                       v.origen,
                       v.estado_gestion,
                       CONCAT('FAC-', LPAD(v.id, 3, '0')) as id_formateado,
                       c.nombre as cliente_nombre, 
                       COALESCE(sv.nombre, u.username, 'SISTEMA') as vendedor_nombre,
                       COALESCE(vh.placa, v.placa) as placa,
                       COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo,
                       CASE 
                           WHEN v.orden_id IS NOT NULL THEN 'OS' 
                           WHEN (v.placa IS NOT NULL AND v.placa != '') THEN 'TALLER' 
                           ELSE 'MOSTRADOR' 
                       END as tipo_procedencia,
                       (SELECT COUNT(*) FROM table_facturas_detalle WHERE factura_id = v.id AND producto_id IS NOT NULL) as cant_productos,
                       (SELECT COUNT(*) FROM table_facturas_detalle WHERE factura_id = v.id AND producto_id IS NULL) as cant_servicios,
                       (SELECT MAX(a.fecha) FROM table_abonos_clientes a WHERE a.factura_id = v.id) as ultimo_abono_fecha,
                       (SELECT a.monto FROM table_abonos_clientes a WHERE a.factura_id = v.id ORDER BY a.fecha DESC LIMIT 1) as ultimo_abono_monto
                FROM table_facturas v
                LEFT JOIN table_clientes c ON v.cliente_id = c.id
                LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                LEFT JOIN table_staff sv ON u.staff_id = sv.id
                LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                WHERE v.status IN ('COMPLETADO', 'CREDITO', 'ANULADO')";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (v.id LIKE :search OR c.nombre LIKE :search OR vh.placa LIKE :search OR v.placa LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if ($desde) {
            $sql .= " AND DATE(v.fecha) >= :desde";
            $params[':desde'] = $desde;
        }
        
        if ($hasta) {
            $sql .= " AND DATE(v.fecha) <= :hasta";
            $params[':hasta'] = $hasta;
        }

        // MEJORA 9: filtro por estado
        if ($estado && in_array(strtoupper($estado), ['COMPLETADO', 'CREDITO', 'ANULADO'], true)) {
            $sql .= " AND v.status = :estado";
            $params[':estado'] = strtoupper($estado);
        }

        // MEJORA 10: filtro por cliente (para "ver cliente con un clic")
        if ($clienteId) {
            $sql .= " AND v.cliente_id = :cliente_id";
            $params[':cliente_id'] = $clienteId;
        }

        $sql .= " ORDER BY v.fecha DESC, v.id DESC";
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = (int)$limit;
            $params[':offset'] = (int)$offset;
        }
        
        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return $this->db->resultSet();
    }

    public function contarTotal() {
        $this->db->query("SELECT COUNT(*) as total FROM table_facturas WHERE status IN ('COMPLETADO', 'CREDITO')");
        return (int)$this->db->single()->total;
    }

    public function contarFiltrados($search = null, $desde = null, $hasta = null, $estado = null, $clienteId = null) {
        $sql = "SELECT COUNT(*) as total FROM table_facturas v
                LEFT JOIN table_clientes c ON v.cliente_id = c.id
                LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                WHERE v.status IN ('COMPLETADO', 'CREDITO', 'ANULADO')";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (v.id LIKE :search OR c.nombre LIKE :search OR vh.placa LIKE :search OR v.placa LIKE :search)";
            $params[':search'] = "%$search%";
        }
        if ($desde) {
            $sql .= " AND DATE(v.fecha) >= :desde";
            $params[':desde'] = $desde;
        }
        if ($hasta) {
            $sql .= " AND DATE(v.fecha) <= :hasta";
            $params[':hasta'] = $hasta;
        }
        if ($estado && in_array(strtoupper($estado), ['COMPLETADO', 'CREDITO', 'ANULADO'], true)) {
            $sql .= " AND v.status = :estado";
            $params[':estado'] = strtoupper($estado);
        }
        if ($clienteId) {
            $sql .= " AND v.cliente_id = :cliente_id";
            $params[':cliente_id'] = $clienteId;
        }

        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return (int)$this->db->single()->total;
    }

    /**
     * Obtiene una factura por ID con todos sus detalles, incluyendo
     * estado_gestion, ultimo_abono y datos para "ver cliente".
     */
    public function obtenerPorId($id) {
        $this->db->query("SELECT v.*, 
                                 v.origen,
                                 v.estado_gestion,
                                 CONCAT('FAC-', LPAD(v.id, 3, '0')) as id_formateado,
                                 c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.email as cliente_email, 
                                 COALESCE(vh.placa, v.placa) as placa, 
                                 COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo,
                                 vh.marca as marca_vehiculo,
                                 COALESCE(st_m.nombre, (SELECT s2.nombre FROM table_facturas_detalle vd2 JOIN table_staff s2 ON vd2.mecanico_id = s2.id WHERE vd2.factura_id = v.id AND vd2.mecanico_id IS NOT NULL LIMIT 1)) as mecanico_nombre,
                                 sv.nombre as vendedor_nombre,
                                 os.kilometraje, os.nivel_combustible, os.diagnostico_entrada as diagnostico_entrada, os.observaciones as observaciones_orden,
                                 CASE 
                                     WHEN v.orden_id IS NOT NULL THEN 'OS' 
                                     WHEN (v.placa IS NOT NULL AND v.placa != '') THEN 'TALLER' 
                                     ELSE 'MOSTRADOR' 
                                 END as tipo_procedencia
                          FROM table_facturas v
                          LEFT JOIN table_ordenes_servicio os ON v.orden_id = os.id
                          LEFT JOIN table_staff st_m ON os.mecanico_id = st_m.id
                          LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                          LEFT JOIN table_staff sv ON u.staff_id = sv.id
                          WHERE v.id = :id");
        $this->db->bind(':id', $id);
        $venta = $this->db->single();

        if ($venta) {
            $this->db->query("SELECT vd.*, s.nombre as mecanico_nombre 
                              FROM table_facturas_detalle vd
                              LEFT JOIN table_staff s ON vd.mecanico_id = s.id 
                              WHERE vd.factura_id = :vid");
            $this->db->bind(':vid', $id);
            $venta->items = $this->db->resultSet();

            // MEJORA 3: Cargar historial de abonos en la vista ver.php
            $this->db->query("SELECT a.*, COALESCE(s.nombre, u.username, 'SISTEMA') as usuario_nombre
                              FROM table_abonos_clientes a
                              LEFT JOIN table_usuarios u ON a.usuario_id = u.id
                              LEFT JOIN table_staff s ON u.staff_id = s.id
                              WHERE a.factura_id = :vid
                              ORDER BY a.fecha DESC");
            $this->db->bind(':vid', $id);
            $venta->abonos = $this->db->resultSet();
        }

        if ($venta && $venta->orden_id) {
            $this->db->query("SELECT item, observacion FROM table_orden_checklist WHERE orden_id = :oid");
            $this->db->bind(':oid', $venta->orden_id);
            $venta->checklist = $this->db->resultSet();
        }

        return $venta;
    }

    public function obtenerVentaCompleta($id) {
        return $this->obtenerPorId($id);
    }
}