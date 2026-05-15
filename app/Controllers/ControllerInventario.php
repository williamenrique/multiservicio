<?php
class ControllerInventario extends Controller {
    private $inventarioModel;

    public function __construct() {
        AuthGuard::handle();
        $this->inventarioModel = $this->model('Inventario');
    }

    public function index() {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO']);
        $data = [
            'titulo' => 'Control de Inventario',
            'user_role' => $_SESSION['user_role']
        ];

        $this->view('inventario/index', $data);
    }

    public function listar() {
        header('Content-Type: application/json');
        echo json_encode($this->inventarioModel->listar());
    }

    public function guardar() {
        RoleGuard::isAdmin();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['nombre'])) {
                echo json_encode(['success' => false, 'mensaje' => 'El nombre es obligatorio']);
                return;
            }

            if (!empty($input['id'])) {
                $res = $this->inventarioModel->actualizar($input);
            } else {
                $res = $this->inventarioModel->crear($input);
            }

            if ($res) {
                echo json_encode(['success' => true, 'mensaje' => 'Producto guardado']);
            } else {
                echo json_encode(['success' => false, 'mensaje' => 'Error en la base de datos']);
            }
        }
    }

    public function eliminar($id = null) {
        RoleGuard::isAdmin();
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE' && $id) {
            if ($this->inventarioModel->eliminar($id)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
        }
    }
}