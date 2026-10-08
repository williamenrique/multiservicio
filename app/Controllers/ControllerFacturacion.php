<?php
class ControllerFacturacion extends Controller {
    private $facturaModel;
    private $empresaModel;
    private $billingService;

    public function __construct() {
        AuthGuard::handle();
        $this->facturaModel = $this->model('Facturacion');
        $this->empresaModel = $this->model('Empresa');
        $this->billingService = new BillingService();
    }

    public function index() {
        $config = $this->empresaModel->obtenerConfiguracion();
        $reportModel = $this->model('Reportes');
        $data = [
            'titulo' => 'Nueva Facturación',
            'iva_defecto' => $config->iva ?? 0,
            'usuario_actual' => $_SESSION['user_nombre'],
            'user_role' => $_SESSION['user_role'],
            'user_staff_id' => $_SESSION['user_staff_id'] ?? null,
            'staff' => $reportModel->obtenerStaffSimple()
        ];

        if (isset($_GET['orden_id'])) {
            $ordenModel = $this->model('Orden');
            $orden = $ordenModel->obtenerDetalleOrden($_GET['orden_id']);
            if ($orden) {
                $orden->cliente_id = trim($orden->cliente_id ?? '');

                $borrador = $this->facturaModel->obtenerBorradorPorOrden($_GET['orden_id']);
                if ($borrador && !empty($borrador->observaciones)) {
                    $orden->observaciones_factura = $borrador->observaciones;
                }

                $data['orden'] = $orden;
            }
        }

        $this->view('facturacion/index', $data);
    }

    public function buscarItems() {
        $term = $_GET['term'] ?? '';
        $items = $this->facturaModel->buscarItems($term);
        return $this->jsonResponse($items);
    }

