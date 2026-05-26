<?php
class ControllerCaja extends Controller {
    private $cajaModel;

    public function __construct() {
        AuthGuard::handle();
        $this->cajaModel = $this->model('Caja');
    }

    public function index() {
        RoleGuard::hasAccess(['ADMINISTRADOR']);
        $this->view('caja/index', ['titulo' => 'Cierre de Caja y Arqueo']);
    }

    public function obtenerEstado() {
        $data = $this->cajaModel->obtenerSaldoEsperado();
        $this->jsonResponse($data);
    }

    public function cerrar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $res = $this->cajaModel->registrarCierre($input);
            if ($res) {
                $this->jsonResponse(['success' => true, 'mensaje' => 'Cierre de caja exitoso']);
            } else {
                $this->jsonResponse(['success' => false, 'error' => 'No se pudo registrar el cierre'], 500);
            }
        }
    }
}