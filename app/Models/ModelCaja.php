<?php
class ModelCaja {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function obtenerSesionActiva() {
        $this->db->query("SELECT * FROM table_sesiones_caja WHERE estado = 'ABIERTA' LIMIT 1");
        return $this->db->single();
    }

    public function abrirSesion($data) {
        $this->db->query("INSERT INTO table_sesiones_caja (usuario_id, monto_inicial, estado) VALUES (:uid, :inicial, 'ABIERTA')");
        $this->db->bind(':uid', $data['usuario_id']);
        $this->db->bind(':inicial', $data['monto_inicial']);
        return $this->db->execute() ? $this->db->lastInsertId() : false;
    }

    public function registrarMovimiento($data) {
        $this->db->query("INSERT INTO table_caja_movimientos (sesion_id, tipo, monto, metodo_pago, referencia_id, concepto) 
                          VALUES (:sid, :tipo, :monto, :metodo, :ref, :concepto)");
        $this->db->bind(':sid', $data['sesion_id']);
        $this->db->bind(':tipo', $data['tipo']);
        $this->db->bind(':monto', $data['monto']);
        $this->db->bind(':metodo', $data['metodo_pago']);
        $this->db->bind(':ref', $data['referencia_id'] ?? null);
        $this->db->bind(':concepto', $data['concepto']);
        return $this->db->execute();
    }

    public function obtenerTotalesSesion($sesionId) {
        // Sumar ingresos en efectivo (Ventas y abonos)
        $this->db->query("SELECT SUM(monto) as total FROM table_caja_movimientos 
                          WHERE sesion_id = :sid AND tipo = 'INGRESO' AND metodo_pago = 'EFECTIVO'");
        $this->db->bind(':sid', $sesionId);
        $efectivo = $this->db->single()->total ?? 0;

        // Sumar egresos en efectivo (Gastos)
        $this->db->query("SELECT SUM(monto) as total FROM table_caja_movimientos 
                          WHERE sesion_id = :sid AND tipo = 'EGRESO' AND metodo_pago = 'EFECTIVO'");
        $this->db->bind(':sid', $sesionId);
        $egresos = $this->db->single()->total ?? 0;

        return (float)$efectivo - (float)$egresos;
    }

    public function cerrarSesion($sesionId, $montoReal, $montoEsperado) {
        $diferencia = $montoReal - $montoEsperado;
        $this->db->query("UPDATE table_sesiones_caja 
                          SET fecha_cierre = NOW(), 
                              monto_final_esperado = :esperado, 
                              monto_final_real = :real, 
                              diferencia = :dif, 
                              estado = 'CERRADA' 
                          WHERE id = :id");
        $this->db->bind(':esperado', $montoEsperado);
        $this->db->bind(':real', $montoReal);
        $this->db->bind(':dif', $diferencia);
        $this->db->bind(':id', $sesionId);
        return $this->db->execute();
    }
}