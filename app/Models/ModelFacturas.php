<?php
/**
 * Modelo de Facturas
 * Maneja la consulta y listado de facturas realizadas
 */
class ModelFacturas {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    /**
     * Lista facturas con soporte opcional para paginación (LIMIT/OFFSET)
     * Incluye filtros de búsqueda y rango de fechas
     */
    public function listar($limit = null, $offset = null, $search = null, $desde = null, $hasta = null) {
        $sql = "SELECT v.*, 
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
                       (SELECT COUNT(*) FROM table_facturas_detalle WHERE factura_id = v.id AND producto_id IS NULL) as cant_servicios
                FROM table_facturas v
                LEFT JOIN table_clientes c ON v.cliente_id = c.id
                LEFT JOIN table_usuarios u ON v.usuario_id = u.id
                LEFT JOIN table_staff sv ON u.staff_id = sv.id
                LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                WHERE v.status IN ('COMPLETADO', 'CREDITO')";
        
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

    /**
     * Retorna la cantidad total de facturas completadas
     */
    public function contarTotal() {
        $this->db->query("SELECT COUNT(*) as total FROM table_facturas WHERE status IN ('COMPLETADO', 'CREDITO')");
        return (int)$this->db->single()->total;
    }

    /**
     * Retorna la cantidad de facturas que coinciden con los filtros
     */
    public function contarFiltrados($search = null, $desde = null, $hasta = null) {
        $sql = "SELECT COUNT(*) as total FROM table_facturas v
                LEFT JOIN table_clientes c ON v.cliente_id = c.id
                LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                WHERE v.status IN ('COMPLETADO', 'CREDITO')";
        
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

        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return (int)$this->db->single()->total;
    }

    /**
     * Obtiene una factura por ID con todos sus detalles
     */
    public function obtenerPorId($id) {
        $this->db->query("SELECT v.*, 
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
        }

        // Cargar Checklist si la factura proviene de una Orden de Servicio
        if ($venta && $venta->orden_id) {
            $this->db->query("SELECT item, observacion FROM table_orden_checklist WHERE orden_id = :oid");
            $this->db->bind(':oid', $venta->orden_id);
            $venta->checklist = $this->db->resultSet();
        }

        return $venta;
    }

    /**
     * Obtiene los detalles completos de una venta para su impresión
     * (Alias para compatibilidad con el controlador)
     */
    public function obtenerVentaCompleta($id) {
        return $this->obtenerPorId($id);
    }
}