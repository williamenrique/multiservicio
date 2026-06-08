<?php
class ModelGasto {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    public function listar($limit = null, $offset = null, $search = null, $desde = null, $hasta = null) {
        $sql = "SELECT g.* FROM table_gastos g WHERE 1=1";
        
        if ($search) {
            $sql .= " AND (g.descripcion LIKE :search OR g.categoria LIKE :search)";
        }

        if ($desde && $hasta) {
            $sql .= " AND g.fecha BETWEEN :desde AND :hasta";
        }

        $sql .= " ORDER BY g.fecha DESC, g.id DESC";
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $this->db->query($sql);
        if ($search) $this->db->bind(':search', "%$search%");
        if ($desde && $hasta) {
            $this->db->bind(':desde', $desde);
            $this->db->bind(':hasta', $hasta);
        }
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

    public function contarFiltrados($search, $desde = null, $hasta = null) {
        $sql = "SELECT COUNT(*) as total FROM table_gastos WHERE 1=1";
        if ($search) $sql .= " AND (descripcion LIKE :search OR categoria LIKE :search)";
        if ($desde && $hasta) $sql .= " AND fecha BETWEEN :desde AND :hasta";
        
        $this->db->query($sql);
        if ($search) $this->db->bind(':search', "%$search%");
        if ($desde && $hasta) {
            $this->db->bind(':desde', $desde);
            $this->db->bind(':hasta', $hasta);
        }
        return (int)$this->db->single()->total;
    }

    public function crear($data) {
        $this->db->query("INSERT INTO table_gastos (fecha, descripcion, categoria, monto, metodo_pago, usuario_id) 
                         VALUES (:fecha, :descripcion, :categoria, :monto, :metodo, :uid)");
        $this->db->bind(':fecha', $data['fecha']);
        $this->db->bind(':descripcion', mb_strtoupper($data['descripcion'], 'UTF-8'));
        $this->db->bind(':categoria', mb_strtoupper($data['categoria'], 'UTF-8'));
        $this->db->bind(':monto', $data['monto']);
        $this->db->bind(':metodo', $data['metodo_pago'] ?? 'EFECTIVO');
        $this->db->bind(':uid', $_SESSION['user_id'] ?? null);
        if ($this->db->execute()) {
            $gastoId = $this->db->lastInsertId();
            // REGISTRAR EN LIBRO MAYOR (Transacciones)
            $this->db->query("INSERT INTO table_transacciones (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id, fecha) 
                              VALUES (1, 'EGRESO', 'GASTO', :monto, :ref, :desc, :uid, :fecha)");
            $this->db->bind(':monto', $data['monto']);
            $this->db->bind(':ref', $gastoId);
            $this->db->bind(':desc', "GASTO: " . mb_strtoupper($data['descripcion'], 'UTF-8'));
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->bind(':fecha', $data['fecha']);
            $this->db->execute();
            return $gastoId;
        }
        return false;
    }

    public function eliminar($id) {
        $this->db->query("DELETE FROM table_gastos WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}