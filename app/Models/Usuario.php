<?php
/**
 * Modelo de Usuario
 * Maneja todas las consultas a la tabla 'usuarios'
 */
class Usuario {
    private $db;

    public function __construct() {
        // Instanciamos la conexión a la base de datos
        $this->db = new Database();
    }

    /**
     * Buscar un usuario por su correo electrónico
     * Útil para el proceso de Login
     */
    public function buscarPorEmail($email) {
        // Traemos los datos del usuario y el nombre del rol en una sola consulta
        $this->db->query("SELECT u.*, r.nombre_rol 
                          FROM table_usuarios u 
                          INNER JOIN table_roles r ON u.role_id = r.id 
                          WHERE u.email = :email AND u.estado = 1");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }


    /**
     * Obtener un usuario por su ID
     * Útil para perfiles o verificar permisos
     */
    public function obtenerUsuarioPorId($id) {
        $this->db->query("SELECT id, nombre, email, role_id, created_at FROM table_usuarios WHERE id = :id");
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
                          VALUES (:session_id, :usuario_id, :ip_address, :usuario_agent, :created_at)");
        
        $this->db->bind(':session_id', $datos['session_id']);
        $this->db->bind(':usuario_id', $datos['usuario_id']);
        $this->db->bind(':ip_address', $datos['ip_address']);
        $this->db->bind(':usuario_agent', $datos['usuario_agent']);
        $this->db->bind(':created_at', date('Y-m-d H:i:s'));

        return $this->db->execute();
    }
}
