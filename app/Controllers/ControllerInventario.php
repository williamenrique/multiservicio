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
        $limit = isset($_GET['length']) ? (int)$_GET['length'] : null;
        $offset = isset($_GET['start']) ? (int)$_GET['start'] : null;
        
        // Detectar búsqueda compatible con DataTables y peticiones manuales vía URL
        $searchValue = $_GET['search']['value'] ?? $_GET['search'] ?? null;
        $search = ($searchValue !== '' && $searchValue !== null) ? $searchValue : null;

        // Aseguramos que los conteos siempre devuelvan enteros
        $total = (int)$this->inventarioModel->contarTotal();
        $filtered = ($search !== null) ? (int)$this->inventarioModel->contarFiltrados($search) : $total;

        if ($limit !== null && $offset !== null) {
            $items = $this->inventarioModel->listar($limit, $offset, $search);
            
            return $this->jsonResponse([
                'draw' => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $items
            ]);
        } else {
            // Carga manual sin DataTables
            $items = $this->inventarioModel->listar(null, null, $search);
            return $this->jsonResponse([
                'success' => true,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $items
            ]);
        }
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