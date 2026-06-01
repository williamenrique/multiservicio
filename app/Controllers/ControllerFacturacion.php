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
        $reportModel = $this->model('Reportes');
        $data = [
            'titulo' => 'Nueva Facturación',
            'iva_defecto' => $config->iva ?? 0,
            'usuario_actual' => $_SESSION['user_nombre'],
            'user_role' => $_SESSION['user_role'],
            'user_staff_id' => $_SESSION['user_staff_id'] ?? null,
            'staff' => $reportModel->obtenerStaffSimple()
        ];

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
        return $this->jsonResponse($this->facturaModel->obtenerBorradoresCompleto());
    }

    /**
     * Procesa el guardado de la venta
     */
    public function procesar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            try {
                $db = new Database();
                $db->beginTransaction();
                
                $datos = json_decode(file_get_contents('php://input'), true);
                
                // Si el usuario es un MECANICO, forzamos que el mecanico_id sea el suyo automáticamente
                if ($_SESSION['user_role'] === 'MECANICO') {
                    $datos['mecanico_id'] = $_SESSION['user_staff_id'];
                }

                // Si el mecánico no viene en el JSON (posiblemente porque el select está oculto o no seleccionado),
                // pero es una factura existente (borrador), intentamos rescatarlo de la base de datos.
                $idReal = $datos['id_db'] ?? null;
                if (empty($datos['mecanico_id']) && $idReal) {
                    $borradorExistente = $this->facturaModel->obtenerBorradorPorId($idReal);
                    if ($borradorExistente && !empty($borradorExistente->mecanico_id)) {
                        $datos['mecanico_id'] = $borradorExistente->mecanico_id;
                    }
                }

                $v = new Validator($datos);
                $v->required(['items', 'pago_efectivo', 'pago_transferencia', 'mecanico_id'])
                  ->array('items');

                if (!$v->success()) {
                    throw new Exception(implode(" ", $v->getErrors()));
                }

                $facturaModel = new ModelFacturacion($db);
                $invModel = new ModelInventario($db);
                $cajaModel = new ModelCaja($db);

                // 1. Cálculos de totales y determinación de Status
                $subtotal = 0;
                foreach($datos['items'] as $it) $subtotal += ($it['precio'] * $it['cantidad']);
                $tasaIva = (float)($datos['tasa_iva'] ?? 0);
                $iva = ($datos['aplicar_iva'] ?? false) ? ($subtotal * ($tasaIva / 100)) : 0;
                $totalVenta = $subtotal + $iva;
                $saldoPendiente = $totalVenta - ((float)$datos['pago_efectivo'] + (float)$datos['pago_transferencia']);
                
                // Asegurar que el mecánico venga del JSON del frontend
                $mecanicoId = !empty($datos['mecanico_id']) ? $datos['mecanico_id'] : null;
                $status = ($saldoPendiente > 0.05) ? 'CREDITO' : 'COMPLETADO';
                $totales = ['subtotal' => $subtotal, 'iva' => $iva, 'total' => $totalVenta, 'saldo' => max(0, $saldoPendiente)];

                // 2. Guardar Cabecera
                $datos['mecanico_id'] = $mecanicoId; // Forzamos la reinyección por si se perdió
                $ventaId = $facturaModel->guardarCabeceraVenta($datos, $status, $totales);

                // 3. Limpiar y registrar detalles + actualizar STOCK y KARDEX
                $db->query("DELETE FROM table_ventas_detalle WHERE venta_id = :vid");
                $db->bind(':vid', $ventaId);
                $db->execute();

                foreach ($datos['items'] as $item) {
                    $db->query("INSERT INTO table_ventas_detalle (venta_id, producto_id, descripcion, cantidad, precio_unitario, costo_unitario) 
                                VALUES (:vid, :pid, :desc, :cant, :pre, :costo)");
                    $db->bind(':vid', $ventaId);
                    $db->bind(':pid', $item['tipo'] === 'PRODUCTO' ? $item['id'] : null);
                    $db->bind(':desc', mb_strtoupper($item['nombre'], 'UTF-8'));
                    $db->bind(':cant', $item['cantidad']);
                    $db->bind(':pre', $item['precio']);
                    $db->bind(':costo', $item['ultimo_costo'] ?? 0);
                    $db->execute();

                    if ($item['tipo'] === 'PRODUCTO') {
                        // Descontar Stock y registrar en Kardex
                        $db->query("UPDATE table_inventario SET stock = stock - :cant WHERE id = :pid");
                        $db->bind(':cant', $item['cantidad']);
                        $db->bind(':pid', $item['id']);
                        $db->execute();
                        $invModel->registrarMovimiento($item['id'], 'SALIDA_VENTA', $item['cantidad'], $ventaId, "Venta Factura #$ventaId");
                    }
                }

                // 4. Registrar movimiento en caja si hay pago en efectivo
                if ((float)$datos['pago_efectivo'] > 0) {
                    $cajaModel->registrarMovimiento([
                        'tipo' => 'INGRESO', 'monto' => $datos['pago_efectivo'], 'metodo_pago' => 'EFECTIVO',
                        'referencia_id' => $ventaId, 'concepto' => "VENTA FACTURA #$ventaId"
                    ]);
                }

                $db->commit();
                return $this->jsonResponse([
                    'success' => true,
                    'mensaje' => 'Venta realizada con éxito',
                    'venta_id' => $ventaId
                ]);
            } catch (Exception $e) {
                if (isset($db)) $db->rollBack();
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

                // Misma lógica para sincronizar borradores: mantener el mecánico si ya existe
                $idReal = $datos['id_db'] ?? null;
                if (empty($datos['mecanico_id']) && $idReal) {
                    $borradorExistente = $this->facturaModel->obtenerBorradorPorId($idReal);
                    if ($borradorExistente && !empty($borradorExistente->mecanico_id)) {
                        $datos['mecanico_id'] = $borradorExistente->mecanico_id;
                    }
                }

                // Cálculos rápidos para el borrador
                $subtotal = 0;
                foreach($datos['items'] as $it) {
                    $subtotal += ($it['precio'] * $it['cantidad']);
                }
                $tasaIva = (float)($datos['tasa_iva'] ?? 0);
                $iva = ($datos['aplicar_iva'] ?? false) ? ($subtotal * ($tasaIva / 100)) : 0;
                $totales = [
                    'subtotal' => $subtotal,
                    'iva' => $iva,
                    'total' => $subtotal + $iva,
                    'saldo' => $subtotal + $iva
                ];
                
                // Inyectar el mecánico en los datos para el modelo
                $datos['mecanico_id'] = !empty($datos['mecanico_id']) ? $datos['mecanico_id'] : null;

                // Guardar cabecera usando el modelo inyectando la conexión actual
                $tempModel = new ModelFacturacion($db);
                $ventaId = $tempModel->guardarCabeceraVenta($datos, 'PENDIENTE', $totales);

                // Limpiar y actualizar items del borrador
                $db->query("DELETE FROM table_ventas_detalle WHERE venta_id = :vid");
                $db->bind(':vid', $ventaId);
                $db->execute();

                foreach ($datos['items'] as $item) {
                    $db->query("INSERT INTO table_ventas_detalle (venta_id, producto_id, descripcion, cantidad, precio_unitario, costo_unitario) 
                                VALUES (:vid, :pid, :desc, :cant, :pre, :costo)");
                    $db->bind(':vid', $ventaId);
                    $db->bind(':pid', $item['tipo'] === 'PRODUCTO' ? $item['id'] : null);
                    $db->bind(':desc', mb_strtoupper($item['nombre'], 'UTF-8'));
                    $db->bind(':cant', $item['cantidad']);
                    $db->bind(':pre', $item['precio']);
                    $db->bind(':costo', $item['tipo'] === 'PRODUCTO' ? ($item['ultimo_costo'] ?? 0) : 0);
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
     * Genera o sirve el PDF de la Factura (URL: /facturacion/imprimir/ID)
     */
    public function imprimir($id = null) {
        if (!$id) {
            die("ID de factura o archivo no proporcionado.");
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
            die("La factura #$id no existe o el documento solicitado no se encontró.");
        }

        $pdfService = new PdfService();
        $pdfService->generarDocumento('factura', [
            'titulo_documento' => 'Factura de Venta',
            'documento_id' => $venta->id,
            'venta' => $venta
        ], 'Factura_' . $id . '.pdf'); // Stream to browser por defecto
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
                throw new Exception('Datos de devolución inválidos');
            }

            $this->billingService->procesarDevolucionSegura($input);
            return $this->jsonResponse(['success' => true, 'mensaje' => 'Devolución procesada correctamente']);

        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }
}