<?php
class ControllerPersonal extends Controller {
    private $staffModel;

    public function __construct() {
        AuthGuard::handle();
        RoleGuard::isAdmin(); // Solo el administrador gestiona personal
        $this->staffModel = $this->model('Personal');
    }

    public function index() {
        $data = [
            'titulo' => 'Gestión de Personal',
            'roles' => $this->staffModel->listarRoles()
        ];

        $this->view('personal/index', $data);
    }

    public function listar() {
        $searchValue = $_GET['q'] ?? $_GET['search']['value'] ?? null;
        $search = ($searchValue !== '' && $searchValue !== null) ? $searchValue : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $items = $this->staffModel->listar($limit, $offset, $search);
        $total = $this->staffModel->contarTotal();
        $totalFiltrados = $search ? $this->staffModel->contarFiltrados($search) : $total;

        return $this->jsonResponse([
            'success' => true,
            'data' => $items ?: [],
            'total' => (int)$total,
            'totalFiltrados' => (int)$totalFiltrados
        ]);
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['id'])) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'El ID de empleado es requerido'], 400);
            }
            
            $existe = $this->staffModel->obtenerPorId($input['id']);

            if ($existe) {
                $res = $this->staffModel->actualizar($input);
            } else {
                $res = $this->staffModel->crear($input);
            }

            if ($res) {
                // Lógica para gestionar el usuario del sistema
                if (isset($input['has_system_access']) && $input['has_system_access'] === true) {
                    $this->staffModel->gestionarUsuario($input['id'], [
                        'username' => $input['username'] ?? '',
                        'password' => $input['password'] ?? '',
                        'role_id'  => $input['role_id'] ?? 3 // Default a 'Empleado'
                    ]);
                } elseif ($existe) {
                    // Si se está editando y se desmarcó el acceso, eliminamos el usuario vinculado
                    $this->staffModel->eliminarUsuario($input['id']);
                }

                return $this->jsonResponse(['success' => true, 'mensaje' => 'Registro guardado correctamente']);
            } else {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Error en la base de datos'], 500);
            }
        }
    }

    public function eliminar($id = null) {
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE' && $id) {
            if ($this->staffModel->eliminar($id)) {
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Empleado eliminado']);
            } else {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'No se pudo eliminar'], 500);
            }
        }
    }
}