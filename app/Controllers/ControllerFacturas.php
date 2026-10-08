<?php
class ControllerFacturas extends Controller {
    private $facturasModel;

    public function __construct() {
        AuthGuard::handle();
        $this->facturasModel = $this->model('Facturas');
    }

    public function index() {
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
        $searchValue = $_GET['search']['value'] ?? $_GET['search'] ?? $_GET['q'] ?? null;
        $search = ($searchValue !== '' && $searchValue !== null) ? $searchValue : null;

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $desde = $_GET['desde'] ?? null;
        $hasta = $_GET['hasta'] ?? null;
        // MEJORA 9: filtro por estado
        $estado = $_GET['estado'] ?? null;
        // MEJORA 10: filtro por cliente
        $clienteId = $_GET['cliente_id'] ?? null;

        $items = $this->facturasModel->listar($limit, $offset, $search, $desde, $hasta, $estado, $clienteId);

        $total = $this->facturasModel->contarTotal();
        $totalFiltrados = ($search || $desde || $hasta || $estado || $clienteId) 
            ? $this->facturasModel->contarFiltrados($search, $desde, $hasta, $estado, $clienteId) 
            : $total;

        return $this->jsonResponse([
            'success' => true,
            'data' => $items,
            'total' => $total,
            'totalFiltrados' => $totalFiltrados
        ]);
    }

    public function ver($id = null) {
        if (!$id) redirect('facturas');

        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO', 'CAJERO']);
        
        $factura = $this->facturasModel->obtenerPorId($id);
        if (!$factura) redirect('facturas?error=factura_no_encontrada');

        $this->view('facturas/ver', [
            'titulo' => 'Detalle Factura #' . str_pad((string)$factura->id, 3, '0', STR_PAD_LEFT),
            'factura' => $factura
        ]);
    }

    public function imprimir($id) {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO', 'CAJERO']);
        
        $venta = $this->facturasModel->obtenerVentaCompleta($id);
        if (!$venta) die("Factura no encontrada.");

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