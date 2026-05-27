<?php
class ControllerFacturacion extends Controller {
    private $facturaModel;
    private $empresaModel;
    private $cajaModel;
    private $billingService;

    public function __construct() {
        AuthGuard::handle();
        $this->facturaModel = $this->model('Facturacion');
        $this->empresaModel = $this->model('Empresa');
        $this->cajaModel = $this->model('Caja');
        $this->billingService = new BillingService();
    }

    public function index() {
        $config = $this->empresaModel->obtenerConfiguracion();
        $data = [
            'titulo' => 'Nueva Facturación',
            'iva_defecto' => $config->iva ?? 0,
            'usuario_actual' => $_SESSION['user_nombre']
        ];

        $this->view('facturacion/index', $data);
    }

    /**
     * Endpoint para buscar items en tiempo real
     */
    public function buscarItems() {
        $term = $_GET['term'] ?? '';
        $items = $this->facturaModel->buscarItems($term);
        header('Content-Type: application/json');
        echo json_encode($items);
    }

    /**
     * Lista todos los borradores activos en el sistema (Global)
     */
    public function listarBorradores() {
        header('Content-Type: application/json');
        echo json_encode($this->facturaModel->obtenerBorradoresCompleto());
    }

    /**
     * Procesa el guardado de la venta
     */
    public function procesar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                header('Content-Type: application/json');
                $datos = json_decode(file_get_contents('php://input'), true);

                
                // Validación de Esquema
                $v = new Validator($datos);
                $v->required(['items', 'pago_efectivo', 'pago_transferencia'])
                  ->array('items')
                  ->numeric(['pago_efectivo', 'pago_transferencia']);

                if (!$v->success()) {
                    $errorMsg = implode(" ", $v->getErrors());
                    throw new AppException($errorMsg);
                }

                // Delegar TODA la lógica pesada al Servicio
                $resultado = $this->billingService->procesarVentaCompleta($datos);

