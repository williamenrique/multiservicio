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

    /**
     * Lista emails con paginación y filtros
     */
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

        // Contar total
        $this->db->query("SELECT COUNT(*) as total FROM table_emails e $where");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        $total = (int)$this->db->single()->total;

        // Obtener datos
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

    /**
     * Obtiene un email por ID
     */
    public function obtenerPorId($id) {
        $this->db->query("SELECT e.*, u.username as usuario_nombre, u.email as usuario_email
                          FROM table_emails e
                          LEFT JOIN table_usuarios u ON e.usuario_id = u.id
                          WHERE e.id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    /**
     * Registra un email enviado
     */
    public function registrar($data) {
        $this->db->query("INSERT INTO table_emails 
                          (tipo, destinatario_email, destinatario_nombre, asunto, cuerpo_html, cuerpo_texto, 
                           adjuntos, referencia_tipo, referencia_id, estado, error_mensaje, usuario_id, fecha_envio)
                          VALUES 
                          (:tipo, :dest_email, :dest_nombre, :asunto, :cuerpo_html, :cuerpo_texto,
                           :adjuntos, :ref_tipo, :ref_id, :estado, :error, :usuario_id, :fecha_envio)");
        
        $this->db->bind(':tipo', $data['tipo'] ?? 'OTRO');
        $this->db->bind(':dest_email', $data['destinatario_email']);
        $this->db->bind(':dest_nombre', $data['destinatario_nombre'] ?? null);
        $this->db->bind(':asunto', $data['asunto']);
        $this->db->bind(':cuerpo_html', $data['cuerpo_html']);
        $this->db->bind(':cuerpo_texto', $data['cuerpo_texto'] ?? null);
        $this->db->bind(':adjuntos', $data['adjuntos'] ? json_encode($data['adjuntos']) : null);
        $this->db->bind(':ref_tipo', $data['referencia_tipo'] ?? 'NINGUNO');
        $this->db->bind(':ref_id', $data['referencia_id'] ?? null);
        $this->db->bind(':estado', $data['estado'] ?? 'PENDIENTE');
        $this->db->bind(':error', $data['error_mensaje'] ?? null);
        $this->db->bind(':usuario_id', $data['usuario_id'] ?? null);
        $this->db->bind(':fecha_envio', $data['fecha_envio'] ?? date('Y-m-d H:i:s'));
        
        return $this->db->execute();
    }

    /**
     * Actualiza el estado de un email
     */
    public function actualizarEstado($id, $estado, $error = null) {
        $this->db->query("UPDATE table_emails SET estado = :estado, error_mensaje = :error WHERE id = :id");
        $this->db->bind(':estado', $estado);
        $this->db->bind(':error', $error);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    /**
     * Obtiene plantillas de email por tipo
     */
    public function obtenerPlantillas($tipo = null, $soloActivas = true) {
        $where = "WHERE 1=1";
        $params = [];
        
        if ($tipo) {
            $where .= " AND tipo = :tipo";
            $params[':tipo'] = $tipo;
        }
        if ($soloActivas) {
            $where .= " AND activo = 1";
        }
        
        $this->db->query("SELECT * FROM table_email_templates $where ORDER BY nombre");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        return $this->db->resultSet();
    }

    /**
     * Obtiene una plantilla por ID
     */
    public function obtenerPlantilla($id) {
        $this->db->query("SELECT * FROM table_email_templates WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    /**
     * Guarda o actualiza una plantilla
     */
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
        
        $this->db->bind(':nombre', $data['nombre']);
        $this->db->bind(':tipo', $data['tipo'] ?? 'OTRO');
        $this->db->bind(':asunto', $data['asunto']);
        $this->db->bind(':cuerpo_html', $data['cuerpo_html']);
        $this->db->bind(':vars', $data['variables_disponibles'] ? json_encode($data['variables_disponibles']) : null);
        $this->db->bind(':activo', $data['activo'] ?? 1);
        
        return $this->db->execute();
    }

    /**
     * Elimina una plantilla
     */
    public function eliminarPlantilla($id) {
        $this->db->query("DELETE FROM table_email_templates WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    /**
     * Obtiene estadísticas de emails
     */
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