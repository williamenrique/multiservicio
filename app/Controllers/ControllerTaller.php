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
        
        $placa = isset($_GET['placa']) ? strtoupper(trim($_GET['placa'])) : '';
        if (!empty($placa)) {
            $vehiculo = $this->vehiculoModel->buscarPorPlaca($placa);
            if ($vehiculo) {
                $data['vehiculo'] = $vehiculo;
                $clienteModel = $this->model('Cliente');
                if ($vehiculo->cliente_id) {
                    $data['cliente'] = $clienteModel->obtenerPorId($vehiculo->cliente_id);
                }
            }
        }
        
        $this->view('taller/nueva_orden', $data);
    }

    public function cerradas() {
        $this->view('taller/cerradas', [
            'titulo' => 'Historial de Órdenes Finalizadas'
        ]);
    }

    public function imprimir($id) {
        $db = new Database();
        $db->query("SELECT id FROM table_facturas WHERE orden_id = :oid AND status IN ('COMPLETADO', 'CREDITO') ORDER BY id DESC LIMIT 1");
        $db->bind(':oid', $id);
        $facturaAsociada = $db->single();

        if ($facturaAsociada) {
            redirect('facturacion/imprimir/' . $facturaAsociada->id);
        } else {
            $orden = $this->ordenModel->obtenerDetalleOrden($id);
            
            if (!$orden) {
                die("La orden de servicio #$id no existe.");
            }

            $orden->fecha_entrada = $orden->fecha_ingreso;
            $orden->observaciones_entrada = $orden->diagnostico_entrada;
            $orden->checklist = $this->ordenModel->obtenerChecklist($id);
            $orden->servicios = $this->ordenModel->obtenerServicios($id);
            $orden->items = $this->ordenModel->obtenerItemsOrden($id);

            $empresa = $this->model('Empresa')->obtenerConfiguracion();

            $pdfService = new PdfService();
            $pdfService->generarDocumento('orden', [
                'titulo_pestaña' => 'Orden de Servicio',
                'orden' => $orden,
                'empresa' => $empresa,
            ], 'Orden_Servicio_' . $id . '.pdf');
            exit;
        }
    }

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

    public function historial($tipo = 'placa', $valor = '') {
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
                $db = new Database();
                $db->query("SELECT placa FROM table_ordenes_servicio WHERE id = :id");
                $db->bind(':id', $valor);
                $res = $db->single();
                if ($res) redirect("taller/historial/placa/{$res->placa}");
                break;

            default:
                $vehiculo = $this->vehiculoModel->buscarPorPlaca($valor);
                $historial = $vehiculo ? $this->vehiculoModel->obtenerHistorial($vehiculo->placa) : [];
                $titulo = "Hoja de Vida: " . strtoupper($valor);
                break;
        }

        if (!empty($historial)) {
            $facturaModel = $this->model('Facturacion');
            $db = new Database();
            foreach ($historial as &$itemH) {
                $itemH->checklist_data = $this->ordenModel->obtenerChecklist($itemH->id);
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

        $this->view('taller/historial', [
            'titulo' => $titulo,
            'vehiculo' => $vehiculo,
            'entidad' => $entidad,
            'historial' => $historial,
            'tipo' => strtoupper($tipo)
        ]);
    }

    public function buscar() {
        $term = trim($_GET['q'] ?? '');
        if (strlen($term) < 2) return $this->jsonResponse(['success' => true, 'results' => []]);

        $db = new Database();
        $results = [];

        $db->query("SELECT id, placa FROM table_ordenes_servicio WHERE id LIKE :term OR placa LIKE :term LIMIT 3");
        $db->bind(':term', "%$term%");
        foreach($db->resultSet() as $r) {
            $results[] = ['id' => $r->id, 'tipo' => 'orden', 'title' => "Orden #{$r->id}", 'subtitle' => "Placa vinculada: {$r->placa}", 'icon' => 'file-text'];
        }

        $db->query("SELECT id, nombre FROM table_clientes WHERE nombre LIKE :term OR id LIKE :term LIMIT 3");
        $db->bind(':term', "%$term%");
        foreach($db->resultSet() as $r) {
            $results[] = ['id' => $r->id, 'tipo' => 'cliente', 'title' => $r->nombre, 'subtitle' => "Cliente ID: {$r->id}", 'icon' => 'user'];
        }

        $db->query("SELECT id, nombre FROM table_staff WHERE cargo LIKE '%MECANICO%' AND (nombre LIKE :term OR id LIKE :term) LIMIT 3");
        $db->bind(':term', "%$term%");
        foreach($db->resultSet() as $r) {
            $results[] = ['id' => $r->id, 'tipo' => 'mecanico', 'title' => $r->nombre, 'subtitle' => "Técnico Especialista", 'icon' => 'wrench'];
        }

        $db->query("SELECT placa, marca, modelo FROM table_vehiculos WHERE placa LIKE :term LIMIT 3");
        $db->bind(':term', "%$term%");
        foreach($db->resultSet() as $r) {
            $results[] = ['id' => $r->placa, 'tipo' => 'placa', 'title' => $r->placa, 'subtitle' => "{$r->marca} {$r->modelo}", 'icon' => 'truck'];
        }

        return $this->jsonResponse(['success' => true, 'results' => $results]);
    }

    /**
     * Procesa la creación de una nueva Orden de Servicio.
     */
    public function guardarOrden() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['mecanico_id']) && $_SESSION['user_role'] === 'MECANICO') {
                $input['mecanico_id'] = $_SESSION['user_staff_id'];
            }

            $vehiculo = $this->vehiculoModel->buscarPorPlaca($input['placa']);
            
            if (!$vehiculo) {
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

            $input['placa'] = strtoupper(trim($input['placa']));
            $ordenId = $this->ordenModel->crear($input);
            
            if ($ordenId) {
                if (!empty($input['checklist'])) {
                    $this->ordenModel->guardarChecklist($ordenId, $input['checklist']);
                }

                if (!empty($input['servicios'])) {
                    $this->ordenModel->guardarServicios($ordenId, $input['servicios']);
                }

                if (!empty($input['items'])) {
                    $this->sincronizarItemsOrden($ordenId, $input);
                }

                // ────────────────────────────────────────────────────────────
                // ANEXAR PRESUPUESTO: cambia estado a ANEXADO y libera reservas.
                // El borrador de factura PENDIENTE (creado arriba) toma el relevo
                // del bloqueo de stock_disponible.
                // ────────────────────────────────────────────────────────────
                if (!empty($input['presupuesto_activo_id'])) {
                    try {
                        $presupuestoModel = $this->model('Presupuesto');
                        $presupuestoModel->iniciarProceso((int)$input['presupuesto_activo_id'], false);
                        logAction('TALLER', 'ANEXAR_PRESUPUESTO', "Presupuesto #{$input['presupuesto_activo_id']} anexado a O.S. #{$ordenId}");
                    } catch (\Exception $e) {
                        error_log("Error anexando presupuesto a OS #{$ordenId}: " . $e->getMessage());
                    }
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
                                if ($cant <= 0) continue;
                                $sub = $precio * $cant;
                                $itemsEmail[] = [
                                    'descripcion' => $it['nombre'] ?? $it['descripcion'] ?? 'Ítem',
                                    'cantidad' => $cant,
                                    'precio' => $precio,
                                    'subtotal' => $sub
                                ];
                                $totalEmail += $sub;
                            }
                        }
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
                            'observaciones' => $input['observaciones_entrada'] ?? '',
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

                return $this->jsonResponse(['success' => true, 'id' => $ordenId, 'mensaje' => 'Orden creada correctamente']);
            }
            return $this->jsonResponse(['success' => false, 'error' => 'No se pudo crear la orden']);
        }
    }

    /**
     * Sincroniza los items de la OS con un borrador de factura PENDIENTE.
     * 
     * ⚠️ FIX CRÍTICO v2.1:
     *   Se sanitizan los FK opcionales (mecanico_id, producto_id) para convertir
     *   strings vacíos a NULL. Esto evita FK violations silenciosas que impedían
     *   insertar los items y, por ende, que el inventario mostrara la reserva.
     */
    private function sincronizarItemsOrden($ordenId, $input) {
        try {
            $modelFacturacion = $this->model('Facturacion');
            
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

            // ⚠️ SANITIZACIÓN CRÍTICA: Convertir strings vacíos a NULL para FK
            $mecanicoIdSanitizado = !empty($input['mecanico_id']) ? $input['mecanico_id'] : null;

            $datosFactura = [
                'id_db' => $facturaId,
                'orden_id' => $ordenId,
                'cliente_id' => $input['cliente_id'],
                'placa' => $input['placa'],
                'modelo' => $input['modelo'] ?? '',
                'pago_efectivo' => 0,
                'pago_transferencia' => 0,
                'mecanico_id' => $mecanicoIdSanitizado,
                // ✅ Guardar el vínculo con el presupuesto anexado
                'presupuesto_activo_id' => !empty($input['presupuesto_activo_id']) ? (int)$input['presupuesto_activo_id'] : null
            ];

            $totales = [
                'subtotal' => $subtotal,
                'iva' => 0,
                'total' => $subtotal,
                'saldo' => $subtotal
            ];

            $ventaId = $modelFacturacion->guardarCabeceraVenta($datosFactura, 'PENDIENTE', $totales, $_SESSION['user_id']);

            if (!$ventaId) {
                error_log("sincronizarItemsOrden: No se pudo crear/actualizar la cabecera del borrador para OS #$ordenId");
                return;
            }

            if ($facturaId) {
                $db->query("DELETE FROM table_facturas_detalle WHERE factura_id = :fid");
                $db->bind(':fid', $ventaId);
                $db->execute();
            }

            $itemsInsertados = 0;
            $itemsConError = 0;

            foreach ($itemsArr as $item) {
                // Aceptar items con 'nombre' o 'id' (o ambos)
                if (empty($item['nombre']) && empty($item['id'])) {
                    continue;
                }

                $esProducto = (strtoupper($item['tipo'] ?? '') === 'PRODUCTO');
                $descripcion = $item['nombre'] ?? $item['descripcion'] ?? 'Ítem';

                // ⚠️ SANITIZACIÓN: producto_id solo si es producto y tiene id válido
                $productoIdSanitizado = ($esProducto && !empty($item['id'])) ? (int)$item['id'] : null;

                $db->query("INSERT INTO table_facturas_detalle (factura_id, producto_id, mecanico_id, descripcion, cantidad, precio_unitario, costo_unitario) 
                            VALUES (:fid, :pid, :mid, :desc, :cant, :pre, :costo)");
                $db->bind(':fid', $ventaId);
                $db->bind(':pid', $productoIdSanitizado);
                $db->bind(':mid', $mecanicoIdSanitizado);
                $db->bind(':desc', mb_strtoupper($descripcion, 'UTF-8'));
                $db->bind(':cant', (int)($item['cantidad'] ?? 0));
                $db->bind(':pre', (float)($item['precio'] ?? 0));
                $db->bind(':costo', $esProducto ? (float)($item['costo_promedio'] ?? $item['costo'] ?? 0) : 0);

                if ($db->execute()) {
                    $itemsInsertados++;
                } else {
                    $itemsConError++;
                    error_log("sincronizarItemsOrden: Fallo INSERT item para factura #$ventaId - " . json_encode($item));
                }
            }

            error_log("sincronizarItemsOrden: OS #$ordenId → Factura #$ventaId | Insertados: $itemsInsertados | Errores: $itemsConError");
        } catch (Exception $e) {
            error_log("Error sincronizando items de OS: " . $e->getMessage());
        }
    }

    public function obtenerDetalle($id) {
        try {
            $orden = $this->ordenModel->obtenerDetalleOrden($id);
            if (!$orden) {
                return $this->jsonResponse(['success' => false, 'error' => 'Orden no encontrada'], 404);
            }
            
            $reportModel = $this->model('Reportes');
            $staff = $reportModel->obtenerStaffSimple();
            
            $db = new Database();
            $db->query("SELECT id FROM table_facturas WHERE orden_id = :oid AND status = 'PENDIENTE' LIMIT 1");
            $db->bind(':oid', $id);
            $borrador = $db->single();
            
            $items = [];
            if ($borrador) {
                $facturaModel = $this->model('Facturacion');
                $venta = $facturaModel->obtenerVentaCompleta($borrador->id);
                if ($venta && !empty($venta->items)) {
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

            $servicios = $this->ordenModel->obtenerServicios($id);
            $logs = $this->ordenModel->obtenerLogsEstado($id);
            $checklist = $this->ordenModel->obtenerChecklist($id);

            return $this->jsonResponse([
                'success' => true, 
                'data' => $orden,
                'items' => $items,
                'servicios' => $servicios,
                'staff' => $staff,
                'logs' => $logs,
                'checklist' => $checklist
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

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

    public function obtenerLogs($id) {
        $logs = $this->ordenModel->obtenerLogsEstado($id);
        return $this->jsonResponse(['success' => true, 'data' => $logs]);
    }

    public function obtenerChecklist($id) {
        $checklist = $this->ordenModel->obtenerChecklist($id);
        return $this->jsonResponse(['success' => true, 'data' => $checklist]);
    }

    public function cambiarEstado() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id']) || empty($input['estado'])) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Datos incompletos'], 400);
            }

            if ($this->ordenModel->actualizarEstado($input['id'], $input['estado'], 'Cambio de estado desde el panel de taller')) {
                if (in_array($input['estado'], ['DIAGNOSTICANDO', 'EN_REPARACION', 'LISTO'])) {
                    $this->prepararBorradorDesdeOrden($input['id']);
                }

                try {
                    $orden = $this->ordenModel->obtenerDetalleOrden($input['id']);
                    if ($orden && !empty($orden->cliente_email)) {
                        $logs = $this->ordenModel->obtenerLogsEstado($input['id']);
                        $estadoAnterior = (count($logs) >= 2) ? $logs[1]->estado : 'RECIBIDO';
                        $emailService = new \App\Services\EmailService();
                        $datosEmail = [
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
                        ];
                        $emailService->notificarOrdenServicioCambioEstado($datosEmail);
                    }
                } catch (\Exception $e) {
                    error_log('Error enviando email de cambio de estado: ' . $e->getMessage());
                }

                return $this->jsonResponse(['success' => true, 'mensaje' => 'Estado actualizado correctamente']);
            }
            return $this->jsonResponse(['success' => false, 'mensaje' => 'Error al actualizar el estado']);
        }
    }

    private function prepararBorradorDesdeOrden($ordenId) {
        $modelFacturacion = $this->model('Facturacion');
        
        $borrador = $modelFacturacion->obtenerBorradorPorOrden($ordenId);
        if ($borrador) return;

        $orden = $this->ordenModel->obtenerDetalleOrden($ordenId);
        if (!$orden) return;

        $datosBase = [
            'placa' => $orden->placa,
            'cliente_id' => $orden->cliente_id,
            'modelo' => $orden->modelo,
            'mecanico_id' => $orden->mecanico_id,
            'items' => []
        ];
        $this->sincronizarItemsOrden($ordenId, $datosBase);
    }

    public function entregarOrden() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID de orden requerido'], 400);
            }

            $comentario = !empty($input['comentario']) ? $input['comentario'] : 'Vehículo entregado al cliente.';
            
            $this->ordenModel->completarServiciosPendientes($input['id']);
            
            if ($this->ordenModel->actualizarEstado($input['id'], 'ENTREGADO', $comentario)) {
                try {
                    $ordenado = $this->ordenModel->obtenerDetalleOrden($input['id']);
                    if ($ordenado && !empty($ordenado->cliente_email)) {
                        $db = new Database();
                        $db->query("SELECT fd.descripcion, fd.cantidad, fd.precio_unitario, (fd.cantidad * fd.precio_unitario) as subtotal
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
                                'precio' => (float)$it->precio_unitario,
                                'subtotal' => (float)$it->subtotal
                            ];
                            $totalEmail += (float)$it->subtotal;
                        }
                        $emailService = new \App\Services\EmailService();
                        $datosEmail = [
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
                        ];
                        $emailService->notificarOrdenServicioLista($datosEmail);
                    }
                } catch (\Exception $e) {
                    error_log('Error enviando email de orden lista: ' . $e->getMessage());
                }

                return $this->jsonResponse(['success' => true, 'mensaje' => 'Orden finalizada correctamente']);
            }
            return $this->jsonResponse(['success' => false, 'mensaje' => 'Error al procesar la entrega']);
        }
    }

    public function obtenerAlertas() {
        $db = new Database();
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

    public function obtenerServicios($id) {
        $servicios = $this->ordenModel->obtenerServicios($id);
        return $this->jsonResponse(['success' => true, 'data' => $servicios]);
    }

    public function actualizarEstadoServicio() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id']) || empty($input['estado'])) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Datos incompletos'], 400);
            }

            $estadosValidos = ['PENDIENTE', 'EN_PROCESO', 'COMPLETADO', 'CANCELADO'];
            if (!in_array($input['estado'], $estadosValidos)) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Estado inválido'], 400);
            }

            if ($this->ordenModel->actualizarEstadoServicio($input['id'], $input['estado'])) {
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Estado del servicio actualizado']);
            }
            return $this->jsonResponse(['success' => false, 'mensaje' => 'Error al actualizar el servicio']);
        }
    }

    public function guardarServicio() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['orden_id']) || empty($input['descripcion'])) {
                return $this->jsonResponse(['success' => false, 'error' => 'Datos incompletos'], 400);
            }

            $servicio = [
                'descripcion' => $input['descripcion'],
                'estado' => $input['estado'] ?? 'PENDIENTE'
            ];

            if ($this->ordenModel->agregarServicio($input['orden_id'], $servicio)) {
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Servicio agregado correctamente']);
            }
            return $this->jsonResponse(['success' => false, 'error' => 'Error al agregar el servicio']);
        }
    }

    public function eliminarServicio() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID de servicio requerido'], 400);
            }

            if ($this->ordenModel->eliminarServicio($input['id'])) {
                return $this->jsonResponse(['success' => true, 'mensaje' => 'Servicio eliminado correctamente']);
            }
            return $this->jsonResponse(['success' => false, 'mensaje' => 'Error al eliminar el servicio']);
        }
    }

    public function buscarPresupuestosActivos() {
        try {
            $search = $_GET['q'] ?? '';
            $presupuestoModel = $this->model('Presupuesto');
            $presupuestos = $presupuestoModel->buscarActivos($search);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $presupuestos
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}