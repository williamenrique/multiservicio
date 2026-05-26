<?php
class ControllerPlantilla extends Controller {
    private $staffModel;

    public function __construct() {
        AuthGuard::handle();
        RoleGuard::isAdmin(); // Solo el administrador gestiona Plantilla
        $this->staffModel = $this->model('Plantilla');
    }

    public function index() {
        $data = [
            'titulo' => 'Gestión de Plantilla'
        ];

        $this->view('plantilla/index', $data);
    }
}