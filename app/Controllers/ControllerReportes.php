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
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $res = $this->reporteModel->obtenerCarteraPorEdades($desde, $hasta);
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
     * Retorna el historial de pagos de nómina realizados.
     */
    public function historialPagosNomina() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $res = $this->reporteModel->obtenerHistorialPagosNomina($desde, $hasta);
        return $this->jsonResponse(['success' => true, 'data' => $res]);
    }

    /**
     * Genera o sirve el comprobante de pago de nómina en PDF (Uniforme con Facturación)
     */
    public function imprimirRecibo($id = null) {
        RoleGuard::isAdmin();
        if (!$id) die("ID de recibo no proporcionado.");

        // 1. Si el parámetro es un nombre de archivo (.pdf), lo servimos directamente
        if (strpos($id, '.pdf') !== false) {
            $filePath = APPROOT . '/../public/temp_pdfs/' . $id;
            if (file_exists($filePath)) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $id . '"');
                readfile($filePath);
                exit;
            }
        }

        // 2. Si es un ID numérico, generamos el PDF en tiempo real usando el template uniformado
        $pago = $this->reporteModel->obtenerDetallePago($id);
        if (!$pago) die("El recibo de pago #$id no existe o el documento no se encontró.");

        $empresa = $this->model('Empresa')->obtenerConfiguracion();
        $pdfService = new PdfService();
        $pdfService->generarDocumento('recibo_pago', [
            'titulo_documento' => 'Comprobante de Pago',
            'empresa' => $empresa,
            'pago' => $pago,
            'documento_id' => $pago->id
        ], 'Recibo_Nomina_' . $id . '.pdf'); // Stream directo al navegador
        exit;
    }

    /**
     * Genera el reporte PDF de la Auditoría de Trabajos (Listado completo)
     */
    public function imprimirAuditoria() {
        RoleGuard::isAdmin();
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $search = $_GET['q'] ?? null;

        $res = $this->reporteModel->obtenerReporteDetallado($desde, $hasta);
        $ventas = $res['ventas'] ?? [];
        
        if ($search && !empty($search)) {
            $s = strtolower($search);
            $ventas = array_filter($ventas, function($v) use ($s) {
                return strpos(strtolower($v->placa ?? ''), $s) !== false ||
                       strpos(strtolower($v->modelo_vehiculo ?? ''), $s) !== false ||
                       strpos(strtolower($v->cliente_nombre ?? ''), $s) !== false ||
                       strpos(strtolower($v->id ?? ''), $s) !== false;
            });
        }

        $pdfService = new PdfService();
        $pdfService->generarDocumento('reporte_auditoria', [
            'titulo_documento' => 'Auditoría de Trabajos Realizados',
            'ventas' => array_values($ventas),
            'desde' => $desde,
            'hasta' => $hasta,
            'documento_id' => 'REPORTE'
        ], 'Reporte_Auditoria_' . date('Ymd_His') . '.pdf');
        exit;
    }

    /**
     * Genera el reporte PDF de Gastos (Listado filtrado)
     */
    public function imprimirGastos() {
        RoleGuard::isAdmin();
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $search = $_GET['q'] ?? null;

        $res = $this->reporteModel->obtenerFlujoCaja($desde, $hasta, null, null, $search);
        $movimientos = $res['data'] ?? [];
        
        // Filtrar solo los egresos (Gastos y Compras)
        $gastos = array_filter($movimientos, function($m) {
            return $m->tipo === 'GASTO' || $m->tipo === 'COMPRA';
        });

        $pdfService = new PdfService();
        $pdfService->generarDocumento('reporte_gastos', [
            'titulo_documento' => 'Reporte Detallado de Egresos',
            'gastos' => array_values($gastos),
            'desde' => $desde,
            'hasta' => $hasta,
            'totales' => $res['totales'] ?? []
        ], 'Reporte_Gastos_' . date('Ymd_His') . '.pdf');
        exit;
    }

    /**
     * Endpoint API para obtener el detalle de un pago de nómina para el modal de historial
     */
    public function detallePagoNomina($id) {
        RoleGuard::isAdmin();
        $pago = $this->reporteModel->obtenerDetallePago($id);
        return $this->jsonResponse([
            'success' => $pago ? true : false,
            'data' => $pago
        ]);
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