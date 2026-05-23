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

    /**
     * Obtiene los totales financieros para los cuadros del resumen (Ingresos, Egresos, etc.)
     */
    public function obtenerTotales() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $totales = $this->reporteModel->obtenerResumenTotales($desde, $hasta);
        $this->jsonResponse(['totales' => $totales]);
    }

    /**
     * Endpoint para DataTables Server-Side de movimientos de flujo de caja
     */
    public function listarMovimientos() {
        // Parámetros que envía DataTables automáticamente
        $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
        $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
        $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

        // Filtros de fecha específicos del reporte
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $resultado = $this->reporteModel->listarMovimientosServerSide($start, $length, $search, $desde, $hasta);

        $this->jsonResponse([
            "draw" => $draw,
            "recordsTotal" => $resultado['total'],
            "recordsFiltered" => $resultado['filtrados'],
            "data" => $resultado['data']
        ]);
    }

    public function detallado() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $data = $this->reporteModel->obtenerReporteDetallado($desde, $hasta);
        $this->jsonResponse($data);
    }
}