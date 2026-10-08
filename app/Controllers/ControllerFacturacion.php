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

                error_log("FACTURACION::procesar - Payload: " . json_encode([
                    'cliente_id' => $datos['cliente_id'] ?? null,
                    'items_count' => is_array($datos['items'] ?? null) ? count($datos['items']) : 0,
                    'presupuesto_activo_id' => $datos['presupuesto_activo_id'] ?? null,
                    'iva_activo' => $datos['iva_activo'] ?? null,
                    'placa' => $datos['placa'] ?? null,
                    'origen' => $datos['origen'] ?? null,
                ]));

                $ventaId = $this->billingService->procesarVentaCompleta($datos, $_SESSION['user_id']);

                // Marcar presupuesto como CONVERTIDO (post-venta)
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

    /**
     * Sincroniza un borrador de factura desde el POS.
     */
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

                $totales = [
                    'subtotal' => $subtotal,
                    'iva' => $iva,
                    'total' => $total,
                    'saldo' => $saldo
                ];

                $status = 'PENDIENTE';

                $ventaId = $this->facturaModel->guardarCabeceraVenta($datos, $status, $totales, $_SESSION['user_id']);

                // Guardar items
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

                return $this->jsonResponse([
                    'success' => true,
                    'venta_id' => $ventaId
                ]);
            } catch (\Throwable $e) {
                error_log("Error en sincronizarBorrador: " . $e->getMessage());
                return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
        }
    }

    /**
     * Elimina un borrador de factura.
     */
    public function eliminarBorrador($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

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

    /**
     * Genera el PDF de una factura vía AJAX.
     */
    public function generarPdfAjax($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }
            return $this->jsonResponse([
                'success' => true,
                'pdf_url' => URLROOT . '/facturacion/imprimir/' . (int)$id
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Imprime una factura en PDF.
     */
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
     * Registra un abono a una factura a crédito.
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

                return $this->jsonResponse([
                    'success' => true,
                    'mensaje' => 'Abono registrado correctamente'
                ]);
            } else {
                throw new Exception('No se pudo registrar el abono');
            }
        } catch (\Throwable $e) {
            error_log("Error en registrarAbono: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * NOTIFICACIÓN DE CRÉDITO (CAMPANITA DEL NAVBAR)
     * Devuelve las facturas a crédito con saldo pendiente y +15 días de atraso.
     * 
     * Solo accesible para ADMINISTRADORES.
     * 
     * GET /facturacion/alertasCredito
     * 
     * Respuesta:
     * {
     *   success: true,
     *   data: [
     *     { id, cliente_nombre, placa, modelo_vehiculo, saldo_pendiente, fecha, dias_vencido },
     *     ...
     *   ],
     *   total: N
     * }
     */
    public function alertasCredito() {
        try {
            // Solo administradores
            RoleGuard::hasAccess(['ADMINISTRADOR']);

            $dias = 15;
            $facturas = $this->facturaModel->obtenerCreditosVencidos($dias);

            $data = array_map(function($f) {
                $fecha = $f->fecha ?? null;
                $diasTranscurridos = 0;
                if ($fecha) {
                    $diasTranscurridos = (int)round((time() - strtotime($fecha)) / 86400);
                }
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

            return $this->jsonResponse([
                'success' => true,
                'data'    => $data,
                'total'   => count($data)
            ]);
        } catch (\Throwable $e) {
            error_log("Error en alertasCredito: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'mensaje' => $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }

    /**
     * RESUMEN DE DEUDORES (TARJETA DEL DASHBOARD)
     * Devuelve TODAS las facturas a crédito con saldo pendiente,
     * sin importar los días de atraso (incluso 1 día cuenta).
     * 
     * Solo accesible para ADMINISTRADORES.
     * 
     * GET /facturacion/getDeudoresSummary
     * 
     * Respuesta:
     * {
     *   success: true,
     *   data: {
     *     resumen: { total_deuda, cantidad_deudores },
     *     lista: [ { id, cliente_nombre, placa, modelo_vehiculo, saldo_pendiente, fecha }, ... ]
     *   }
     * }
     */
    public function getDeudoresSummary() {
        try {
            // Solo administradores
            RoleGuard::hasAccess(['ADMINISTRADOR']);

            $db = new Database();

            // Resumen global
            $db->query("SELECT 
                            COALESCE(SUM(v.saldo_pendiente), 0) as total_deuda,
                            COUNT(DISTINCT v.cliente_id) as cantidad_deudores
                        FROM table_facturas v
                        WHERE v.status = 'CREDITO' 
                          AND v.saldo_pendiente > 0.05");
            $resumen = $db->single();

            // Lista de TODAS las facturas a crédito con saldo pendiente
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
            error_log("Error en getDeudoresSummary: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Devuelve los items de una factura aptos para devolución (solo repuestos).
     */
    public function getItemsDevolucion($ventaId = null) {
        try {
            if (!$ventaId) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $db = new Database();
            $db->query("SELECT vd.id, vd.producto_id, vd.descripcion, vd.cantidad, 
                               vd.precio_unitario, vd.costo_unitario
                        FROM table_facturas_detalle vd
                        WHERE vd.factura_id = :vid AND vd.producto_id IS NOT NULL
                        ORDER BY vd.id");
            $db->bind(':vid', (int)$ventaId);
            $items = $db->resultSet();

            return $this->jsonResponse([
                'success' => true,
                'items' => $items ?: []
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista el historial de devoluciones.
     */
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

    /**
     * Procesa una devolución de un ítem de factura.
     */
    public function procesarDevolucion() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') return;

        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['venta_id']) || empty($input['detalle_id'])) {
                throw new Exception('Datos incompletos para la devolución');
            }

            $resultado = $this->billingService->procesarDevolucionSegura([
                'factura_id' => (int)$input['venta_id'],
                'detalle_id' => (int)$input['detalle_id'],
                'destino' => $input['destino'] ?? 'STOCK'
            ]);

            if ($resultado) {
                logAction('FACTURACION', 'PROCESAR_DEVOLUCION',
                    "Devolución procesada para Factura #{$input['venta_id']}, detalle #{$input['detalle_id']}");

                return $this->jsonResponse([
                    'success' => true,
                    'mensaje' => 'Devolución procesada correctamente'
                ]);
            } else {
                throw new Exception('No se pudo procesar la devolución');
            }
        } catch (\Throwable $e) {
            error_log("Error en procesarDevolucion: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }
}