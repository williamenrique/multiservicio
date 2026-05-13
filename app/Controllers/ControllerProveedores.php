<?php
class ControllerProveedores extends Controller {
    public function __construct() {
        AuthGuard::handle();
    }

    public function index() {
        $data = [
            'titulo' => 'Gestión de Proveedores'
        ];

        $this->view('proveedores/index', $data);
    }
}