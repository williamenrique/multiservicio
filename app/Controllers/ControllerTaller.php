<?php
class ControllerTaller extends Controller {
    private $ordenModel;
    private $vehiculoModel;

    public function __construct() {
        AuthGuard::handle();
        $this->ordenModel = $this->model('Orden');
        $this->vehiculoModel = $this->model('Vehiculo');
    }

    public function index() {
        $ordenesActivas = $this->ordenModel->obtenerOrdenesActivas();
        $resumen = $this->ordenModel->obtenerResumenTaller();
        $this->view('taller/index', [
            'titulo' => 'Panel Operativo del Taller',
            'ordenes' => $ordenesActivas,
            'stats' => $resumen
        ]);
    }

    public function nuevaOrden() {
        $reportModel = $this->model('Reportes');
        
        $data = [
            'titulo' => 'Nueva Orden de Servicio',
            'staff' => $reportModel->obtenerStaffSimple(),
            'vehiculo' => null,
            'cliente' => null
        ];
        
        // Si viene una placa por parámetro GET, buscar el vehículo y cliente
        $placa = isset($_GET['placa']) ? strtoupper(trim($_GET['placa'])) : '';
        if (!empty($placa)) {
            $vehiculo = $this->vehiculoModel->buscarPorPlaca($placa);
            if ($vehiculo) {
                $data['vehiculo'] = $vehiculo;
                
                // Buscar información del cliente
                $clienteModel = $this->model('Cliente');
                if ($vehiculo->cliente_id) {
                    $data['cliente'] = $clienteModel->obtenerPorId($vehiculo->cliente_id);
                }
            }
        }
        
        $this->view('taller/nueva_orden', $data);
    }

    /**
     * Vista del historial de órdenes finalizadas
     */
    public function cerradas() {
        $this->view('taller/cerradas', [
            'titulo' => 'Historial de Órdenes Finalizadas'
        ]);
    }

    /**
     * Genera el comprobante PDF de la Orden de Servicio
     * URL: /taller/imprimir/ID
     */
    public function imprimir($id) {
        // Primero, verificar si esta orden ya tiene una factura final (COMPLETADO o CREDITO)
        // Si es así, redirigimos a la impresión de la factura para centralizar la lógica.
        $db = new Database();
        $db->query("SELECT id FROM table_facturas WHERE orden_id = :oid AND status IN ('COMPLETADO', 'CREDITO') ORDER BY id DESC LIMIT 1");
        $db->bind(':oid', $id);
        $facturaAsociada = $db->single();

        if ($facturaAsociada) {
            // Si existe una factura final, redirigir a la ruta de impresión de factura
            redirect('facturacion/imprimir/' . $facturaAsociada->id);
        } else {
            // Si no hay factura final, o la factura está PENDIENTE/ANULADO,
            // entonces generamos el PDF de la Orden de Servicio.
            $orden = $this->ordenModel->obtenerDetalleOrden($id);
            
            if (!$orden) {
                die("La orden de servicio #$id no existe.");
            }

            // Mapeo de datos para compatibilidad con la vista orden.php
            $orden->fecha_entrada = $orden->fecha_ingreso;
            $orden->observaciones_entrada = $orden->diagnostico_entrada;
            
            // Cargar el Checklist de entrada para el PDF
            $orden->checklist = $this->ordenModel->obtenerChecklist($id);
            
            // Cargar los ítems de la orden (si existen en el borrador de factura)
            $orden->items = $this->ordenModel->obtenerItemsOrden($id);

            $empresa = $this->model('Empresa')->obtenerConfiguracion();

            $pdfService = new PdfService();
            $pdfService->generarDocumento('orden', [
                'titulo_pestaña' => 'Orden de Servicio',
                'orden' => $orden,
                'empresa' => $empresa,
                // Las variables de cabecera (titulo_documento, documento_numero, etc.) se definirán dentro de orden.php
            ], 'Orden_Servicio_' . $id . '.pdf');
            exit;
        }
    }

