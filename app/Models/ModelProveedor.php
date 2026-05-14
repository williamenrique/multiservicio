<?php
class ModelProveedor {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function listar() {
        $this->db->query("SELECT * FROM table_proveedores ORDER BY nombre ASC");
        return $this->db->resultSet();
    }

    public function obtenerPorId($id) {
        $this->db->query("SELECT * FROM table_proveedores WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function crear($datos) {
        $this->db->query("INSERT INTO table_proveedores (id, nombre, telefono, email, direccion) 
                          VALUES (:id, :nombre, :telefono, :email, :direccion)");
        $this->db->bind(':id', $datos['id']);
        $this->db->bind(':nombre', $datos['nombre']);
        $this->db->bind(':telefono', $datos['telefono']);
        $this->db->bind(':email', $datos['email']);
        $this->db->bind(':direccion', $datos['direccion']);
        return $this->db->execute();
    }

    public function actualizar($datos) {
        $this->db->query("UPDATE table_proveedores 
                          SET nombre = :nombre, telefono = :telefono, email = :email, direccion = :direccion 
                          WHERE id = :id");
        $this->db->bind(':id', $datos['id']);
        $this->db->bind(':nombre', $datos['nombre']);
        $this->db->bind(':telefono', $datos['telefono']);
        $this->db->bind(':email', $datos['email']);
        $this->db->bind(':direccion', $datos['direccion']);
        return $this->db->execute();
    }

    public function eliminar($id) {
        $this->db->query("DELETE FROM table_proveedores WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}