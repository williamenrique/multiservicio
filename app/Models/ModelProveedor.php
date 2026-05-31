<?php
class ModelProveedor {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

       /**
     * Obtener todos los proveedores
     */
    public function listar($limit = null, $offset = null, $search = null) {
        $sql = "SELECT * FROM table_proveedores";
        
        if ($search) {
            $sql .= " WHERE nombre LIKE :search OR id LIKE :search OR telefono LIKE :search";
        }

        $sql .= " ORDER BY nombre ASC";
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        
        $this->db->query($sql);
        
        if ($search) {
            $this->db->bind(':search', "%$search%");
        }
        
        if ($limit !== null && $offset !== null) {
            $this->db->bind(':limit', (int)$limit);
            $this->db->bind(':offset', (int)$offset);
        }

        return $this->db->resultSet();
    }

    public function contarTotal() {
        $this->db->query("SELECT COUNT(*) as total FROM table_proveedores");
        return (int)$this->db->single()->total;
    }

    public function contarFiltrados($search) {
        $this->db->query("SELECT COUNT(*) as total FROM table_proveedores 
                          WHERE nombre LIKE :search 
                          OR id LIKE :search 
                          OR telefono LIKE :search");
        $this->db->bind(':search', "%$search%");
        return (int)$this->db->single()->total;
    }

    /**
     * Obtiene un solo proveedor por su identificador (NIT/ID)
     */
    public function obtenerPorId($id) {
        $this->db->query("SELECT * FROM table_proveedores WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function listarDeudas() {
        $this->db->query("SELECT p.id, p.nombre, p.telefono, 
                          SUM(c.total) as total_compras,
                          SUM(c.pagado) as total_pagado,
                          SUM(c.total - c.pagado) as saldo_pendiente,
                          MIN(CASE WHEN c.total > c.pagado THEN c.fecha_vencimiento ELSE NULL END) as proximo_vencimiento,
                          COUNT(CASE WHEN c.total > c.pagado THEN 1 END) as facturas_pendientes
                          FROM table_proveedores p
                          INNER JOIN table_compras c ON p.id = c.proveedor_id
                          GROUP BY p.id, p.nombre, p.telefono
                          HAVING saldo_pendiente > 0
                          ORDER BY proximo_vencimiento ASC");
        return $this->db->resultSet();
    }

    public function obtenerComprasPendientes($proveedorId) {
        $this->db->query("SELECT * FROM table_compras 
                          WHERE proveedor_id = :pid AND (total - pagado) > 0 
                          ORDER BY fecha ASC");
        $this->db->bind(':pid', $proveedorId);
        return $this->db->resultSet();
    }

    public function registrarPagoCompra($datos) {
        try {
            $this->db->beginTransaction();

            // 1. Obtener estado actual de la compra
            $this->db->query("SELECT total, pagado FROM table_compras WHERE id = :id");
            $this->db->bind(':id', $datos['compra_id']);
            $compra = $this->db->single();

            if (!$compra) throw new Exception("Compra no encontrada");

            // 2. Calcular nuevo saldo
            $nuevoPagado = (float)$compra->pagado + (float)$datos['monto'];
            $nuevoStatus = ($nuevoPagado >= (float)$compra->total) ? 'PAGADO' : 'PENDIENTE';

            // 3. Actualizar saldo y registrar qué usuario procesó el abono
            $this->db->query("UPDATE table_compras SET pagado = :pag, status = :status, usuario_id = :uid WHERE id = :id");
            $this->db->bind(':pag', $nuevoPagado);
            $this->db->bind(':status', $nuevoStatus);
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->bind(':id', $datos['compra_id']);
            $this->db->execute();

            // 4. Registrar el Egreso en Caja para el balance financiero
            $caja = new ModelCaja();
            $caja->registrarMovimiento([
                'tipo' => 'EGRESO',
                'monto' => $datos['monto'],
                'metodo_pago' => $datos['metodo_pago'] ?? 'EFECTIVO',
                'referencia_id' => $datos['compra_id'],
                'concepto' => "ABONO A FACTURA PROVEEDOR #" . $datos['compra_id']
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function registrarCompra($datos) {
        try {
            $this->db->beginTransaction();

            $productoId = $datos['producto_id'];
            $costo = (float)$datos['costo'];
            $precioVenta = (float)($datos['precio_venta'] ?? ($costo * 1.30));
            
            // 1. Si el producto no existe (ID null), lo creamos en el inventario
            if (empty($productoId)) {
                $this->db->query("INSERT INTO table_inventario (nombre, categoria, stock, ultimo_costo, precio) 
                                  VALUES (:nom, :cat, :stock, :costo, :precio)");
                $this->db->bind(':nom', mb_strtoupper($datos['nombre'], 'UTF-8'));
                $this->db->bind(':cat', mb_strtoupper($datos['categoria'] ?? 'REPUESTOS', 'UTF-8'));
                $this->db->bind(':stock', $datos['cantidad']);
                $this->db->bind(':costo', $costo);
                $this->db->bind(':precio', $precioVenta);
                $this->db->execute();
                $productoId = $this->db->lastInsertId();
            } else {
                // 2. Si existe, sumamos el stock y actualizamos costos/precios (Estrategia Reposición)
                $this->db->query("UPDATE table_inventario SET stock = stock + :cant, ultimo_costo = :costo, precio = :precio WHERE id = :id");
                $this->db->bind(':cant', $datos['cantidad']);
                $this->db->bind(':costo', $costo);
                $this->db->bind(':precio', $precioVenta);
                $this->db->bind(':id', $productoId);
                $this->db->execute();
            }

            // 3. Registrar Cabecera de Compra (Deuda)
            $totalCompra = $datos['cantidad'] * $datos['costo'];
            $statusCompra = ($datos['pagado'] >= $totalCompra) ? 'PAGADO' : 'PENDIENTE';
            
            $this->db->query("INSERT INTO table_compras (proveedor_id, total, pagado, status, fecha_vencimiento, usuario_id) 
                              VALUES (:prov, :total, :pagado, :status, :vence, :uid)");
            $this->db->bind(':prov', $datos['proveedor_id']);
            $this->db->bind(':total', $totalCompra);
            $this->db->bind(':pagado', $datos['pagado']);
            $this->db->bind(':status', $statusCompra);
            $this->db->bind(':vence', !empty($datos['fecha_cobro']) ? $datos['fecha_cobro'] : null);
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->execute();
            $compraId = $this->db->lastInsertId();

            // 4. Registrar Movimiento en Kardex vinculado a la compra
            $invModel = new ModelInventario();
            $invModel->registrarMovimiento($productoId, 'ENTRADA_COMPRA', $datos['cantidad'], $compraId, "Compra a proveedor Factura #$compraId");

            // 4. Registrar Detalle de la Compra
            $this->db->query("INSERT INTO table_compras_detalle (compra_id, producto_id, descripcion, cantidad, costo_unitario) 
                              VALUES (:cid, :pid, :desc, :cant, :costo)");
            $this->db->bind(':cid', $compraId);
            $this->db->bind(':pid', $productoId);
            $this->db->bind(':desc', mb_strtoupper($datos['nombre'], 'UTF-8'));
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
        $this->db->bind(':nom', mb_strtoupper(trim($data['nombre']), 'UTF-8'));
        $this->db->bind(':tel', $data['telefono']);
        $this->db->bind(':em', mb_strtolower(trim($data['email']), 'UTF-8'));
        $this->db->bind(':dir', mb_strtoupper(trim($data['direccion']), 'UTF-8'));
        return $this->db->execute();
    }

    public function eliminar($id) {
        $this->db->query("DELETE FROM table_proveedores WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function obtenerDetalleCompra($id) {
        $this->db->query("SELECT c.*, p.nombre as proveedor_nombre, p.telefono as proveedor_telefono, 
                          u.username as usuario_nombre 
                          FROM table_compras c
                          INNER JOIN table_proveedores p ON c.proveedor_id = p.id
                          INNER JOIN table_usuarios u ON c.usuario_id = u.id
                          WHERE c.id = :id");
        $this->db->bind(':id', $id);
        $compra = $this->db->single();

        if ($compra) {
            $this->db->query("SELECT cd.*, i.nombre as producto_nombre 
                              FROM table_compras_detalle cd 
                              LEFT JOIN table_inventario i ON cd.producto_id = i.id
                              WHERE cd.compra_id = :id");
            $this->db->bind(':id', $id);
            $compra->items = $this->db->resultSet();
        }
        return $compra;
    }
}