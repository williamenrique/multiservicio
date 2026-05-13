<?php
class ControllerHistorial extends Controller {
    public function __construct() {
        AuthGuard::handle();
    }

    public function index() {
        $data = [
            'titulo' => 'Historial de Ventas'
        ];

        $this->view('historial/index', $data);
    }
}