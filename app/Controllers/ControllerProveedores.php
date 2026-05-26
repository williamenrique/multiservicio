<?php
/**
 * Controlador de Proveedores
 */
class ControllerProveedores extends Controller {
    private $proveedorModel;
    private $inventarioModel;

    public function __construct() {
        AuthGuard::handle();
        $this->proveedorModel = $this->model('Proveedor');
        $this->inventarioModel = $this->model('Inventario');
    }

    public function index() {
        RoleGuard::hasAccess(['ADMINISTRADOR']);
        $empresaModel = $this->model('Empresa');
        $config = $empresaModel->obtenerConfiguracion();
        
        $data = [
            'titulo' => 'Gestión de Proveedores',
            'markup_default' => $config->markup_default ?? 30.00
        ];
        $this->view('proveedor/index', $data);
    }

    public function listar() {
        header('Content-Type: application/json');
        
        $limit = isset($_GET['length']) ? (int)$_GET['length'] : null;
        $offset = isset($_GET['start']) ? (int)$_GET['start'] : null;
        $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : null;

        if ($limit !== null && $offset !== null) {
            $items = $this->proveedorModel->listar($limit, $offset, $search);
            $total = $this->proveedorModel->contarTotal();
            $filtered = $search ? $this->proveedorModel->contarFiltrados($search) : $total;
            
            echo json_encode([
                'draw' => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $items
            ]);
        } else {
            echo json_encode($this->proveedorModel->listar());
        }
    }

    public function listarDeudas() {
        header('Content-Type: application/json');
        echo json_encode($this->proveedorModel->listarDeudas());
    }

    public function listarComprasPendientes($id) {
        header('Content-Type: application/json');
        echo json_encode($this->proveedorModel->obtenerComprasPendientes($id));
    }

    public function registrarPago() {
        RoleGuard::isAdmin();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($this->proveedorModel->registrarPagoCompra($data)) {
                echo json_encode(['success' => true, 'mensaje' => 'Abono registrado correctamente']);
            } else {
                echo json_encode(['success' => false, 'mensaje' => 'Error al registrar el pago']);
            }
        }
    }

    /**
     * Procesa el ingreso de mercancía (Compra)
     * Maneja: Creación/Actualización de producto + Registro de deuda
     */
    public function registrarCompra() {
        RoleGuard::isAdmin();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
                return;
            }

            $resultado = $this->proveedorModel->registrarCompra($data);

            if ($resultado) {
                echo json_encode(['success' => true, 'mensaje' => 'Mercancía registrada y stock actualizado']);
            } else {
                echo json_encode(['success' => false, 'mensaje' => 'Error al procesar la operación']);
            }
        }
    }

    public function guardar() {
        RoleGuard::isAdmin();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $res = $this->proveedorModel->guardar($data);
            echo json_encode(['success' => $res]);
        }
    }

    public function eliminar($id = null) {
        RoleGuard::isAdmin();
        $res = $this->proveedorModel->eliminar($id);
        echo json_encode(['success' => $res]);
    }

    public function obtenerDetalleCompra($id) {
        header('Content-Type: application/json');
        echo json_encode($this->proveedorModel->obtenerDetalleCompra($id));
    }

    /**
     * Endpoint para obtener los datos de un proveedor específico (AJAX)
     */
    public function obtener($id) {
        RoleGuard::isAdmin();
        header('Content-Type: application/json');
        echo json_encode($this->proveedorModel->obtenerPorId($id));
    }
}