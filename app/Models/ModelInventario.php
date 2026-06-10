<?php
/**
 * Modelo de Inventario
 */
class ModelInventario {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: new Database();
    }

    /**
     * Lista productos con soporte opcional para paginación (LIMIT/OFFSET)
     */
    public function listar($limit = null, $offset = null, $search = null) {
        $sql = "SELECT i.*, 
                (i.stock - COALESCE((
                    SELECT SUM(vd.cantidad) 
                    FROM table_facturas_detalle vd 
                    JOIN table_facturas v ON vd.factura_id = v.id 
                    WHERE vd.producto_id = i.id AND v.status = 'PENDIENTE'
                ), 0)) as stock_disponible
                FROM table_inventario i";
        
        if ($search) {
            $sql .= " WHERE i.nombre LIKE :search OR i.categoria LIKE :search";
        }

        $sql .= " ORDER BY i.nombre ASC";
        
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

    /**
     * Retorna la cantidad total de registros en el inventario
     */
    public function contarTotal() {
        $this->db->query("SELECT COUNT(*) as total FROM table_inventario");
        return (int)$this->db->single()->total;
    }

    /**
     * Retorna la cantidad de registros que coinciden con la búsqueda
     */
    public function contarFiltrados($search) {
        $this->db->query("SELECT COUNT(*) as total FROM table_inventario 
                          WHERE nombre LIKE :search 
                          OR categoria LIKE :search");
        $this->db->bind(':search', "%$search%");
        return (int)$this->db->single()->total;
    }

    public function buscar($termino) {
        $this->db->query("SELECT * FROM table_inventario 
                          WHERE nombre LIKE :term 
                          OR categoria LIKE :term 
                          ORDER BY nombre ASC");
        $this->db->bind(':term', "%$termino%");
        return $this->db->resultSet();
    }

    public function obtenerPorId($id) {
        $this->db->query("SELECT * FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function crear($datos) {
        $this->db->query("INSERT INTO table_inventario (nombre, categoria, stock, stock_minimo, ultimo_costo, costo_promedio, precio, imagen) 
                          VALUES (:nombre, :categoria, :stock, :smin, :costo, :cprom, :precio, :imagen)");
        
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'], 'UTF-8'));
        $this->db->bind(':categoria', mb_strtoupper($datos['categoria'], 'UTF-8'));
        $this->db->bind(':stock', $datos['stock']);
        $this->db->bind(':smin', $datos['stock_minimo'] ?? 5);
        $this->db->bind(':costo', $datos['ultimo_costo'] ?? 0);
        $this->db->bind(':cprom', $datos['costo_promedio'] ?? $datos['ultimo_costo'] ?? 0);
        $this->db->bind(':precio', $datos['precio']);
        $this->db->bind(':imagen', $datos['imagen'] ?? null);

        if (!$this->db->execute()) {
            throw new Exception("Error al insertar el producto en la base de datos.");
        }
        return true;
    }

    public function actualizar($datos) {
        $this->db->query("UPDATE table_inventario 
                          SET nombre = :nombre, 
                              categoria = :categoria, 
                              stock = :stock,
                              stock_minimo = :smin,
                              ultimo_costo = :costo,
                              costo_promedio = :cprom,
                              precio = :precio, 
                              imagen = :imagen 
                          WHERE id = :id");
        
        $this->db->bind(':id', $datos['id']);
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'], 'UTF-8'));
        $this->db->bind(':categoria', mb_strtoupper($datos['categoria'], 'UTF-8'));
        $this->db->bind(':stock', $datos['stock']);
        $this->db->bind(':smin', $datos['stock_minimo'] ?? 5);
        $this->db->bind(':costo', $datos['ultimo_costo'] ?? 0);
        $this->db->bind(':cprom', $datos['costo_promedio'] ?? 0);
        $this->db->bind(':precio', $datos['precio']);
        $this->db->bind(':imagen', $datos['imagen'] ?? null);

        if (!$this->db->execute()) {
            throw new Exception("Error al actualizar los datos del producto.");
        }
        return true;
    }

    public function eliminar($id) {
        $this->db->query("DELETE FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Registra un movimiento en el Kardex
     */
    public function registrarMovimiento($producto_id, $tipo, $cantidad, $referencia = null, $obs = null) {
        $prod = $this->obtenerPorId($producto_id);
        $stock_anterior = $prod->stock;
        
        // Calcular stock actual basado en el tipo
        $es_entrada = in_array($tipo, ['ENTRADA_COMPRA', 'DEVOLUCION']);
        $stock_actual = $es_entrada ? ($stock_anterior + $cantidad) : ($stock_anterior - $cantidad);

        $this->db->query("INSERT INTO table_kardex (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, referencia_id, usuario_id, observacion) 
                          VALUES (:pid, :tipo, :cant, :ant, :act, :ref, :uid, :obs)");
        $this->db->bind(':pid', $producto_id);
        $this->db->bind(':tipo', $tipo);
        $this->db->bind(':cant', $cantidad);
        $this->db->bind(':ant', $stock_anterior);
        $this->db->bind(':act', $stock_actual);
        $this->db->bind(':ref', $referencia);
        $this->db->bind(':uid', $_SESSION['user_id']);
        $this->db->bind(':obs', $obs);
        
        return $this->db->execute();
    }

    /**
     * Obtiene los movimientos de Kardex con soporte para paginación y búsqueda
     */
    public function obtenerKardexPaginado($producto_id, $limit = 10, $offset = 0, $search = null) {
        $where = "WHERE k.producto_id = :pid";
        if ($search) {
            $where .= " AND (k.tipo_movimiento LIKE :search OR k.observacion LIKE :search OR k.referencia_id LIKE :search)";
        }

        // Contar total
        $this->db->query("SELECT COUNT(*) as total FROM table_kardex k $where");
        $this->db->bind(':pid', $producto_id);
        if ($search) $this->db->bind(':search', "%$search%");
        $total = (int)$this->db->single()->total;

        // Obtener datos
        $this->db->query("SELECT k.*, u.username, s.nombre as usuario_nombre, i.nombre as producto_nombre
                          FROM table_kardex k
                          LEFT JOIN table_usuarios u ON k.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          LEFT JOIN table_inventario i ON k.producto_id = i.id
                          $where
                          ORDER BY k.fecha DESC 
                          LIMIT :limit OFFSET :offset");
        $this->db->bind(':pid', $producto_id);
        if ($search) $this->db->bind(':search', "%$search%");
        $this->db->bind(':limit', (int)$limit);
        $this->db->bind(':offset', (int)$offset);
        
        return ['data' => $this->db->resultSet(), 'total' => $total];
    }

    /**
     * Obtiene el historial de costos de un producto a partir de sus compras.
     */
    public function getCostHistory($productId) {
        $this->db->query("SELECT cd.costo_unitario, c.fecha
                          FROM table_compras_detalle cd
                          JOIN table_compras c ON cd.compra_id = c.id
                          WHERE cd.producto_id = :pid
                          ORDER BY c.fecha ASC");
        $this->db->bind(':pid', $productId);
        return $this->db->resultSet();
    }

    /**
     * Obtiene los productos que están en nivel crítico o agotados
     */
    public function obtenerBajoStock() {
        $this->db->query("SELECT id, nombre, stock, stock_minimo FROM table_inventario WHERE stock <= stock_minimo ORDER BY stock ASC");
        return $this->db->resultSet();
    }
}