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
        $this->view('taller/index', [
            'titulo' => 'Panel Operativo del Taller',
            'ordenes' => $ordenesActivas
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
            
            // Si el usuario es mecánico, auto-asignarlo como responsable de la orden
            if ($_SESSION['user_role'] === 'MECANICO') {
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
                logAction('TALLER', 'CREATE_OS', "Nueva O.S. #$ordenId para placa {$input['placa']}");
                return $this->jsonResponse(['success' => true, 'id' => $ordenId, 'mensaje' => 'Orden creada correctamente']);
            }
            return $this->jsonResponse(['success' => false, 'error' => 'No se pudo crear la orden']);
        }
    }

    /**
     * Actualiza el estado del ciclo de vida (API)
     */
    public function cambiarEstado() {
        $input = json_decode(file_get_contents('php://input'), true);
        $res = $this->ordenModel->actualizarEstado($input['id'], $input['estado'], $input['comentario'] ?? '');
        
        return $this->jsonResponse([
            'success' => $res, 
            'mensaje' => $res ? 'Estado actualizado' : 'Error al actualizar'
        ]);
    }

    /**
     * API para obtener alertas de entregas (Tarde/Próximo)
     */
    public function obtenerAlertas() {
        $ordenes = $this->ordenModel->obtenerOrdenesActivas();
        
        $alertas = [];
        $sinMecanico = [];

        foreach ($ordenes as $o) {
            // 1. Detectar órdenes sin mecánico (Prioridad inmediata para el Admin)
            if (!isset($o->mecanico_id) || empty($o->mecanico_id)) {
                $sinMecanico[] = [
                    'id' => $o->id,
                    'placa' => $o->placa
                ];
                continue; // Si no tiene mecánico, no evaluamos tiempo aún para no duplicar en el badge si no es necesario
            }

            // 2. Detectar alertas de tiempo solo para órdenes que NO estén terminadas
            $estadoActual = strtoupper($o->estado ?? '');
            if (!empty($o->fecha_entrega_estimada) && !in_array($estadoActual, ['LISTO', 'ENTREGADO', 'CANCELADO'])) {
                $minutos = isset($o->minutos_restantes) ? (int)$o->minutos_restantes : 0;
                $esTarde = $minutos < 0;
                $esProximo = ($minutos >= 0 && $minutos <= 120); // Próximas 2 Horas

                if ($esTarde || $esProximo) {
                    $alertas[] = [
                        'id' => $o->id,
                        'placa' => $o->placa,
                        'tiempo' => $minutos,
                        'es_tarde' => $esTarde,
                        'es_proximo' => $esProximo
                    ];
                }
            }
        }

        return $this->jsonResponse([
            'success' => true, 
            'alertas' => $alertas,
            'sin_mecanico' => $sinMecanico
        ]);
    }

    /**
     * Genera el PDF de la Orden de Servicio
     */
    public function imprimir($id = null) {
        if (!$id) {
            redirect('taller');
        }

        $orden = $this->ordenModel->obtenerDetalleOrden($id);
        if (!$orden) {
            die("La orden de servicio #$id no existe.");
        }

        $pdf = new PdfService();
        $pdf->generarDocumento('orden', [
            'titulo_documento' => 'Orden de Servicio',
            'documento_id' => $orden->id,
            'orden' => $orden
        ], 'OrdenServicio_' . $id . '.pdf');
    }
}