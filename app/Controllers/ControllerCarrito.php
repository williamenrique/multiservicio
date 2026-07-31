<?php
// app/controllers/ControllerCarrito.php

class ControllerCarrito extends Controller{
    private $carrito;
    private $repuesto;
    
    public function __construct() {
        $this->carrito = new Carrito();
        $this->repuesto = new Repuesto();
    }
    
    public function index() {
        $items = $this->carrito->getItems();
        $total = $this->carrito->getTotal();
        $count = $this->carrito->getCount();
        
        // Verificar stock
        $erroresStock = $this->carrito->validateStock();
        
        $this->render('carrito/index', [
            'items' => $items,
            'total' => $total,
            'count' => $count,
            'erroresStock' => $erroresStock
        ]);
    }
    
    public function agregar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
            
            if ($id > 0) {
                $resultado = $this->carrito->addItem($id, $cantidad);
                echo json_encode([
                    'success' => $resultado,
                    'count' => $this->carrito->getCount()
                ]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit;
        }
    }
    
    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;
            
            if ($id > 0) {
                $resultado = $this->carrito->updateItem($id, $cantidad);
                $total = $this->carrito->getTotal();
                $count = $this->carrito->getCount();
                
                echo json_encode([
                    'success' => $resultado,
                    'total' => number_format($total, 2),
                    'count' => $count
                ]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit;
        }
    }
    
    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            
            if ($id > 0) {
                $resultado = $this->carrito->removeItem($id);
                $total = $this->carrito->getTotal();
                $count = $this->carrito->getCount();
                
                echo json_encode([
                    'success' => $resultado,
                    'total' => number_format($total, 2),
                    'count' => $count
                ]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit;
        }
    }
    
    public function contar() {
        echo json_encode(['total' => $this->carrito->getCount()]);
        exit;
    }
    
    private function render($view, $data = []) {
        extract($data);
        ob_start();
        require_once __DIR__ . '/../views/' . $view . '.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }
}
?>