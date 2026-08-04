<?php
/**
 * Modelo de Catálogo Público
 * Gestiona las operaciones de consulta de repuestos y pedidos públicos.
 * NO requiere autenticación.
 */
class ModelCatalogo {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    /**
     * Lista repuestos activos con búsqueda y filtro por categoría
     */
    public function listarRepuestos($busqueda = null, $categoria = null, $limit = 12, $offset = 0) {
        $sql = "SELECT * FROM table_inventario WHERE estado = 'ACTIVO'";
        $params = [];

        if ($busqueda) {
            $sql .= " AND (nombre LIKE :busqueda 
                      OR categoria LIKE :busqueda)";
            $params[':busqueda'] = "%$busqueda%";
        }

        if ($categoria) {
            $sql .= " AND categoria = :categoria";
            $params[':categoria'] = $categoria;
        }

        $sql .= " ORDER BY nombre ASC LIMIT :limit OFFSET :offset";
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;

        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return $this->db->resultSet();
    }

    /**
     * Cuenta total de repuestos activos (para paginación)
     */
    public function contarRepuestos($busqueda = null, $categoria = null) {
        $sql = "SELECT COUNT(*) as total FROM table_inventario WHERE estado = 'ACTIVO'";
        $params = [];

        if ($busqueda) {
            $sql .= " AND (nombre LIKE :busqueda OR categoria LIKE :busqueda)";
            $params[':busqueda'] = "%$busqueda%";
        }

        if ($categoria) {
            $sql .= " AND categoria = :categoria";
            $params[':categoria'] = $categoria;
        }

        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return (int)$this->db->single()->total;
    }

    /**
     * Obtiene las categorías disponibles (con stock)
     */
    public function obtenerCategorias() {
        $this->db->query("SELECT DISTINCT categoria FROM table_inventario 
                          WHERE estado = 'ACTIVO' AND categoria IS NOT NULL 
                          ORDER BY categoria ASC");
        return $this->db->resultSet();
    }

    /**
     * Obtiene un repuesto por su ID
     */
    public function obtenerRepuesto($id) {
        $this->db->query("SELECT * FROM table_inventario WHERE id = :id AND estado = 'ACTIVO'");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Busca repuestos por ID exacto
     */
    public function buscarPorCodigo($id) {
        $this->db->query("SELECT * FROM table_inventario WHERE id = :id AND estado = 'ACTIVO'");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Busca un repuesto por ID (alias para obtenerRepuesto)
     */
    public function buscarPorId($id) {
        return $this->obtenerRepuesto($id);
    }

    /**
     * Busca múltiples repuestos por sus IDs
     */
    public function buscarPorIds($ids) {
        if (empty($ids)) return [];
        $placeholders = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $key = ":id$i";
            $placeholders[] = $key;
            $params[$key] = (int)$id;
        }
        $in = implode(',', $placeholders);
        $this->db->query("SELECT * FROM table_inventario WHERE id IN ($in) AND estado = 'ACTIVO'");
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }
        return $this->db->resultSet();
    }

    /**
     * Obtiene repuestos destacados (con stock > 0, aleatorio)
     */
    public function obtenerDestacados($limit = 8) {
        $this->db->query("SELECT * FROM table_inventario 
                          WHERE estado = 'ACTIVO' AND stock > 0 
                          ORDER BY RAND() LIMIT :limit");
        $this->db->bind(':limit', (int)$limit);
        return $this->db->resultSet();
    }

    // ============================================================
    // GESTIÓN DE PEDIDOS PÚBLICOS
    // ============================================================

    /**
     * Crea un nuevo pedido desde el carrito público
     */
    public function crearPedido($datosCliente, $items) {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['precio'] * $item['cantidad'];
        }

        // IVA DESHABILITADO — Se usará 0 hasta que se active
        $iva = 0;
        $total = $subtotal;

        $this->db->query("INSERT INTO pedidos_clientes 
            (nombre_cliente, cedula, correo, telefono, direccion, notas, subtotal, iva, total, estado) 
            VALUES (:nombre, :cedula, :correo, :telefono, :direccion, :notas, :subtotal, :iva, :total, 'PENDIENTE')");

        $this->db->bind(':nombre', mb_strtoupper($datosCliente['nombre'], 'UTF-8'));
        $this->db->bind(':cedula', mb_strtoupper($datosCliente['cedula'], 'UTF-8'));
        $this->db->bind(':correo', mb_strtoupper($datosCliente['correo'], 'UTF-8'));
        $this->db->bind(':telefono', $datosCliente['telefono']);
        $this->db->bind(':direccion', mb_strtoupper($datosCliente['direccion'] ?? '', 'UTF-8'));
        $this->db->bind(':notas', mb_strtoupper($datosCliente['notas'] ?? '', 'UTF-8'));
        $this->db->bind(':subtotal', $subtotal);
        $this->db->bind(':iva', $iva);
        $this->db->bind(':total', $total);

        if (!$this->db->execute()) {
            throw new Exception("Error al crear el pedido.");
        }

        $pedidoId = $this->db->lastInsertId();

        // Insertar detalles del pedido
        foreach ($items as $item) {
            $itemSubtotal = $item['precio'] * $item['cantidad'];
            $this->db->query("INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, subtotal) 
                              VALUES (:pedido_id, :producto_id, :cantidad, :precio, :subtotal)");
            $this->db->bind(':pedido_id', $pedidoId);
            $this->db->bind(':producto_id', $item['id']);
            $this->db->bind(':cantidad', $item['cantidad']);
            $this->db->bind(':precio', $item['precio']);
            $this->db->bind(':subtotal', $itemSubtotal);
            $this->db->execute();
        }

        return $pedidoId;
    }

    /**
     * Obtiene un pedido por su ID
     */
    public function obtenerPedido($id) {
        $this->db->query("SELECT * FROM pedidos_clientes WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Obtiene los detalles de un pedido
     */
    public function obtenerDetallesPedido($pedidoId) {
        $this->db->query("SELECT pd.*, i.nombre, i.codigo, i.imagen 
                          FROM pedido_detalles pd 
                          JOIN table_inventario i ON pd.producto_id = i.id 
                          WHERE pd.pedido_id = :pedido_id");
        $this->db->bind(':pedido_id', $pedidoId);
        return $this->db->resultSet();
    }

    /**
     * Lista pedidos pendientes (para que el staff los procese)
     */
    public function listarPedidosPendientes() {
        $this->db->query("SELECT pc.*, 
                          (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = pc.id) as total_items
                          FROM pedidos_clientes pc 
                          WHERE pc.estado IN ('PENDIENTE', 'CONFIRMADO')
                          ORDER BY pc.fecha_pedido ASC");
        return $this->db->resultSet();
    }

    /**
     * Lista pedidos ya procesados (PROCESADO, CANCELADO)
     */
    public function listarPedidosProcesados() {
        $this->db->query("SELECT pc.*, 
                          (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = pc.id) as total_items,
                          u.nombre as nombre_usuario
                          FROM pedidos_clientes pc 
                          LEFT JOIN table_usuarios u ON pc.usuario_procesa = u.id
                          WHERE pc.estado IN ('PROCESADO', 'CANCELADO')
                          ORDER BY pc.fecha_pedido DESC
                          LIMIT 100");
        return $this->db->resultSet();
    }

    /**
     * Lista todos los pedidos (historial)
     */
    public function listarPedidos($estado = null, $limit = 50, $offset = 0) {
        $sql = "SELECT pc.*, 
                (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = pc.id) as total_items
                FROM pedidos_clientes pc";
        $params = [];

        if ($estado) {
            $sql .= " WHERE pc.estado = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY pc.fecha_pedido DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;

        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return $this->db->resultSet();
    }

    /**
     * Procesa un pedido (cambia estado a PROCESADO y descuenta inventario)
     */
    public function procesarPedido($pedidoId, $usuarioId) {
        $this->db->query("SELECT * FROM pedidos_clientes WHERE id = :id AND estado = 'PENDIENTE'");
        $this->db->bind(':id', $pedidoId);
        $pedido = $this->db->single();

        if (!$pedido) {
            throw new Exception("El pedido no existe o ya fue procesado.");
        }

        // Obtener detalles del pedido
        $detalles = $this->obtenerDetallesPedido($pedidoId);

        // Iniciar transacción
        $this->db->query("START TRANSACTION");

        try {
            // Descontar stock de cada producto y registrar en kardex
            foreach ($detalles as $detalle) {
                // Obtener stock actual antes del descuento
                $this->db->query("SELECT stock FROM table_inventario WHERE id = :id");
                $this->db->bind(':id', $detalle->producto_id);
                $producto = $this->db->single();
                if (!$producto) {
                    throw new Exception("Producto no encontrado: {$detalle->nombre}");
                }
                $stockAnterior = (int)$producto->stock;

                // Descontar stock
                $this->db->query("UPDATE table_inventario SET stock = stock - :cantidad WHERE id = :id AND stock >= :cantidad");
                $this->db->bind(':cantidad', $detalle->cantidad);
                $this->db->bind(':id', $detalle->producto_id);
                $this->db->execute();

                // Verificar que se descontó correctamente
                if ($this->db->rowCount() === 0) {
                    throw new Exception("Stock insuficiente para: {$detalle->nombre}");
                }

                $stockActual = $stockAnterior - (int)$detalle->cantidad;

                // Registrar movimiento en kardex
                $this->db->query("INSERT INTO table_kardex 
                    (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, referencia_id, usuario_id, observacion) 
                    VALUES (:producto_id, 'SALIDA_VENTA', :cantidad, :stock_anterior, :stock_actual, :referencia, :usuario_id, :observacion)");
                $this->db->bind(':producto_id', $detalle->producto_id);
                $this->db->bind(':cantidad', $detalle->cantidad);
                $this->db->bind(':stock_anterior', $stockAnterior);
                $this->db->bind(':stock_actual', $stockActual);
                $this->db->bind(':referencia', 'PEDIDO-CATALOGO-' . $pedidoId);
                $this->db->bind(':usuario_id', $usuarioId);
                $this->db->bind(':observacion', 'Venta por catálogo - Pedido #' . $pedidoId . ' - Cliente: ' . ($pedido->nombre_cliente ?? 'N/A'));
                $this->db->execute();
            }

            // Actualizar estado del pedido
            $this->db->query("UPDATE pedidos_clientes SET estado = 'PROCESADO', usuario_procesa = :usuario, fecha_procesado = NOW() WHERE id = :id");
            $this->db->bind(':usuario', $usuarioId);
            $this->db->bind(':id', $pedidoId);
            $this->db->execute();

            $this->db->query("COMMIT");
            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Cambia el estado de un pedido
     */
    public function cambiarEstadoPedido($pedidoId, $estado) {
        $this->db->query("UPDATE pedidos_clientes SET estado = :estado WHERE id = :id");
        $this->db->bind(':estado', $estado);
        $this->db->bind(':id', $pedidoId);
        return $this->db->execute();
    }
}