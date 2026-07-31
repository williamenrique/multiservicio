<?php
// app/controllers/ControllerRepuesto.php

class ControllerRepuesto extends Controller {
    private $repuesto;
    private $uploadHelper;
    
    public function __construct() {
        $this->repuesto = new Repuesto();
        $this->uploadHelper = new UploadHelper();
    }
    
    public function index() {
        $busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
        $categoria_id = isset($_GET['categoria']) ? $_GET['categoria'] : null;
        
        if ($categoria_id) {
            $repuestos = $this->repuesto->getByCategoria($categoria_id);
        } else {
            $repuestos = $this->repuesto->getAll($busqueda);
        }
        
        $categorias = $this->repuesto->getCategorias();
        $carrito = new Carrito();
        $carritoCount = $carrito->getCount();
        
        // Cargar vista
        $this->render('repuestos/index', [
            'repuestos' => $repuestos,
            'busqueda' => $busqueda,
            'categorias' => $categorias,
            'categoria_id' => $categoria_id,
            'carritoCount' => $carritoCount
        ]);
    }
    
    public function detalle($id) {
        $repuesto = $this->repuesto->getById($id);
        if (!$repuesto) {
            http_response_code(404);
            echo "Repuesto no encontrado";
            return;
        }
        
        $this->render('repuestos/detalle', [
            'repuesto' => $repuesto
        ]);
    }
    
    public function admin() {
        // Verificar autenticación (para futura implementación)
        $repuestos = $this->repuesto->getAll();
        $this->render('repuestos/admin', [
            'repuestos' => $repuestos
        ]);
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            
            // Subir imagen
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $resultado = $this->uploadHelper->upload($_FILES['imagen']);
                if ($resultado['success']) {
                    $data['imagen'] = $resultado['filename'];
                } else {
                    $this->render('repuestos/crear', [
                        'error' => $resultado['message'],
                        'categorias' => $this->repuesto->getCategorias()
                    ]);
                    return;
                }
            }
            
            if ($this->repuesto->create($data)) {
                header('Location: /catalogo_repuestos_mvc/repuesto/admin');
                exit;
            } else {
                $this->render('repuestos/crear', [
                    'error' => 'Error al crear el repuesto',
                    'categorias' => $this->repuesto->getCategorias()
                ]);
            }
        } else {
            $this->render('repuestos/crear', [
                'categorias' => $this->repuesto->getCategorias()
            ]);
        }
    }
    
    public function editar($id) {
        $repuesto = $this->repuesto->getById($id);
        if (!$repuesto) {
            http_response_code(404);
            echo "Repuesto no encontrado";
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            
            // Subir nueva imagen
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $resultado = $this->uploadHelper->upload($_FILES['imagen']);
                if ($resultado['success']) {
                    $data['imagen'] = $resultado['filename'];
                    // Eliminar imagen anterior
                    if ($repuesto['imagen']) {
                        $this->uploadHelper->delete($repuesto['imagen']);
                    }
                } else {
                    $this->render('repuestos/editar', [
                        'error' => $resultado['message'],
                        'repuesto' => $repuesto,
                        'categorias' => $this->repuesto->getCategorias()
                    ]);
                    return;
                }
            }
            
            if ($this->repuesto->update($id, $data)) {
                header('Location: /catalogo_repuestos_mvc/repuesto/admin');
                exit;
            } else {
                $this->render('repuestos/editar', [
                    'error' => 'Error al actualizar el repuesto',
                    'repuesto' => $repuesto,
                    'categorias' => $this->repuesto->getCategorias()
                ]);
            }
        } else {
            $this->render('repuestos/editar', [
                'repuesto' => $repuesto,
                'categorias' => $this->repuesto->getCategorias()
            ]);
        }
    }
    
    public function eliminar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $repuesto = $this->repuesto->getById($id);
            if ($repuesto && $repuesto['imagen']) {
                $this->uploadHelper->delete($repuesto['imagen']);
            }
            
            if ($this->repuesto->delete($id)) {
                echo json_encode(['success' => true]);
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