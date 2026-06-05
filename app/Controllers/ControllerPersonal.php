<?php
/**
 * Controlador de Personal
 * Maneja la gestión del staff y la vinculación de cuentas de usuario 2.0.
 */
class ControllerPersonal extends Controller {
    private $personalModel;

    public function __construct() {
        AuthGuard::handle();
        RoleGuard::isAdmin(); // Solo administradores gestionan personal
        $this->personalModel = $this->model('Personal');
    }

    public function index() {
        $data = [
            'titulo' => 'Gestión de Personal',
            'roles' => $this->personalModel->listarRoles()
        ];
        $this->view('personal/index', $data);
    }

    /**
     * Endpoint para el listado paginado del staff (AJAX)
     */
    public function listar() {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $search = $_GET['search'] ?? null;

        $data = $this->personalModel->listar($limit, $offset, $search);
        $total = $this->personalModel->contarTotal();
        $filtrados = $search ? $this->personalModel->contarFiltrados($search) : $total;

        return $this->jsonResponse([
            'success' => true,
            'data' => $data,
            'total' => $total,
            'totalFiltrados' => $filtrados
        ]);
    }

    /**
     * Crea un nuevo registro de staff generando el ID STAFF-XXX automático
     */
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            $v = new Validator($input);
            $v->required(['cedula', 'nombre', 'cargo']);

            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'error' => implode(' ', $v->getErrors())], 400);
            }

            if ($this->personalModel->verificarCedulaUnica($input['cedula'])) {
                return $this->jsonResponse(['success' => false, 'error' => 'La cédula ya existe en el sistema.'], 400);
            }

            // Lógica de ID correlativo STAFF-XXX basado en el último registro
            $ultimoCorrelativo = $this->personalModel->obtenerUltimoCorrelativo();
            $input['id'] = 'STAFF-' . str_pad($ultimoCorrelativo + 1, 3, '0', STR_PAD_LEFT);

            if ($this->personalModel->crear($input)) {
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Empleado registrado con ID: ' . $input['id']]);
            }

            return $this->jsonResponse(['success' => false, 'error' => 'Error interno al crear personal.'], 500);
        }
    }

    public function editar($id) {
        $staff = $this->personalModel->obtenerPorId($id);
        if (!$staff) throw new AppException("Empleado no encontrado", 404);
        return $this->jsonResponse(['success' => true, 'data' => $staff]);
    }

    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($this->personalModel->actualizar($input)) {
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Datos actualizados.']);
            }
            return $this->jsonResponse(['success' => false, 'error' => 'No se realizaron cambios.'], 500);
        }
    }

    /**
     * Vincula o actualiza el usuario de acceso para un miembro del staff
     */
    public function guardarUsuario() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validación preventiva de campos
            if (empty($input['staff_id']) || empty($input['username'])) {
                return $this->jsonResponse(['success' => false, 'error' => 'Faltan datos obligatorios para la cuenta.'], 400);
            }

            if ($this->personalModel->verificarUsernameUnico($input['username'], $input['staff_id'])) {
                return $this->jsonResponse(['success' => false, 'error' => 'El nombre de usuario ya está en uso.'], 400);
            }

            if (!empty($input['password'])) {
                $input['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            }

            if ($this->personalModel->gestionarUsuario($input['staff_id'], $input)) {
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Cuenta de acceso actualizada.']);
            }
            return $this->jsonResponse(['success' => false, 'error' => 'Error al procesar usuario.'], 500);
        }
    }

    public function eliminar($id) {
        $this->personalModel->eliminarUsuario($id);
        return $this->jsonResponse(['success' => $this->personalModel->eliminar($id)]);
    }
}