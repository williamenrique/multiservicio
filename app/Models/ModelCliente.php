<?php
/**
 * Modelo de Cliente
 * Gestiona la persistencia de datos de clientes en la base de datos.
 */
class ModelCliente {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Obtener todos los clientes
     */
    public function listar() {
        $this->db->query("SELECT * FROM table_clientes ORDER BY created_at DESC");
        return $this->db->resultSet();
    }

    /**
     * Buscar un cliente por su ID
     */
    public function obtenerPorId($id) {
        $this->db->query("SELECT * FROM table_clientes WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Registrar un nuevo cliente
     */
    public function crear($datos) {
        $this->db->query("INSERT INTO table_clientes (id, nombre, email, telefono, direccion) 
                          VALUES (:id, :nombre, :email, :telefono, :direccion)");
        
        $this->db->bind(':id', mb_strtoupper($datos['id'], 'UTF-8'));
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'], 'UTF-8'));
        $this->db->bind(':email', mb_strtolower($datos['email'], 'UTF-8'));
        $this->db->bind(':telefono', $datos['telefono']);
        $this->db->bind(':direccion', mb_strtoupper($datos['direccion'], 'UTF-8'));

        return $this->db->execute();
    }

    /**
     * Actualizar datos de un cliente existente
     */
    public function actualizar($datos) {
        $this->db->query("UPDATE table_clientes 
                          SET nombre = :nombre, 
                              email = :email, 
                              telefono = :telefono, 
                              direccion = :direccion 
                          WHERE id = :id");
        
        $this->db->bind(':id', mb_strtoupper($datos['id'], 'UTF-8'));
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'], 'UTF-8'));
        $this->db->bind(':email', mb_strtolower($datos['email'], 'UTF-8'));
        $this->db->bind(':telefono', $datos['telefono']);
        $this->db->bind(':direccion', mb_strtoupper($datos['direccion'], 'UTF-8'));

        return $this->db->execute();
    }

    /**
     * Eliminar un cliente
     */
    public function eliminar($id) {
        $this->db->query("DELETE FROM table_clientes WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}