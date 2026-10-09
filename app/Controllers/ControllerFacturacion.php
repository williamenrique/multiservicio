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

                $ventaId = $this->billingService->procesarVentaCompleta($datos, $_SESSION['user_id']);

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
                error_log("FACTURACION::procesar FALLÓ: [" . $e->getMessage() . "]");
                return $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Error al procesar la venta: ' . $e->getMessage(),
                ], 500);
            }
        }
    }

    public function sincronizarBorrador() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            try {
                $datos = json_decode(file_get_contents('php://input'), true);
                if (!$datos) throw new Exception('Payload inválido');

                $subtotal = 0;
                if (!empty($datos['items'])) {
                    foreach ($datos['items'] as $it) {
                        $subtotal += ((float)($it['precio'] ?? 0) * (int)($it['cantidad'] ?? 0));
                    }
                }

                $ivaActivo = !empty($datos['iva_activo']);
                $tasaIva = (float)($datos['tasa_iva'] ?? 19);
                $iva = $ivaActivo ? ($subtotal * ($tasaIva / 100)) : 0;
                $total = $subtotal + $iva;

                $pef = (float)($datos['pago_efectivo'] ?? 0);
                $ptra = (float)($datos['pago_transferencia'] ?? 0);
                $saldo = max(0, $total - ($pef + $ptra));

                $totales = ['subtotal' => $subtotal, 'iva' => $iva, 'total' => $total, 'saldo' => $saldo];
                $status = 'PENDIENTE';

                $ventaId = $this->facturaModel->guardarCabeceraVenta($datos, $status, $totales, $_SESSION['user_id']);

                $db = new Database();
                $db->query("DELETE FROM table_facturas_detalle WHERE factura_id = :fid");
                $db->bind(':fid', $ventaId);
                $db->execute();

                if (!empty($datos['items'])) {
                    foreach ($datos['items'] as $item) {
                        $esProducto = (strtoupper($item['tipo'] ?? '') === 'PRODUCTO');
                        $productoId = ($esProducto && !empty($item['id'])) ? (int)$item['id'] : null;
                        $mecanicoId = !empty($datos['mecanico_id']) ? $datos['mecanico_id'] : null;

                        $db->query("INSERT INTO table_facturas_detalle 
                                    (factura_id, producto_id, mecanico_id, descripcion, cantidad, precio_unitario, costo_unitario) 
                                    VALUES (:fid, :pid, :mid, :desc, :cant, :pre, :costo)");
                        $db->bind(':fid', $ventaId);
                        $db->bind(':pid', $productoId);
                        $db->bind(':mid', $mecanicoId);
                        $db->bind(':desc', mb_strtoupper($item['nombre'] ?? 'ITEM', 'UTF-8'));
                        $db->bind(':cant', (int)($item['cantidad'] ?? 0));
                        $db->bind(':pre', (float)($item['precio'] ?? 0));
                        $db->bind(':costo', $esProducto ? (float)($item['costo_promedio'] ?? 0) : 0);
                        $db->execute();
                    }
                }

                return $this->jsonResponse(['success' => true, 'venta_id' => $ventaId]);
            } catch (\Throwable $e) {
                return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
        }
    }

    public function eliminarBorrador($id = null) {
        try {
            if (!$id) return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);

            $db = new Database();
            $db->query("SELECT presupuesto_activo_id FROM table_facturas WHERE id = :id AND status = 'PENDIENTE'");
            $db->bind(':id', (int)$id);
            $factura = $db->single();

            if ($factura && !empty($factura->presupuesto_activo_id)) {
                try {
                    $presupuestoModel = $this->model('Presupuesto');
                    $presupuestoModel->liberarInventario(
                        (int)$factura->presupuesto_activo_id,
                        $_SESSION['user_id'],
                        'BORRADOR_FACTURA_ELIMINADO'
                    );
                } catch (\Throwable $e) {
                    error_log("Error liberando presupuesto al eliminar borrador: " . $e->getMessage());
                }
            }

            $this->facturaModel->eliminarBorrador((int)$id);
            return $this->jsonResponse(['success' => true, 'mensaje' => 'Borrador eliminado']);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function generarPdfAjax($id = null) {
        try {
            if (!$id) return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            return $this->jsonResponse(['success' => true, 'pdf_url' => URLROOT . '/facturacion/imprimir/' . (int)$id]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function imprimir($id) {
        $venta = $this->facturaModel->obtenerVentaCompleta($id);
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

    /**
     * Registra un abono. Envía email al cliente si tiene email registrado.
     */
    public function registrarAbono() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') return;

        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['venta_id']) || empty($input['monto'])) {
                throw new Exception('Datos incompletos');
            }

            $monto = (float)$input['monto'];
            $metodo = strtoupper($input['metodo'] ?? 'EFECTIVO');

            if ($monto <= 0) throw new Exception('El monto debe ser mayor a cero');
            if (!in_array($metodo, ['EFECTIVO', 'TRANSFERENCIA'])) {
                throw new Exception('Método de pago no válido');
            }

            $resultado = $this->billingService->registrarAbonoSeguro(
                (int)$input['venta_id'],
                $monto,
                $metodo
            );

            if ($resultado) {
                logAction('FACTURACION', 'REGISTRAR_ABONO',
                    "Abono de $" . number_format($monto, 2) . " a Factura #{$input['venta_id']} vía $metodo");

                // Mejora 7: Notificar por email al cliente (asíncrono — falla silenciosa)
                try {
                    $this->enviarNotificacionAbono((int)$input['venta_id'], $monto, $metodo);
                } catch (\Throwable $e) {
                    error_log('Error enviando notificación de abono: ' . $e->getMessage());
                }

                return $this->jsonResponse(['success' => true, 'mensaje' => 'Abono registrado correctamente']);
            } else {
                throw new Exception('No se pudo registrar el abono');
            }
        } catch (\Throwable $e) {
            error_log("Error en registrarAbono: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * MEJORA 7 — Envía email al cliente cuando se registra un abono.
     * Falla silenciosamente si EmailService no está disponible o el cliente
     * no tiene email registrado.
     */
    private function enviarNotificacionAbono($facturaId, $monto, $metodo) {
        $db = new Database();
        $db->query("SELECT 
                        v.id, v.total, v.pago_efectivo, v.pago_transferencia, v.saldo_pendiente,
                        CONCAT('FAC-', LPAD(v.id, 3, '0')) as id_formateado,
                        COALESCE(vh.placa, v.placa) as placa,
                        COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo,
                        c.nombre as cliente_nombre, c.email as cliente_email
                    FROM table_facturas v
                    LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                    LEFT JOIN table_clientes c ON v.cliente_id = c.id
                    WHERE v.id = :id");
        $db->bind(':id', $facturaId);
        $factura = $db->single();

        if (!$factura || empty($factura->cliente_email)) {
            return; // Sin email, no se envía
        }

        // Hook hacia EmailService (si tiene el método, se usa; si no, falla silencioso)
        if (class_exists('\App\Services\EmailService')) {
            $emailService = new \App\Services\EmailService();
            if (method_exists($emailService, 'notificarAbonoRegistrado')) {
                $emailService->notificarAbonoRegistrado([
                    'cliente_nombre' => $factura->cliente_nombre,
                    'cliente_email'  => $factura->cliente_email,
                    'factura_id'     => $factura->id,
                    'id_formateado'  => $factura->id_formateado,
                    'placa'          => $factura->placa,
                    'modelo_vehiculo'=> $factura->modelo_vehiculo,
                    'monto_abono'    => $monto,
                    'metodo_pago'    => $metodo,
                    'total'          => $factura->total,
                    'pago_efectivo'  => $factura->pago_efectivo,
                    'pago_transferencia' => $factura->pago_transferencia,
                    'saldo_pendiente'=> $factura->saldo_pendiente,
                ]);
            }
        }
    }

    /**
     * MEJORA 2 — Imprime el recibo PDF de un abono individual.
     * GET /facturacion/imprimirReciboAbono/{id}
     */
    public function imprimirReciboAbono($id = null) {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        if (!$id) die("ID de abono no proporcionado.");

        $abono = $this->facturaModel->obtenerReciboAbono($id);
        if (!$abono) die("El abono #$id no existe.");

        $empresa = $this->empresaModel->obtenerConfiguracion();
        $tituloPestaña = 'RECIBO-ABONO-' . str_pad((string)$abono->abono_id, 4, '0', STR_PAD_LEFT);

        $pdfService = new PdfService();
        $pdfService->generarDocumento('recibo_abono', [
            'titulo_pestaña' => $tituloPestaña,
            'titulo_documento' => 'RECIBO DE ABONO',
            'documento_id' => $tituloPestaña,
            'empresa' => $empresa,
            'abono' => $abono
        ], $tituloPestaña . '.pdf');
        exit;
    }

    /**
     * MEJORA 3 — Devuelve el historial de abonos de una factura.
     * GET /facturacion/getAbonosFactura/{facturaId}
     */
    public function getAbonosFactura($facturaId = null) {
        try {
            RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
            if (!$facturaId) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $abonos = $this->facturaModel->obtenerAbonosPorFactura($facturaId);
            return $this->jsonResponse(['success' => true, 'data' => $abonos]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * MEJORA 8 — Actualiza el estado de gestión de una factura.
     * POST /facturacion/actualizarEstadoGestion
     */
    public function actualizarEstadoGestion() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') return;
        header('Content-Type: application/json');

        try {
            RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['factura_id']) || empty($input['estado'])) {
                throw new Exception('Datos incompletos');
            }

            $this->facturaModel->actualizarEstadoGestion((int)$input['factura_id'], $input['estado']);

            logAction('FACTURACION', 'ESTADO_GESTION',
                "Factura #{$input['factura_id']} → {$input['estado']}");

            return $this->jsonResponse(['success' => true, 'mensaje' => 'Estado actualizado']);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * MEJORA 12 — Envía un recordatorio de pago al cliente por email.
     * POST /facturacion/enviarRecordatorio
     */
    public function enviarRecordatorio() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') return;
        header('Content-Type: application/json');

        try {
            RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['factura_id'])) {
                throw new Exception('ID de factura requerido');
            }

            $db = new Database();
            $db->query("SELECT 
                            v.id, v.fecha, v.total, v.saldo_pendiente,
                            CONCAT('FAC-', LPAD(v.id, 3, '0')) as id_formateado,
                            COALESCE(vh.placa, v.placa) as placa,
                            COALESCE(vh.modelo, v.modelo_vehiculo) as modelo_vehiculo,
                            c.nombre as cliente_nombre, c.email as cliente_email,
                            DATEDIFF(CURDATE(), DATE(v.fecha)) as dias_atraso
                        FROM table_facturas v
                        LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                        LEFT JOIN table_clientes c ON v.cliente_id = c.id
                        WHERE v.id = :id AND v.status = 'CREDITO' AND v.saldo_pendiente > 0.05");
            $db->bind(':id', (int)$input['factura_id']);
            $factura = $db->single();

            if (!$factura) throw new Exception('Factura no encontrada o ya está pagada');
            if (empty($factura->cliente_email)) throw new Exception('El cliente no tiene email registrado');

            if (class_exists('\App\Services\EmailService')) {
                $emailService = new \App\Services\EmailService();
                if (method_exists($emailService, 'enviarRecordatorioPago')) {
                    $emailService->enviarRecordatorioPago([
                        'cliente_nombre'  => $factura->cliente_nombre,
                        'cliente_email'   => $factura->cliente_email,
                        'factura_id'      => $factura->id,
                        'id_formateado'   => $factura->id_formateado,
                        'placa'           => $factura->placa,
                        'modelo_vehiculo' => $factura->modelo_vehiculo,
                        'total'           => $factura->total,
                        'saldo_pendiente' => $factura->saldo_pendiente,
                        'dias_atraso'     => $factura->dias_atraso,
                        'fecha_emision'   => $factura->fecha,
                    ]);
                } else {
                    throw new Exception('El sistema de email no tiene el método enviarRecordatorioPago');
                }
            } else {
                throw new Exception('El servicio de email no está disponible');
            }

            logAction('FACTURACION', 'ENVIAR_RECORDATORIO',
                "Recordatorio enviado al cliente de Factura #{$input['factura_id']}");

            return $this->jsonResponse(['success' => true, 'mensaje' => 'Recordatorio enviado al cliente']);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function alertasCredito() {
        try {
            RoleGuard::hasAccess(['ADMINISTRADOR']);
            $dias = 15;
            $facturas = $this->facturaModel->obtenerCreditosVencidos($dias);

            $data = array_map(function($f) {
                $fecha = $f->fecha ?? null;
                $diasTranscurridos = 0;
                if ($fecha) $diasTranscurridos = (int)round((time() - strtotime($fecha)) / 86400);
                return [
                    'id'              => (int)$f->id,
                    'cliente_nombre'  => $f->cliente_nombre ?? 'SIN CLIENTE',
                    'placa'           => $f->placa ?? '---',
                    'modelo_vehiculo' => $f->modelo_vehiculo ?? 'N/A',
                    'saldo_pendiente' => (float)($f->saldo_pendiente ?? 0),
                    'fecha'           => $fecha,
                    'dias_vencido'    => $diasTranscurridos
                ];
            }, $facturas ?: []);

            return $this->jsonResponse(['success' => true, 'data' => $data, 'total' => count($data)]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage(), 'data' => []], 500);
        }
    }

    public function getDeudoresSummary() {
        try {
            RoleGuard::hasAccess(['ADMINISTRADOR']);
            $db = new Database();

            $db->query("SELECT 
                            COALESCE(SUM(v.saldo_pendiente), 0) as total_deuda,
                            COUNT(DISTINCT v.cliente_id) as cantidad_deudores
                        FROM table_facturas v
                        WHERE v.status = 'CREDITO' 
                          AND v.saldo_pendiente > 0.05");
            $resumen = $db->single();

            $db->query("SELECT v.id, v.saldo_pendiente, v.fecha,
                               COALESCE(c.nombre, 'SIN CLIENTE') as cliente_nombre,
                               COALESCE(vh.placa, v.placa, '---') as placa,
                               COALESCE(vh.modelo, v.modelo_vehiculo, 'N/A') as modelo_vehiculo
                        FROM table_facturas v
                        LEFT JOIN table_clientes c ON v.cliente_id = c.id
                        LEFT JOIN table_vehiculos vh ON v.placa = vh.placa
                        WHERE v.status = 'CREDITO' 
                          AND v.saldo_pendiente > 0.05
                        ORDER BY v.fecha DESC
                        LIMIT 20");
            $lista = $db->resultSet();

            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'resumen' => [
                        'total_deuda'        => (float)($resumen->total_deuda ?? 0),
                        'cantidad_deudores'  => (int)($resumen->cantidad_deudores ?? 0)
                    ],
                    'lista' => $lista ?: []
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function getFacturasCliente($clienteId = null) {
        try {
            RoleGuard::hasAccess(['ADMINISTRADOR']);
            if (!$clienteId) return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);

            $facturas = $this->facturaModel->obtenerFacturasCreditoPorCliente($clienteId);

            if (empty($facturas)) {
                return $this->jsonResponse([
                    'success' => true,
                    'data' => [
                        'cliente' => null,
                        'facturas' => [],
                        'totales' => ['total_deuda' => 0, 'cantidad_facturas' => 0]
                    ]
                ]);
            }

            $primera = $facturas[0];
            $cliente = [
                'id'        => $clienteId,
                'nombre'    => $primera->cliente_nombre ?? 'SIN CLIENTE',
                'telefono'  => $primera->cliente_telefono ?? '',
                'email'     => $primera->cliente_email ?? ''
            ];

            $totalDeuda = 0;
            foreach ($facturas as $f) $totalDeuda += (float)$f->saldo_pendiente;

            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'cliente' => $cliente,
                    'facturas' => $facturas,
                    'totales' => [
                        'total_deuda' => $totalDeuda,
                        'cantidad_facturas' => count($facturas)
                    ]
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function getItemsDevolucion($ventaId = null) {
        try {
            if (!$ventaId) return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            $db = new Database();
            $db->query("SELECT vd.id, vd.producto_id, vd.descripcion, vd.cantidad, 
                               vd.precio_unitario, vd.costo_unitario
                        FROM table_facturas_detalle vd
                        WHERE vd.factura_id = :vid AND vd.producto_id IS NOT NULL
                        ORDER BY vd.id");
            $db->bind(':vid', (int)$ventaId);
            $items = $db->resultSet();
            return $this->jsonResponse(['success' => true, 'items' => $items ?: []]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function listarDevoluciones() {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = (int)($_GET['offset'] ?? 0);
            $search = $_GET['q'] ?? null;
            $desde = $_GET['desde'] ?? null;
            $hasta = $_GET['hasta'] ?? null;

            $devolucionesModel = $this->model('Devoluciones');
            $result = $devolucionesModel->listarDevoluciones($limit, $offset, $search, $desde, $hasta);

            return $this->jsonResponse([
                'success' => true,
                'data' => $result['data'],
                'total' => $result['total'],
                'totalFiltrados' => $result['total']
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function procesarDevolucion() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') return;
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['venta_id']) || empty($input['detalle_id'])) {
                throw new Exception('Datos incompletos para la devolución');
            }

            // FIX P0-07: Delegar a ModelDevoluciones (fuente única de verdad).
            // Antes se llamaba a BillingService->procesarDevolucionSegura que
            // internamente usaba ModelFacturacion::procesarDevolucion (duplicado).
            // Ahora un solo modelo procesa devoluciones, y el motivo ya no se pierde.
            $devolucionesModel = $this->model('Devoluciones');
            $resultado = $devolucionesModel->procesarDevolucion(
                (int)$input['venta_id'],
                (int)$input['detalle_id'],
                $input['destino'] ?? 'STOCK',
                trim((string)($input['motivo'] ?? ''))
            );

            if ($resultado) {
                logAction('FACTURACION', 'PROCESAR_DEVOLUCION',
                    "Devolución procesada para Factura #{$input['venta_id']}, detalle #{$input['detalle_id']}");
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Devolución procesada correctamente']);
            } else {
                throw new Exception('No se pudo procesar la devolución');
            }
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }
}