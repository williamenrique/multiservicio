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
        // Ajustado para esquema 2.0: se elimina markup_default de la configuración de empresa
        
        $data = [
            'titulo' => 'Gestión de Proveedores'
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
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                $this->proveedorModel->registrarPagoCompra($data);
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Abono registrado correctamente']);
            } catch (Exception $e) {
                return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
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

    /**
     * Endpoint para validar si un ID/NIT ya existe vía AJAX (proveedores.js)
     */
    public function verificarId() {
        $idValue = $_GET['value'] ?? '';
        $excludeId = $_GET['id'] ?? null;

        $exists = $this->proveedorModel->existeId($idValue, $excludeId);

        // Retornamos JSON para que el validador de proveedores.js lo procese
        return $this->jsonResponse(['exists' => $exists]);
    }

    /**
     * Endpoint para validar si un correo ya existe vía AJAX (proveedores.js)
     */
    public function verificarEmail() {
        // Los datos vienen por GET según el rastro del error
        $email = $_GET['value'] ?? '';
        $id = $_GET['id'] ?? null;

        $exists = $this->proveedorModel->existeEmail($email, $id);

        // Retornamos JSON puro para que el JS no falle al parsear
        return $this->jsonResponse(['exists' => $exists]);
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Permitir tanto JSON como FormData (POST tradicional) para compatibilidad con el JS
            $json = json_decode(file_get_contents('php://input'), true);
            $input = $json ?? $_POST;

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

            try {
                $res = $this->proveedorModel->guardar($input);
                return $this->jsonResponse([
                    'success' => $res,
                    'mensaje' => $res ? 'Proveedor guardado correctamente' : 'Error al procesar la solicitud'
                ]);
            } catch (Exception $e) {
                // Capturar errores de integridad (ej: NIT duplicado)
                $mensaje = (strpos($e->getMessage(), 'Duplicate entry') !== false) 
                    ? 'El NIT/ID ingresado ya existe en el sistema' 
                    : 'Error al guardar: ' . $e->getMessage();
                return $this->jsonResponse(['success' => false, 'mensaje' => $mensaje], 400);
            }
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