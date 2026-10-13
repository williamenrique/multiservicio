<?php
/**
 * Controlador de Garantías
 * Gestiona el módulo de garantías de servicios y repuestos.
 *
 * v3.0 (2026-10-13) — REDISEÑO DEL FLUJO DE GARANTÍA:
 *   • procesar() ahora devuelve un 'resumen' con los totales del impacto:
 *       egreso_devolucion, ingreso_adicional, items_devueltos_stock, items_descartados.
 *   • Se mantiene la validación de `factura_id`, `tipo_garantia`, `motivo`, `items`.
 *   • El modelo hace la validación fina de acciones/destinos por tipo de item.
 *
 * v2.1 (2026-10-09):
 *   • FIX P0-09: pdf() devuelve URL al endpoint imprimir/{id}.
 *   • FIX P0-09: imprimir() sin temp_pdfs (PdfService nunca escribe en disco).
 */
class ControllerGarantia extends Controller {

    public function __construct() {
        AuthGuard::handle();
        $this->model('Garantia');
    }

    public function index() {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        $this->view('garantia/index');
    }

    /**
     * AJAX: Lista facturas con derecho a garantía (COMPLETADO/CREDITO, no anuladas).
     */
    public function listarFacturas() {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
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
     * v3.0: el modelo devuelve un `resumen` con el impacto contable/stock.
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

        // Log de auditoría
        if (!empty($resultado['success'])) {
            try {
                $resumen = $resultado['resumen'] ?? [];
                $desc = "Garantía #{$resultado['garantia_id']} sobre factura #{$data['factura_id']}"
                      . " | Nueva factura: #{$resultado['factura_garantia_id']}"
                      . " | Egreso devolución: $" . number_format($resumen['egreso_devolucion'] ?? 0, 2)
                      . " | Ingreso adicional: $" . number_format($resumen['ingreso_adicional'] ?? 0, 2)
                      . " | Reingresos stock: " . ($resumen['items_devueltos_stock'] ?? 0) . " uds"
                      . " | Descartados: " . ($resumen['items_descartados'] ?? 0) . " uds";
                logAction('GARANTIA', 'PROCESAR', $desc);
            } catch (\Throwable $e) {
                error_log('ControllerGarantia::procesar logAction falló: ' . $e->getMessage());
            }
        }

        $this->jsonResponse($resultado);
    }

    /**
     * Vista/AJAX: Historial de garantías.
     */
    public function historial() {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['CONTENT_TYPE'])
                && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
            || isset($_GET['ajax']);

        if ($isAjax) {
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
        $facturaOriginal = $this->model('Garantia')->obtenerFacturaOriginalCompleta($garantia->factura_original_id);
        $this->jsonResponse([
            'success' => true,
            'garantia' => $garantia,
            'factura_original' => $facturaOriginal,
        ]);
    }

    /**
     * AJAX: Devuelve la URL para imprimir el PDF de una garantía.
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

        return $this->jsonResponse([
            'success' => true,
            'pdf_url' => URLROOT . '/garantia/imprimir/' . $garantiaId
        ]);
    }

    /**
     * Sirve el PDF de la garantía directamente en el navegador.
     */
    public function imprimir($id = null) {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        if (!$id) {
            throw new AppException("ID de garantía no proporcionado.", 400);
        }

        $garantiaId = (int)$id;
        $garantia = $this->model('Garantia')->obtenerGarantia($garantiaId);
        if (!$garantia) {
            throw new AppException("La garantía #$garantiaId no existe.", 404);
        }

        $pdfService = new PdfService();
        $doc_name = 'GAR-' . str_pad($garantia->id, 4, '0', STR_PAD_LEFT);
        $pdfService->generarDocumento('garantia', [
            'garantia' => $garantia,
            'items'    => $garantia->detalle,
        ], $doc_name . '.pdf');
        exit;
    }
}