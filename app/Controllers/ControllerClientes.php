<?php
class ControllerClientes extends Controller {
    public function __construct() {
        AuthGuard::handle();
    }

    public function index() {
        $data = [
            'titulo' => 'Gestión de Clientes'
        ];

        $this->view('clientes/index', $data);
    }
}