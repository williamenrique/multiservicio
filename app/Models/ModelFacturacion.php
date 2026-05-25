<?php
/**
 * Modelo de Facturación
 * Maneja la persistencia de ventas y la actualización de stock.
 */
class ModelFacturacion {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Busca productos o servicios disponibles en el inventario
     */
    public function buscarItems($termino) {
        // Traemos solo columnas necesarias para el POS (excluimos imagen por peso)
        $this->db->query("SELECT i.id, i.nombre, i.categoria, i.stock, i.precio, i.ultimo_costo,
                          (i.stock - COALESCE((
                              SELECT SUM(vd.cantidad) 
                              FROM table_ventas_detalle vd 
                              JOIN table_ventas v ON vd.venta_id = v.id 
                              WHERE vd.producto_id = i.id AND v.status = 'PENDIENTE'
                          ), 0)) as stock_disponible
                          FROM table_inventario i
                          WHERE (i.nombre LIKE :term OR i.categoria LIKE :term)
                          LIMIT 15");
        $this->db->bind(':term', "%$termino%");
        return $this->db->resultSet();
    }

    public function obtenerBorradores() {
        $this->db->query("SELECT * FROM table_ventas WHERE status = 'PENDIENTE' ORDER BY fecha DESC");
        return $this->db->resultSet();
    }

    /**
     * Obtiene todos los borradores con sus respectivos items cargados
     */
    public function obtenerBorradoresCompleto() {
        $this->db->query("SELECT v.*, s.nombre as usuario_nombre, c.nombre as cliente_nombre 
                          FROM table_ventas v 
                          LEFT JOIN table_usuarios u ON v.usuario_id = u.id 
                          LEFT JOIN table_staff s ON u.staff_id = s.id 
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE v.status = 'PENDIENTE' ORDER BY v.fecha DESC");
        $ventas = $this->db->resultSet();

        foreach ($ventas as $key => $venta) {
            $this->db->query("SELECT vd.*, i.id as prod_id 
                              FROM table_ventas_detalle vd 
                              LEFT JOIN table_inventario i ON vd.producto_id = i.id
                              WHERE vd.venta_id = :vid");
            $this->db->bind(':vid', $venta->id);
            $items = $this->db->resultSet();
            
            $ventas[$key]->items = array_map(function($it) {
                return [
                    'id' => $it->producto_id,
                    'nombre' => $it->descripcion,
                    'precio' => (float)$it->precio_unitario,
                    'cantidad' => (int)$it->cantidad,
                    'tipo' => $it->producto_id ? 'PRODUCTO' : 'SERVICIO'
                ];
            }, $items);
        }
        return $ventas;
    }

    /**
     * Procesa una venta completa usando una transacción SQL
     */
    public function guardarFactura($datos, $status = 'PENDIENTE') {
        try {
            $ventaId = !empty($datos['id_db']) ? $datos['id_db'] : null;

            // Obtener IVA dinámico desde la configuración de la empresa
            $this->db->query("SELECT iva FROM table_company_settings WHERE id = 1 LIMIT 1");
            $config = $this->db->single();
            $iva_porcentaje = ($config->iva ?? 0) / 100;

            // Asegurar que items sea un array
            $items = isset($datos['items']) && is_array($datos['items']) ? $datos['items'] : [];

            $subtotal = 0;
            foreach ($items as $item) $subtotal += ($item['precio'] * $item['cantidad']);

            // Respetar el estado del interruptor de IVA enviado desde el frontend
            $iva_activo = isset($datos['iva_activo']) ? (bool)$datos['iva_activo'] : false;
            $iva_monto = $iva_activo ? ($subtotal * $iva_porcentaje) : 0;
            $total = $subtotal + $iva_monto;

            // Nuevos campos de pago
            $pago_efectivo = (float)($datos['pago_efectivo'] ?? 0);
            $pago_transferencia = (float)($datos['pago_transferencia'] ?? 0);
            $pago_total = $pago_efectivo + $pago_transferencia;
            $saldo_pendiente = $total - $pago_total;

            // Lógica de Estado Automática:
            // Si se intenta completar pero hay saldo pendiente > 0, se marca como CREDITO
            if ($status === 'COMPLETADO' && $saldo_pendiente > 0.05) {
                $status = 'CREDITO';
            }

            if ($ventaId) {
                // Actualizar factura existente (Borrador)
                $this->db->query("UPDATE table_ventas SET 
                                  cliente_id = :cid, placa = :placa, modelo_vehiculo = :modelo, 
                                  subtotal = :sub, iva_monto = :iva, total = :total, 
                                  pago_efectivo = :pef, pago_transferencia = :ptra, saldo_pendiente = :spend,
                                  status = :status" . 
                                  ($status === 'COMPLETADO' ? ", fecha_cierre = NOW()" : "") . " 
                                  WHERE id = :id");
                $this->db->bind(':id', $ventaId);
            } else {
                // Insertar nueva factura
                $this->db->query("INSERT INTO table_ventas (cliente_id, placa, modelo_vehiculo, subtotal, iva_monto, total, 
                                  pago_efectivo, pago_transferencia, saldo_pendiente, usuario_id, status) 
                                  VALUES (:cid, :placa, :modelo, :sub, :iva, :total, :pef, :ptra, :spend, :uid, :status)");
                $this->db->bind(':uid', $_SESSION['user_id']);
            }

            $this->db->bind(':cid', !empty($datos['cliente_id']) ? $datos['cliente_id'] : null);
            $this->db->bind(':placa', !empty($datos['placa']) ? mb_strtoupper($datos['placa'], 'UTF-8') : '');
            $this->db->bind(':modelo', !empty($datos['modelo']) ? mb_strtoupper($datos['modelo'], 'UTF-8') : '');
            $this->db->bind(':sub', $subtotal);
            $this->db->bind(':iva', $iva_monto);
            $this->db->bind(':total', $total);
            $this->db->bind(':pef', $pago_efectivo);
            $this->db->bind(':ptra', $pago_transferencia);
            $this->db->bind(':spend', ($saldo_pendiente > 0) ? $saldo_pendiente : 0);
            $this->db->bind(':status', $status);
            if ($ventaId) $this->db->bind(':id', $ventaId);

            $this->db->execute();
            if (!$ventaId) $ventaId = $this->db->lastInsertId();

            // Si estamos actualizando, borramos los detalles anteriores para re-insertar
            if (!empty($datos['id_db'])) {
                $this->db->query("DELETE FROM table_ventas_detalle WHERE venta_id = :vid");
                $this->db->bind(':vid', $ventaId);
                $this->db->execute();
            }

            foreach ($items as $item) {
                $this->db->query("INSERT INTO table_ventas_detalle (venta_id, producto_id, descripcion, cantidad, precio_unitario) 
                                  VALUES (:vid, :pid, :desc, :cant, :precio)");
                $this->db->bind(':vid', $ventaId);
                $this->db->bind(':pid', !empty($item['id']) ? $item['id'] : null);
                $this->db->bind(':desc', $item['nombre']);
                $this->db->bind(':cant', $item['cantidad']);
                $this->db->bind(':precio', $item['precio']);
                $this->db->execute();

                // SOLO descontar stock físico si la venta se FINALIZÓ (COMPLETADO)
                if ($status === 'COMPLETADO' && $item['tipo'] === 'PRODUCTO' && !empty($item['id'])) {
                    // Registrar en Kardex antes de actualizar el stock
                    $invModel = new ModelInventario();
                    $invModel->registrarMovimiento($item['id'], 'SALIDA_VENTA', $item['cantidad'], $ventaId, "Venta Finalizada");

                    $this->db->query("UPDATE table_inventario SET stock = stock - :cant WHERE id = :pid");
                    $this->db->bind(':cant', $item['cantidad']);
                    $this->db->bind(':pid', $item['id']);
                    $this->db->execute();
                }
            }

            return $ventaId;
        } catch (Exception $e) {
            error_log("Error en procesarVenta: " . $e->getMessage());
            return false;
        }
    }

    public function procesarVenta($datos) {
        return $this->guardarFactura($datos, 'COMPLETADO');
    }

    /**
     * Obtiene los detalles completos de una venta para su impresión
     */
    public function obtenerVentaCompleta($id) {
        $this->db->query("SELECT v.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.email as cliente_email
                          FROM table_ventas v
                          LEFT JOIN table_clientes c ON v.cliente_id = c.id
                          WHERE v.id = :id");
        $this->db->bind(':id', $id);
        $venta = $this->db->single();

        if ($venta) {
            $this->db->query("SELECT vd.descripcion, vd.cantidad, vd.precio_unitario 
                              FROM table_ventas_detalle vd 
                              WHERE vd.venta_id = :vid");
            $this->db->bind(':vid', $id);
            $venta->items = $this->db->resultSet();
        }
        return $venta;
    }

    /**
     * Métodos de gestión de borradores requeridos por el controlador
     */
    public function obtenerBorradorPorId($id) {
        $this->db->query("SELECT * FROM table_ventas WHERE id = :id AND status = 'PENDIENTE'");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Elimina un borrador de factura (Venta en estado PENDIENTE).
     */
    public function eliminarBorrador($id) {
        $this->db->query("DELETE FROM table_ventas WHERE id = :id AND status = 'PENDIENTE'");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

}