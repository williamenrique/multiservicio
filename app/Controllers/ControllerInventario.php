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

        // Verificar si existe la imagen, de lo contrario enviamos null para que la vista maneje el icono 'package'
        if (empty($producto->imagen) || !file_exists(APPROOT . '/../public/' . $producto->imagen)) {
            $producto->imagen = null;
        }

        $costHistory = $this->inventarioModel->getCostHistory($producto_id);

        $this->view('inventario/kardex', [
            'titulo' => 'Kardex de ' . $producto->nombre,
            'producto' => $producto,
            'costHistory' => $costHistory
        ]);
    }

    /**
     * Endpoint AJAX para alimentar la tabla de movimientos de Kardex
     */
    public function kardexData($producto_id) {
        RoleGuard::isAdmin();
        $limit = $_GET['limit'] ?? 10;
        $offset = $_GET['offset'] ?? 0;
        $search = $_GET['q'] ?? null;

        $res = $this->inventarioModel->obtenerKardexPaginado($producto_id, (int)$limit, (int)$offset, $search);
        return $this->jsonResponse([
            'success' => true,
            'data' => $res['data'],
            'total' => $res['total']
        ]);
    }

    /**
     * Genera un PDF detallado (tipo lista) de un único movimiento de Kardex
     */
    public function imprimirMovimiento($id) {
        RoleGuard::isAdmin();
        $db = new Database();
        $db->query("SELECT k.*, i.nombre as producto_nombre, i.categoria, u.username, s.nombre as usuario_nombre
                          FROM table_kardex k
                          JOIN table_inventario i ON k.producto_id = i.id
                          LEFT JOIN table_usuarios u ON k.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE k.id = :id");
        $db->bind(':id', $id);
        $movimiento = $db->single();

        if (!$movimiento) die("Movimiento no encontrado.");

        $pdfService = new PdfService();
        $pdfService->generarDocumento('kardex_individual', [
            'titulo_documento' => 'Detalle de Movimiento de Inventario',
            'mov' => $movimiento,
            'documento_id' => $movimiento->id
        ], 'Movimiento_Kardex_' . $id . '.pdf');
        exit;
    }

    /**
     * Genera un PDF con todo el historial de Kardex del producto en formato de lista legible
     */
    public function imprimirKardexCompleto($producto_id) {
        RoleGuard::isAdmin();
        $producto = $this->inventarioModel->obtenerPorId($producto_id);
        if (!$producto) die("Producto no encontrado.");

        $db = new Database();
        $db->query("SELECT k.*, s.nombre as usuario_nombre 
                          FROM table_kardex k
                          LEFT JOIN table_usuarios u ON k.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE k.producto_id = :pid ORDER BY k.fecha DESC");
        $db->bind(':pid', $producto_id);
        $movimientos = $db->resultSet();

        $pdfService = new PdfService();
    }
} 