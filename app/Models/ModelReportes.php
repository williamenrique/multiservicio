<?php
class ModelReportes {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function obtenerFlujoCaja($desde, $hasta) {
        // 1. Obtener Ventas (Ingresos)
        $this->db->query("SELECT id, fecha, total as monto, 'VENTA' as tipo, modelo_vehiculo as descripcion 
                          FROM table_ventas 
                          WHERE status = 'COMPLETADO' AND DATE(fecha) BETWEEN :desde AND :hasta");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $ingresos = $this->db->resultSet() ?: [];

        // 2. Obtener Gastos (Egresos)
        $this->db->query("SELECT id, fecha, monto, 'GASTO' as tipo, descripcion 
                          FROM table_gastos 
                          WHERE DATE(fecha) BETWEEN :desde AND :hasta");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $egresos = $this->db->resultSet() ?: [];

        // 3. Unificar y Calcular Totales
        $movimientos = array_merge($ingresos, $egresos);
        
        // Ordenar por fecha descendente
        usort($movimientos, function($a, $b) {
            return strtotime($b->fecha) - strtotime($a->fecha);
        });

        // Cálculos seguros
        $totalIngresos = array_reduce($ingresos, function($acc, $item) { 
            return $acc + (float)($item->monto ?? 0); 
        }, 0);
        $totalEgresos = array_reduce($egresos, function($acc, $item) { 
            return $acc + (float)($item->monto ?? 0); 
        }, 0);

        return [
            'movimientos' => $movimientos,
            'totales' => [
                'ingresos' => $totalIngresos,
                'egresos' => $totalEgresos,
                'balance' => $totalIngresos - $totalEgresos
            ]
        ];
    }
}