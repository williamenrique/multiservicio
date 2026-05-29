<?php
/**
 * Controlador para el Historial de Ventas
 * Permite a los administradores consultar y ver detalles de ventas completadas.
 */
class ControllerHistorial extends Controller {
    private $historialModel;

    public function __construct() {
        AuthGuard::handle(); // Asegura que el usuario esté logueado
        $this->historialModel = $this->model('Historial');
    }

    /**
     * Muestra la vista principal del historial de ventas.
     */
    public function index() {
        RoleGuard::hasAccess(['ADMINISTRADOR']); // Solo administradores tienen acceso
        $data = ['titulo' => 'Historial de Ventas'];
        // La ruta apunta a app/Views/historial/index.php
        $this->view('historial/index', $data);
    }

    /**
     * Endpoint API para listar todas las ventas completadas.
     */
    public function listar() {
        RoleGuard::hasAccess(['ADMINISTRADOR']);
        $search = $_GET['q'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $items = $this->historialModel->listarVentas($limit, $offset, $search);
        $total = $this->historialModel->contarVentas();
        $totalFiltrados = $search ? $this->historialModel->contarVentas($search) : $total;

        return $this->jsonResponse([
            'success' => true,
            'data' => $items ?: [],
            'total' => $total,
            'totalFiltrados' => $totalFiltrados
        ]);
    }

    /**
     * Endpoint API para obtener los detalles de una venta específica.
     */
    public function detalle($id) {
        RoleGuard::hasAccess(['ADMINISTRADOR']);
        return $this->jsonResponse([
            'success' => true,
            'data' => $this->historialModel->obtenerDetalleVenta($id)
        ]);
    }
}