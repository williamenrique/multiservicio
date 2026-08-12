<?php
/**
 * Controlador de Garantías
 * Gestiona el módulo de garantías de servicios y repuestos.
 */
class ControllerGarantia extends Controller {

    public function __construct() {
        AuthGuard::handle();
        $this->model('Garantia');
    }

    /**
     * Vista principal del módulo de garantías.
     */
    public function index() {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        $this->view('garantia/index');
    }

    /**
     * AJAX: Lista facturas con derecho a garantía (COMPLETADO/CREDITO, no anuladas).
     */
    public function listarFacturas() {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        // Leer datos: soporta JSON body y form-urlencoded
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }
        $pagina = isset($data['pagina']) ? (int)$data['pagina'] : 1;
        $search = isset($data['search']) ? trim($data['search']) : null;
        $porPagina = 10;
        $offset = ($pagina - 1) * $porPagina;

        $facturas = $this->model('Garantia')->listarFacturasConGarantia($porPagina, $offset, $search);
        $total = $this->model('Garantia')->contarFacturasConGarantia($search);
        $totalPaginas = ceil($total / $porPagina);

        $this->jsonResponse([
            'success' => true,
            'data' => $facturas,
            'paginacion' => [
                'pagina_actual' => $pagina,
                'total_paginas' => $totalPaginas,
                'total_registros' => $total,
            ],
        ]);
    }

    /**
     * AJAX: Obtiene el detalle completo de una factura con info de garantía por item.
     */
    public function getDetalleFactura($id) {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        $facturaId = (int)$id;
        if (!$facturaId) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'ID DE FACTURA REQUERIDO']);
            return;
        }

        $factura = $this->model('Garantia')->obtenerFacturaCompleta($facturaId);
        if (!$factura) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'FACTURA NO ENCONTRADA']);
            return;
        }
        $items = $this->model('Garantia')->obtenerItemsConGarantia($facturaId);

        $this->jsonResponse([
            'success' => true,
            'factura' => $factura,
            'items' => $items,
        ]);
    }

    /**
     * AJAX: Procesa una garantía.
     */
    public function procesar() {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!$data) {
            $data = $_POST;
        }

        $v = new Validator($data);
        $v->required(['factura_id', 'tipo_garantia', 'motivo', 'items']);
        if (!$v->success()) {
            $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())]);
            return;
        }

        $resultado = $this->model('Garantia')->procesarGarantia($data);
        $this->jsonResponse($resultado);
    }

    /**
     * Vista/AJAX: Historial de garantías.
     */
    public function historial() {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);

        // Detectar peticiones AJAX: header X-Requested-With, Content-Type JSON o parámetro ajax
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['CONTENT_TYPE'])
                && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
            || isset($_GET['ajax']);

        if ($isAjax) {
            // Leer datos: soporta JSON body y form-urlencoded
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                $data = $_POST;
            }
            $pagina = isset($data['pagina']) ? (int)$data['pagina'] : 1;
            $search = isset($data['search']) ? trim($data['search']) : null;
            $desde = isset($data['desde']) ? trim($data['desde']) : null;
            $hasta = isset($data['hasta']) ? trim($data['hasta']) : null;
            $porPagina = 10;
            $offset = ($pagina - 1) * $porPagina;

            $garantias = $this->model('Garantia')->listarGarantias($porPagina, $offset, $search, $desde, $hasta);
            $total = $this->model('Garantia')->contarGarantias($search, $desde, $hasta);
            $totalPaginas = ceil($total / $porPagina);

            $this->jsonResponse([
                'success' => true,
                'data' => $garantias,
                'paginacion' => [
                    'pagina_actual' => $pagina,
                    'total_paginas' => $totalPaginas,
                    'total_registros' => $total,
                ],
            ]);
        } else {
            $this->view('garantia/index', ['tab' => 'historial']);
        }
    }

    /**
     * AJAX: Obtiene el detalle de una garantía.
     */
    public function detalle($id) {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        $garantiaId = (int)$id;
        if (!$garantiaId) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'ID DE GARANTÍA REQUERIDO']);
            return;
        }
        $garantia = $this->model('Garantia')->obtenerGarantia($garantiaId);
        if (!$garantia) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'GARANTÍA NO ENCONTRADA']);
            return;
        }
        // Información completa de la factura original
        $facturaOriginal = $this->model('Garantia')->obtenerFacturaOriginalCompleta($garantia->factura_original_id);
        $this->jsonResponse([
            'success' => true,
            'garantia' => $garantia,
            'factura_original' => $facturaOriginal,
        ]);
    }

    /**
     * AJAX: Genera el PDF de una garantía y devuelve la URL temporal.
     */
    public function pdf($id = null) {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        $garantiaId = (int)$id;
        if (!$garantiaId) {
            return $this->jsonResponse(['success' => false, 'mensaje' => 'ID DE GARANTÍA REQUERIDO'], 400);
        }

        $garantia = $this->model('Garantia')->obtenerGarantia($garantiaId);
        if (!$garantia) {
            return $this->jsonResponse(['success' => false, 'mensaje' => 'GARANTÍA NO ENCONTRADA'], 404);
        }

        try {
            $pdfService = new PdfService();
            $doc_name = 'GAR-' . str_pad($garantia->id, 4, '0', STR_PAD_LEFT);
            $filename = $doc_name . '_' . time() . '.pdf';
            $filePath = $pdfService->generarDocumento('garantia', [
                'garantia' => $garantia,
                'items'    => $garantia->detalle,
            ], $filename, false);

            return $this->jsonResponse(['success' => true, 'pdf_url' => URLROOT . '/' . $filePath]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Sirve el PDF de la garantía directamente en el navegador (URL: /garantia/imprimir/ID).
     */
    public function imprimir($id = null) {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        if (!$id) {
            throw new AppException("ID de garantía o archivo no proporcionado.", 400);
        }

        // 1. Si el parámetro es un nombre de archivo (.pdf), servimos el archivo temporal
        if (strpos($id, '.pdf') !== false) {
            $filePath = APPROOT . '/../public/temp_pdfs/' . $id;
            if (file_exists($filePath)) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $id . '"');
                readfile($filePath);
                exit;
            }
        }

        // 2. Si es ID numérico, generamos el PDF en tiempo real
        $garantiaId = (int)$id;
        $garantia = $this->model('Garantia')->obtenerGarantia($garantiaId);
        if (!$garantia) {
            throw new AppException("La garantía #$garantiaId no existe o el documento solicitado no se encontró.", 404);
        }

        $pdfService = new PdfService();
        $doc_name = 'GAR-' . str_pad($garantia->id, 4, '0', STR_PAD_LEFT);
        $pdfService->generarDocumento('garantia', [
            'garantia' => $garantia,
            'items'    => $garantia->detalle,
        ], $doc_name . '.pdf'); // Stream to browser por defecto
        exit;
    }
}
