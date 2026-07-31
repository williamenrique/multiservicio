<?php
// app/controllers/ControllerPedido.php

class ControllerPedido extends Controller{
    private $pedido;
    private $carrito;
    
    public function __construct() {
        $this->pedido = new Pedido();
        $this->carrito = new Carrito();
    }
    
    public function finalizar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /catalogo_repuestos_mvc/carrito');
            exit;
        }
        
        $items = $this->carrito->getItems();
        if (empty($items)) {
            $_SESSION['error'] = 'El carrito está vacío';
            header('Location: /catalogo_repuestos_mvc/carrito');
            exit;
        }
        
        // Validar datos del cliente
        $nombre = trim($_POST['nombre']);
        $cedula = trim($_POST['cedula']);
        $correo = trim($_POST['correo']);
        $telefono = trim($_POST['telefono']);
        
        if (empty($nombre) || empty($cedula) || empty($correo) || empty($telefono)) {
            $_SESSION['error'] = 'Todos los campos son obligatorios';
            header('Location: /catalogo_repuestos_mvc/carrito');
            exit;
        }
        
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Correo electrónico no válido';
            header('Location: /catalogo_repuestos_mvc/carrito');
            exit;
        }
        
        // Validar stock
        $erroresStock = $this->carrito->validateStock();
        if (!empty($erroresStock)) {
            $_SESSION['error'] = implode(', ', $erroresStock);
            header('Location: /catalogo_repuestos_mvc/carrito');
            exit;
        }
        
        try {
            $datosCliente = [
                'nombre' => $nombre,
                'cedula' => $cedula,
                'correo' => $correo,
                'telefono' => $telefono
            ];
            
            $pedidoId = $this->pedido->create($datosCliente, $items);
            
            // Limpiar carrito
            $this->carrito->clear();
            
            // Guardar ID del pedido en sesión para la confirmación
            $_SESSION['pedido_id'] = $pedidoId;
            
            header('Location: /catalogo_repuestos_mvc/pedido/confirmacion');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al procesar el pedido: ' . $e->getMessage();
            header('Location: /catalogo_repuestos_mvc/carrito');
            exit;
        }
    }
    
    public function confirmacion() {
        if (!isset($_SESSION['pedido_id'])) {
            header('Location: /catalogo_repuestos_mvc');
            exit;
        }
        
        $pedidoId = $_SESSION['pedido_id'];
        $pedido = $this->pedido->getById($pedidoId);
        $detalles = $this->pedido->getDetalles($pedidoId);
        
        unset($_SESSION['pedido_id']);
        
        $this->render('pedidos/confirmacion', [
            'pedido' => $pedido,
            'detalles' => $detalles
        ]);
    }
    
    public function historial() {
        $cedula = isset($_GET['cedula']) ? $_GET['cedula'] : '';
        $pedidos = [];
        
        if (!empty($cedula)) {
            $pedidos = $this->pedido->getByCliente($cedula);
        }
        
        $this->render('pedidos/historial', [
            'pedidos' => $pedidos,
            'cedula' => $cedula
        ]);
    }
    
    public function admin() {
        $pedidos = $this->pedido->getAll();
        $this->render('pedidos/admin', [
            'pedidos' => $pedidos
        ]);
    }
    
    public function actualizarEstado() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $estado = isset($_POST['estado']) ? $_POST['estado'] : '';
            
            if ($id > 0 && $estado) {
                $resultado = $this->pedido->updateEstado($id, $estado);
                echo json_encode(['success' => $resultado]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit;
        }
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