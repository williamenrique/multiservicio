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
        header('Content-Type: application/json');
        echo json_encode($this->staffModel->listar());
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['id'])) {
                echo json_encode(['success' => false, 'mensaje' => 'El ID de empleado es requerido']);
                return;
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

                echo json_encode(['success' => true, 'mensaje' => 'Registro guardado correctamente']);
            } else {
                echo json_encode(['success' => false, 'mensaje' => 'Error en la base de datos']);
            }
        }
    }

    public function eliminar($id = null) {
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE' && $id) {
            if ($this->staffModel->eliminar($id)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
        }
    }
}