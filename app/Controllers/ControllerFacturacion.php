<?php
class ControllerFacturacion extends Controller {
    public function __construct() {
        AuthGuard::handle();
    }

    public function index() {
        $data = [
            'titulo' => 'Nueva Facturación'
        ];

        $this->view('facturacion/index', $data);
    }
}