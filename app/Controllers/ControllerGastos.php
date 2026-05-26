<?php
class ControllerGastos extends Controller {
    private $gastoModel;

    public function __construct() {
        AuthGuard::handle();
        $this->gastoModel = $this->model('Gasto');
    }

    public function index() {
        $this->view('gastos/index', ['titulo' => 'Gastos del Taller']);
    }

    public function listar() {
        $this->jsonResponse($this->gastoModel->listar());
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($this->gastoModel->crear($input)) {
                $this->jsonResponse(['success' => true]);
            } else {
                $this->jsonResponse(['success' => false, 'error' => 'No se pudo guardar el gasto'], 400);
            }
        }
    }

    public function eliminar($id) {
        RoleGuard::isAdmin();
        if ($this->gastoModel->eliminar($id)) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['success' => false], 400);
        }
    }
}