<?php
class ControllerReportes extends Controller {
    private $reporteModel;

    public function __construct() {
        AuthGuard::role('ADMINISTRADOR');
        $this->reporteModel = $this->model('Reportes');
    }

    public function index() {
        $this->view('reportes/index', ['titulo' => 'Reportes Financieros']);
    }

    public function generar() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $data = $this->reporteModel->obtenerFlujoCaja($desde, $hasta);
        
        // Limpiar cualquier salida previa (avisos/warnings) para asegurar JSON puro
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function detallado() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $data = $this->reporteModel->obtenerReporteDetallado($desde, $hasta);
        
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}