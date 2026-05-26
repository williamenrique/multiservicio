<?php
class ModelCaja extends Model {
    /**
     * Calcula el saldo esperado en efectivo para el turno actual
     */
    public function obtenerSaldoEsperado() {
        // 1. Obtener la fecha del último cierre
        $this->db->query("SELECT fecha FROM table_cierres_caja ORDER BY fecha DESC LIMIT 1");
        $ultimoCierre = $this->db->single();
        $desde = $ultimoCierre ? $ultimoCierre->fecha : '2000-01-01 00:00:00';

        // 2. Sumar ventas en efectivo
        $this->db->query("SELECT SUM(pago_efectivo) as total FROM table_ventas 
                          WHERE status = 'COMPLETADO' AND fecha_cierre > :desde");
        $this->db->bind(':desde', $desde);
        $ventas = $this->db->single()->total ?? 0;

        // 3. Sumar abonos de clientes en efectivo
        $this->db->query("SELECT SUM(monto) as total FROM table_abonos_clientes 
                          WHERE metodo_pago = 'EFECTIVO' AND fecha > :desde");
        $this->db->bind(':desde', $desde);
        $abonos = $this->db->single()->total ?? 0;

        // 4. Restar gastos pagados en efectivo
        $this->db->query("SELECT SUM(monto) as total FROM table_gastos 
                          WHERE metodo_pago = 'EFECTIVO' AND fecha > :desde");
        $this->db->bind(':desde', $desde);
        $gastos = $this->db->single()->total ?? 0;
        
        // 5. Restar pagos a proveedores en efectivo
        $this->db->query("SELECT SUM(monto_pagado) as total FROM table_compras_pagos 
                          WHERE metodo_pago = 'EFECTIVO' AND fecha > :desde");
        $this->db->bind(':desde', $desde);
        $compras = $this->db->single()->total ?? 0;

        return [
            'ventas_efectivo' => (float)$ventas,
            'abonos_efectivo' => (float)$abonos,
            'gastos_efectivo' => (float)$gastos,
            'compras_efectivo' => (float)$compras,
            'total_esperado' => ($ventas + $abonos) - ($gastos + $compras)
        ];
    }

    public function registrarCierre($datos) {
        return $this->db->insert('table_cierres_caja', [
            'usuario_id' => $_SESSION['user_id'],
            'monto_esperado' => $datos['esperado'],
            'monto_real' => $datos['real'],
            'diferencia' => $datos['real'] - $datos['esperado'],
            'observaciones' => mb_strtoupper($datos['observaciones'], 'UTF-8'),
            'fecha' => date('Y-m-d H:i:s')
        ]);
    }
}