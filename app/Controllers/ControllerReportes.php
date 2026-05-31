<?php
/**
 * Controlador de Reportes
 * Centraliza estadísticas, flujo de caja, nómina y devoluciones.
 */
class ControllerReportes extends Controller {
    private $reporteModel;

    public function __construct() {
        AuthGuard::handle();
        RoleGuard::isAdmin();
        $this->reporteModel = $this->model('Reportes');
    }

    public function index() {
        $this->view('reportes/index', [
            'titulo' => 'Reportes y Estadísticas'
        ]);
    }

    /**
     * Endpoint para el flujo de caja unificado
     */
    public function generar() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $search = $_GET['q'] ?? null;

        $res = $this->reporteModel->obtenerFlujoCaja($desde, $hasta, $limit, $offset, $search);
        // Agregamos la bandera de éxito para que el frontend procese los datos
        $res['success'] = true;
        return $this->jsonResponse($res);
    }

    public function detallado() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $res = $this->reporteModel->obtenerReporteDetallado($desde, $hasta);
        return $this->jsonResponse(['success' => true, 'data' => $res]);
    }

    /**
     * Retorna el listado de empleados para el selector (Evita Error 404)
     */
    public function simple_staff() {
        $res = $this->reporteModel->obtenerStaffSimple();
        return $this->jsonResponse(['success' => true, 'data' => $res]);
    }

    /**
     * Endpoint para el reporte de Cartera por Edades
     */
    public function cartera() {
        $res = $this->reporteModel->obtenerCarteraPorEdades();
        return $this->jsonResponse(['success' => true, 'data' => $res]);
    }

    /**
     * Endpoint para el análisis de rentabilidad Detallado
     */
    public function rentabilidad() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $res = $this->reporteModel->obtenerAnalisisRentabilidad($desde, $hasta);
        return $this->jsonResponse(['success' => true, 'data' => $res]);
    }

    /**
     * Endpoint para el reporte de nómina de empleados
     */
    public function nomina() {
        $staff_id = $_GET['staff_id'] ?? '0';
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $res = $this->reporteModel->obtenerNominaEmpleado($staff_id, $desde, $hasta);
        return $this->jsonResponse(['success' => true, 'data' => $res]);
    }

    /**
     * Procesa el registro de un pago o adelanto
     */
    public function registrarPagoNomina() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Lógica de cálculo basada en el Switch
            $montoBase = (float)($data['monto_base'] ?? 0);
            $factor = (float)($data['factor_calculo'] ?? 0);

            if (($data['modo_calculo'] ?? 'FIJO') === 'PORCENTAJE') {
                // Si es porcentaje, calculamos el monto final a pagar
                $data['monto'] = $montoBase * ($factor / 100);
            } else {
                // Si es monto fijo, el factor es el monto mismo
                $data['monto'] = $factor;
            }

            $data['usuario_id'] = $_SESSION['user_id'];
            $res = $this->reporteModel->registrarPagoEmpleado($data);
            return $this->jsonResponse(['success' => $res]);
        }
    }

    /**
     * Historial de devoluciones para la tabla de reportes
     */
    public function devoluciones() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $search = $_GET['q'] ?? null;

        $rows = $this->reporteModel->obtenerReporteDevoluciones($desde, $hasta, $limit, $offset, $search);
        $total = $this->reporteModel->contarDevoluciones($desde, $hasta, $search);

        return $this->jsonResponse([
            'success' => true,
            'data' => $rows,
            'total' => $total,
            'totalFiltrados' => $total
        ]);
    }
}