    public function listarBorradores() {
        try {
            return $this->jsonResponse($this->facturaModel->obtenerBorradoresCompleto());
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function obtenerPorOrden($id) {
        try {
            $borrador = $this->facturaModel->obtenerBorradorPorOrden($id);
            if (!$borrador) return $this->jsonResponse(['success' => false, 'mensaje' => 'No hay borrador para esta orden'], 404);
            return $this->jsonResponse(['success' => true, 'data' => $borrador]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Procesa el guardado de la venta.
     * 
     * FIX v2.2:
     *   - catch (\Throwable) en lugar de catch (Exception): captura también
     *     fatales (TypeError, Error, etc.) y los devuelve como JSON.
     *   - Log detallado del payload cuando algo falla.
     *   - marcarComoConvertido() ahora se envuelve en su propio try/catch
     *     con \Throwable para no tumbar la venta por un fallo secundario.
     */
    public function procesar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            try {
                $datos = json_decode(file_get_contents('php://input'), true);
                
                if (!$datos) {
                    throw new Exception('Payload inválido o vacío');
                }

                if ($_SESSION['user_role'] === 'MECANICO') {
                    $datos['mecanico_id'] = $_SESSION['user_staff_id'];
                }

                $datos['iva_activo'] = $datos['iva_activo'] ?? false;
                $datos['placa'] = !empty($datos['placa']) ? strtoupper(trim($datos['placa'])) : '';
                $datos['origen'] = !empty($datos['orden_id']) ? 'TALLER' : 'MOSTRADOR';

                $v = new Validator($datos);
                $v->required(['items'])->array('items');

                if (!empty($datos['placa'])) {
                    $v->required(['mecanico_id']);
                }

                if (!$v->success()) {
                    throw new Exception(implode(" ", $v->getErrors()));
                }

                if (!empty($datos['orden_id']) && empty($datos['id_db'])) {
                    $borradorExistente = $this->facturaModel->obtenerBorradorPorOrden($datos['orden_id']);
                    if ($borradorExistente) {
                        $datos['id_db'] = $borradorExistente->id;
                    }
                }

                if (!empty($datos['orden_id'])) {
                    $dbCheck = new Database();
                    $dbCheck->query("SELECT id FROM table_facturas 
                                     WHERE orden_id = :oid 
                                     AND status IN ('COMPLETADO', 'CREDITO') 
                                     AND id != :current_id");
                    $dbCheck->bind(':oid', $datos['orden_id']);
                    $dbCheck->bind(':current_id', $datos['id_db'] ?? 0);
                    if ($dbCheck->single()) {
                        throw new Exception("Error: Esta Orden de Servicio ya tiene una factura procesada o un crédito activo.");
                    }
                }

                // Log del request para depurar
                error_log("FACTURACION::procesar - Payload: " . json_encode([
                    'cliente_id' => $datos['cliente_id'] ?? null,
                    'items_count' => is_array($datos['items'] ?? null) ? count($datos['items']) : 0,
                    'presupuesto_activo_id' => $datos['presupuesto_activo_id'] ?? null,
                    'iva_activo' => $datos['iva_activo'] ?? null,
                    'placa' => $datos['placa'] ?? null,
                    'origen' => $datos['origen'] ?? null,
                ]));

                $ventaId = $this->billingService->procesarVentaCompleta($datos, $_SESSION['user_id']);

                // ─── Marcar presupuesto como CONVERTIDO (post-venta) ───
                // El presupuesto ya había sido ACEPTADO (con reservas RESERVADA) o
                // simplemente cargado desde el carrito. BillingService ya descontó
                // el stock físico. Aquí solo marcamos reservas como FACTURADA y
                // cambiamos el estado a CONVERTIDO. NO se toca stock físico.
                if (!empty($datos['presupuesto_activo_id'])) {
                    try {
                        $presupuestoModel = $this->model('Presupuesto');
                        $presupuestoModel->marcarComoConvertido(
                            (int)$datos['presupuesto_activo_id'],
                            (int)$ventaId,
                            $_SESSION['user_id']
                        );
                        logAction('FACTURACION', 'CONVERTIR_PRESUPUESTO',
                            "Presupuesto #{$datos['presupuesto_activo_id']} convertido a venta (Factura #{$ventaId})");
                    } catch (\Throwable $e) {
                        // No revertimos la venta; solo lo logueamos
                        error_log("Error marcando presupuesto como convertido: " . $e->getMessage());
                    }
                }

                if (!empty($datos['orden_id'])) {
                    logAction('TALLER', 'FINALIZAR_ORDEN', "Venta procesada para O.S. #{$datos['orden_id']}");
                }

                if (empty($datos['orden_id'])) {
                    try {
                        $ventaCompleta = $this->facturaModel->obtenerVentaCompleta($ventaId);
                        if ($ventaCompleta && !empty($ventaCompleta->cliente_email)) {
                            $emailData = [
                                'cliente_nombre'     => $ventaCompleta->cliente_nombre ?? 'Cliente',
                                'cliente_email'      => $ventaCompleta->cliente_email,
                                'venta_id'           => $ventaId,
                                'id_formateado'      => $ventaCompleta->id_formateado ?? null,
                                'placa'              => $ventaCompleta->placa ?? null,
                                'marca_vehiculo'     => $ventaCompleta->marca_vehiculo ?? null,
                                'modelo_vehiculo'    => $ventaCompleta->modelo_vehiculo ?? null,
                                'items'              => $ventaCompleta->items ?? [],
                                'subtotal'           => $ventaCompleta->subtotal ?? 0,
                                'iva_monto'          => $ventaCompleta->iva_monto ?? 0,
                                'total'              => $ventaCompleta->total ?? 0,
                                'pago_efectivo'      => $ventaCompleta->pago_efectivo ?? 0,
                                'pago_transferencia' => $ventaCompleta->pago_transferencia ?? 0,
                                'saldo_pendiente'    => $ventaCompleta->saldo_pendiente ?? 0,
                                'status'             => $ventaCompleta->status ?? 'PENDIENTE',
                                'observaciones_factura' => $ventaCompleta->observaciones_factura ?? null,
                                'vendedor_nombre'    => $ventaCompleta->vendedor_nombre ?? null,
                            ];
                            $emailService = new \App\Services\EmailService();
                            $emailService->notificarFacturaDirecta($emailData);
                        }
                    } catch (\Throwable $e) {
                        error_log('ControllerFacturacion: Error al enviar email de factura directa: ' . $e->getMessage());
                    }
                }

                return $this->jsonResponse([
                    'success' => true,
                    'mensaje' => 'Venta realizada con éxito',
                    'venta_id' => $ventaId
                ]);
            } catch (\Throwable $e) {
                // Captura Exception Y Error (TypeError, ArgumentCountError, Error, etc.)
                $errorMsg = $e->getMessage();
                $errorFile = $e->getFile();
                $errorLine = $e->getLine();
                
                error_log("FACTURACION::procesar FALLÓ: [$errorMsg] en $errorFile:$errorLine");
                error_log("FACTURACION::procesar Stack: " . $e->getTraceAsString());

                return $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Error al procesar la venta: ' . $errorMsg,
                    'debug' => [
                        'file' => basename($errorFile),
                        'line' => $errorLine,
                    ]
                ], 500);
            }
        }
    }

    // ... resto del archivo sin cambios: sincronizarBorrador, eliminarBorrador, generarPdfAjax, imprimir, registrarAbono, alertasCredito, getDeudoresSummary, getItemsDevolucion, listarDevoluciones, procesarDevolucion
}