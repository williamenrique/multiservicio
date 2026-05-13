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
        $this->db->query("SELECT id, nombre, email, rol, created_at FROM usuarios WHERE id = :id");
        $this->db->bind(':id', $id);

        return $this->db->single();
    }

    /**
     * Ejemplo de registro (si decides añadir creación de usuarios)
     */
    public function registrar($datos) {
        $this->db->query("INSERT INTO usuarios (nombre, email, password, rol) VALUES (:nombre, :email, :password, :rol)");
        
        // Vincular valores
        $this->db->bind(':nombre', $datos['nombre']);
        $this->db->bind(':email', $datos['email']);
        $this->db->bind(':password', $datos['password']); // Ya debe venir hasheada
        $this->db->bind(':rol', $datos['rol']);

        return $this->db->execute();
    }
}

