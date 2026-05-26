<?php
class ControllerRecuperar extends Controller {
    private $staffModel;

    public function __construct() {
        AuthGuard::handle();
        RoleGuard::isAdmin(); // Solo el administrador gestiona Recuperar
        $this->staffModel = $this->model('Recuperar');
    }

    public function index() {
        $data = [
            'titulo' => 'Gestión de Recuperar'
        ];

        $this->view('recuperar/index', $data);
    }
}