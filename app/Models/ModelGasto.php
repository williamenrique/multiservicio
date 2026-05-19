<?php
class ModelGasto {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function listar() {
        $this->db->query("SELECT g.*, u.username 
                         FROM table_gastos g 
                         JOIN table_usuarios u ON g.usuario_id = u.id 
                         ORDER BY g.fecha DESC");
        return $this->db->resultSet();
    }

    public function crear($data) {
        $this->db->query("INSERT INTO table_gastos (fecha, descripcion, categoria, monto, metodo_pago, usuario_id) 
                         VALUES (:fecha, :descripcion, :categoria, :monto, :metodo, :usuario_id)");
        $this->db->bind(':fecha', $data['fecha']);
        $this->db->bind(':descripcion', $data['descripcion']);
        $this->db->bind(':categoria', $data['categoria']);
        $this->db->bind(':monto', $data['monto']);
        $this->db->bind(':metodo', $data['metodo_pago'] ?? 'EFECTIVO');
        $this->db->bind(':usuario_id', $_SESSION['user_id']);
        return $this->db->execute();
    }

    public function eliminar($id) {
        $this->db->query("DELETE FROM table_gastos WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}