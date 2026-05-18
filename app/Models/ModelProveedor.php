<?php
class ModelProveedor {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function listar() {
        $this->db->query("SELECT * FROM table_proveedores ORDER BY nombre ASC");
        return $this->db->resultSet();
    }

    public function registrarCompra($datos) {
        try {
            $this->db->beginTransaction();

            $productoId = $datos['producto_id'];
            
            // 1. Si el producto no existe (ID null), lo creamos en el inventario
            if (empty($productoId)) {
                $this->db->query("INSERT INTO table_inventario (nombre, categoria, stock, precio) 
                                  VALUES (:nom, :cat, :stock, :precio)");
                $this->db->bind(':nom', $datos['nombre']);
                $this->db->bind(':cat', 'REPUESTOS');
                $this->db->bind(':stock', $datos['cantidad']);
                $this->db->bind(':precio', $datos['costo'] * 1.30); // Precio venta sugerido +30%
                $this->db->execute();
                $productoId = $this->db->lastInsertId();
            } else {
                // 2. Si existe, sumamos el stock
                $this->db->query("UPDATE table_inventario SET stock = stock + :cant WHERE id = :id");
                $this->db->bind(':cant', $datos['cantidad']);
                $this->db->bind(':id', $productoId);
                $this->db->execute();
            }

            // 3. Registrar Cabecera de Compra (Deuda)
            $totalCompra = $datos['cantidad'] * $datos['costo'];
            $this->db->query("INSERT INTO table_compras (proveedor_id, total, pagado, fecha_vencimiento, usuario_id) 
                              VALUES (:prov, :total, :pagado, :vence, :uid)");
            $this->db->bind(':prov', $datos['proveedor_id']);
            $this->db->bind(':total', $totalCompra);
            $this->db->bind(':pagado', $datos['pagado']);
            $this->db->bind(':vence', !empty($datos['fecha_cobro']) ? $datos['fecha_cobro'] : null);
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->execute();
            $compraId = $this->db->lastInsertId();

            // 4. Registrar Detalle de la Compra
            $this->db->query("INSERT INTO table_compras_detalle (compra_id, producto_id, descripcion, cantidad, costo_unitario) 
                              VALUES (:cid, :pid, :desc, :cant, :costo)");
            $this->db->bind(':cid', $compraId);
            $this->db->bind(':pid', $productoId);
            $this->db->bind(':desc', $datos['nombre']);
            $this->db->bind(':cant', $datos['cantidad']);
            $this->db->bind(':costo', $datos['costo']);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en registrarCompra: " . $e->getMessage());
            return false;
        }
    }

    public function guardar($data) {
        if (!empty($data['id_existente'])) {
            $this->db->query("UPDATE table_proveedores SET nombre = :nom, telefono = :tel, email = :em, direccion = :dir WHERE id = :id");
            $this->db->bind(':id', $data['id']);
        } else {
            $this->db->query("INSERT INTO table_proveedores (id, nombre, telefono, email, direccion) VALUES (:id, :nom, :tel, :em, :dir)");
            $this->db->bind(':id', $data['id']);
        }
        $this->db->bind(':nom', $data['nombre']);
        $this->db->bind(':tel', $data['telefono']);
        $this->db->bind(':em', $data['email']);
        $this->db->bind(':dir', $data['direccion']);
        return $this->db->execute();
    }

    public function eliminar($id) {
        $this->db->query("DELETE FROM table_proveedores WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}