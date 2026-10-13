<?php
/**
 * Modelo de Auditoría
 * Recupera los registros de la bitácora del sistema.
 */
class ModelAudit {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function listarLogs($limit = 50, $offset = 0, $filters = []) {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['desde'])) {
            $where .= " AND DATE(a.fecha) >= :desde";
            $params[':desde'] = $filters['desde'];
        }
        if (!empty($filters['hasta'])) {
            $where .= " AND DATE(a.fecha) <= :hasta";
            $params[':hasta'] = $filters['hasta'];
        }
        if (!empty($filters['usuario_id'])) {
            $where .= " AND a.usuario_id = :usuario_id";
            $params[':usuario_id'] = (int)$filters['usuario_id'];
        }
        if (!empty($filters['modulo'])) {
            $where .= " AND a.modulo = :modulo";
            $params[':modulo'] = $filters['modulo'];
        }
        if (!empty($filters['accion'])) {
            $where .= " AND a.accion = :accion";
            $params[':accion'] = $filters['accion'];
        }
        if (!empty($filters['search'])) {
            $where .= " AND (a.descripcion LIKE :search OR u.username LIKE :search OR s.nombre LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }

        // Contar total
        $this->db->query("SELECT COUNT(*) as total 
                          FROM table_audit_logs a 
                          LEFT JOIN table_usuarios u ON a.usuario_id = u.id 
                          LEFT JOIN table_staff s ON u.staff_id = s.id 
                          $where");
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }
        $total = (int)$this->db->single()->total;

        // Obtener datos
        $this->db->query("SELECT a.*, u.username, s.nombre as staff_name 
                          FROM table_audit_logs a 
                          LEFT JOIN table_usuarios u ON a.usuario_id = u.id 
                          LEFT JOIN table_staff s ON u.staff_id = s.id 
                          $where
                          ORDER BY a.fecha DESC
                          LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }
        $this->db->bind(':limit', (int)$limit);
        $this->db->bind(':offset', (int)$offset);

        return ['data' => $this->db->resultSet(), 'total' => $total];
    }

    public function obtenerUsuarios() {
        $this->db->query("SELECT u.id, u.username, s.nombre as staff_name 
                          FROM table_usuarios u 
                          LEFT JOIN table_staff s ON u.staff_id = s.id 
                          WHERE u.estado = 'ACTIVO'
                          ORDER BY u.username");
        return $this->db->resultSet();
    }

    public function obtenerModulos() {
        $this->db->query("SELECT DISTINCT modulo FROM table_audit_logs ORDER BY modulo");
        return $this->db->resultSet();
    }

    public function obtenerAcciones() {
        $this->db->query("SELECT DISTINCT accion FROM table_audit_logs ORDER BY accion");
        return $this->db->resultSet();
    }
}