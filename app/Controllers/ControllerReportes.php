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
        $search = $_GET['q'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $data = $this->reporteModel->obtenerFlujoCaja($desde, $hasta, $limit, $offset, $search);
        $data['success'] = true;
        
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

    /**
     * Endpoint para el reporte de Cartera por Edades
     */
    public function cartera() {
        $data = $this->reporteModel->obtenerCarteraPorEdades();
        return $this->jsonResponse(['success' => true, 'data' => $data ?: []]);
    }

    /**
     * Endpoint para el análisis de rentabilidad Detallado
     */
    public function rentabilidad() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $data = $this->reporteModel->obtenerAnalisisRentabilidad($desde, $hasta);
        return $this->jsonResponse(['success' => true, 'data' => $data ?: []]);
    }

    /**
     * Endpoint para el reporte de nómina de empleados
     */
    public function nomina() {
        $staff_id = $_GET['staff_id'] ?? 0;
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $data = $this->reporteModel->obtenerNominaEmpleado($staff_id, $desde, $hasta);
        return $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    /**
     * Historial de devoluciones para la tabla de reportes
     */
    public function devoluciones() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        
        $rows = $this->reporteModel->obtenerReporteDevoluciones($desde, $hasta);
        return $this->jsonResponse([
            'success' => true,
            'data' => $rows,
            'total' => count($rows)
        ]);
    }
}