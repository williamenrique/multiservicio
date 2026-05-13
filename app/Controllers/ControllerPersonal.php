<?php
class ControllerPersonal extends Controller {
    public function __construct() {
        AuthGuard::handle();
        RoleGuard::isAdmin(); // Solo el administrador gestiona personal
    }

    public function index() {
        $data = [
            'titulo' => 'Gestión de Personal'
        ];

        $this->view('personal/index', $data);
    }
}