    /**
     * API para listar órdenes entregadas (Paginado)
     */
    public function listarCerradas() {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $search = $_GET['q'] ?? null;

        $items = $this->ordenModel->obtenerOrdenesCerradas($limit, $offset, $search);
        $total = $this->ordenModel->contarCerradas();
        $totalFiltrados = $search ? $this->ordenModel->contarCerradas($search) : $total;

        return $this->jsonResponse([
            'success' => true,
            'data' => $items ?: [],
            'total' => $total,
            'totalFiltrados' => $totalFiltrados
        ]);
    }

    /**
     * Muestra la hoja de vida de un vehículo por placa
     */
    public function historial($tipo = 'placa', $valor = '') {
        // Compatibilidad: si solo llega un parámetro, es la placa
        if (empty($valor)) {
            $valor = $tipo;
            $tipo = 'placa';
        }

        $titulo = "Historial";
        $vehiculo = null;
        $entidad = null;
        $historial = [];

        switch (strtoupper($tipo)) {
            case 'MECANICO':
                $staffModel = $this->model('Personal');
                $entidad = $staffModel->obtenerPorId($valor);
                $historial = $this->ordenModel->obtenerHistorialExtendido('MECANICO', $valor);
                $titulo = "Órdenes del Técnico: " . ($entidad->nombre ?? 'Desconocido');
                break;

            case 'CLIENTE':
                $clienteModel = $this->model('Cliente');
                $entidad = $clienteModel->obtenerPorId($valor);
                $historial = $this->ordenModel->obtenerHistorialExtendido('CLIENTE', $valor);
                $titulo = "Historial del Cliente: " . ($entidad->nombre ?? 'Desconocido');
                break;

            case 'ORDEN':
                // Si busca una orden específica, encontramos su placa y mostramos el historial de ese vehículo
                $db = new Database();
                $db->query("SELECT placa FROM table_ordenes_servicio WHERE id = :id");
                $db->bind(':id', $valor);
                $res = $db->single();
                if ($res) redirect("taller/historial/placa/{$res->placa}");
                break;

            default: // PLACA
                $vehiculo = $this->vehiculoModel->buscarPorPlaca($valor);
                $historial = $vehiculo ? $this->vehiculoModel->obtenerHistorial($vehiculo->placa) : [];
                $titulo = "Hoja de Vida: " . strtoupper($valor);
                break;
        }

        // Enriquecer cada registro del historial con su checklist e ítems facturados
        if (!empty($historial)) {
            $facturaModel = $this->model('Facturacion');
            $db = new Database();
            foreach ($historial as &$itemH) {
                // Cargar Checklist
                $itemH->checklist_data = $this->ordenModel->obtenerChecklist($itemH->id);
                
                // Buscar si tiene factura para traer los repuestos/servicios
                $db->query("SELECT id FROM table_facturas WHERE orden_id = :oid AND status != 'ANULADO' ORDER BY id DESC LIMIT 1");
                $db->bind(':oid', $itemH->id);
                $resFac = $db->single();
                
                $itemH->items_facturados = [];
                if ($resFac) {
                    $vDetalle = $facturaModel->obtenerVentaCompleta($resFac->id);
                    $itemH->items_facturados = $vDetalle->items ?? [];
                }
            }
        }

        // CORRECCIÓN: La vista está en taller/historial.php directamente
        $this->view('taller/historial', [
            'titulo' => $titulo,
            'vehiculo' => $vehiculo,
            'entidad' => $entidad,
            'historial' => $historial,
            'tipo' => strtoupper($tipo)
        ]);
    }

