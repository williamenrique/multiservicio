<?php
// app/models/Repuesto.php

class ModelRepuesto {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function getAll($busqueda = '') {
        $sql = "SELECT r.*, c.nombre as categoria_nombre 
                FROM repuestos r 
                LEFT JOIN categorias c ON r.categoria_id = c.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " AND (r.nombre LIKE :busqueda 
                    OR r.codigo LIKE :busqueda 
                    OR r.marca LIKE :busqueda
                    OR c.nombre LIKE :busqueda)";
            $params[':busqueda'] = "%$busqueda%";
        }
        
        $sql .= " ORDER BY r.fecha_creacion DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $sql = "SELECT r.*, c.nombre as categoria_nombre 
                FROM repuestos r 
                LEFT JOIN categorias c ON r.categoria_id = c.id 
                WHERE r.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getByCodigo($codigo) {
        $sql = "SELECT * FROM repuestos WHERE codigo = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$codigo]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $sql = "INSERT INTO repuestos (codigo, nombre, marca, descripcion, precio, imagen, stock, categoria_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['codigo'],
            $data['nombre'],
            $data['marca'],
            $data['descripcion'],
            $data['precio'],
            $data['imagen'] ?? null,
            $data['stock'] ?? 0,
            $data['categoria_id'] ?? null
        ]);
    }
    
    public function update($id, $data) {
        $sql = "UPDATE repuestos SET 
                codigo = ?, 
                nombre = ?, 
                marca = ?, 
                descripcion = ?, 
                precio = ?, 
                stock = ?,
                categoria_id = ?";
        $params = [
            $data['codigo'],
            $data['nombre'],
            $data['marca'],
            $data['descripcion'],
            $data['precio'],
            $data['stock'] ?? 0,
            $data['categoria_id'] ?? null
        ];
        
        if (isset($data['imagen'])) {
            $sql .= ", imagen = ?";
            $params[] = $data['imagen'];
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM repuestos WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function updateStock($id, $cantidad) {
        $sql = "UPDATE repuestos SET stock = stock - ? WHERE id = ? AND stock >= ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$cantidad, $id, $cantidad]);
    }
    
    public function getCategorias() {
        $sql = "SELECT * FROM categorias ORDER BY nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getByCategoria($categoria_id) {
        $sql = "SELECT r.*, c.nombre as categoria_nombre 
                FROM repuestos r 
                LEFT JOIN categorias c ON r.categoria_id = c.id 
                WHERE r.categoria_id = ? 
                ORDER BY r.fecha_creacion DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$categoria_id]);
        return $stmt->fetchAll();
    }
    
    public function getDestacados($limit = 8) {
        $sql = "SELECT r.*, c.nombre as categoria_nombre 
                FROM repuestos r 
                LEFT JOIN categorias c ON r.categoria_id = c.id 
                WHERE r.stock > 0 
                ORDER BY r.fecha_creacion DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
?>