<?php
class ModelOrden {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    public function crear($data) {
        $this->db->query("INSERT INTO table_ordenes_servicio (cliente_id, placa, mecanico_id, kilometraje, nivel_combustible, diagnostico_entrada, diagnostico_salida, observaciones, estado, fecha_entrega_estimada) 
                          VALUES (:cid, :placa, :mid, :km, :comb, :diag, :diag_salida, :obs, 'RECIBIDO', :f_entrega)");
        $this->db->bind(':cid', $data['cliente_id']);
        $this->db->bind(':placa', $data['placa']);
        $this->db->bind(':mid', !empty($data['mecanico_id']) ? $data['mecanico_id'] : null);
        $this->db->bind(':km', $data['kilometraje']);
        $this->db->bind(':comb', $data['nivel_combustible']);
        $this->db->bind(':diag', mb_strtoupper($data['observaciones_entrada'] ?? '', 'UTF-8'));
        $this->db->bind(':diag_salida', null);
        $this->db->bind(':obs', mb_strtoupper($data['observaciones'] ?? '', 'UTF-8'));
        $this->db->bind(':f_entrega', !empty($data['fecha_entrega']) ? $data['fecha_entrega'] : null);
        
        if($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function guardarChecklist($ordenId, $items) {
        foreach ($items as $item) {
            $this->db->query("INSERT INTO table_orden_checklist (orden_id, item, estado, observacion) 
                              VALUES (:oid, :item, :estado, :obs)");
            $this->db->bind(':oid', $ordenId);
            $this->db->bind(':item', mb_strtoupper($item['item'], 'UTF-8'));
            // Si el ítem llega en el array es porque se marcó el checkbox en el formulario
            $this->db->bind(':estado', 1); 
            $this->db->bind(':obs', mb_strtoupper($item['nota'] ?? '', 'UTF-8'));
            $this->db->execute();
        }
        return true;
    }

    public function obtenerChecklist($ordenId) {
        $this->db->query("SELECT * FROM table_orden_checklist WHERE orden_id = :oid");
        $this->db->bind(':oid', $ordenId);
        return $this->db->resultSet();
    }

    public function obtenerResumenTaller() {
        $this->db->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'RECIBIDO' THEN 1 ELSE 0 END) as recibidos,
            SUM(CASE WHEN estado IN ('DIAGNOSTICANDO', 'EN_REPARACION') THEN 1 ELSE 0 END) as reparacion,
            SUM(CASE WHEN estado = 'LISTO' THEN 1 ELSE 0 END) as listos,
            SUM(CASE WHEN mecanico_id IS NULL THEN 1 ELSE 0 END) as sin_mecanico,
            SUM(CASE WHEN fecha_entrega_estimada < NOW() AND estado NOT IN ('LISTO', 'ENTREGADO') THEN 1 ELSE 0 END) as vencidas
            FROM table_ordenes_servicio 
            WHERE estado NOT IN ('ENTREGADO', 'ANULADO')");
        return $this->db->single();
    }

    public function actualizarEstado($id, $nuevoEstado, $comentario = '') {
        try {
            $this->db->beginTransaction();
            
            // 1. Obtener estado anterior
            $this->db->query("SELECT estado FROM table_ordenes_servicio WHERE id = :id");
            $this->db->bind(':id', $id);
            $anterior = $this->db->single()->estado;

            // 2. Actualizar estado
            $this->db->query("UPDATE table_ordenes_servicio SET estado = :estado WHERE id = :id");
            $this->db->bind(':estado', $nuevoEstado);
            $this->db->bind(':id', $id);
            $this->db->execute();

            // 3. Log de auditoría de estado
            $this->db->query("INSERT INTO table_orden_estados_log (orden_id, estado_anterior, estado_nuevo, usuario_id, comentario) 
                              VALUES (:id, :ant, :nue, :uid, :com)");
            $this->db->bind(':id', $id); $this->db->bind(':ant', $anterior); $this->db->bind(':nue', $nuevoEstado);
            $this->db->bind(':uid', $_SESSION['user_id']); $this->db->bind(':com', $comentario);
            $this->db->execute();

            return $this->db->commit();
        } catch (Exception $e) { $this->db->rollBack(); return false; }
    }

    public function obtenerLogsEstado($orden_id) {
        $this->db->query("SELECT l.*, s.nombre as usuario_nombre 
                          FROM table_orden_estados_log l
                          LEFT JOIN table_usuarios u ON l.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE l.orden_id = :id 
                          ORDER BY l.fecha ASC");
        $this->db->bind(':id', $orden_id);
        return $this->db->resultSet();
    }

    public function obtenerOrdenesActivas() {
        $this->db->query("SELECT os.*, v.placa, v.marca, v.modelo, s.nombre as mecanico_nombre,
                          TIMESTAMPDIFF(MINUTE, NOW(), os.fecha_entrega_estimada) as minutos_restantes,
                          (SELECT status FROM table_facturas WHERE orden_id = os.id AND status != 'ANULADO' ORDER BY id DESC LIMIT 1) as factura_status
                          FROM table_ordenes_servicio os
                          INNER JOIN table_vehiculos v ON os.placa = v.placa
                          LEFT JOIN table_staff s ON os.mecanico_id = s.id
                          WHERE os.estado NOT IN ('ENTREGADO')
                          ORDER BY os.fecha_ingreso DESC");
        return $this->db->resultSet();
    }

    public function obtenerDetalleOrden($id) {
        $this->db->query("SELECT os.*, v.placa, v.marca, v.modelo, v.color, v.anio, 
                          c.nombre as cliente_nombre, c.telefono as cliente_telefono,
                          s.nombre as mecanico_nombre
                          FROM table_ordenes_servicio os
                          INNER JOIN table_vehiculos v ON os.placa = v.placa
                          INNER JOIN table_clientes c ON v.cliente_id = c.id
                          LEFT JOIN table_staff s ON os.mecanico_id = s.id
                          WHERE os.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function obtenerHistorialExtendido($tipo, $valor) {
        $sql = "SELECT os.*, v.marca, v.modelo, s.nombre as mecanico_nombre, c.nombre as cliente_nombre
                FROM table_ordenes_servicio os
                INNER JOIN table_vehiculos v ON os.placa = v.placa
                INNER JOIN table_clientes c ON v.cliente_id = c.id
                LEFT JOIN table_staff s ON os.mecanico_id = s.id ";
        
        if (strtoupper($tipo) === 'MECANICO') {
            $sql .= "WHERE os.mecanico_id = :val ";
        } elseif (strtoupper($tipo) === 'CLIENTE') {
            $sql .= "WHERE os.cliente_id = :val ";
        } else {
            $sql .= "WHERE os.placa = :val ";
        }
        
        $sql .= "ORDER BY os.fecha_ingreso DESC";
        $this->db->query($sql);
        $this->db->bind(':val', $valor);
        return $this->db->resultSet();
    }

    /**
     * Obtiene las órdenes de servicio finalizadas (ENTREGADO) con paginación y búsqueda.
     */
    public function obtenerOrdenesCerradas($limit = 10, $offset = 0, $search = null) {
        $sql = "SELECT os.*, v.marca, v.modelo, s.nombre as mecanico_nombre, c.nombre as cliente_nombre
                FROM table_ordenes_servicio os
                INNER JOIN table_vehiculos v ON os.placa = v.placa
                INNER JOIN table_clientes c ON os.cliente_id = c.id
                LEFT JOIN table_staff s ON os.mecanico_id = s.id
                WHERE os.estado = 'ENTREGADO'";
        
        if ($search) {
            $sql .= " AND (os.id LIKE :search OR os.placa LIKE :search OR c.nombre LIKE :search OR s.nombre LIKE :search)";
        }

        $sql .= " ORDER BY os.fecha_entrega_real DESC LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        if ($search) $this->db->bind(':search', "%$search%");
        $this->db->bind(':limit', (int)$limit);
        $this->db->bind(':offset', (int)$offset);
        
        return $this->db->resultSet();
    }

    public function contarCerradas($search = null) {
        $sql = "SELECT COUNT(*) as total FROM table_ordenes_servicio os
                INNER JOIN table_clientes c ON os.cliente_id = c.id
                LEFT JOIN table_staff s ON os.mecanico_id = s.id
                WHERE os.estado = 'ENTREGADO'";
        
        if ($search) {
            $sql .= " AND (os.id LIKE :search OR os.placa LIKE :search OR c.nombre LIKE :search OR s.nombre LIKE :search)";
        }
        
        $this->db->query($sql);
        if ($search) $this->db->bind(':search', "%$search%");
        return (int)$this->db->single()->total;
    }

    /**
     * Obtiene el último kilometraje registrado para una placa específica.
     */
    public function obtenerUltimoKilometrajePorPlaca($placa) {
        $this->db->query("SELECT kilometraje FROM table_ordenes_servicio WHERE placa = :placa ORDER BY fecha_ingreso DESC LIMIT 1");
        $this->db->bind(':placa', $placa);
        return $this->db->single();
    }
}