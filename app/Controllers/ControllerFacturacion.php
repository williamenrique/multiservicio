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

        // Si recibimos una Orden de Servicio, cargamos sus datos para la factura
        if (isset($_GET['orden_id'])) {
            $ordenModel = $this->model('Orden');
            $orden = $ordenModel->obtenerDetalleOrden($_GET['orden_id']);
            if ($orden) {
                // Asegurar que el ID del cliente sea una cadena limpia para el selector
                $orden->cliente_id = trim($orden->cliente_id ?? '');

                // IMPORTANTE: Si ya existe un borrador para esta orden, traer las observaciones de la FACTURA (Salida)
                $borrador = $this->facturaModel->obtenerBorradorPorOrden($_GET['orden_id']);
                if ($borrador && !empty($borrador->observaciones)) {
                    $orden->observaciones_factura = $borrador->observaciones;
                }

                $data['orden'] = $orden;
            }
        }

        $this->view('facturacion/index', $data);
    }

    /**
     * Endpoint para buscar items en tiempo real
     */
    public function buscarItems() {
        $term = $_GET['term'] ?? '';
        $items = $this->facturaModel->buscarItems($term);
        return $this->jsonResponse($items);
    }

    /**
     * Lista todos los borradores activos en el sistema (Global)
     */
    public function listarBorradores() {
        try {
            return $this->jsonResponse($this->facturaModel->obtenerBorradoresCompleto());
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API para obtener un borrador por ID de Orden (AJAX)
     */
    public function obtenerPorOrden($id) {
        try {
            $borrador = $this->facturaModel->obtenerBorradorPorOrden($id);
            if (!$borrador) return $this->jsonResponse(['success' => false, 'mensaje' => 'No hay borrador para esta orden'], 404);
            return $this->jsonResponse(['success' => true, 'data' => $borrador]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Procesa el guardado de la venta
     */
    public function procesar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            try {
                $datos = json_decode(file_get_contents('php://input'), true);
                
                if ($_SESSION['user_role'] === 'MECANICO') {
                    $datos['mecanico_id'] = $_SESSION['user_staff_id'];
                }

                // Normalizar datos para ventas de mostrador (asegurar campos mínimos para BillingService)
                $datos['iva_activo'] = $datos['iva_activo'] ?? false;
                $datos['placa'] = !empty($datos['placa']) ? strtoupper(trim($datos['placa'])) : '';

                $v = new Validator($datos);
                $v->required(['items'])->array('items');

                if (!empty($datos['placa'])) {
                    $v->required(['mecanico_id']);
                }

                if (!$v->success()) {
                    throw new Exception(implode(" ", $v->getErrors()));
                }

                // EVITAR DUPLICADOS: Si es una Orden de Servicio, verificamos si ya existe un borrador (id_db)
                if (!empty($datos['orden_id']) && empty($datos['id_db'])) {
                    $borradorExistente = $this->facturaModel->obtenerBorradorPorOrden($datos['orden_id']);
                    if ($borradorExistente) {
                        $datos['id_db'] = $borradorExistente->id;
                    }
                }

                // VALIDACIÓN DE CONSISTENCIA (Reemplaza al Trigger tg_evitar_doble_facturacion)
                // Si es una Orden de Servicio, verificamos que no tenga ya una factura FINALIZADA.
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

                if (!empty($datos['orden_id'])) {
                    logAction('TALLER', 'FINALIZAR_ORDEN', "Venta procesada para O.S. #{$datos['orden_id']}");
                }

                return $this->jsonResponse([
                    'success' => true,
                    'mensaje' => 'Venta realizada con éxito',
                    'venta_id' => $ventaId
                ]);
            } catch (Exception $e) {
                return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
        }
    }

    /**
     * Sincroniza un borrador con la base de datos para reservar stock en tiempo real.
     */
    public function sincronizarBorrador() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            try {
                $db = new Database();
                $db->beginTransaction();
                
                $datos = json_decode(file_get_contents('php://input'), true);
                
                // Si el usuario es un MECANICO, forzamos que el mecanico_id sea el suyo en el borrador
                if ($_SESSION['user_role'] === 'MECANICO') {
                    $datos['mecanico_id'] = $_SESSION['user_staff_id'];
                }

                // Si no hay mecánico en el input pero hay una Orden de Servicio vinculada,
                // intentamos recuperar el mecánico desde la orden.
                if (empty($datos['mecanico_id']) && !empty($datos['orden_id'])) {
                    $db->query("SELECT mecanico_id FROM table_ordenes_servicio WHERE id = :oid");
                    $db->bind(':oid', $datos['orden_id']);
                    $resOS = $db->single();
                    if ($resOS) $datos['mecanico_id'] = $resOS->mecanico_id;
                }

                // Cálculos rápidos para el borrador
                $subtotal = 0;
                foreach($datos['items'] as $it) {
                    $subtotal += ($it['precio'] * $it['cantidad']);
                }
                $tasaIva = (float)($datos['tasa_iva'] ?? 0);
                $iva = ($datos['aplicar_iva'] ?? false) ? ($subtotal * ($tasaIva / 100)) : 0;
                
                $pef = (float)($datos['pago_efectivo'] ?? 0);
                $ptra = (float)($datos['pago_transferencia'] ?? 0);
                $totalFinal = $subtotal + $iva;

                $totales = [
                    'subtotal' => $subtotal,
                    'iva' => $iva,
                    'total' => $totalFinal,
                    'saldo' => max(0, $totalFinal - ($pef + $ptra))
                ];
                
                // Inyectar el mecánico en los datos para el modelo
                $datos['mecanico_id'] = !empty($datos['mecanico_id']) ? $datos['mecanico_id'] : null;

                // Guardar cabecera usando el modelo inyectando la conexión actual
                $tempModel = new ModelFacturacion($db);
                $ventaId = $tempModel->guardarCabeceraVenta($datos, 'PENDIENTE', $totales, $_SESSION['user_id']);

                // Limpiar y actualizar items del borrador
                $db->query("DELETE FROM table_facturas_detalle WHERE factura_id = :vid");
                $db->bind(':vid', $ventaId);
                $db->execute();

                foreach ($datos['items'] as $item) {
                    $db->query("INSERT INTO table_facturas_detalle (factura_id, producto_id, mecanico_id, descripcion, cantidad, precio_unitario, costo_unitario) 
                                VALUES (:fid, :pid, :mid, :desc, :cant, :pre, :costo)");
                    $db->bind(':fid', $ventaId);
                    $db->bind(':pid', $item['tipo'] === 'PRODUCTO' ? $item['id'] : null);
                    $db->bind(':mid', $datos['mecanico_id'] ?? null);
                    $db->bind(':desc', mb_strtoupper($item['nombre'], 'UTF-8'));
                    $db->bind(':cant', $item['cantidad']);
                    $db->bind(':pre', $item['precio']);
                    $db->bind(':costo', $item['tipo'] === 'PRODUCTO' ? ($item['costo_promedio'] ?? 0) : 0);
                    $db->execute();
                }

                $db->commit();
                return $this->jsonResponse(['success' => true, 'venta_id' => $ventaId]);
            } catch (Exception $e) {
                if(isset($db)) $db->rollBack();
                return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
        }
    }

    /**
     * Elimina un borrador de la base de datos verificando permisos por rol
     */
    public function eliminarBorrador($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            
            try {
                $borrador = $this->facturaModel->obtenerBorradorPorId($id);
                if (!$borrador) {
                    return $this->jsonResponse(['success' => false, 'mensaje' => 'Borrador no encontrado']);
                }

                $resultado = $this->facturaModel->eliminarBorrador($id);
                return $this->jsonResponse(['success' => $resultado]);
            } catch (Exception $e) {
                return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
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
            $doc_name = $venta->id_formateado ?: 'Factura_' . $id;
            $filename = $doc_name . '_' . time() . '.pdf';
            $filePath = $pdfService->generarDocumento('factura', [
                'titulo_pestaña' => 'Factura de Venta',
                'titulo_documento' => 'Factura de Venta',
                'documento_id' => $doc_name,
                'venta' => $venta
            ], $filename, false);

            return $this->jsonResponse(['success' => true, 'pdf_url' => URLROOT . '/' . $filePath]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Genera o sirve el PDF de la Factura (URL: /facturacion/imprimir/ID)
     */
    public function imprimir($id = null) {
        if (!$id) {
            throw new AppException("ID de factura o archivo no proporcionado.", 400);
        }

        // 1. Si el parámetro es un nombre de archivo (contiene .pdf), intentamos servirlo directamente
        if (strpos($id, '.pdf') !== false) {
            $filePath = APPROOT . '/../public/temp_pdfs/' . $id;
            if (file_exists($filePath)) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $id . '"');
                readfile($filePath);
                exit;
            }
        }

        // 2. Si es un ID numérico o el archivo anterior no existe, generamos el PDF en tiempo real
        $venta = $this->facturaModel->obtenerVentaCompleta($id);
        if (!$venta) {
            throw new AppException("La factura #$id no existe o el documento solicitado no se encontró.", 404);
        }

        $pdfService = new PdfService();
        $doc_name = $venta->id_formateado ?: 'Factura_' . $id;
        $pdfService->generarDocumento('factura', [
            'titulo_pestaña' => 'Factura de Venta',
            'titulo_documento' => 'Factura de Venta',
            'documento_id' => $doc_name,
            'venta' => $venta
        ], $doc_name . '.pdf'); // Stream to browser por defecto
        exit;
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
        try {
            $data = $this->facturaModel->obtenerCreditosVencidos(15);
            return $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint para obtener el resumen de deudores para el dashboard (AJAX)
     */
    public function getDeudoresSummary() {
        RoleGuard::isAdmin();
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            $data = $this->facturaModel->obtenerAuditoriaTrabajos($limit, $offset);
            return $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene los items de una factura que son aptos para devolución (solo productos)
     */
    public function getItemsDevolucion($id) {
        $venta = $this->facturaModel->obtenerVentaCompleta($id);
        if (!$venta) return $this->jsonResponse(['success' => false, 'mensaje' => 'Venta no encontrada'], 404);

        $items = array_filter($venta->items ?? [], function($it) {
            return !empty($it->producto_id);
        });
        return $this->jsonResponse(['success' => true, 'items' => array_values($items)]);
    }

    /**
     * Lista las devoluciones realizadas (Endpoint para el reporte de historial)
     */
    public function listarDevoluciones() {
        RoleGuard::isAdmin();
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $reporteModel = $this->model('Reportes');
        $rows = $reporteModel->obtenerReporteDevoluciones($desde, $hasta);
        $total = $reporteModel->contarDevoluciones($desde, $hasta);

        return $this->jsonResponse([
            'success' => true, 
            'data' => $rows, 
            'total' => $total
        ]);
    }

    public function procesarDevolucion() {
        RoleGuard::isAdmin();
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $v = new Validator($input);
            $v->required(['venta_id', 'detalle_id', 'destino'])
              ->in('destino', ['STOCK', 'DANADO']); // Ajustado para coincidir con el valor del frontend

            if (!$v->success()) {
                throw new Exception(implode(" ", $v->getErrors()));
            }

            $resultado = $this->facturaModel->procesarDevolucion($input['venta_id'], $input['detalle_id'], $input['destino']);

            return $this->jsonResponse([
                'success' => $resultado,
                'mensaje' => $resultado ? 'Devolución procesada con éxito y stock actualizado.' : 'No se pudo procesar la devolución.'
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }
}