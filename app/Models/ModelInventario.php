<?php
/**
 * Modelo de Inventario
 */
class ModelInventario {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function listar() {
        $this->db->query("SELECT * FROM table_inventario ORDER BY nombre ASC");
        return $this->db->resultSet();
    }

    public function obtenerPorId($id) {
        $this->db->query("SELECT * FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function crear($datos) {
        $this->db->query("INSERT INTO table_inventario (nombre, categoria, stock, precio, imagen) 
                          VALUES (:nombre, :categoria, :stock, :precio, :imagen)");
        
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'], 'UTF-8'));
        $this->db->bind(':categoria', mb_strtoupper($datos['categoria'], 'UTF-8'));
        $this->db->bind(':stock', $datos['stock']);
        $this->db->bind(':precio', $datos['precio']);
        $this->db->bind(':imagen', $datos['imagen'] ?? null);

        return $this->db->execute();
    }

    public function actualizar($datos) {
        $this->db->query("UPDATE table_inventario 
                          SET nombre = :nombre, 
                              categoria = :categoria, 
                              stock = :stock, 
                              precio = :precio, 
                              imagen = :imagen 
                          WHERE id = :id");
        
        $this->db->bind(':id', $datos['id']);
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'], 'UTF-8'));
        $this->db->bind(':categoria', mb_strtoupper($datos['categoria'], 'UTF-8'));
        $this->db->bind(':stock', $datos['stock']);
        $this->db->bind(':precio', $datos['precio']);
        $this->db->bind(':imagen', $datos['imagen'] ?? null);

        return $this->db->execute();
    }

    public function eliminar($id) {
        $this->db->query("DELETE FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}