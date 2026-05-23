<?php
class ControllerInventario extends Controller {
    private $inventarioModel;

    public function __construct() {
        AuthGuard::handle();
        $this->inventarioModel = $this->model('Inventario');
    }

    public function index() {
        RoleGuard::isAdmin();
        $data = [
            'titulo' => 'Control de Inventario',
            'user_role' => $_SESSION['user_role']
        ];

        $this->view('inventario/index', $data);
    }

    public function listar() {
        // Detectar si la petición viene de DataTables (Server-side)
        if (isset($_GET['draw'])) {
            $start = $_GET['start'] ?? 0;
            $length = $_GET['length'] ?? 10;
            $search = $_GET['search']['value'] ?? '';
            
            // Mapeo corregido según el orden real de las columnas en el JS
            $columns = [0 => 'id', 1 => 'nombre', 2 => 'categoria', 3 => 'stock', 4 => 'precio', 5 => 'stock'];
            $orderColIndex = intval($_GET['order'][0]['column'] ?? 1);
            $orderCol = $columns[$orderColIndex] ?? 'nombre';
            $orderDir = $_GET['order'][0]['dir'] ?? 'asc';

            $totalRecords = $this->inventarioModel->contarTodos();
            $filteredRecords = !empty($search) ? $this->inventarioModel->contarFiltrados($search) : $totalRecords;
            $data = $this->inventarioModel->obtenerPaginado($start, $length, $search, $orderCol, $orderDir) ?: [];

            return $this->jsonResponse([
                "draw" => intval($_GET['draw']),
                "recordsTotal" => intval($totalRecords),
                "recordsFiltered" => intval($filteredRecords),
                "data" => $data
            ]);
        }

        return $this->jsonResponse($this->inventarioModel->listar());
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

        $this->view('inventario/kardex', [
            'titulo' => 'Kardex de ' . $producto->nombre,
            'producto' => $producto,
            'kardexMovimientos' => $kardexMovimientos
        ]);
    }

    public function guardar() {
        RoleGuard::isAdmin();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Ahora recibimos datos vía $_POST por usar FormData
            $data = $_POST;
            $data['imagen'] = isset($data['imagen']) ? trim($data['imagen']) : null;
            
            if (empty($data['nombre'])) {
                echo json_encode(['success' => false, 'mensaje' => 'El nombre es obligatorio']);
                return;
            }

            // Procesar Imagen Local
            if (!empty($_FILES['imagen_archivo']['name'])) {
                $archivo = $_FILES['imagen_archivo'];
                $nombreLimpio = str_replace(' ', '_', strtolower($data['nombre']));
                $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
                $nombreFinal = $nombreLimpio . "_" . time() . "." . $extension;
                
                $subfolder = 'inventario';
                $uploadPath = APPROOT . '/../public/uploads/' . $subfolder . '/';
                
                // Crear subcarpeta específica si no existe
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                // Validar tamaño (máx 2MB)
                if ($archivo['size'] > 2097152) {
                    echo json_encode(['success' => false, 'mensaje' => 'La imagen es muy pesada (Máx 2MB)']);
                    return;
                }

                if (move_uploaded_file($archivo['tmp_name'], $uploadPath . $nombreFinal)) {
                    $data['imagen'] = 'uploads/' . $subfolder . '/' . $nombreFinal;
                }
            }

            try {
                if (!empty($data['id'])) {
                    $this->inventarioModel->actualizar($data);
                } else {
                    $this->inventarioModel->crear($data);
                }
                echo json_encode(['success' => true, 'mensaje' => 'Producto guardado']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
            }
        }
    }

    public function eliminar($id = null) {
        RoleGuard::isAdmin();
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE' && $id) {
            if ($this->inventarioModel->eliminar($id)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
        }
    }
}