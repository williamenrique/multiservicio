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

            // 2. Validaciones de negocio
            if (empty($datos['monto']) || (float)$datos['monto'] <= 0) {
                throw new Exception("El monto del abono debe ser mayor a cero");
            }
            
            $nuevoPagado = (float)$compra->pagado + (float)$datos['monto'];
            $nuevoStatus = ($nuevoPagado >= (float)$compra->total) ? 'PAGADO' : 'PENDIENTE';

            // 3. Registrar el abono en el historial (Para flujo de caja granular)
            $this->db->query("INSERT INTO table_abonos_proveedores (compra_id, monto, metodo_pago, usuario_id) 
                              VALUES (:cid, :monto, :metodo, :uid)");
            $this->db->bind(':cid', $datos['compra_id']);
            $this->db->bind(':monto', $datos['monto']);
            $this->db->bind(':metodo', $datos['metodo_pago'] ?? 'EFECTIVO');
            $this->db->bind(':uid', $_SESSION['user_id'] ?? null);
            $this->db->execute();

            // 4. Actualizar saldo en la cabecera de la compra
            $this->db->query("UPDATE table_compras SET pagado = :pag, status = :status, usuario_id = :uid WHERE id = :id");
            $this->db->bind(':pag', $nuevoPagado);
            $this->db->bind(':status', $nuevoStatus);
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->bind(':id', $datos['compra_id']);
            $this->db->execute();

            // 5. Registrar Egreso en el Libro Mayor (table_transacciones)
            $this->db->query("INSERT INTO table_transacciones (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id) 
                              VALUES (1, 'EGRESO', 'ABONO_PROVEEDOR', :monto, :ref, :desc, :uid)");
            $this->db->bind(':monto', $datos['monto']);
            $this->db->bind(':ref', $datos['compra_id']);
            $this->db->bind(':desc', "PAGO A PROVEEDOR - COMPRA #{$datos['compra_id']} (" . ($datos['metodo_pago'] ?? 'EFECTIVO') . ")");
            $this->db->bind(':uid', $_SESSION['user_id']);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene el historial de pagos realizados a una compra específica
     */
    public function obtenerHistorialAbonos($compraId) {
        $this->db->query("SELECT a.*, u.username 
                          FROM table_abonos_proveedores a 
                          LEFT JOIN table_usuarios u ON a.usuario_id = u.id 
                          WHERE a.compra_id = :cid ORDER BY a.fecha DESC");
        $this->db->bind(':cid', $compraId);
        return $this->db->resultSet();
    }

    public function registrarCompra($datos) {
        try {
            $this->db->beginTransaction();

            $productoId = $datos['producto_id'];
            $costo = (float)$datos['costo'];
            
            // 1. Si el producto no existe (ID null), lo creamos en el inventario
            if (empty($productoId)) {
                $precioVenta = (float)($datos['precio_venta'] ?? ($costo * 1.30));
                $this->db->query("INSERT INTO table_inventario (nombre, categoria, stock, ultimo_costo, costo_promedio, precio) 
                                  VALUES (:nom, :cat, :stock, :costo, :cpp, :precio)");
                $this->db->bind(':nom', mb_strtoupper($datos['nombre'], 'UTF-8'));
                $this->db->bind(':cat', mb_strtoupper($datos['categoria'] ?? 'REPUESTOS', 'UTF-8'));
                $this->db->bind(':stock', $datos['cantidad']);
                $this->db->bind(':costo', $costo);
                $this->db->bind(':cpp', $costo);
                $this->db->bind(':precio', $precioVenta);
                $this->db->execute();
                $productoId = $this->db->lastInsertId();
            } else {
                // 2. Si existe, recalculamos CPP (Costo Promedio Ponderado)
                $this->db->query("SELECT stock, costo_promedio, precio FROM table_inventario WHERE id = :id");
                $this->db->bind(':id', $productoId);
                $actual = $this->db->single();

                $stockActual = (float)($actual->stock ?? 0);
                $cppActual = (float)($actual->costo_promedio ?? 0);
                $nuevaCant = (float)$datos['cantidad'];
                
                // Fórmula CPP: ((Stock Actual * CPP Actual) + (Nueva Cant * Nuevo Costo)) / (Stock Actual + Nueva Cant)
                $nuevoCpp = ($stockActual > 0) 
                    ? (($stockActual * $cppActual) + ($nuevaCant * $costo)) / ($stockActual + $nuevaCant)
                    : $costo;

                // Mantenemos el precio actual si no se envía uno nuevo explícitamente
                $precioVenta = isset($datos['precio_venta']) ? (float)$datos['precio_venta'] : (float)$actual->precio;

                $this->db->query("UPDATE table_inventario SET stock = stock + :cant, ultimo_costo = :costo, costo_promedio = :cpp, precio = :precio, estado = 'ACTIVO' WHERE id = :id");
                $this->db->bind(':cant', $datos['cantidad']);
                $this->db->bind(':costo', $costo);
                $this->db->bind(':cpp', $nuevoCpp);
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
            $invModel = new ModelInventario($this->db);
            $invModel->registrarMovimiento($productoId, 'ENTRADA_COMPRA', $datos['cantidad'], $compraId, "Compra a proveedor Factura #$compraId");

            // 5. Registrar Detalle de la Compra
            $this->db->query("INSERT INTO table_compras_detalle (compra_id, producto_id, descripcion, cantidad, costo_unitario) 
                              VALUES (:cid, :pid, :desc, :cant, :costo)");
            $this->db->bind(':cid', $compraId);
            $this->db->bind(':pid', $productoId);
            $this->db->bind(':desc', mb_strtoupper($datos['nombre'], 'UTF-8'));
            $this->db->bind(':cant', $datos['cantidad']);
            $this->db->bind(':costo', $datos['costo']);
            $this->db->execute();

            // 6. Registrar Egreso en el Libro Mayor (table_transacciones) si hubo pago inmediato
            if ($datos['pagado'] > 0) {
                $this->db->query("INSERT INTO table_transacciones (cuenta_id, tipo, categoria, monto, referencia_id, descripcion, usuario_id) 
                                  VALUES (1, 'EGRESO', 'COMPRA_PROVEEDOR', :monto, :ref, :desc, :uid)");
                $this->db->bind(':monto', $datos['pagado']);
                $this->db->bind(':ref', $compraId);
                $this->db->bind(':desc', "COMPRA DE REPUESTOS #$compraId - " . mb_strtoupper($datos['nombre'], 'UTF-8'));
                $this->db->bind(':uid', $_SESSION['user_id']);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en registrarCompra: " . $e->getMessage());
            return false;
        }
    }

    public function guardar($data) {
        $id = mb_strtoupper(trim($data['id'] ?? ''), 'UTF-8');
        $nombre = mb_strtoupper(trim($data['nombre'] ?? ''), 'UTF-8');
        $email = mb_strtolower(trim($data['email'] ?? ''), 'UTF-8');
        $direccion = mb_strtoupper(trim($data['direccion'] ?? ''), 'UTF-8');

        if (!empty($data['id_existente'])) {
            $this->db->query("UPDATE table_proveedores SET id = :new_id, nombre = :nom, telefono = :tel, email = :em, direccion = :dir WHERE id = :old_id");
            $this->db->bind(':new_id', $id);
            $this->db->bind(':old_id', mb_strtoupper(trim($data['id_existente']), 'UTF-8'));
        } else {
            $this->db->query("INSERT INTO table_proveedores (id, nombre, telefono, email, direccion) VALUES (:id, :nom, :tel, :em, :dir)");
            $this->db->bind(':id', $id);
        }
        $this->db->bind(':nom', $nombre);
        $this->db->bind(':tel', $data['telefono'] ?? '');
        $this->db->bind(':em', $email);
        $this->db->bind(':dir', $direccion);
        return $this->db->execute();
    }

    /**
     * Verifica la existencia de un ID (NIT/Cédula) en la tabla de proveedores
     */
    public function existeId($id, $excludeId = null) {
        $sql = "SELECT COUNT(*) as total FROM table_proveedores WHERE id = :id";
        
        // Si estamos editando, excluimos el ID original para permitir guardar sin cambios
        if ($excludeId) {
            $sql .= " AND id <> :exclude";
        }

        $this->db->query($sql);
        $this->db->bind(':id', mb_strtoupper(trim($id), 'UTF-8'));
        if ($excludeId) {
            $this->db->bind(':exclude', mb_strtoupper(trim($excludeId), 'UTF-8'));
        }

        $res = $this->db->single();
        return (int)$res->total > 0;
    }

    /**
     * Verifica la existencia de un email en la tabla de proveedores
     */
    public function existeEmail($email, $id = null) {
        $sql = "SELECT COUNT(*) as total FROM table_proveedores WHERE email = :email";
        
        // Si estamos editando, excluimos el ID del proveedor actual
        if ($id) {
            $sql .= " AND id <> :id";
        }

        $this->db->query($sql);
        $this->db->bind(':email', $email);
        if ($id) {
            $this->db->bind(':id', $id);
        }

        $res = $this->db->single();
        return (int)$res->total > 0;
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