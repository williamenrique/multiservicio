<?php
class ModelGasto {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function listar($limit = null, $offset = null, $search = null) {
        $sql = "SELECT g.*, u.username FROM table_gastos g JOIN table_usuarios u ON g.usuario_id = u.id";
        
        if ($search) {
            $sql .= " WHERE g.descripcion LIKE :search OR g.categoria LIKE :search";
        }

        $sql .= " ORDER BY g.fecha DESC";
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $this->db->query($sql);
        if ($search) $this->db->bind(':search', "%$search%");
        if ($limit !== null && $offset !== null) {
            $this->db->bind(':limit', (int)$limit);
            $this->db->bind(':offset', (int)$offset);
        }
        return $this->db->resultSet();
    }

    public function contarTotal() {
        $this->db->query("SELECT COUNT(*) as total FROM table_gastos");
        return (int)$this->db->single()->total;
    }

    public function contarFiltrados($search) {
        $this->db->query("SELECT COUNT(*) as total FROM table_gastos WHERE descripcion LIKE :search OR categoria LIKE :search");
        $this->db->bind(':search', "%$search%");
        return (int)$this->db->single()->total;
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