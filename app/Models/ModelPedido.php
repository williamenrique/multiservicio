<?php
// app/models/Pedido.php

class ModelPedido {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function create($datosCliente, $items) {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['precio'] * $item['cantidad'];
        }
        
        $this->pdo->beginTransaction();
        
        try {
            // Insertar pedido
            $sql = "INSERT INTO pedidos (nombre_cliente, cedula, correo, telefono, total) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                mb_strtoupper($datosCliente['nombre'], 'UTF-8'),
                mb_strtoupper($datosCliente['cedula'], 'UTF-8'),
                mb_strtoupper($datosCliente['correo'], 'UTF-8'),
                $datosCliente['telefono'],
                $total
            ]);
            
            $pedidoId = $this->pdo->lastInsertId();
            
            // Insertar detalles
            $repuesto = new Repuesto();
            $sql = "INSERT INTO pedido_detalles (pedido_id, repuesto_id, cantidad, precio_unitario) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($items as $item) {
                $stmt->execute([
                    $pedidoId,
                    $item['id'],
                    $item['cantidad'],
                    $item['precio']
                ]);
                
                // Actualizar stock
                $repuesto->updateStock($item['id'], $item['cantidad']);
            }
            
            $this->pdo->commit();
            return $pedidoId;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM pedidos WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getDetalles($pedidoId) {
        $sql = "SELECT pd.*, r.nombre, r.codigo 
                FROM pedido_detalles pd 
                JOIN repuestos r ON pd.repuesto_id = r.id 
                WHERE pd.pedido_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$pedidoId]);
        return $stmt->fetchAll();
    }
    
    public function getAll($limit = 50, $offset = 0) {
        $sql = "SELECT * FROM pedidos ORDER BY fecha_pedido DESC LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function updateEstado($id, $estado) {
        $sql = "UPDATE pedidos SET estado = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$estado, $id]);
    }
    
    public function getByCliente($cedula) {
        $sql = "SELECT * FROM pedidos WHERE cedula = ? ORDER BY fecha_pedido DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetchAll();
    }
}
?>