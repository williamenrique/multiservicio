<?php
class ControllerProveedores extends Controller {
    private $proveedorModel;

    public function __construct() {
        AuthGuard::handle();
        $this->proveedorModel = $this->model('Proveedor');
    }

    public function index() {
        $data = ['titulo' => 'Gestión de Proveedores'];
        $this->view('proveedor/index', $data);
    }

    public function listar() {
        header('Content-Type: application/json');
        echo json_encode($this->proveedorModel->listar());
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $existe = $this->proveedorModel->obtenerPorId($input['id']);

            if ($existe) {
                $res = $this->proveedorModel->actualizar($input);
            } else {
                $res = $this->proveedorModel->crear($input);
            }

            if ($res) {
                echo json_encode(['success' => true, 'mensaje' => 'Proveedor guardado']);
            } else {
                echo json_encode(['success' => false, 'mensaje' => 'Error al procesar']);
            }
        }
    }

    public function eliminar($id = null) {
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE' && $id) {
            if ($this->proveedorModel->eliminar($id)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
        }
    }
}