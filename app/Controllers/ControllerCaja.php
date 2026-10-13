<?php
class ControllerCaja extends Controller {
    private $cajaModel;

    public function __construct() {
        AuthGuard::handle();
        $this->cajaModel = $this->model('Caja');
    }

    public function estado() {
        // Retornamos siempre como abierta para no romper componentes de la UI que dependan de este endpoint
        $this->jsonResponse([
            'success' => true,
            'abierta' => true,
            'sesion' => null
        ]);
    }
}