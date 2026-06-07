<?php
class ModelOrden {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    public function crear($data) {
        $this->db->query("INSERT INTO table_ordenes_servicio (cliente_id, placa, mecanico_id, kilometraje, nivel_combustible, diagnostico_entrada, observaciones) 
                          VALUES (:cid, :placa, :mid, :km, :comb, :diag, :obs)");
        $this->db->bind(':cid', $data['cliente_id']);
        $this->db->bind(':placa', $data['placa']);
        $this->db->bind(':mid', !empty($data['mecanico_id']) ? $data['mecanico_id'] : null);
        $this->db->bind(':km', $data['kilometraje']);
        $this->db->bind(':comb', $data['nivel_combustible']);
        $this->db->bind(':diag', mb_strtoupper($data['observaciones_entrada'] ?? '', 'UTF-8'));
        $this->db->bind(':obs', mb_strtoupper($data['observaciones'] ?? '', 'UTF-8'));
        
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
        $this->db->query("SELECT os.*, v.placa, v.marca, v.modelo, s.nombre as mecanico_nombre 
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
}