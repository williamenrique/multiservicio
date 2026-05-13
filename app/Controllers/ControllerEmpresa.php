<?php
class ControllerEmpresa extends Controller {
    public function __construct() {
        AuthGuard::handle();
        RoleGuard::isAdmin();
    }

    public function index() {
        $data = [
            'titulo' => 'Configuración de la Empresa'
        ];

        $this->view('empresa/index', $data);
    }
}