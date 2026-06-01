<?php
class ControllerInventario extends Controller {
    private $inventarioModel;

    public function __construct() {
        AuthGuard::handle();
        $this->inventarioModel = $this->model('Inventario');
    }

    public function index() {
        RoleGuard::isAdmin();
        $total = $this->inventarioModel->contarTotal();
        $data = [
            'titulo' => 'Control de Inventario',
            'user_role' => $_SESSION['user_role'],
            'total_items' => $total
        ];

        $this->view('inventario/index', $data);
    }

    public function listar() {
        // Detectar búsqueda manual o de DataTables (por compatibilidad)
        $searchValue = $_GET['search']['value'] ?? $_GET['search'] ?? $_GET['q'] ?? null;
        $search = ($searchValue !== '' && $searchValue !== null) ? $searchValue : null;

        // Soporte para paginación manual
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $items = $this->inventarioModel->listar($limit, $offset, $search);
        $total = $this->inventarioModel->contarTotal();
        $totalFiltrados = $search ? $this->inventarioModel->contarFiltrados($search) : $total;

        return $this->jsonResponse([
            'success' => true,
            'data' => $items,
            'total' => $total,
            'totalFiltrados' => $totalFiltrados
        ]);
    }

    /**
     * Muestra el historial de movimientos (Kardex) para un producto específico.
     */
    public function kardex($producto_id = null) {
        if (!$producto_id) {
            redirect('inventario');
        }

        RoleGuard::isAdmin();
        $producto = $this->inventarioModel->obtenerPorId($producto_id);
        if (!$producto) {
            redirect('inventario?error=producto_no_encontrado');
        }

        // Verificar si existe la imagen, de lo contrario usar la de por defecto
        if (empty($producto->imagen) || !file_exists(APPROOT . '/../public/' . $producto->imagen)) {
            $producto->imagen = 'img/default.png';
        }

        $kardexMovimientos = $this->inventarioModel->obtenerKardexPorProducto($producto_id);
        $costHistory = $this->inventarioModel->getCostHistory($producto_id);

        $this->view('inventario/kardex', [
            'titulo' => 'Kardex de ' . $producto->nombre,
            'producto' => $producto,
            'kardexMovimientos' => $kardexMovimientos,
            'costHistory' => $costHistory
        ]);
    }

    public function guardar() {
        RoleGuard::isAdmin();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Ahora recibimos datos vía $_POST por usar FormData
                $data = $_POST;
                $data['imagen'] = isset($data['imagen']) ? trim($data['imagen']) : null;
                
                if (empty($data['nombre'])) {
                    return $this->jsonResponse(['success' => false, 'mensaje' => 'El nombre es obligatorio'], 400);
                }

                // Procesar Imagen Local
                if (!empty($_FILES['imagen_archivo']['name'])) {
                    $archivo = $_FILES['imagen_archivo'];
                    $nombreLimpio = str_replace(' ', '_', strtolower($data['nombre']));
                    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
                    $nombreFinal = $nombreLimpio . "_" . time() . "." . $extension;
                    
                    $subfolder = 'inventario';
                    $uploadPath = APPROOT . '/../public/uploads/' . $subfolder . '/';

                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }

                    if (move_uploaded_file($archivo['tmp_name'], $uploadPath . $nombreFinal)) {
                        $data['imagen'] = 'uploads/' . $subfolder . '/' . $nombreFinal;
                    }
                }

                if (!empty($data['id'])) {
                    $res = $this->inventarioModel->actualizar($data);
                    $mensaje = "Producto actualizado";
                } else {
                    $res = $this->inventarioModel->crear($data);
                    $mensaje = "Producto registrado";
                }

                return $this->jsonResponse(['success' => $res, 'mensaje' => $res ? $mensaje : 'Error al procesar']);
            } catch (Exception $e) {
                return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
        }
    }

    public function eliminar($id) {
        RoleGuard::isAdmin();
        $res = $this->inventarioModel->eliminar($id);
        return $this->jsonResponse(['success' => $res, 'mensaje' => $res ? 'Producto eliminado' : 'Error al eliminar']);
    }
}