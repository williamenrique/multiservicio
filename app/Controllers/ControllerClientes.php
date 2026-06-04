<?php
/**
 * Controlador de Clientes
 * Maneja la lógica de visualización y API para la gestión de clientes.
 */
class ControllerClientes extends Controller {
    private $clienteModel;

    public function __construct() {
        AuthGuard::handle();
        $this->clienteModel = $this->model('Cliente'); // Cargar modelo después de la autenticación
    }

    /**
     * Carga la vista principal de gestión de clientes
     */
    public function index() {
        // Eliminamos el acento para que coincida exactamente con el valor en la DB
        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO']); 
        $data = [
            'titulo' => 'Gestión de Clientes',
            'user_role' => $_SESSION['user_role'] // Pasar el rol del usuario a la vista para ajustes de UI
        ];

        $this->view('cliente/index', $data);
    }

    /**
     * Endpoint API para obtener la lista de clientes (AJAX)
     */
    public function listar() {
        $searchValue = $_GET['q'] ?? $_GET['search']['value'] ?? null;
        $search = ($searchValue !== '' && $searchValue !== null) ? $searchValue : null;

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $items = $this->clienteModel->listar($limit, $offset, $search);
        $total = $this->clienteModel->contarTotal();
        $totalFiltrados = $search ? $this->clienteModel->contarFiltrados($search) : $total;
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $items ?: [],
            'total' => $total,
            'totalFiltrados' => $totalFiltrados
        ]);
    }

    /**
     * Endpoint API para obtener un cliente por su ID/Cédula (Cédula)
     */
    public function obtener($id) {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO']);
        $cliente = $this->clienteModel->obtenerPorId($id);
        return $this->jsonResponse($cliente);
    }

    /**
     * Endpoint API para listar vehículos de un cliente (AJAX)
     */
    public function vehiculos($id) {
        $data = $this->clienteModel->obtenerVehiculos($id);
        return $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    /**
     * Guarda o actualiza un cliente
     */
    public function guardar() {
        // Permitir a Administradores y Mecánicos crear clientes (necesario para facturación rápida)
        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO']); 
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['id']) || empty($input['nombre'])) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Identificación y nombre son requeridos'], 400);
            }

            $existe = $this->clienteModel->obtenerPorId($input['id']);
            
            if ($existe) {
                $res = $this->clienteModel->actualizar($input);
            } else {
                $res = $this->clienteModel->crear($input);
            }

            return $this->jsonResponse([
                'success' => $res, 
                'mensaje' => $res ? 'Cliente guardado correctamente' : 'Error al procesar la solicitud'
            ]);
        }
    }

    public function eliminar($id) {
        RoleGuard::isAdmin();
        $res = $this->clienteModel->eliminar($id);
        return $this->jsonResponse(['success' => $res, 'mensaje' => $res ? 'Cliente eliminado' : 'Error al eliminar']);
    }
}