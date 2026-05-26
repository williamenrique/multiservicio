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
        header('Content-Type: application/json');
        echo json_encode($this->historialModel->listarVentas());
    }

    /**
     * Endpoint API para obtener los detalles de una venta específica.
     */
    public function detalle($id) {
        RoleGuard::hasAccess(['ADMINISTRADOR']);
        header('Content-Type: application/json');
        echo json_encode($this->historialModel->obtenerDetalleVenta($id));
    }
}