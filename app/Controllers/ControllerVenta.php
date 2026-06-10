<?php
/**
 * Controlador para Venta de Repuestos (Mostrador)
 * Acceso exclusivo para administradores.
 */
class ControllerVenta extends Controller {
    private $facturaModel;
    private $reporteModel;

    public function __construct() {
        AuthGuard::handle();
        RoleGuard::isAdmin(); // Restricción de seguridad para administradores
        $this->facturaModel = $this->model('Facturacion');
        $this->reporteModel = $this->model('Reportes');
    }

    /**
     * Vista principal del mostrador: app/Views/venta/index.php
     */
    public function index() {
        $data = [
            'titulo' => 'Venta de Repuestos - Administrador',
            'clientes' => $this->model('Cliente')->listar() 
        ];
        $this->view('venta/index', $data);
    }

    /**
     * API: Listar historial de ventas de mostrador
     */
    public function historial() {
        $limit = $_GET['limit'] ?? 10;
        $offset = $_GET['offset'] ?? 0;
        $search = $_GET['q'] ?? null;
        $desde = !empty($_GET['desde']) ? $_GET['desde'] : null;
        $hasta = !empty($_GET['hasta']) ? $_GET['hasta'] : null;
        
        $res = $this->facturaModel->obtenerVentasMostrador($limit, $offset, $search, $desde, $hasta);
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $res['data'],
            'total' => $res['total'], // Total absoluto para paginación
            'totalFiltrados' => $res['total']
        ]);
    }

    /**
     * Genera un reporte PDF de las ventas de repuestos en un rango de fechas
     */
    public function imprimirReporte() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        
        $reporte = $this->reporteModel->obtenerReporteRepuestos($desde, $hasta);
        $empresa = $this->model('Empresa')->obtenerConfiguracion();

        $pdfService = new PdfService();
        $pdfService->generarDocumento('reporte_mostrador', [
            'titulo_documento' => 'Reporte de Ventas de Repuestos',
            'desde' => $desde,
            'hasta' => $hasta,
            'datos' => $reporte,
            'empresa' => $empresa
        ], 'Reporte_Repuestos_' . date('Ymd') . '.pdf');
        exit;
    }

    /**
     * Reutiliza la generación de factura del sistema central
     */
    public function imprimirFactura($id) {
        $venta = $this->facturaModel->obtenerVentaCompleta($id);
        if (!$venta) die("Venta no encontrada.");

        $pdfService = new PdfService();
        $pdfService->generarDocumento('factura', [
            'titulo_documento' => 'Comprobante de Venta',
            'venta' => $venta,
            'documento_id' => 'Factura Número: ' . $venta->id
        ], 'Factura_Repuestos_' . $id . '.pdf');
        exit;
    }
}