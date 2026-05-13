<?php
class ControllerGastos extends Controller {
    public function __construct() {
        AuthGuard::handle();
    }

    public function index() {
        $data = [
            'titulo' => 'Gastos del Taller'
        ];

        $this->view('gastos/index', $data);
    }
}