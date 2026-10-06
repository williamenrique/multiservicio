<?php
/**
 * Modelo de Emails
 * Gestiona el envío, historial y plantillas de emails del sistema.
 */
class ModelEmail {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    public function listar($limit = 20, $offset = 0, $filters = []) {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['tipo'])) {
            $where .= " AND e.tipo = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }
        if (!empty($filters['estado'])) {
            $where .= " AND e.estado = :estado";
            $params[':estado'] = $filters['estado'];
        }
        if (!empty($filters['desde'])) {
            $where .= " AND DATE(e.fecha_creacion) >= :desde";
            $params[':desde'] = $filters['desde'];
        }
        if (!empty($filters['hasta'])) {
            $where .= " AND DATE(e.fecha_creacion) <= :hasta";
            $params[':hasta'] = $filters['hasta'];
        }
        if (!empty($filters['search'])) {
            $where .= " AND (e.destinatario_email LIKE :search OR e.destinatario_nombre LIKE :search OR e.asunto LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }

        $this->db->query("SELECT COUNT(*) as total FROM table_emails e $where");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        $total = (int)$this->db->single()->total;

        $this->db->query("SELECT e.*, u.username as usuario_nombre 
                          FROM table_emails e
                          LEFT JOIN table_usuarios u ON e.usuario_id = u.id
                          $where
                          ORDER BY e.fecha_creacion DESC
                          LIMIT :limit OFFSET :offset");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        $this->db->bind(':limit', (int)$limit);
        $this->db->bind(':offset', (int)$offset);

        return ['data' => $this->db->resultSet(), 'total' => $total];
    }

    public function obtenerPorId($id) {
        $this->db->query("SELECT e.*, u.username as usuario_nombre, u.email as usuario_email
                          FROM table_emails e
                          LEFT JOIN table_usuarios u ON e.usuario_id = u.id
                          WHERE e.id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    public function registrar($data) {
        $this->db->query("INSERT INTO table_emails 
                          (tipo, destinatario_email, destinatario_nombre, asunto, cuerpo_html, cuerpo_texto, 
                           adjuntos, referencia_tipo, referencia_id, estado, error_mensaje, usuario_id, fecha_envio)
                          VALUES 
                          (:tipo, :dest_email, :dest_nombre, :asunto, :cuerpo_html, :cuerpo_texto,
                           :adjuntos, :ref_tipo, :ref_id, :estado, :error, :usuario_id, :fecha_envio)");
        
        $this->db->bind(':tipo', mb_strtoupper($data['tipo'] ?? 'OTRO', 'UTF-8'));
        // CORREO EN MINÚSCULAS
        $this->db->bind(':dest_email', mb_strtolower($data['destinatario_email'], 'UTF-8'));
        $this->db->bind(':dest_nombre', mb_strtoupper($data['destinatario_nombre'] ?? '', 'UTF-8'));
        $this->db->bind(':asunto', mb_strtoupper($data['asunto'], 'UTF-8'));
        $this->db->bind(':cuerpo_html', $data['cuerpo_html']);
        $this->db->bind(':cuerpo_texto', $data['cuerpo_texto'] ?? null);
        $this->db->bind(':adjuntos', $data['adjuntos'] ? json_encode($data['adjuntos']) : null);
        $this->db->bind(':ref_tipo', mb_strtoupper($data['referencia_tipo'] ?? 'NINGUNO', 'UTF-8'));
        $this->db->bind(':ref_id', $data['referencia_id'] ?? null);
        $this->db->bind(':estado', mb_strtoupper($data['estado'] ?? 'PENDIENTE', 'UTF-8'));
        $this->db->bind(':error', isset($data['error_mensaje']) ? mb_strtoupper($data['error_mensaje'], 'UTF-8') : null);
        $this->db->bind(':usuario_id', $data['usuario_id'] ?? null);
        $this->db->bind(':fecha_envio', $data['fecha_envio'] ?? date('Y-m-d H:i:s'));
        
        return $this->db->execute();
    }

    public function actualizarEstado($id, $estado, $error = null) {
        $this->db->query("UPDATE table_emails SET estado = :estado, error_mensaje = :error WHERE id = :id");
        $this->db->bind(':estado', mb_strtoupper($estado, 'UTF-8'));
        $this->db->bind(':error', $error !== null ? mb_strtoupper($error, 'UTF-8') : null);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function obtenerPlantillas($tipo = null, $soloActivas = true) {
        $where = "WHERE 1=1";
        $params = [];
        
        if ($tipo) {
            $where .= " AND tipo = :tipo";
            $params[':tipo'] = mb_strtoupper($tipo, 'UTF-8');
        }
        if ($soloActivas) {
            $where .= " AND activo = 1";
        }
        
        $this->db->query("SELECT * FROM table_email_templates $where ORDER BY nombre");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->resultSet();
    }

    public function obtenerPlantilla($id) {
        $this->db->query("SELECT * FROM table_email_templates WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    public function guardarPlantilla($data, $id = null) {
        if ($id) {
            $this->db->query("UPDATE table_email_templates 
                              SET nombre = :nombre, tipo = :tipo, asunto = :asunto, 
                                  cuerpo_html = :cuerpo_html, variables_disponibles = :vars, activo = :activo
                              WHERE id = :id");
            $this->db->bind(':id', (int)$id);
        } else {
            $this->db->query("INSERT INTO table_email_templates 
                              (nombre, tipo, asunto, cuerpo_html, variables_disponibles, activo)
                              VALUES (:nombre, :tipo, :asunto, :cuerpo_html, :vars, :activo)");
        }
        
        $this->db->bind(':nombre', mb_strtoupper($data['nombre'], 'UTF-8'));
        $this->db->bind(':tipo', mb_strtoupper($data['tipo'] ?? 'OTRO', 'UTF-8'));
        $this->db->bind(':asunto', mb_strtoupper($data['asunto'], 'UTF-8'));
        $this->db->bind(':cuerpo_html', $data['cuerpo_html']);
        $this->db->bind(':vars', $data['variables_disponibles'] ? json_encode($data['variables_disponibles']) : null);
        $this->db->bind(':activo', $data['activo'] ?? 1);
        
        return $this->db->execute();
    }

    public function eliminarPlantilla($id) {
        $this->db->query("DELETE FROM table_email_templates WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function obtenerEstadisticas($desde = null, $hasta = null) {
        $where = "WHERE 1=1";
        $params = [];
        
        if ($desde) {
            $where .= " AND DATE(fecha_creacion) >= :desde";
            $params[':desde'] = $desde;
        }
        if ($hasta) {
            $where .= " AND DATE(fecha_creacion) <= :hasta";
            $params[':hasta'] = $hasta;
        }
        
        $this->db->query("SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN estado = 'ENVIADO' THEN 1 ELSE 0 END) as enviados,
                            SUM(CASE WHEN estado = 'FALLIDO' THEN 1 ELSE 0 END) as fallidos,
                            SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) as pendientes
                          FROM table_emails $where");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->single();
    }
}