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
        $limit = isset($_GET['length']) ? (int)$_GET['length'] : null;
        $offset = isset($_GET['start']) ? (int)$_GET['start'] : null;
        $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : null;

        if ($limit !== null && $offset !== null) {
            $items = $this->clienteModel->listar($limit, $offset, $search);
            $total = $this->clienteModel->contarTotal();
            $filtered = $search ? $this->clienteModel->contarFiltrados($search) : $total;
            
            return $this->jsonResponse([
                'draw' => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $items
            ]);
        } else {
            return $this->jsonResponse($this->clienteModel->listar());
        }
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