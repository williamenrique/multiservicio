<?php
/**
 * Modelo de Inventario
 */
class ModelInventario {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function listar() {
        $this->db->query("SELECT * FROM table_inventario ORDER BY nombre ASC");
        return $this->db->resultSet();
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
        $this->db->query("INSERT INTO table_inventario (nombre, categoria, stock, stock_minimo, ultimo_costo, precio, imagen) 
                          VALUES (:nombre, :categoria, :stock, :smin, :costo, :precio, :imagen)");
        
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'], 'UTF-8'));
        $this->db->bind(':categoria', mb_strtoupper($datos['categoria'], 'UTF-8'));
        $this->db->bind(':stock', $datos['stock']);
        $this->db->bind(':smin', $datos['stock_minimo'] ?? 5);
        $this->db->bind(':costo', $datos['ultimo_costo'] ?? 0);
        $this->db->bind(':precio', $datos['precio']);
        $this->db->bind(':imagen', $datos['imagen'] ?? null);

        if (!$this->db->execute()) {
            throw new Exception("Error al insertar el producto en la base de datos.");
        }
    }

    public function actualizar($datos) {
        $this->db->query("UPDATE table_inventario 
                          SET nombre = :nombre, 
                              categoria = :categoria, 
                              stock = :stock,
                              stock_minimo = :smin,
                              ultimo_costo = :costo,
                              precio = :precio, 
                              imagen = :imagen 
                          WHERE id = :id");
        
        $this->db->bind(':id', $datos['id']);
        $this->db->bind(':nombre', mb_strtoupper($datos['nombre'], 'UTF-8'));
        $this->db->bind(':categoria', mb_strtoupper($datos['categoria'], 'UTF-8'));
        $this->db->bind(':stock', $datos['stock']);
        $this->db->bind(':smin', $datos['stock_minimo'] ?? 5);
        $this->db->bind(':costo', $datos['ultimo_costo'] ?? 0);
        $this->db->bind(':precio', $datos['precio']);
        $this->db->bind(':imagen', $datos['imagen'] ?? null);

        if (!$this->db->execute()) {
            throw new Exception("Error al actualizar los datos del producto.");
        }
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

        $this->db->query("INSERT INTO table_kardex (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, referencia_id, usuario_id, observaciones) 
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

    public function obtenerKardexPorProducto($producto_id) {
        $this->db->query("SELECT k.*, u.username, s.nombre as usuario_nombre 
                          FROM table_kardex k
                          LEFT JOIN table_usuarios u ON k.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE k.producto_id = :pid
                          ORDER BY k.fecha DESC");
        $this->db->bind(':pid', $producto_id);
        return $this->db->resultSet();
    }
}