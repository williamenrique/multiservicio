<?php
/**
 * Modelo de Usuario
 * Maneja todas las consultas a la tabla 'usuarios'
 */
class ModelUsuario {
    private $db;

    public function __construct() {
        // Instanciamos la conexión a la base de datos
        $this->db = new Database();
    }

    /**
     * Buscar un usuario por su identificador (email o nombre de usuario)
     */
    public function buscarPorIdentificador($identificador) {
        // Traemos los datos del usuario y el nombre del rol en una sola consulta
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
     * Útil para perfiles o verificar permisos
     */
    public function obtenerUsuarioPorId($id) {
        // Modificado para traer también el nombre del rol y detalles del staff si está asociado
        $this->db->query("SELECT u.id, u.username, s.nombre, s.email, u.role_id, u.staff_id, u.created_at, 
                                 r.nombre_rol, s.nombre as staff_name, s.cargo as staff_job_role
                          FROM table_usuarios u 
                          INNER JOIN table_roles r ON u.role_id = r.id 
                          INNER JOIN table_staff s ON u.staff_id = s.id 
                          WHERE u.id = :id AND u.estado = 1"); // Asumiendo que 'estado' es para usuarios activos
        $this->db->bind(':id', $id);

        return $this->db->single();
    }

    /**
     * Ejemplo de registro (si decides añadir creación de usuarios)
     */
    public function registrar($datos) {
        $this->db->query("INSERT INTO table_usuarios (nombre, email, password, role_id) VALUES (:nombre, :email, :password, :rol)");
        
        // Vincular valores
        $this->db->bind(':nombre', $datos['nombre']);
        $this->db->bind(':email', $datos['email']);
        $this->db->bind(':password', $datos['password']); // Ya debe venir hasheada
        $this->db->bind(':rol', $datos['rol']);

        return $this->db->execute();
    }

    /**
     * Verifica si el usuario tiene una sesión activa en la base de datos
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
     * Crea un registro de sesión vinculando el ID de sesión de PHP con el usuario
     */
    public function registrarSesion($datos) {
        $this->db->query("INSERT INTO table_usuario_sessions (session_id, usuario_id, ip_address, usuario_agent, created_at)
                          VALUES (:session_id, :usuario_id, :ip_address, :usuario_agent, :created_at)
                          ON DUPLICATE KEY UPDATE
                              session_id = VALUES(session_id),
                              ip_address = VALUES(ip_address),
                              usuario_agent = VALUES(usuario_agent),
                              last_activity = NOW()"); // Actualiza la última actividad al actualizar el registro
        
        $this->db->bind(':session_id', $datos['session_id']);
        $this->db->bind(':usuario_id', $datos['usuario_id']);
        $this->db->bind(':ip_address', $datos['ip_address']);
        $this->db->bind(':usuario_agent', $datos['usuario_agent']);
        $this->db->bind(':created_at', date('Y-m-d H:i:s'));
        return $this->db->execute();
    }
}
