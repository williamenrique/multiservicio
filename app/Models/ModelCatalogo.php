<?php
/**
 * Modelo de Catálogo Público
 * Gestiona las operaciones de consulta de repuestos y pedidos públicos.
 * NO requiere autenticación.
 * 
 * v2.2 (2026-10-09) — P3-07:
 *   • `buscarPorCodigo($id)` ahora delega a `buscarPorId($id)`, que a su vez
 *     delega a `obtenerRepuesto($id)`. Antes tenía su propia query idéntica
 *     a `obtenerRepuesto`, duplicando ~15 líneas de SQL.
 */
class ModelCatalogo {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    /**
     * Lista repuestos activos con búsqueda y filtro por categoría
     * Incluye cálculo de precio con oferta si está activa y vigente
     */
    public function listarRepuestos($busqueda = null, $categoria = null, $limit = 12, $offset = 0, $oferta = 0) {
        $sql = "SELECT i.*,
                CASE 
                    WHEN i.oferta_activa = 1 
                         AND i.oferta_porcentaje > 0 
                         AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                         AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())
                    THEN ROUND(i.precio * (1 - i.oferta_porcentaje / 100), 2)
                    ELSE i.precio
                END as precio_final,
                CASE 
                    WHEN i.oferta_activa = 1 
                         AND i.oferta_porcentaje > 0 
                         AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                         AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())
                    THEN 1
                    ELSE 0
                END as en_oferta_vigente
                FROM table_inventario i
                WHERE i.estado = 'ACTIVO'";
        $params = [];

        if ($busqueda) {
            $sql .= " AND (i.nombre LIKE :busqueda 
                      OR i.categoria LIKE :busqueda)";
            $params[':busqueda'] = "%$busqueda%";
        }

        if ($categoria) {
            $sql .= " AND i.categoria = :categoria";
            $params[':categoria'] = $categoria;
        }

        if ($oferta) {
            $sql .= " AND i.oferta_activa = 1 
                         AND i.oferta_porcentaje > 0 
                         AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                         AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())";
        }

        $sql .= " ORDER BY i.nombre ASC LIMIT :limit OFFSET :offset";
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;

        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return $this->db->resultSet();
    }

    public function contarRepuestos($busqueda = null, $categoria = null, $oferta = 0) {
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

        if ($oferta) {
            $sql .= " AND oferta_activa = 1 
                         AND oferta_porcentaje > 0 
                         AND (oferta_fecha_inicio IS NULL OR oferta_fecha_inicio <= CURDATE())
                         AND (oferta_fecha_fin IS NULL OR oferta_fecha_fin >= CURDATE())";
        }

        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return (int)$this->db->single()->total;
    }

    public function obtenerCategorias() {
        $this->db->query("SELECT DISTINCT categoria FROM table_inventario 
                          WHERE estado = 'ACTIVO' AND categoria IS NOT NULL 
                          ORDER BY categoria ASC");
        return $this->db->resultSet();
    }

    public function obtenerRepuesto($id) {
        $this->db->query("SELECT i.*,
                CASE 
                    WHEN i.oferta_activa = 1 
                         AND i.oferta_porcentaje > 0 
                         AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                         AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())
                    THEN ROUND(i.precio * (1 - i.oferta_porcentaje / 100), 2)
                    ELSE i.precio
                END as precio_final,
                CASE 
                    WHEN i.oferta_activa = 1 
                         AND i.oferta_porcentaje > 0 
                         AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                         AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())
                    THEN 1
                    ELSE 0
                END as en_oferta_vigente
                FROM table_inventario i
                WHERE i.id = :id AND i.estado = 'ACTIVO'");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Alias histórico de `buscarPorId()`. Se mantiene por retrocompatibilidad
     * porque hay código externo que puede invocarlo.
     * 
     * FIX P3-07: antes tenía su propia query idéntica a `obtenerRepuesto`.
     * Ahora delega para no duplicar SQL.
     */
    public function buscarPorCodigo($id) {
        return $this->buscarPorId($id);
    }

    public function buscarPorId($id) {
        return $this->obtenerRepuesto($id);
    }

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
        $this->db->query("SELECT i.*,
                CASE 
                    WHEN i.oferta_activa = 1 
                         AND i.oferta_porcentaje > 0 
                         AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                         AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())
                    THEN ROUND(i.precio * (1 - i.oferta_porcentaje / 100), 2)
                    ELSE i.precio
                END as precio_final,
                CASE 
                    WHEN i.oferta_activa = 1 
                         AND i.oferta_porcentaje > 0 
                         AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                         AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())
                    THEN 1
                    ELSE 0
                END as en_oferta_vigente
                FROM table_inventario i
                WHERE i.id IN ($in) AND i.estado = 'ACTIVO'");
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }
        return $this->db->resultSet();
    }

    public function obtenerDestacados($limit = 8) {
        $this->db->query("SELECT i.*,
                CASE 
                    WHEN i.oferta_activa = 1 
                         AND i.oferta_porcentaje > 0 
                         AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                         AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())
                    THEN ROUND(i.precio * (1 - i.oferta_porcentaje / 100), 2)
                    ELSE i.precio
                END as precio_final,
                CASE 
                    WHEN i.oferta_activa = 1 
                         AND i.oferta_porcentaje > 0 
                         AND (i.oferta_fecha_inicio IS NULL OR i.oferta_fecha_inicio <= CURDATE())
                         AND (i.oferta_fecha_fin IS NULL OR i.oferta_fecha_fin >= CURDATE())
                    THEN 1
                    ELSE 0
                END as en_oferta_vigente
                FROM table_inventario i
                WHERE i.estado = 'ACTIVO' AND i.stock > 0 
                ORDER BY RAND() LIMIT :limit");
        $this->db->bind(':limit', (int)$limit);
        return $this->db->resultSet();
    }

    // ============================================================
    // GESTIÓN DE PEDIDOS PÚBLICOS
    // ============================================================

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
        // CORREO EN MINÚSCULAS
        $this->db->bind(':correo', mb_strtolower($datosCliente['correo'], 'UTF-8'));
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

    public function obtenerPedido($id) {
        $this->db->query("SELECT * FROM pedidos_clientes WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function obtenerDetallesPedido($pedidoId) {
        $this->db->query("SELECT pd.*, i.nombre, i.codigo, i.imagen 
                          FROM pedido_detalles pd 
                          JOIN table_inventario i ON pd.producto_id = i.id 
                          WHERE pd.pedido_id = :pedido_id");
        $this->db->bind(':pedido_id', $pedidoId);
        return $this->db->resultSet();
    }

    public function listarPedidosPendientes() {
        $this->db->query("SELECT pc.*, 
                          (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = pc.id) as total_items
                          FROM pedidos_clientes pc 
                          WHERE pc.estado IN ('PENDIENTE', 'CONFIRMADO')
                          ORDER BY pc.fecha_pedido ASC");
        return $this->db->resultSet();
    }

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

    public function procesarPedido($pedidoId, $usuarioId) {
        $this->db->query("SELECT * FROM pedidos_clientes WHERE id = :id AND estado = 'PENDIENTE'");
        $this->db->bind(':id', $pedidoId);
        $pedido = $this->db->single();

        if (!$pedido) {
            throw new Exception("El pedido no existe o ya fue procesado.");
        }

        $detalles = $this->obtenerDetallesPedido($pedidoId);

        $this->db->query("START TRANSACTION");

        try {
            foreach ($detalles as $detalle) {
                $this->db->query("SELECT stock FROM table_inventario WHERE id = :id");
                $this->db->bind(':id', $detalle->producto_id);
                $producto = $this->db->single();
                if (!$producto) {
                    throw new Exception("Producto no encontrado: {$detalle->nombre}");
                }
                $stockAnterior = (int)$producto->stock;

                $this->db->query("UPDATE table_inventario SET stock = stock - :cantidad WHERE id = :id AND stock >= :cantidad");
                $this->db->bind(':cantidad', $detalle->cantidad);
                $this->db->bind(':id', $detalle->producto_id);
                $this->db->execute();

                if ($this->db->rowCount() === 0) {
                    throw new Exception("Stock insuficiente para: {$detalle->nombre}");
                }

                $stockActual = $stockAnterior - (int)$detalle->cantidad;

                $this->db->query("INSERT INTO table_kardex 
                    (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, referencia_id, usuario_id, observacion) 
                    VALUES (:producto_id, 'SALIDA_VENTA', :cantidad, :stock_anterior, :stock_actual, :referencia, :usuario_id, :observacion)");
                $this->db->bind(':producto_id', $detalle->producto_id);
                $this->db->bind(':cantidad', $detalle->cantidad);
                $this->db->bind(':stock_anterior', $stockAnterior);
                $this->db->bind(':stock_actual', $stockActual);
                $this->db->bind(':referencia', 'PEDIDO-CATALOGO-' . $pedidoId);
                $this->db->bind(':usuario_id', $usuarioId);
                $this->db->bind(':observacion', mb_strtoupper('Venta por catálogo - Pedido #' . $pedidoId . ' - Cliente: ' . ($pedido->nombre_cliente ?? 'N/A'), 'UTF-8'));
                $this->db->execute();
            }

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

    public function cambiarEstadoPedido($pedidoId, $estado) {
        $this->db->query("UPDATE pedidos_clientes SET estado = :estado WHERE id = :id");
        $this->db->bind(':estado', $estado);
        $this->db->bind(':id', $pedidoId);
        return $this->db->execute();
    }

    public function actualizarIvaPedido($pedidoId, $aplicarIva, $tasaIva) {
        $pedido = $this->obtenerPedido($pedidoId);
        if (!$pedido) {
            throw new Exception("Pedido no encontrado para actualizar IVA.");
        }

        $subtotal = (float)$pedido->subtotal;
        $iva = $aplicarIva ? ($subtotal * ((float)$tasaIva / 100)) : 0;
        $total = $subtotal + $iva;

        $this->db->query("UPDATE pedidos_clientes SET iva = :iva, total = :total WHERE id = :id");
        $this->db->bind(':iva', $iva);
        $this->db->bind(':total', $total);
        $this->db->bind(':id', $pedidoId);
        return $this->db->execute();
    }
}