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
        $this->view('taller/nueva_orden', [
            'titulo' => 'Nueva Orden de Servicio',
            'staff' => $reportModel->obtenerStaffSimple()
        ]);
    }

    /**
     * Muestra la hoja de vida de un vehículo por placa
     */
    public function historial($placa = '') {
        $vehiculo = $this->vehiculoModel->buscarPorPlaca($placa);
        $historial = $vehiculo ? $this->vehiculoModel->obtenerHistorial($vehiculo->placa) : [];

        $this->view('taller/vehiculos/historial', [
            'titulo' => 'Hoja de Vida: ' . strtoupper($placa),
            'vehiculo' => $vehiculo,
            'historial' => $historial
        ]);
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
                return $this->jsonResponse(['success' => true, 'id' => $ordenId, 'mensaje' => 'Orden creada correctamente']);
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
                $subtotal += ($item['precio'] * $item['cantidad']);
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
                $db->query("INSERT INTO table_facturas_detalle (factura_id, producto_id, mecanico_id, descripcion, cantidad, precio_unitario, costo_unitario) 
                            VALUES (:fid, :pid, :mid, :desc, :cant, :pre, :costo)");
                $db->bind(':fid', $ventaId);
                $db->bind(':pid', (strtoupper($item['tipo'] ?? '') === 'PRODUCTO') ? $item['id'] : null);
                $db->bind(':mid', $input['mecanico_id'] ?? null);
                $db->bind(':desc', mb_strtoupper($item['nombre'], 'UTF-8'));
                $db->bind(':cant', $item['cantidad']);
                $db->bind(':pre', $item['precio']);
                $db->bind(':costo', ($item['tipo'] === 'PRODUCTO') ? ($item['costo_promedio'] ?? 0) : 0);
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
                    INNER JOIN table_vehiculos v ON os.placa = v.placa
                    WHERE os.estado NOT IN ('ENTREGADO', 'ANULADO', 'LISTO')
                    ORDER BY (os.mecanico_id IS NULL) DESC, os.fecha_entrega_estimada ASC");
        
        $ordenes = $db->resultSet();
        
        return $this->jsonResponse([
            'success' => true,
            'total' => count($ordenes),
            'data' => $ordenes
        ]);
    }

    /**
     * Punto 3: Asignar o actualizar mecánico de la orden (API)
     * Requerido por la interfaz de gestión operativa en app.min.js
     */
    public function asignarMecanico() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id']) || empty($input['mecanico_id'])) {
                return $this->jsonResponse(['success' => false, 'error' => 'ID de orden y técnico son obligatorios'], 400);
            }

            // 1. Actualizar en la tabla de ordenes
            $db = new Database();
            $db->query("UPDATE table_ordenes_servicio SET mecanico_id = :mid WHERE id = :id");
            $db->bind(':mid', $input['mecanico_id']);
            $db->bind(':id', $input['id']);
            
            if ($db->execute()) {
                // 2. Sincronizar mecánico en los items del borrador de factura vinculado para reporte de nómina posterior
                $db->query("UPDATE table_facturas_detalle SET mecanico_id = :mid 
                            WHERE factura_id IN (SELECT id FROM table_facturas WHERE orden_id = :oid AND status = 'PENDIENTE')");
                $db->bind(':mid', $input['mecanico_id']);
                $db->bind(':oid', $input['id']);
                $db->execute();

                return $this->jsonResponse(['success' => true, 'mensaje' => 'Mecánico asignado correctamente']);
            }
            return $this->jsonResponse(['success' => false, 'error' => 'No se pudo actualizar el registro']);
        }
    }
}