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

    /**
     * Obtiene la sesión activa de un usuario filtrando por tipo (WEB o APP).
     * @param int $usuarioId
     * @param string $tipo 'WEB' o 'APP'
     * @return object|false
     */
    public function obtenerSesionActiva($usuarioId, $tipo = null) {
        if ($tipo) {
            $this->db->query("SELECT session_id FROM table_usuario_sessions WHERE usuario_id = :uid AND tipo = :tipo");
            $this->db->bind(':uid', $usuarioId);
            $this->db->bind(':tipo', $tipo);
        } else {
            // Retrocompatibilidad: sin tipo, busca cualquier sesión
            $this->db->query("SELECT session_id FROM table_usuario_sessions WHERE usuario_id = :uid");
            $this->db->bind(':uid', $usuarioId);
        }
        return $this->db->single();
    }

    /**
     * Registra o reemplaza la sesión para un usuario en una plataforma específica.
     * Usa REPLACE con la UK (usuario_id, tipo) para garantizar solo 1 sesión por tipo.
     */
    public function registrarSesion($data) {
        $this->db->query("INSERT INTO table_usuario_sessions (usuario_id, tipo, session_id, ip_address, usuario_agent) 
                          VALUES (:uid, :tipo, :sid, :ip, :ua)
                          ON CONFLICT (usuario_id, tipo) DO UPDATE SET 
                          session_id = :sid, ip_address = :ip, usuario_agent = :ua");
        $this->db->bind(':uid', $data['usuario_id']);
        $this->db->bind(':tipo', $data['tipo'] ?? 'WEB');
        $this->db->bind(':sid', $data['session_id']);
        $this->db->bind(':ip', $data['ip_address']);
        $this->db->bind(':ua', $data['usuario_agent']);
        return $this->db->execute();
    }

    /**
     * Elimina las sesiones de un usuario. Si se especifica $tipo, solo elimina esa plataforma.
     * @param int $usuarioId
     * @param string|null $tipo 'WEB', 'APP' o null para eliminar todas
     */
    public function eliminarSesiones($usuarioId, $tipo = null) {
        if ($tipo) {
            $this->db->query("DELETE FROM table_usuario_sessions WHERE usuario_id = :uid AND tipo = :tipo");
            $this->db->bind(':uid', $usuarioId);
            $this->db->bind(':tipo', $tipo);
        } else {
            $this->db->query("DELETE FROM table_usuario_sessions WHERE usuario_id = :uid");
            $this->db->bind(':uid', $usuarioId);
        }
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