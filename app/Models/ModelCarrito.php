<?php
// app/models/Carrito.php

class ModelCarrito {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function getItems() {
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }
        return $_SESSION['carrito'];
    }
    
    public function addItem($id, $cantidad = 1) {
        $repuesto = new Repuesto();
        $producto = $repuesto->getById($id);
        
        if (!$producto) {
            return false;
        }
        
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }
        
        if (isset($_SESSION['carrito'][$id])) {
            $_SESSION['carrito'][$id]['cantidad'] += $cantidad;
        } else {
            $_SESSION['carrito'][$id] = [
                'id' => $producto['id'],
                'codigo' => $producto['codigo'],
                'nombre' => $producto['nombre'],
                'precio' => $producto['precio'],
                'imagen' => $producto['imagen'],
                'cantidad' => $cantidad,
                'marca' => $producto['marca']
            ];
        }
        
        return true;
    }
    
    public function updateItem($id, $cantidad) {
        if (!isset($_SESSION['carrito'][$id])) {
            return false;
        }
        
        if ($cantidad <= 0) {
            $this->removeItem($id);
            return true;
        }
        
        $_SESSION['carrito'][$id]['cantidad'] = $cantidad;
        return true;
    }
    
    public function removeItem($id) {
        if (isset($_SESSION['carrito'][$id])) {
            unset($_SESSION['carrito'][$id]);
            return true;
        }
        return false;
    }
    
    public function clear() {
        $_SESSION['carrito'] = [];
        return true;
    }
    
    public function getTotal() {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item['precio'] * $item['cantidad'];
        }
        return $total;
    }
    
    public function getCount() {
        $count = 0;
        foreach ($this->getItems() as $item) {
            $count += $item['cantidad'];
        }
        return $count;
    }
    
    public function validateStock() {
        $repuesto = new Repuesto();
        $items = $this->getItems();
        $errors = [];
        
        foreach ($items as $id => $item) {
            $producto = $repuesto->getById($id);
            if (!$producto || $producto['stock'] < $item['cantidad']) {
                $errors[] = "Stock insuficiente para: {$item['nombre']}";
            }
        }
        
        return $errors;
    }
}
?>