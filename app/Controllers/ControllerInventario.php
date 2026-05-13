<?php
class ControllerInventario extends Controller {
    public function __construct() {
        AuthGuard::handle();
    }

    public function index() {
        $data = [
            'titulo' => 'Control de Inventario'
        ];

        $this->view('inventario/index', $data);
    }
}