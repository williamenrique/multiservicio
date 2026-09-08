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
     * Lista productos con información de ofertas (para catálogo público)
     */
    public function listarConOfertas($limit = null, $offset = null, $search = null, $categoria = null) {
        $sql = "SELECT i.*, 
                (i.stock - COALESCE((
                    SELECT SUM(vd.cantidad) 
                    FROM table_facturas_detalle vd 
                    JOIN table_facturas v ON vd.factura_id = v.id 
                    WHERE vd.producto_id = i.id AND v.status = 'PENDIENTE'
                ), 0)) as stock_disponible,
                -- Calcular precio con oferta si está activa y vigente
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
        
        if ($search) {
            $sql .= " AND (i.nombre LIKE :search OR i.categoria LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if ($categoria) {
            $sql .= " AND i.categoria = :categoria";
            $params[':categoria'] = $categoria;
        }

        $sql .= " ORDER BY i.nombre ASC";
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = (int)$limit;
            $params[':offset'] = (int)$offset;
        }
        
        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return $this->db->resultSet();
    }

    /**
     * Cuenta total de productos con ofertas (para paginación catálogo)
     */
    public function contarConOfertas($search = null, $categoria = null) {
        $sql = "SELECT COUNT(*) as total FROM table_inventario i WHERE i.estado = 'ACTIVO'";
        $params = [];
        
        if ($search) {
            $sql .= " AND (i.nombre LIKE :search OR i.categoria LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if ($categoria) {
            $sql .= " AND i.categoria = :categoria";
            $params[':categoria'] = $categoria;
        }

        $this->db->query($sql);
        foreach ($params as $key => $val) {
            $this->db->bind($key, $val);
        }

        return (int)$this->db->single()->total;
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

    /**
     * Busca repuestos por código, nombre o categoría para el buscador global.
     */
    public function searchRepuestos($term) {
        $this->db->query("SELECT id, codigo, nombre, categoria, stock FROM table_inventario
                          WHERE (codigo LIKE :term OR nombre LIKE :term OR categoria LIKE :term OR id LIKE :term)
                          AND estado = 'ACTIVO'
                          ORDER BY nombre ASC LIMIT 5");
        $this->db->bind(':term', "%$term%");
        return $this->db->resultSet();
    }

    public function obtenerPorId($id) {
        $this->db->query("SELECT * FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function crear($datos) {
        $this->db->query("INSERT INTO table_inventario (codigo, nombre, marca, categoria, descripcion, stock, stock_minimo, ultimo_costo, costo_promedio, precio, imagen, dias_garantia, oferta_activa, oferta_porcentaje, oferta_fecha_inicio, oferta_fecha_fin) 
                          VALUES (:codigo, :nombre, :marca, :categoria, :descripcion, :stock, :smin, :costo, :cprom, :precio, :imagen, :diasGarantia, :ofertaActiva, :ofertaPorcentaje, :ofertaFechaInicio, :ofertaFechaFin)");
        
        $this->db->bind(':codigo', $datos['codigo'] ?? null);
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'], 'UTF-8'));
        $this->db->bind(':marca', !empty($datos['marca']) ? mb_strtoupper($datos['marca'], 'UTF-8') : null);
        $this->db->bind(':categoria', mb_strtoupper($datos['categoria'], 'UTF-8'));
        $this->db->bind(':descripcion', !empty($datos['descripcion']) ? mb_strtoupper($datos['descripcion'], 'UTF-8') : null);
        $this->db->bind(':stock', $datos['stock']);
        $this->db->bind(':smin', $datos['stock_minimo'] ?? 5);
        $this->db->bind(':costo', $datos['ultimo_costo'] ?? 0);
        $this->db->bind(':cprom', $datos['costo_promedio'] ?? $datos['ultimo_costo'] ?? 0);
        $this->db->bind(':precio', $datos['precio']);
        $this->db->bind(':imagen', $datos['imagen'] ?? null);
        $this->db->bind(':diasGarantia', !empty($datos['dias_garantia']) ? (int)$datos['dias_garantia'] : null);
        $this->db->bind(':ofertaActiva', isset($datos['oferta_activa']) ? (int)$datos['oferta_activa'] : 0);
        $this->db->bind(':ofertaPorcentaje', isset($datos['oferta_porcentaje']) ? (float)$datos['oferta_porcentaje'] : 0.00);
        $this->db->bind(':ofertaFechaInicio', !empty($datos['oferta_fecha_inicio']) ? $datos['oferta_fecha_inicio'] : null);
        $this->db->bind(':ofertaFechaFin', !empty($datos['oferta_fecha_fin']) ? $datos['oferta_fecha_fin'] : null);

        if (!$this->db->execute()) {
            throw new Exception("Error al insertar el producto en la base de datos.");
        }
        return true;
    }

    public function actualizar($datos) {
        // Si solo se pasan campos de oferta (id + campos de oferta), obtener el producto actual y fusionar
        $camposOferta = ['oferta_activa', 'oferta_porcentaje', 'oferta_fecha_inicio', 'oferta_fecha_fin'];
        $soloOferta = isset($datos['id']) && count(array_diff(array_keys($datos), array_merge(['id'], $camposOferta))) === 0;
        
        if ($soloOferta) {
            $productoActual = $this->obtenerPorId($datos['id']);
            if (!$productoActual) {
                throw new Exception("Producto no encontrado.");
            }
            // Fusionar datos actuales con los nuevos campos de oferta
            $datos = array_merge((array)$productoActual, $datos);
        }
        
        $this->db->query("UPDATE table_inventario 
                          SET codigo = :codigo,
                              nombre = :nombre,
                              marca = :marca,
                              categoria = :categoria,
                              descripcion = :descripcion,
                              stock = :stock,
                              stock_minimo = :smin,
                              ultimo_costo = :costo,
                              costo_promedio = :cprom,
                              precio = :precio, 
                              imagen = :imagen,
                              dias_garantia = :diasGarantia,
                              oferta_activa = :ofertaActiva,
                              oferta_porcentaje = :ofertaPorcentaje,
                              oferta_fecha_inicio = :ofertaFechaInicio,
                              oferta_fecha_fin = :ofertaFechaFin
                          WHERE id = :id");
        
        $this->db->bind(':id', $datos['id']);
        $this->db->bind(':codigo', $datos['codigo'] ?? null);
        $this->db->bind(':nombre', isset($datos['nombre']) ? mb_strtoupper($datos['nombre'], 'UTF-8') : '');
        $this->db->bind(':marca', !empty($datos['marca']) ? mb_strtoupper($datos['marca'], 'UTF-8') : null);
        $this->db->bind(':categoria', isset($datos['categoria']) ? mb_strtoupper($datos['categoria'], 'UTF-8') : '');
        $this->db->bind(':descripcion', !empty($datos['descripcion']) ? mb_strtoupper($datos['descripcion'], 'UTF-8') : null);
        $this->db->bind(':stock', $datos['stock'] ?? 0);
        $this->db->bind(':smin', $datos['stock_minimo'] ?? 5);
        $this->db->bind(':costo', $datos['ultimo_costo'] ?? 0);
        $this->db->bind(':cprom', $datos['costo_promedio'] ?? 0);
        $this->db->bind(':precio', $datos['precio'] ?? 0);
        $this->db->bind(':imagen', $datos['imagen'] ?? null);
        $this->db->bind(':diasGarantia', !empty($datos['dias_garantia']) ? (int)$datos['dias_garantia'] : null);
        $this->db->bind(':ofertaActiva', isset($datos['oferta_activa']) ? (int)$datos['oferta_activa'] : 0);
        $this->db->bind(':ofertaPorcentaje', isset($datos['oferta_porcentaje']) ? (float)$datos['oferta_porcentaje'] : 0.00);
        $this->db->bind(':ofertaFechaInicio', !empty($datos['oferta_fecha_inicio']) ? $datos['oferta_fecha_inicio'] : null);
        $this->db->bind(':ofertaFechaFin', !empty($datos['oferta_fecha_fin']) ? $datos['oferta_fecha_fin'] : null);

        if (!$this->db->execute()) {
            throw new Exception("Error al actualizar los datos del producto.");
        }
        return true;
    }

    /**
     * Actualiza solo los campos de oferta de un producto
     * Evita problemas de merge con datos parciales
     */
    public function actualizarOferta($id, $ofertaActiva, $ofertaPorcentaje, $ofertaFechaInicio, $ofertaFechaFin) {
        $this->db->query("UPDATE table_inventario 
                          SET oferta_activa = :ofertaActiva,
                              oferta_porcentaje = :ofertaPorcentaje,
                              oferta_fecha_inicio = :ofertaFechaInicio,
                              oferta_fecha_fin = :ofertaFechaFin
                          WHERE id = :id");
        
        $this->db->bind(':id', $id);
        $this->db->bind(':ofertaActiva', (int)$ofertaActiva);
        $this->db->bind(':ofertaPorcentaje', (float)$ofertaPorcentaje);
        $this->db->bind(':ofertaFechaInicio', !empty($ofertaFechaInicio) ? $ofertaFechaInicio : null);
        $this->db->bind(':ofertaFechaFin', !empty($ofertaFechaFin) ? $ofertaFechaFin : null);

        if (!$this->db->execute()) {
            throw new Exception("Error al actualizar la oferta del producto.");
        }
        return true;
    }

    public function eliminar($id) {
        $this->db->query("DELETE FROM table_inventario WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Obtiene todos los códigos únicos existentes en el inventario
     */
    public function obtenerCodigos() {
        $this->db->query("SELECT DISTINCT codigo FROM table_inventario WHERE codigo IS NOT NULL AND codigo != '' ORDER BY codigo ASC");
        return $this->db->resultSet();
    }

    /**
     * Obtiene todas las marcas únicas existentes en el inventario
     */
    public function obtenerMarcas() {
        $this->db->query("SELECT DISTINCT marca FROM table_inventario WHERE marca IS NOT NULL AND marca != '' ORDER BY marca ASC");
        return $this->db->resultSet();
    }

    /**
     * Obtiene el siguiente número correlativo para un prefijo de código dado
     * Ej: para prefijo 'BOMGAS-' devuelve el siguiente número disponible
     */
    public function obtenerSiguienteCorrelativo($prefijo) {
        $this->db->query("SELECT codigo FROM table_inventario WHERE codigo LIKE :prefijo ORDER BY codigo DESC LIMIT 1");
        $this->db->bind(':prefijo', $prefijo . '%');
        $ultimo = $this->db->single();
        
        if ($ultimo && preg_match('/-(\d+)$/', $ultimo->codigo, $m)) {
            return (int)$m[1] + 1;
        }
        return 1;
    }

    /**
     * Registra un movimiento en el Kardex
     */
    public function registrarMovimiento($producto_id, $tipo, $cantidad, $referencia = null, $obs = null) {
        $prod = $this->obtenerPorId($producto_id);
        $stock_anterior = $prod->stock;
        
        // Calcular stock actual basado en el tipo
        $es_entrada = in_array($tipo, ['ENTRADA_COMPRA', 'DEVOLUCION', 'GARANTIA']);
        $stock_actual = $es_entrada ? ($stock_anterior + $cantidad) : ($stock_anterior - $cantidad);

        $this->db->query("INSERT INTO table_kardex (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, referencia_id, usuario_id, observacion) 
                          VALUES (:pid, :tipo, :cant, :ant, :act, :ref, :uid, :obs)");
        $this->db->bind(':pid', $producto_id);
        $this->db->bind(':tipo', $tipo);
        $this->db->bind(':cant', $cantidad);
        $this->db->bind(':ant', $stock_anterior);
        $this->db->bind(':act', $stock_actual);
        $this->db->bind(':ref', $referencia);
        $this->db->bind(':uid', $_SESSION['user_id'] ?? null);
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