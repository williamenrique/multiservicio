<?php
class ModelReportes {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function obtenerFlujoCaja($desde, $hasta) {
        // 1. Obtener Ventas (Ingresos)
        $this->db->query("SELECT v.id, v.fecha, v.total as monto, v.total as monto_pagado, 'VENTA' as tipo, 'VENTA' as categoria,
                          v.modelo_vehiculo, v.placa, c.nombre as cliente_nombre,
                          (SELECT COUNT(*) FROM table_ventas_detalle WHERE venta_id = v.id) as cantidad_items,
                          NULL as proveedor_nombre, 0 as saldo_pendiente
                          FROM table_ventas v
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE v.status = 'COMPLETADO' AND DATE(v.fecha) BETWEEN :desde AND :hasta");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $ingresos = $this->db->resultSet() ?: [];

        // 2. Obtener Gastos (Egresos)
        // Unificamos gastos generales y compras a proveedores
        $this->db->query("SELECT g.id, g.fecha, g.monto, g.monto as monto_pagado, 'GASTO' as tipo, g.categoria, 
                          g.descripcion, NULL as modelo_vehiculo, NULL as placa, NULL as cliente_nombre, 
                          0 as cantidad_items, NULL as proveedor_nombre, 0 as saldo_pendiente
                          FROM table_gastos g 
                          WHERE DATE(g.fecha) BETWEEN :desde AND :hasta
                          UNION ALL
                          SELECT c.id, c.fecha, c.total as monto, c.pagado as monto_pagado, 'COMPRA' as tipo, 'MERCANCÍA' as categoria,
                          'COMPRA DE MERCANCIA' as descripcion, NULL as modelo_vehiculo, NULL as placa, NULL as cliente_nombre,
                          (SELECT COUNT(*) FROM table_compras_detalle WHERE compra_id = c.id) as cantidad_items,
                          p.nombre as proveedor_nombre, (c.total - c.pagado) as saldo_pendiente
                          FROM table_compras c
                          INNER JOIN table_proveedores p ON c.proveedor_id = p.id
                          WHERE DATE(c.fecha) BETWEEN :desde AND :hasta");
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

        // Egresos reales (Gastos + lo que se ha pagado de las compras)
        $totalEgresos = array_reduce($egresos, function($acc, $item) { 
            return $acc + (float)($item->monto_pagado ?? 0); 
        }, 0);

        $totalDeuda = array_reduce($egresos, function($acc, $item) { 
            return $acc + (float)($item->saldo_pendiente ?? 0); 
        }, 0);

        return [
            'movimientos' => $movimientos,
            'totales' => [
                'ingresos' => $totalIngresos,
                'egresos' => $totalEgresos,
                'deuda' => $totalDeuda,
                'balance' => $totalIngresos - $totalEgresos
            ]
        ];
    }

    public function obtenerReporteDetallado($desde, $hasta) {
        // 1. Detalle de Ventas (Vehículos + Items)
        $this->db->query("SELECT v.id, v.fecha, v.placa, v.modelo_vehiculo, vd.descripcion, vd.cantidad, vd.precio_unitario, (vd.cantidad * vd.precio_unitario) as subtotal_item
                          FROM table_ventas v
                          JOIN table_ventas_detalle vd ON v.id = vd.venta_id
                          WHERE v.status = 'COMPLETADO' AND DATE(v.fecha) BETWEEN :desde AND :hasta
                          ORDER BY v.fecha DESC");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $ventas = $this->db->resultSet() ?: [];

        // 2. Detalle de Compras (Proveedores + Items + Deuda)
        $this->db->query("SELECT c.id, c.fecha, p.nombre as proveedor, cd.descripcion, cd.cantidad, cd.costo_unitario, c.total as total_factura, c.pagado, (c.total - c.pagado) as deuda
                          FROM table_compras c
                          JOIN table_proveedores p ON c.proveedor_id = p.id
                          JOIN table_compras_detalle cd ON c.id = cd.compra_id
                          WHERE DATE(c.fecha) BETWEEN :desde AND :hasta
                          ORDER BY c.fecha DESC");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $compras = $this->db->resultSet() ?: [];

        // 3. Detalle de Gastos
        $this->db->query("SELECT * FROM table_gastos 
                          WHERE DATE(fecha) BETWEEN :desde AND :hasta
                          ORDER BY fecha DESC");
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $gastos = $this->db->resultSet() ?: [];

        return [
            'ventas' => $ventas,
            'compras' => $compras,
            'gastos' => $gastos
        ];
    }
}