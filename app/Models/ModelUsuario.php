<?php
/**
 * Modelo de Usuario
 * Gestiona la lógica de datos de acceso, personal vinculado y control de sesiones.
 */
class ModelUsuario {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Busca un usuario permitiendo el acceso mediante su Nick (tabla usuarios)
     * o su Email (tabla staff).
     * @param string $identificador Email o Nickname del usuario.
     * @return object|bool Fila del usuario con datos de staff y rol unidos.
     */
    public function buscarPorIdentificador($identificador) {
        $this->db->query("SELECT u.id, u.username, u.password, u.role_id, u.staff_id, u.estado, s.nombre, s.email, r.nombre_rol 
                          FROM table_usuarios u 
                          INNER JOIN table_staff s ON u.staff_id = s.id 
                          INNER JOIN table_roles r ON u.role_id = r.id 
                          WHERE (s.email = :id OR u.username = :id) AND u.estado = 1");
        $this->db->bind(':id', $identificador);
        return $this->db->single();
    }


    /**
     * Obtener un usuario por su ID
     */
    public function obtenerUsuarioPorId($id) {
        $this->db->query("SELECT u.id, u.username, s.nombre, s.email, u.role_id, u.staff_id, u.created_at, 
                                 r.nombre_rol, s.nombre as staff_name, s.cargo as staff_job_role
                          FROM table_usuarios u 
                          INNER JOIN table_roles r ON u.role_id = r.id 
                          INNER JOIN table_staff s ON u.staff_id = s.id 
                          WHERE u.id = :id AND u.estado = 1");
        $this->db->bind(':id', $id);

        return $this->db->single();
    }

    /**
     * Consulta si existe una sesión registrada para el usuario en la tabla de sesiones.
     */
    public function obtenerSesionActiva($usuario_id) {
        $this->db->query("SELECT * FROM table_usuario_sessions WHERE usuario_id = :id LIMIT 1");
        $this->db->bind(':id', $usuario_id);
        return $this->db->single();
    }

    /**
     * Elimina los registros de sesión para un usuario
     */
    public function eliminarSesiones($usuario_id) {
        $this->db->query("DELETE FROM table_usuario_sessions WHERE usuario_id = :id");
        $this->db->bind(':id', $usuario_id);
        return $this->db->execute();
    }

    /**
     * Registra o actualiza la sesión actual del usuario.
     * Utiliza ON DUPLICATE KEY UPDATE para que el usuario_id (PK) se actualice 
     * si el usuario decide forzar el inicio de sesión desde un nuevo dispositivo.
     */
    public function registrarSesion($datos) {
        $this->db->query("INSERT INTO table_usuario_sessions (session_id, usuario_id, ip_address, usuario_agent, created_at)
                          VALUES (:session_id, :usuario_id, :ip_address, :usuario_agent, :created_at)
                          ON DUPLICATE KEY UPDATE
                              session_id = VALUES(session_id),
                              ip_address = VALUES(ip_address),
                              usuario_agent = VALUES(usuario_agent),
                              last_activity = NOW()");
        
        $this->db->bind(':session_id', $datos['session_id']);
        $this->db->bind(':usuario_id', $datos['usuario_id']);
        $this->db->bind(':ip_address', $datos['ip_address']);
        $this->db->bind(':usuario_agent', $datos['usuario_agent']);
        $this->db->bind(':created_at', date('Y-m-d H:i:s'));
        return $this->db->execute();
    }
}