                echo json_encode([
                    'success' => true, 
                    'mensaje' => 'Venta realizada con éxito',
                    'venta_id' => $resultado
                ]);
            } catch (StockException $e) {
                echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'mensaje' => 'Error Crítico: ' . $e->getMessage()]);
            }
        }
    }

    /**
     * Sincroniza un borrador con la base de datos para reservar stock en tiempo real.
     */
    public function sincronizarBorrador() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            $datos = json_decode(file_get_contents('php://input'), true);
            
            // Validación mínima para borradores
            $v = new Validator($datos);
            $v->array('items', true); // Permitir items vacíos en borradores

            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Estructura de borrador inválida'], 400);
            }

            try {
                $resultado = $this->billingService->sincronizarBorradorSeguro($datos);
                echo json_encode(['success' => true, 'venta_id' => $resultado]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
            }
        }
    }

    /**
     * Elimina un borrador de la base de datos verificando permisos por rol
     */
    public function eliminarBorrador($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            
            $borrador = $this->facturaModel->obtenerBorradorPorId($id);
            if (!$borrador) {
                echo json_encode(['success' => false, 'mensaje' => 'Borrador no encontrado']);
                return;
            }

            $resultado = $this->facturaModel->eliminarBorrador($id);
            echo json_encode(['success' => $resultado]);
        }
    }

    /**
     * Genera el PDF de la Factura de Venta
     */
    public function generarPdfAjax($id = null) {
        if (!$id) {
            return $this->jsonResponse(['success' => false, 'mensaje' => 'ID de factura no proporcionado.'], 400);
        }

        $venta = $this->facturaModel->obtenerVentaCompleta($id);
        if (!$venta) {
            return $this->jsonResponse(['success' => false, 'mensaje' => "La factura #$id no existe o no ha sido completada."], 404);
        }

        try {
            $pdfService = new PdfService();
            $filename = 'Factura_' . $id . '_' . time() . '.pdf';
            $filePath = $pdfService->generarDocumento('factura', [
                'titulo_documento' => 'Factura de Venta',
                'documento_id' => $venta->id,
                'venta' => $venta
            ], $filename, false);

            return $this->jsonResponse(['success' => true, 'pdf_url' => URLROOT . '/' . $filePath]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Sirve el PDF generado (URL: /facturacion/imprimir/archivo.pdf)
     */
    public function imprimir($filename = null) {
        if (!$filename) {
            die("Documento no especificado.");
        }

        $filePath = APPROOT . '/../public/temp_pdfs/' . $filename;

        if (file_exists($filePath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            readfile($filePath);
            // Optionally delete the file after serving
            // unlink($filePath); // Consider a cron job for cleanup instead
            exit;
        } else {
            die("El documento solicitado no se encontró.");
        }
    }
        /**
     * Procesa la petición AJAX para registrar un abono a una deuda de cliente.
     * Ruta: /facturacion/registrarAbono
     */
    public function registrarAbono() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Leer el cuerpo de la petición JSON
            $input = json_decode(file_get_contents('php://input'), true);

            $v = new Validator($input);
            $v->required(['venta_id', 'monto', 'metodo'])
              ->numeric(['venta_id', 'monto'])
              ->in('metodo', ['EFECTIVO', 'TRANSFERENCIA']);

            if (!$v->success()) {
                return $this->jsonResponse([
                    'success' => false, 
                    'mensaje' => 'Datos inválidos: ' . implode(", ", $v->getErrors())
                ], 400);
            }

            $ventaId = (int)$input['venta_id'];
            $monto = (float)$input['monto'];
            $metodo = strtoupper($input['metodo']);

            try {
                // Usamos el servicio para garantizar que el abono y el movimiento de caja ocurran juntos
                $this->billingService->registrarAbonoSeguro($ventaId, $monto, $metodo);
                logAction('VENTA', 'ABONO', "Se registró un abono de " . $monto . " a la factura #" . $ventaId . " vía " . $metodo);
                
                return $this->jsonResponse([
                    'success' => true,
                    'mensaje' => '¡Abono registrado con éxito!'
                ]);
            } catch (Exception $e) {
                return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
        } else {
            // Bloquear accesos que no sean POST
            return $this->jsonResponse(['success' => false, 'mensaje' => 'Método no permitido'], 405);
        }
    }

    /**
     * Endpoint para obtener las alertas de créditos vencidos (AJAX)
     */
    public function alertasCredito() {
        RoleGuard::isAdmin();
        $data = $this->facturaModel->obtenerCreditosVencidos(15);
        return $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    /**
     * Endpoint para obtener el resumen de deudores para el dashboard (AJAX)
     */
    public function getDeudoresSummary() {
        RoleGuard::isAdmin();
        $data = $this->facturaModel->obtenerAuditoriaTrabajos();
        return $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    /**
     * Obtiene los items de una factura que son aptos para devolución (solo productos)
     */
    public function getItemsDevolucion($id) {
        $venta = $this->facturaModel->obtenerVentaCompleta($id);
        // Filtrar solo los que tienen producto_id (no servicios)
        $items = array_filter($venta->items, function($it) {
            return !empty($it->producto_id);
        });
        echo json_encode(['success' => true, 'items' => array_values($items)]);
    }

    /**
     * Lista las devoluciones realizadas (Endpoint para el reporte de historial)
     */
    public function listarDevoluciones() {
        RoleGuard::isAdmin();
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $reporteModel = $this->model('Reportes');
        $data = $reporteModel->obtenerReporteDevoluciones($desde, $hasta);
        
        return $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    public function procesarDevolucion() {
        RoleGuard::isAdmin();
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $v = new Validator($input);
            $v->required(['venta_id', 'detalle_id', 'destino'])
              ->in('destino', ['STOCK', 'DANADO']); // Ajustado para coincidir con el valor del frontend

            if (!$v->success()) {
                throw new Exception('Datos de devolución inválidos');
            }

            $this->billingService->procesarDevolucionSegura($input);
            return $this->jsonResponse(['success' => true, 'mensaje' => 'Devolución procesada correctamente']);

        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }
}