    /**
     * API para búsqueda dinámica en el panel de taller (AJAX)
     */
    public function buscar() {
        $term = trim($_GET['q'] ?? '');
        if (strlen($term) < 2) return $this->jsonResponse(['success' => true, 'results' => []]);

        $db = new Database();
        $results = [];

        // 1. Buscar Órdenes
        $db->query("SELECT id, placa FROM table_ordenes_servicio WHERE id LIKE :term OR placa LIKE :term LIMIT 3");
        $db->bind(':term', "%$term%");
        foreach($db->resultSet() as $r) {
            $results[] = ['id' => $r->id, 'tipo' => 'orden', 'title' => "Orden #{$r->id}", 'subtitle' => "Placa vinculada: {$r->placa}", 'icon' => 'file-text'];
        }

        // 2. Buscar Clientes
        $db->query("SELECT id, nombre FROM table_clientes WHERE nombre LIKE :term OR id LIKE :term LIMIT 3");
        $db->bind(':term', "%$term%");
        foreach($db->resultSet() as $r) {
            $results[] = ['id' => $r->id, 'tipo' => 'cliente', 'title' => $r->nombre, 'subtitle' => "Cliente ID: {$r->id}", 'icon' => 'user'];
        }

        // 3. Buscar Mecánicos
        $db->query("SELECT id, nombre FROM table_staff WHERE cargo LIKE '%MECANICO%' AND (nombre LIKE :term OR id LIKE :term) LIMIT 3");
        $db->bind(':term', "%$term%");
        foreach($db->resultSet() as $r) {
            $results[] = ['id' => $r->id, 'tipo' => 'mecanico', 'title' => $r->nombre, 'subtitle' => "Técnico Especialista", 'icon' => 'wrench'];
        }

        // 4. Buscar Placas
        $db->query("SELECT placa, marca, modelo FROM table_vehiculos WHERE placa LIKE :term LIMIT 3");
        $db->bind(':term', "%$term%");
        foreach($db->resultSet() as $r) {
            $results[] = ['id' => $r->placa, 'tipo' => 'placa', 'title' => $r->placa, 'subtitle' => "{$r->marca} {$r->modelo}", 'icon' => 'truck'];
        }

        return $this->jsonResponse(['success' => true, 'results' => $results]);
    }

    /**
     * Procesa la creación de una nueva Orden de Servicio
     */
    public function guardarOrden() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            // Punto 3: Asignación de mecánico. Se respeta si viene del input (asignado por Admin/Cajero).
            // Si está vacío y el usuario es mecánico, se auto-asigna como responsable.
            if (empty($input['mecanico_id']) && $_SESSION['user_role'] === 'MECANICO') {
                $input['mecanico_id'] = $_SESSION['user_staff_id'];
            }

            // Lógica: Si el vehículo no existe, se registra primero
            $vehiculo = $this->vehiculoModel->buscarPorPlaca($input['placa']);
            
            if (!$vehiculo) {
                // Validar que el cliente exista antes de registrar el vehículo
                $clienteModel = $this->model('Cliente');
                if (!$clienteModel->obtenerPorId($input['cliente_id'])) {
                    return $this->jsonResponse(['success' => false, 'error' => "El cliente con ID {$input['cliente_id']} no existe. Por favor, regístrelo primero en el módulo de Clientes."], 404);
                }
                if (!$this->vehiculoModel->registrar($input)) {
                    return $this->jsonResponse(['success' => false, 'error' => "Error al registrar el vehículo."]);
                }
            } else {
                $input['cliente_id'] = $vehiculo->cliente_id;
            }

            // En el esquema 2.0 la relación es por PLACA, no por un ID numérico
            $input['placa'] = strtoupper(trim($input['placa']));
            $ordenId = $this->ordenModel->crear($input);
            
