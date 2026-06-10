<?php
/**
 * Modelo de Usuario
 * Gestiona la autenticación, sesiones y recuperación de cuentas.
 */
class ModelUsuario {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Busca un usuario activo cruzando datos con el personal y sus roles.
     * Permite identificar al usuario por su Username, Email o Cédula.
     */
    public function buscarPorIdentificador($identificador) {
        $this->db->query("SELECT u.*, s.nombre, s.email, s.foto, r.nombre_rol 
                          FROM table_usuarios u
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          LEFT JOIN table_roles r ON u.role_id = r.id
                          WHERE (u.username = :id OR s.email = :id OR s.cedula = :id)
                          AND u.estado = 'ACTIVO'");
        $this->db->bind(':id', $identificador);
        return $this->db->single();
    }

    public function actualizarPassword($id, $hash) {
        $this->db->query("UPDATE table_usuarios SET password = :hash WHERE id = :id");
        $this->db->bind(':hash', $hash);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function obtenerSesionActiva($usuarioId) {
        $this->db->query("SELECT session_id FROM table_usuario_sessions WHERE usuario_id = :uid");
        $this->db->bind(':uid', $usuarioId);
        return $this->db->single();
    }

    public function registrarSesion($data) {
        // REPLACE asegura que solo exista un registro por usuario_id (Sesión Única)
        $this->db->query("REPLACE INTO table_usuario_sessions (usuario_id, session_id, ip_address, usuario_agent) 
                          VALUES (:uid, :sid, :ip, :ua)");
        $this->db->bind(':uid', $data['usuario_id']);
        $this->db->bind(':sid', $data['session_id']);
        $this->db->bind(':ip', $data['ip_address']);
        $this->db->bind(':ua', $data['usuario_agent']);
        return $this->db->execute();
    }

    public function eliminarSesiones($usuarioId) {
        $this->db->query("DELETE FROM table_usuario_sessions WHERE usuario_id = :uid");
        $this->db->bind(':uid', $usuarioId);
        return $this->db->execute();
    }

    public function registrarSolicitudRecuperacion($usuarioId) {
        $this->db->query("INSERT INTO table_recuperaciones (usuario_id) VALUES (:uid)");
        $this->db->bind(':uid', $usuarioId);
        return $this->db->execute();
    }

    public function obtenerSolicitudesPendientes() {
        $this->db->query("SELECT r.*, u.username, s.nombre, s.cedula, u.password, u.id as user_id
                          FROM table_recuperaciones r
                          JOIN table_usuarios u ON r.usuario_id = u.id
                          JOIN table_staff s ON u.staff_id = s.id
                          ORDER BY r.fecha DESC");
        return $this->db->resultSet();
    }

    public function eliminarSolicitud($id) {
        $this->db->query("DELETE FROM table_recuperaciones WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}