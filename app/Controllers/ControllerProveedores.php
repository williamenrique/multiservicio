<?php
/**
 * Controlador de Proveedores
 */
class ControllerProveedores extends Controller {
    private $proveedorModel;
    private $inventarioModel;
    private $cajaModel;

    public function __construct() {
        AuthGuard::handle();
        RoleGuard::isAdmin(); // Solo el administrador gestiona proveedores
        $this->proveedorModel = $this->model('Proveedor');
        $this->inventarioModel = $this->model('Inventario');
        $this->cajaModel = $this->model('Caja');
    }

    public function index() {
        $empresaModel = $this->model('Empresa');
        $config = $empresaModel->obtenerConfiguracion();
        
        $data = [
            'titulo' => 'Gestión de Proveedores',
            'markup_default' => $config->markup_default ?? 30.00
        ];
        $this->view('proveedor/index', $data);
    }

    public function listar() {
        $searchValue = $_GET['q'] ?? $_GET['search']['value'] ?? null;
        $search = ($searchValue !== '' && $searchValue !== null) ? $searchValue : null;

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $items = $this->proveedorModel->listar($limit, $offset, $search);
        $total = $this->proveedorModel->contarTotal();
        $totalFiltrados = $search ? $this->proveedorModel->contarFiltrados($search) : $total;
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $items ?: [],
            'total' => $total,
            'totalFiltrados' => $totalFiltrados
        ]);
    }

    public function listarDeudas() {
        $data = $this->proveedorModel->listarDeudas();
        return $this->jsonResponse(['success' => true, 'data' => $data ?: []]);
    }

    public function listarComprasPendientes($id) {
        $data = $this->proveedorModel->obtenerComprasPendientes($id);
        return $this->jsonResponse(['success' => true, 'data' => $data ?: []]);
    }

    public function registrarPago() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($this->proveedorModel->registrarPagoCompra($data)) {
                // Registrar el egreso en caja
                $this->cajaModel->registrarMovimiento([
                    'sesion_id' => null,
                    'tipo' => 'EGRESO',
                    'monto' => $data['monto'],
                    'metodo_pago' => 'EFECTIVO',
                    'referencia_id' => $data['compra_id'],
                    'concepto' => "PAGO PROVEEDOR: Compra #" . $data['compra_id']
                ]);

                return $this->jsonResponse(['success' => true, 'mensaje' => 'Abono registrado correctamente']);
            } else {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Error al registrar el pago'], 500);
            }
        }
    }

    /**
     * Procesa el ingreso de mercancía (Compra)
     * Maneja: Creación/Actualización de producto + Registro de deuda
     */
    public function registrarCompra() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Datos inválidos'], 400);
            }

            $resultado = $this->proveedorModel->registrarCompra($data);

            if ($resultado) {
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Mercancía registrada y stock actualizado']);
            } else {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Error al procesar la operación'], 500);
            }
        }
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validación de campos requeridos usando el Helper Validator
            $v = new Validator($input);
            $v->required(['id', 'nombre', 'telefono']);

            if (!$v->success()) {
                return $this->jsonResponse([
                    'success' => false, 
                    'mensaje' => 'Faltan campos obligatorios', 
                    'errors' => $v->getErrors()
                ], 400);
            }

            $res = $this->proveedorModel->guardar($input);
            
            return $this->jsonResponse([
                'success' => $res,
                'mensaje' => $res ? 'Proveedor guardado correctamente' : 'Error al procesar la solicitud'
            ]);
        }
    }

    public function eliminar($id = null) {
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE' || $_SERVER['REQUEST_METHOD'] == 'POST') {
            $res = $this->proveedorModel->eliminar($id);
            return $this->jsonResponse(['success' => $res, 'mensaje' => $res ? 'Eliminado' : 'Error']);
        }
    }

    public function obtenerDetalleCompra($id) {
        return $this->jsonResponse($this->proveedorModel->obtenerDetalleCompra($id));
    }

    /**
     * Endpoint para obtener los datos de un proveedor específico (AJAX)
     */
    public function obtener($id) {
        return $this->jsonResponse($this->proveedorModel->obtenerPorId($id));
    }
}