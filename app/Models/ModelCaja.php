<?php
class ModelCaja {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    public function registrarMovimiento($data) {
        $this->db->query("INSERT INTO table_caja_movimientos (tipo, monto, metodo_pago, referencia_id, concepto, usuario_id) 
                          VALUES (:tipo, :monto, :metodo, :ref, :concepto, :uid)");
        $this->db->bind(':tipo', $data['tipo']);
        $this->db->bind(':monto', $data['monto']);
        $this->db->bind(':metodo', $data['metodo_pago']);
        $this->db->bind(':ref', $data['referencia_id'] ?? null);
        $this->db->bind(':concepto', mb_strtoupper($data['concepto'], 'UTF-8'));
        $this->db->bind(':uid', $_SESSION['user_id']);
        return $this->db->execute();
    }
}