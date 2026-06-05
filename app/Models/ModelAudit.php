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

    public function listarLogs() {
        $this->db->query("SELECT a.*, u.username, s.nombre as staff_name 
                          FROM table_audit_logs a 
                          LEFT JOIN table_usuarios u ON a.usuario_id = u.id 
                          LEFT JOIN table_staff s ON u.staff_id = s.id 
                          ORDER BY a.fecha DESC");
        return $this->db->resultSet();
    }
}