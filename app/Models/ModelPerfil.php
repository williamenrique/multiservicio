<?php
/**
 * Modelo de Perfil
 * Gestiona los datos del perfil del usuario en la base de datos.
 */
class ModelPerfil {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Obtiene los datos completos del perfil
     */
    public function obtenerDatos($userId) {
        $this->db->query("SELECT u.username, u.staff_id, s.cedula, s.nombre, s.email, s.telefono, s.direccion, s.foto, s.foto_frente, r.nombre_rol 
                          FROM table_usuarios u 
                          INNER JOIN table_staff s ON u.staff_id = s.id 
                          INNER JOIN table_roles r ON u.role_id = r.id 
                          WHERE u.id = :id");
        $this->db->bind(':id', $userId);
        return $this->db->single();
    }

    /**
     * Actualiza la información personal en la tabla staff
     */
    public function actualizarStaff($staffId, $datos) {
        $sql = "UPDATE table_staff SET nombre = :nom, email = :em, telefono = :tel, direccion = :dir";
        if (!empty($datos['foto'])) $sql .= ", foto = :foto";
        if (!empty($datos['foto_frente'])) $sql .= ", foto_frente = :foto_f";
        $sql .= " WHERE id = :sid";

        $this->db->query($sql);
        $this->db->bind(':nom', mb_strtoupper($datos['nombre'], 'UTF-8'));
        $this->db->bind(':em', mb_strtolower($datos['email'], 'UTF-8'));
        $this->db->bind(':tel', $datos['telefono']);
        $this->db->bind(':dir', mb_strtoupper($datos['direccion'], 'UTF-8'));
        $this->db->bind(':sid', $staffId);
        if (!empty($datos['foto'])) $this->db->bind(':foto', $datos['foto']);
        if (!empty($datos['foto_frente'])) $this->db->bind(':foto_f', $datos['foto_frente']);
        
        return $this->db->execute();
    }

    /**
     * Actualiza la contraseña si se proporciona
     */
    public function actualizarPassword($userId, $passwordHash) {
        $this->db->query("UPDATE table_usuarios SET password = :pass WHERE id = :id");
        $this->db->bind(':pass', $passwordHash);
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }
}