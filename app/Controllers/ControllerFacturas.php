<?php
class ControllerFacturas extends Controller {
    private $facturasModel;

    public function __construct() {
        AuthGuard::handle();
        $this->facturasModel = $this->model('Facturas');
    }

    public function index() {
        // Permitir acceso a Administradores, Mecánicos y Cajeros
        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO', 'CAJERO']);
        
        $total = $this->facturasModel->contarTotal();
        $data = [
            'titulo' => 'Historial de Facturas',
            'user_role' => $_SESSION['user_role'],
            'total_items' => $total
        ];

        $this->view('facturas/index', $data);
    }

    public function listar() {
        // Detectar búsqueda manual o de DataTables (por compatibilidad)
        $searchValue = $_GET['search']['value'] ?? $_GET['search'] ?? $_GET['q'] ?? null;
        $search = ($searchValue !== '' && $searchValue !== null) ? $searchValue : null;

        // Soporte para paginación manual
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        // Filtros de fecha
        $desde = $_GET['desde'] ?? null;
        $hasta = $_GET['hasta'] ?? null;

        $items = $this->facturasModel->listar($limit, $offset, $search, $desde, $hasta);

        $total = $this->facturasModel->contarTotal();
        $totalFiltrados = $search || $desde || $hasta ? $this->facturasModel->contarFiltrados($search, $desde, $hasta) : $total;

        return $this->jsonResponse([
            'success' => true,
            'data' => $items,
            'total' => $total,
            'totalFiltrados' => $totalFiltrados
        ]);
    }

    /**
     * Ver detalle de una factura
     */
    public function ver($id = null) {
        if (!$id) {
            redirect('facturas');
        }

        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO', 'CAJERO']);
        
        $factura = $this->facturasModel->obtenerPorId($id);
        if (!$factura) {
            redirect('facturas?error=factura_no_encontrada');
        }

        $this->view('facturas/ver', [
            'titulo' => 'Detalle Factura #' . str_pad((string)$factura->id, 3, '0', STR_PAD_LEFT),
            'factura' => $factura
        ]);
    }

    /**
     * Imprimir factura en PDF
     */
    public function imprimir($id) {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO', 'CAJERO']);
        
        $venta = $this->facturasModel->obtenerVentaCompleta($id);
        if (!$venta) {
            die("Factura no encontrada.");
        }

        $tituloPestaña = 'FACTURA - ' . str_pad((string)$venta->id, 3, '0', STR_PAD_LEFT);

        $pdfService = new PdfService();
        $pdfService->generarDocumento('factura', [
            'titulo_pestaña' => $tituloPestaña,
            'titulo_documento' => 'Factura de Venta',
            'documento_id' => $tituloPestaña,
            'venta' => $venta
        ], $tituloPestaña . '.pdf');
        exit;
    }
}