            if ($ordenId) {
                // Guardar Checklist de entrada
                if (!empty($input['checklist'])) {
                    $this->ordenModel->guardarChecklist($ordenId, $input['checklist']);
                }

                // Punto 1: Guardar ítems dinámicos.
                // En el esquema 2.0, los ítems (repuestos/servicios) de una O.S. se persisten como 
                // un borrador de factura vinculado para reservar stock y preparar el cobro.
                if (!empty($input['items'])) {
                    $this->sincronizarItemsOrden($ordenId, $input);
                }

                logAction('TALLER', 'CREATE_OS', "Nueva O.S. #$ordenId para placa {$input['placa']}");

                // Enviar email de notificación al cliente
                try {
                    $ordenCreada = $this->ordenModel->obtenerDetalleOrden($ordenId);
                    if ($ordenCreada && !empty($ordenCreada->cliente_email)) {
                        $emailService = new \App\Services\EmailService();
                        $itemsEmail = [];
                        $totalEmail = 0;
                        if (!empty($input['items'])) {
                            foreach ($input['items'] as $it) {
                                $precio = (float)($it['precio'] ?? 0);
                                $cant = (int)($it['cantidad'] ?? 0);
                                // Solo incluir ítems con cantidad real
                                if ($cant <= 0) continue;
                                $sub = $precio * $cant;
                                $itemsEmail[] = [
                                    'descripcion' => $it['descripcion'] ?? 'Ítem',
                                    'cantidad' => $cant,
                                    'precio' => $precio,
                                    'subtotal' => $sub
                                ];
                                $totalEmail += $sub;
                            }
                        }
                        // Construir datos del email — solo incluir items si hay algo real
                        $datosEmail = [
                            'cliente_email' => $ordenCreada->cliente_email,
                            'cliente_nombre' => $ordenCreada->cliente_nombre ?? 'Cliente',
                            'orden_id' => $ordenId,
                            'id_formateado' => str_pad($ordenId, 6, '0', STR_PAD_LEFT),
                            'placa' => $input['placa'],
                            'vehiculo' => ($ordenCreada->marca ?? '') . ' ' . ($ordenCreada->modelo ?? ''),
                            'kilometraje' => $input['kilometraje'] ?? '',
                            'nivel_combustible' => $input['nivel_combustible'] ?? '',
                            'mecanico_nombre' => $ordenCreada->mecanico_nombre ?? 'Por asignar',
                            'fecha_ingreso' => date('d/m/Y H:i'),
                            'fecha_entrega_estimada' => !empty($input['fecha_entrega']) ? date('d/m/Y', strtotime($input['fecha_entrega'])) : 'No especificada',
                            'observaciones' => $input['observaciones'] ?? '',
                        ];
                        if (!empty($itemsEmail)) {
                            $datosEmail['items'] = $itemsEmail;
                            $datosEmail['total'] = $totalEmail;
                        }
                        $emailService->notificarOrdenServicioCreada($datosEmail);
                    }
                } catch (\Exception $e) {
                    error_log('Error enviando email de orden creada: ' . $e->getMessage());
                }

                // ── Enviar WhatsApp de notificación al cliente ──
                $waOk = true;
                $waMsg = '';
                try {
                    if ($ordenCreada && !empty($ordenCreada->cliente_telefono)) {
                        $waService = new \App\Services\WhatsAppService();
                        $datosWA = $datosEmail;
                        $datosWA['cliente_telefono'] = $ordenCreada->cliente_telefono;
                        $waResult = $waService->notificarOrdenServicioCreada($datosWA);
                        $waOk = $waResult['success'] ?? false;
                        $waMsg = $waResult['mensaje'] ?? '';
                    }
                } catch (\Exception $e) {
                    $waOk = false;
                    $waMsg = $e->getMessage();
                    error_log('Error enviando WhatsApp de orden creada: ' . $e->getMessage());
                }

                $waNotice = $waOk ? '' : ' (WhatsApp no enviado: ' . ($waMsg ?: 'sin teléfono') . ')';
                return $this->jsonResponse(['success' => true, 'id' => $ordenId, 'mensaje' => 'Orden creada correctamente' . $waNotice, 'wa_success' => $waOk, 'wa_mensaje' => $waMsg]);
            }
            return $this->jsonResponse(['success' => false, 'error' => 'No se pudo crear la orden']);
        }
    }

    /**
     * Helper para persistir ítems dinámicos vinculados a la OS en la tabla de facturación (Borrador).
     */
    private function sincronizarItemsOrden($ordenId, $input) {
        try {
            $modelFacturacion = $this->model('Facturacion');
            
            // En el esquema 2.0, si ya existe un borrador para esta orden, lo reutilizamos
            $db = new Database();
            $db->query("SELECT id FROM table_facturas WHERE orden_id = :oid AND status = 'PENDIENTE' LIMIT 1");
            $db->bind(':oid', $ordenId);
            $borradorExistente = $db->single();
            $facturaId = $borradorExistente ? $borradorExistente->id : null;
            
            $subtotal = 0;
            $itemsArr = $input['items'] ?? [];
            foreach ($itemsArr as $item) {
                $subtotal += ((float)($item['precio'] ?? 0) * (int)($item['cantidad'] ?? 0));
            }

            $datosFactura = [
                'id_db' => $facturaId,
                'orden_id' => $ordenId,
                'cliente_id' => $input['cliente_id'],
                'placa' => $input['placa'],
                'modelo' => $input['modelo'] ?? '',
                'pago_efectivo' => 0,
                'pago_transferencia' => 0,
                'mecanico_id' => $input['mecanico_id'] ?? null
            ];

            $totales = [
                'subtotal' => $subtotal,
                'iva' => 0,
                'total' => $subtotal,
                'saldo' => $subtotal
            ];

            $ventaId = $modelFacturacion->guardarCabeceraVenta($datosFactura, 'PENDIENTE', $totales, $_SESSION['user_id']);

            // Limpiamos items previos si es una actualización de borrador
            if ($facturaId) {
                $db->query("DELETE FROM table_facturas_detalle WHERE factura_id = :fid");
                $db->bind(':fid', $ventaId);
                $db->execute();
            }

            foreach ($itemsArr as $item) {
                // Saltar items que no tengan datos válidos
                if (empty($item['nombre']) && empty($item['id'])) continue;

                $esProducto = (strtoupper($item['tipo'] ?? '') === 'PRODUCTO');
                $db->query("INSERT INTO table_facturas_detalle (factura_id, producto_id, mecanico_id, descripcion, cantidad, precio_unitario, costo_unitario) 
                            VALUES (:fid, :pid, :mid, :desc, :cant, :pre, :costo)");
                $db->bind(':fid', $ventaId);
                $db->bind(':pid', $esProducto ? $item['id'] : null);
                $db->bind(':mid', $input['mecanico_id'] ?? null);
                $db->bind(':desc', mb_strtoupper($item['nombre'] ?? '', 'UTF-8'));
                $db->bind(':cant', $item['cantidad'] ?? 0);
                $db->bind(':pre', $item['precio'] ?? 0);
                $db->bind(':costo', $esProducto ? ($item['costo_promedio'] ?? $item['costo'] ?? 0) : 0);
                $db->execute();
            }
        } catch (Exception $e) {
            error_log("Error sincronizando items de OS: " . $e->getMessage());
        }
    }

    /**
     * API para obtener el detalle completo de una orden (AJAX)
     */
    public function obtenerDetalle($id) {
        try {
            $orden = $this->ordenModel->obtenerDetalleOrden($id);
            if (!$orden) {
                return $this->jsonResponse(['success' => false, 'error' => 'Orden no encontrada'], 404);
            }
            
            $reportModel = $this->model('Reportes');
            $staff = $reportModel->obtenerStaffSimple();
            
            // Punto 2: Carga de items desde el borrador vinculado
            $db = new Database();
            $db->query("SELECT id FROM table_facturas WHERE orden_id = :oid AND status = 'PENDIENTE' LIMIT 1");
            $db->bind(':oid', $id);
            $borrador = $db->single();
            
            $items = [];
            if ($borrador) {
                $facturaModel = $this->model('Facturacion');
                $venta = $facturaModel->obtenerVentaCompleta($borrador->id);
                if ($venta && !empty($venta->items)) {
                    // Mapeo de compatibilidad: El frontend espera 'nombre' 
                    // pero la DB de facturación guarda 'descripcion'
                    $items = array_map(function($it) {
                        return [
                            'id' => $it->producto_id,
                            'nombre' => $it->descripcion,
                            'precio' => (float)$it->precio_unitario,
                            'cantidad' => (int)$it->cantidad,
                            'tipo' => $it->producto_id ? 'PRODUCTO' : 'SERVICIO'
                        ];
                    }, $venta->items);
                }
            }

            // Información técnica adicional requerida por el modal
            $logs = $this->ordenModel->obtenerLogsEstado($id);
            $checklist = $this->ordenModel->obtenerChecklist($id);

            return $this->jsonResponse([
                'success' => true, 
                'data' => $orden,
                'items' => $items,
                'staff' => $staff,
                'logs' => $logs,
                'checklist' => $checklist
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API para obtener información de un vehículo y su dueño por placa (AJAX)
     */
    public function obtenerVehiculoPorPlaca($placa) {
        $vehiculo = $this->vehiculoModel->buscarPorPlaca($placa);
        $ultimoKilometraje = null;
        if ($vehiculo) {
            $resKilometraje = $this->ordenModel->obtenerUltimoKilometrajePorPlaca($placa);
            $ultimoKilometraje = $resKilometraje ? $resKilometraje->kilometraje : null;
        }
        return $this->jsonResponse([
            'success' => !!$vehiculo,
            'data' => $vehiculo,
            'ultimo_kilometraje' => $ultimoKilometraje
        ]);
    }

    /**
     * Endpoint para obtener el historial de estados de una orden (AJAX)
     */
    public function obtenerLogs($id) {
        $logs = $this->ordenModel->obtenerLogsEstado($id);
        return $this->jsonResponse(['success' => true, 'data' => $logs]);
    }

    /**
     * Endpoint para obtener el checklist detallado de la orden (AJAX)
     */
    public function obtenerChecklist($id) {
        $checklist = $this->ordenModel->obtenerChecklist($id);
        return $this->jsonResponse(['success' => true, 'data' => $checklist]);
    }

    /**
     * Procesa el cambio de estado de una orden desde la tabla (AJAX)
     */
    public function cambiarEstado() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id']) || empty($input['estado'])) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Datos incompletos'], 400);
            }

            if ($this->ordenModel->actualizarEstado($input['id'], $input['estado'], 'Cambio de estado desde el panel de taller')) {
                // Si la orden entra en fase técnica, activamos el borrador en facturación
                if (in_array($input['estado'], ['DIAGNOSTICANDO', 'EN_REPARACION', 'LISTO'])) {
                    $this->prepararBorradorDesdeOrden($input['id']);
                }

                // ── Enviar email de notificación de cambio de estado ──
                try {
                    $orden = $this->ordenModel->obtenerDetalleOrden($input['id']);
                    if ($orden && !empty($orden->cliente_email)) {
                        // Obtener el estado anterior desde los logs
                        $logs = $this->ordenModel->obtenerLogsEstado($input['id']);
                        $estadoAnterior = (count($logs) >= 2) ? $logs[1]->estado : 'RECIBIDO';
                        $emailService = new \App\Services\EmailService();
                        $emailService->notificarOrdenServicioCambioEstado([
                            'cliente_email' => $orden->cliente_email,
                            'cliente_nombre' => $orden->cliente_nombre ?? 'Cliente',
                            'orden_id' => $input['id'],
                            'id_formateado' => str_pad($input['id'], 6, '0', STR_PAD_LEFT),
                            'placa' => $orden->placa,
                            'vehiculo' => ($orden->marca ?? '') . ' ' . ($orden->modelo ?? ''),
                            'estado_anterior' => $estadoAnterior,
                            'estado_nuevo' => $input['estado'],
                            'fecha_cambio' => date('d/m/Y H:i'),
                            'comentario' => 'Cambio de estado desde el panel de taller',
                            'mecanico_nombre' => $orden->mecanico_nombre ?? 'No asignado'
                        ]);
                    }
                } catch (\Exception $e) {
                    error_log('Error enviando email de cambio de estado: ' . $e->getMessage());
                }

                // ── Enviar WhatsApp de notificación de cambio de estado ──
                $waOk = true;
                $waMsg = '';
                try {
                    if ($orden && !empty($orden->cliente_telefono)) {
                        $waService = new \App\Services\WhatsAppService();
                        $waResult = $waService->notificarOrdenServicioCambioEstado([
                            'cliente_telefono' => $orden->cliente_telefono,
                            'cliente_nombre' => $orden->cliente_nombre ?? 'Cliente',
                            'orden_id' => $input['id'],
                            'id_formateado' => str_pad($input['id'], 6, '0', STR_PAD_LEFT),
                            'placa' => $orden->placa,
                            'vehiculo' => ($orden->marca ?? '') . ' ' . ($orden->modelo ?? ''),
                            'estado_anterior' => $estadoAnterior,
                            'estado_nuevo' => $input['estado'],
                            'fecha_cambio' => date('d/m/Y H:i'),
                            'comentario' => 'Cambio de estado desde el panel de taller',
                            'mecanico_nombre' => $orden->mecanico_nombre ?? 'No asignado'
                        ]);
                        $waOk = $waResult['success'] ?? false;
                        $waMsg = $waResult['mensaje'] ?? '';
                    }
                } catch (\Exception $e) {
                    $waOk = false;
                    $waMsg = $e->getMessage();
                    error_log('Error enviando WhatsApp de cambio de estado: ' . $e->getMessage());
                }

                $waNotice = $waOk ? '' : ' (WhatsApp no enviado: ' . ($waMsg ?: 'sin teléfono') . ')';
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Estado actualizado correctamente' . $waNotice, 'wa_success' => $waOk, 'wa_mensaje' => $waMsg]);
            }
            return $this->jsonResponse(['success' => false, 'mensaje' => 'Error al actualizar el estado']);
        }
    }

    /**
     * Asegura que exista un borrador (factura PENDIENTE) vinculado a la orden
     * para que sea visible en el POS de facturación inmediatamente.
     */
    private function prepararBorradorDesdeOrden($ordenId) {
        $modelFacturacion = $this->model('Facturacion');
        
        // Verificar si ya existe un borrador para evitar duplicados
        $borrador = $modelFacturacion->obtenerBorradorPorOrden($ordenId);
        if ($borrador) return;

        $orden = $this->ordenModel->obtenerDetalleOrden($ordenId);
        if (!$orden) return;

        $datosBase = [
            'placa' => $orden->placa,
            'cliente_id' => $orden->cliente_id,
            'modelo' => $orden->modelo,
            'mecanico_id' => $orden->mecanico_id,
            'items' => [] // Se crea inicialmente sin ítems
        ];
        $this->sincronizarItemsOrden($ordenId, $datosBase);
    }

    /**
     * Finaliza una orden de servicio marcándola como ENTREGADO (AJAX)
     */
    public function entregarOrden() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID de orden requerido'], 400);
            }

            $comentario = !empty($input['comentario']) ? $input['comentario'] : 'Vehículo entregado al cliente.';
            if ($this->ordenModel->actualizarEstado($input['id'], 'ENTREGADO', $comentario)) {
                // ─── Enviar email de notificación: vehículo listo ───
                try {
                    $ordenado = $this->ordenModel->obtenerDetalleOrden($input['id']);
                    if ($ordenado && !empty($ordenado->cliente_email)) {
                        // Obtener ítems desde la factura PENDIENTE vinculada
                        $db = new Database();
                        $db->query("SELECT fd.descripcion, fd.cantidad, fd.precio, (fd.cantidad * fd.precio) as subtotal
                                     FROM table_facturas_detalle fd
                                     JOIN table_facturas f ON fd.factura_id = f.id
                                     WHERE f.orden_id = :oid AND f.status = 'PENDIENTE'");
                        $db->bind(':oid', $input['id']);
                        $itemsFactura = $db->resultSet();
                        $itemsEmail = [];
                        $totalEmail = 0;
                        foreach ($itemsFactura as $it) {
                            $itemsEmail[] = [
                                'descripcion' => $it->descripcion,
                                'cantidad' => (int)$it->cantidad,
                                'precio' => (float)$it->precio,
                                'subtotal' => (float)$it->subtotal
                            ];
                            $totalEmail += (float)$it->subtotal;
                        }
                        $emailService = new \App\Services\EmailService();
                        $emailService->notificarOrdenServicioLista([
                            'cliente_email' => $ordenado->cliente_email,
                            'cliente_nombre' => $ordenado->cliente_nombre ?? 'Cliente',
                            'orden_id' => $input['id'],
                            'id_formateado' => str_pad($input['id'], 6, '0', STR_PAD_LEFT),
                            'placa' => $ordenado->placa,
                            'vehiculo' => ($ordenado->marca ?? '') . ' ' . ($ordenado->modelo ?? ''),
                            'fecha_entrega' => date('d/m/Y H:i'),
                            'mecanico_nombre' => $ordenado->mecanico_nombre ?? 'No asignado',
                            'items' => $itemsEmail,
                            'total' => $totalEmail
                        ]);
                    }
                } catch (\Exception $e) {
                    error_log('Error enviando email de orden lista: ' . $e->getMessage());
                }

                // ── Enviar WhatsApp de notificación: vehículo listo ──
                $waOk = true;
                $waMsg = '';
                try {
                    if ($ordenado && !empty($ordenado->cliente_telefono)) {
                        $waService = new \App\Services\WhatsAppService();
                        $waResult = $waService->notificarOrdenServicioLista([
                            'cliente_telefono' => $ordenado->cliente_telefono,
                            'cliente_nombre' => $ordenado->cliente_nombre ?? 'Cliente',
                            'orden_id' => $input['id'],
                            'id_formateado' => str_pad($input['id'], 6, '0', STR_PAD_LEFT),
                            'placa' => $ordenado->placa,
                            'vehiculo' => ($ordenado->marca ?? '') . ' ' . ($ordenado->modelo ?? ''),
                            'fecha_entrega' => date('d/m/Y H:i'),
                            'mecanico_nombre' => $ordenado->mecanico_nombre ?? 'No asignado',
                            'items' => $itemsEmail,
                            'total' => $totalEmail
                        ]);
                        $waOk = $waResult['success'] ?? false;
                        $waMsg = $waResult['mensaje'] ?? '';
                    }
                } catch (\Exception $e) {
                    $waOk = false;
                    $waMsg = $e->getMessage();
                    error_log('Error enviando WhatsApp de orden lista: ' . $e->getMessage());
                }

                $waNotice = $waOk ? '' : ' (WhatsApp no enviado: ' . ($waMsg ?: 'sin teléfono') . ')';
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Orden finalizada correctamente' . $waNotice, 'wa_success' => $waOk, 'wa_mensaje' => $waMsg]);
            }
            return $this->jsonResponse(['success' => false, 'mensaje' => 'Error al procesar la entrega']);
        }
    }

    /**
     * Punto 4: Disparador de notificaciones (Icono de la Llave)
     * Obtiene todas las órdenes activas para mostrar en el contador del header.
     */
    public function obtenerAlertas() {
        $db = new Database();
        // Punto 4: Consulta inteligente para el dropdown de notificaciones (Llave)
        // Categorizamos las alertas para que el frontend distinga entre órdenes sin mecánico, vencidas o estancadas.
        $db->query("SELECT os.id, os.placa, os.estado, os.mecanico_id, os.fecha_entrega_estimada, os.fecha_ingreso,
                          TIMESTAMPDIFF(MINUTE, NOW(), os.fecha_entrega_estimada) as minutos_restantes,
                          v.marca, v.modelo,
                          CASE 
                            WHEN os.mecanico_id IS NULL THEN 'SIN_MECANICO'
                            WHEN os.fecha_entrega_estimada < NOW() THEN 'VENCIDA'
                            WHEN os.estado = 'RECIBIDO' AND DATEDIFF(NOW(), os.fecha_ingreso) >= 1 THEN 'ESTANCADA'
                            ELSE 'PENDIENTE'
                          END as tipo_alerta,
                          CASE 
                            WHEN os.mecanico_id IS NULL THEN 'Pendiente de asignar técnico'
                            WHEN os.fecha_entrega_estimada < NOW() THEN 'Entrega fuera de tiempo'
                            WHEN os.estado = 'RECIBIDO' AND DATEDIFF(NOW(), os.fecha_ingreso) >= 1 THEN 'Sin seguimiento (24h+)'
                            ELSE 'En tiempo'
                          END as descripcion_alerta
                    FROM table_ordenes_servicio os
                    LEFT JOIN table_vehiculos v ON os.placa = v.placa
                    WHERE os.estado NOT IN ('ENTREGADO', 'ANULADO')
                    ORDER BY minutos_restantes ASC");

        $alertas = $db->resultSet();

        return $this->jsonResponse([
            'success' => true,
            'total' => count($alertas),
            'data' => $alertas
        ]);
    }

    /**
     * API para asignar un mecánico responsable a una orden (AJAX)
     */
    public function asignarMecanico() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id']) || empty($input['mecanico_id'])) {
                return $this->jsonResponse(['success' => false, 'error' => 'Datos incompletos'], 400);
            }

            $db = new Database();
            $db->query("UPDATE table_ordenes_servicio SET mecanico_id = :mid WHERE id = :id");
            $db->bind(':mid', $input['mecanico_id']);
            $db->bind(':id', $input['id']);
            
            if ($db->execute()) {
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Mecánico asignado correctamente']);
            }
            return $this->jsonResponse(['success' => false, 'error' => 'Error al actualizar el registro']);
        }
    }
}