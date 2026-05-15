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
                          AND stock > 0 OR categoria = 'SERVICIO'
                          LIMIT 10");
        $this->db->bind(':term', "%$termino%");
        return $this->db->resultSet();
    }

    /**
     * Procesa una venta completa usando una transacción SQL
     */
    public function procesarVenta($datos) {
        try {
            $this->db->beginTransaction();

            // 1. Insertar Cabecera de Venta
            $this->db->query("INSERT INTO table_ventas (cliente_id, placa, modelo_vehiculo, subtotal, iva_monto, total, usuario_id) 
                              VALUES (:cid, :placa, :modelo, :sub, :iva, :total, :uid)");
            
            $this->db->bind(':cid', $datos['cliente_id'] ?: null);
            $this->db->bind(':placa', mb_strtoupper($datos['placa'] ?? '', 'UTF-8'));
            $this->db->bind(':modelo', mb_strtoupper($datos['modelo'] ?? '', 'UTF-8'));
            $this->db->bind(':sub', $datos['subtotal']);
            $this->db->bind(':iva', $datos['iva_monto']);
            $this->db->bind(':total', $datos['total']);
            $this->db->bind(':uid', $_SESSION['user_id']);
            
            $this->db->execute();
            $ventaId = $this->db->lastInsertId();

            // 2. Insertar Detalles y Actualizar Stock
            foreach ($datos['items'] as $item) {
                // Insertar Detalle
                $this->db->query("INSERT INTO table_ventas_detalle (venta_id, producto_id, descripcion, cantidad, precio_unitario) 
                                  VALUES (:vid, :pid, :desc, :cant, :precio)");
                $this->db->bind(':vid', $ventaId);
                $this->db->bind(':pid', $item['id']);
                $this->db->bind(':desc', $item['nombre']);
                $this->db->bind(':cant', $item['cantidad']);
                $this->db->bind(':precio', $item['precio']);
                $this->db->execute();

                // Descontar Stock (si no es servicio)
                // Asumimos que los servicios tienen una categoría específica o ID nulo
                if (!empty($item['id'])) {
                    $this->db->query("UPDATE table_inventario SET stock = stock - :cant WHERE id = :pid AND categoria != 'SERVICIO'");
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