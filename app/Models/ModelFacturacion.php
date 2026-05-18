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
        $this->db->query("SELECT * FROM table_inventario 
                          WHERE (nombre LIKE :term OR categoria LIKE :term) 
                          AND stock > 0 
                          LIMIT 15");
        $this->db->bind(':term', "%$termino%");
        return $this->db->resultSet();
    }

    /**
     * Procesa una venta completa usando una transacción SQL
     */
    public function procesarVenta($datos) {
        try {
            $this->db->beginTransaction();

            // Obtener IVA dinámico desde la configuración de la empresa
            $this->db->query("SELECT iva FROM table_company_settings WHERE id = 1 LIMIT 1");
            $config = $this->db->single();
            $iva_porcentaje = ($config->iva ?? 0) / 100;
            
            $subtotal = 0;
            foreach ($datos['items'] as $item) $subtotal += ($item['precio'] * $item['cantidad']);
            $iva_monto = $subtotal * $iva_porcentaje;
            $total = $subtotal + $iva_monto;

            $this->db->query("INSERT INTO table_ventas (cliente_id, placa, modelo_vehiculo, subtotal, iva_monto, total, usuario_id) 
                              VALUES (:cid, :placa, :modelo, :sub, :iva, :total, :uid)");
            
            $this->db->bind(':cid', !empty($datos['cliente_id']) ? $datos['cliente_id'] : null);
            $this->db->bind(':placa', !empty($datos['placa']) ? mb_strtoupper($datos['placa'], 'UTF-8') : null);
            $this->db->bind(':modelo', !empty($datos['modelo']) ? mb_strtoupper($datos['modelo'], 'UTF-8') : 'VENTA GENERAL');
            $this->db->bind(':sub', $subtotal);
            $this->db->bind(':iva', $iva_monto);
            $this->db->bind(':total', $total);
            $this->db->bind(':uid', $_SESSION['user_id']);
            
            $this->db->execute();
            $ventaId = $this->db->lastInsertId();

            foreach ($datos['items'] as $item) {
                $this->db->query("INSERT INTO table_ventas_detalle (venta_id, producto_id, descripcion, cantidad, precio_unitario) 
                                  VALUES (:vid, :pid, :desc, :cant, :precio)");
                $this->db->bind(':vid', $ventaId);
                $this->db->bind(':pid', $item['id'] ?: null);
                $this->db->bind(':desc', $item['nombre']);
                $this->db->bind(':cant', $item['cantidad']);
                $this->db->bind(':precio', $item['precio']);
                $this->db->execute();

                // Solo descontar stock si tiene ID (es de inventario)
                if ($item['tipo'] === 'PRODUCTO' && !empty($item['id'])) {
                    $this->db->query("UPDATE table_inventario SET stock = stock - :cant WHERE id = :pid");
                    $this->db->bind(':cant', $item['cantidad']);
                    $this->db->bind(':pid', $item['id']);
                    $this->db->execute();
                }
            }

            $this->db->commit();
            return $ventaId;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en procesarVenta: " . $e->getMessage());
            return false;
        }
    }
}