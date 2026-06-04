<?php
class ControllerRecuperar extends Controller {
    private $staffModel;

    public function __construct() {
        AuthGuard::handle();
        RoleGuard::isAdmin(); // Solo el administrador gestiona Recuperar
        $this->staffModel = $this->model('Usuario');
    }

    public function index() {
        $data = [
            'titulo' => 'Solicitudes de Acceso'
        ];

        $this->view('recuperar/index', $data);
    }
}