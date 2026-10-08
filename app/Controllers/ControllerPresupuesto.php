<?php
/**
 * Controlador de Presupuestos
 * Gestiona la creación, edición, envío y seguimiento de presupuestos/cotizaciones.
 * 
 * FLUJO SIMPLIFICADO:
 *   BORRADOR → ENVIADO → ACEPTADO → ANEXADO → CONVERTIDO
 *                    ↘  RECHAZADO / EXPIRADO
 */
class ControllerPresupuesto extends Controller {
    private $presupuestoModel;
    private $emailModel;
    private $clienteModel;

    public function __construct() {
        AuthGuard::handle();
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        $this->presupuestoModel = $this->model('Presupuesto');
        $this->emailModel = $this->model('Email');
        $this->clienteModel = $this->model('Cliente');
    }

    public function index() {
        $data = [
            'titulo' => 'Presupuestos / Cotizaciones',
            'stats' => $this->presupuestoModel->obtenerEstadisticas(),
            'config_iva' => $this->model('Empresa')->obtenerConfiguracion()->iva ?? 19.00
        ];
        $this->view('presupuesto/index', $data);
    }

    public function listar() {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            
            $limit = isset($input['limit']) ? (int)$input['limit'] : 20;
            $page = isset($input['page']) ? (int)$input['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            $filters = [
                'estado' => $input['estado'] ?? null,
                'cliente_id' => $input['cliente_id'] ?? null,
                'desde' => $input['desde'] ?? null,
                'hasta' => $input['hasta'] ?? null,
                'search' => $input['search'] ?? null
            ];

            $resultado = $this->presupuestoModel->listar($limit, $offset, $filters);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $resultado['data'],
                'total' => $resultado['total'],
                'pagina_actual' => $page,
                'total_paginas' => ceil($resultado['total'] / $limit)
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function crear() {
        $data = [
            'titulo' => 'Nuevo Presupuesto',
            'empresa' => $this->model('Empresa')->obtenerConfiguracion(),
            'config_iva' => $this->model('Empresa')->obtenerConfiguracion()->iva ?? 19.00
        ];
        $this->view('presupuesto/crear', $data);
    }

    public function editar($id = null) {
        if (!$id) {
            redirect('presupuesto');
        }

        $presupuesto = $this->presupuestoModel->obtenerCompleto((int)$id);
        if (!$presupuesto) {
            $this->view('errores/404', ['titulo' => 'Presupuesto no encontrado']);
            return;
        }

        if (!in_array($presupuesto->estado, ['BORRADOR', 'ENVIADO'])) {
            redirect('presupuesto/ver/' . $id);
        }

        redirect('presupuesto?edit=' . $id);
    }

    public function ver($id = null) {
        if (!$id) {
            redirect('presupuesto');
        }

        $presupuesto = $this->presupuestoModel->obtenerCompleto((int)$id);
        if (!$presupuesto) {
            $this->view('errores/404', ['titulo' => 'Presupuesto no encontrado']);
            return;
        }

        $data = [
            'titulo' => 'Presupuesto #' . $presupuesto->numero,
            'presupuesto' => $presupuesto,
            'empresa' => $this->model('Empresa')->obtenerConfiguracion()
        ];
        $this->view('presupuesto/ver', $data);
    }

    private function procesarInput() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            return $input ?: [];
        }
        
        $input = $_POST;
        
        $items = [];
        foreach ($input as $key => $value) {
            if (preg_match('/^items\[(\d+)\]\[(.+)\]$/', $key, $matches)) {
                $index = $matches[1];
                $field = $matches[2];
                $items[$index][$field] = $value;
            }
        }
        
        if (!empty($items)) {
            ksort($items);
            $input['items'] = array_values($items);
        }
        
        $input['iva_activo'] = isset($input['iva_activo']) ? (int)$input['iva_activo'] : 0;
        
        if (!$input['iva_activo']) {
            $input['iva_monto'] = 0;
            $input['tasa_iva'] = 0;
            $input['subtotal'] = $input['total'];
        }
        
        return $input;
    }

    private function procesarClienteVehiculo($data) {
        $clienteModel = $this->model('Cliente');
        $vehiculoModel = $this->model('Vehiculo');
        
        $clienteId = $data['cliente_id'] ?? null;
        $clienteCedula = $data['cliente_cedula'] ?? null;
        $clienteNombre = $data['cliente_nombre'] ?? null;
        
        if (!$clienteId && $clienteCedula) {
            $clienteExistente = $clienteModel->obtenerPorId($clienteCedula);
            if ($clienteExistente) {
                $clienteId = $clienteExistente->id;
            }
        }
        
        if (!$clienteId && $clienteNombre) {
            $nuevoClienteId = $clienteCedula ?: 'CLI_' . date('YmdHis') . '_' . rand(1000, 9999);
            
            $clienteData = [
                'id' => $nuevoClienteId,
                'nombre' => mb_strtoupper($clienteNombre, 'UTF-8'),
                'email' => mb_strtolower($data['cliente_email'] ?? '', 'UTF-8'),
                'telefono' => $data['cliente_telefono'] ?? '',
                'direccion' => mb_strtoupper($data['cliente_direccion'] ?? '', 'UTF-8')
            ];
            
            if ($clienteModel->crear($clienteData)) {
                $clienteId = $clienteData['id'];
            }
        }
        
        $data['cliente_id'] = $clienteId;

        $vehiculoPlaca = trim($data['vehiculo_placa'] ?? '');
        $tieneDatosVehiculo = !empty(trim($data['vehiculo_marca'] ?? '')) 
                           || !empty(trim($data['vehiculo_modelo'] ?? '')) 
                           || !empty(trim($data['vehiculo_anio'] ?? '')) 
                           || !empty(trim($data['vehiculo_color'] ?? ''));

        if ($tieneDatosVehiculo && empty($vehiculoPlaca)) {
            throw new Exception("Debe indicar la PLACA del vehículo cuando completa marca, modelo, año o color.");
        }

        if ($vehiculoPlaca && $clienteId) {
            $vehiculoExistente = $vehiculoModel->buscarPorPlaca($vehiculoPlaca);
            
            $vehiculoData = [
                'placa' => strtoupper($vehiculoPlaca),
                'marca' => mb_strtoupper($data['vehiculo_marca'] ?? '', 'UTF-8'),
                'modelo' => mb_strtoupper($data['vehiculo_modelo'] ?? '', 'UTF-8'),
                'anio' => $data['vehiculo_anio'] ?? null,
                'color' => mb_strtoupper($data['vehiculo_color'] ?? '', 'UTF-8'),
                'cliente_id' => $clienteId
            ];
            
            if (!$vehiculoExistente) {
                $resultado = $vehiculoModel->registrar($vehiculoData);
                if (!$resultado) {
                    throw new Exception("Error al registrar el vehículo con placa " . $vehiculoPlaca);
                }
            } else {
                if ($vehiculoExistente->cliente_id != $clienteId || 
                    $vehiculoExistente->marca != $vehiculoData['marca'] ||
                    $vehiculoExistente->modelo != $vehiculoData['modelo'] ||
                    $vehiculoExistente->anio != $vehiculoData['anio'] ||
                    $vehiculoExistente->color != $vehiculoData['color']) {
                    $vehiculoData['placa'] = strtoupper($vehiculoPlaca);
                    $resultado = $vehiculoModel->actualizar($vehiculoData);
                    if (!$resultado) {
                        throw new Exception("Error al actualizar el vehículo: " . $vehiculoPlaca);
                    }
                }
            }
        }
        
        return $data;
    }

    private function convertirAMayusculas($data) {
        $camposMayusculas = ['observaciones', 'condiciones', 'cliente_nombre', 'cliente_direccion', 
                            'vehiculo_marca', 'vehiculo_modelo', 'vehiculo_color'];
        
        foreach ($camposMayusculas as $campo) {
            if (isset($data[$campo]) && $data[$campo] !== null) {
                $data[$campo] = mb_strtoupper($data[$campo], 'UTF-8');
            }
        }
        
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as &$item) {
                if (isset($item['descripcion']) && $item['descripcion'] !== null) {
                    $item['descripcion'] = mb_strtoupper($item['descripcion'], 'UTF-8');
                }
                if (isset($item['notas']) && $item['notas'] !== null) {
                    $item['notas'] = mb_strtoupper($item['notas'], 'UTF-8');
                }
            }
        }
        
        if (isset($data['cliente_email'])) {
            $data['cliente_email'] = mb_strtolower($data['cliente_email'], 'UTF-8');
        }
        
        return $data;
    }

    public function guardar() {
        try {
            $input = $this->procesarInput();
            
            $v = new Validator($input);
            $v->required(['cliente_nombre', 'items']);
            $v->array('items');
            
            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())], 400);
            }

            $input = $this->convertirAMayusculas($input);
            $input = $this->procesarClienteVehiculo($input);

            $data = array_merge($input, ['usuario_id' => $_SESSION['user_id']]);
            $presupuestoId = $this->presupuestoModel->crear($data);

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Presupuesto creado correctamente',
                'presupuesto_id' => $presupuestoId,
                'redirect' => URLROOT . '/presupuesto/ver/' . $presupuestoId
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function actualizar($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $input = $this->procesarInput();
            
            $v = new Validator($input);
            $v->required(['cliente_nombre', 'items']);
            $v->array('items');
            
            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())], 400);
            }

            $input = $this->convertirAMayusculas($input);
            $input = $this->procesarClienteVehiculo($input);

            $this->presupuestoModel->actualizar((int)$id, $input);

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Presupuesto actualizado correctamente',
                'redirect' => URLROOT . '/presupuesto/ver/' . $id
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Cambia el estado de un presupuesto.
     * 
     * ⚠️ FIX v2.1: Si el nuevo estado es ACEPTADO, delega a aceptar()
     * para que se creen las reservas de stock correctamente. Esto cubre
     * el caso del botón "Marcar Aceptado" de la lista de presupuestos.
     */
    public function cambiarEstado($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $estado = $input['estado'] ?? '';

            // ─── FIX: Delegar a aceptar() si el estado destino es ACEPTADO ───
            // para que se creen las reservas de stock (bloqueo de disponible)
            // y el inventario lo refleje.
            if ($estado === 'ACEPTADO') {
                $this->presupuestoModel->aceptar((int)$id, $_SESSION['user_id']);
                $presupuesto = $this->presupuestoModel->obtenerCompleto((int)$id);
                return $this->jsonResponse([
                    'success' => true,
                    'mensaje' => 'Presupuesto marcado como ACEPTADO. Stock reservado.',
                    'data' => $presupuesto
                ]);
            }

            // Otros cambios de estado no requieren lógica de stock
            $this->presupuestoModel->cambiarEstado((int)$id, $estado);

            $presupuesto = $this->presupuestoModel->obtenerCompleto((int)$id);

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Estado actualizado correctamente',
                'data' => $presupuesto
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function eliminar($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $this->presupuestoModel->eliminar((int)$id);

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Presupuesto eliminado correctamente'
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function pdf($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $presupuesto = $this->presupuestoModel->obtenerCompleto((int)$id);
            if (!$presupuesto) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Presupuesto no encontrado'], 404);
            }

            return $this->jsonResponse([
                'success' => true,
                'pdf_url' => URLROOT . '/presupuesto/imprimir/' . $presupuesto->id
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function imprimir($id = null) {
        if (!$id) {
            throw new AppException("ID de presupuesto no proporcionado.", 400);
        }

        $presupuestoId = (int)$id;
        $presupuesto = $this->presupuestoModel->obtenerCompleto($presupuestoId);
        if (!$presupuesto) {
            throw new AppException("El presupuesto #$presupuestoId no existe.", 404);
        }

        $pdfService = new PdfService();
        $doc_name = 'PRES-' . str_pad($presupuesto->id, 4, '0', STR_PAD_LEFT);
        $pdfService->generarDocumento('presupuesto', [
            'presupuesto' => $presupuesto,
            'items' => $presupuesto->items,
            'empresa' => $this->model('Empresa')->obtenerConfiguracion()
        ], $doc_name . '.pdf');
        exit;
    }

    public function enviarEmail($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            $v = new Validator($input);
            $v->required(['destinatario_email']);
            $v->email('destinatario_email');
            
            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())], 400);
            }

            $presupuesto = $this->presupuestoModel->obtenerCompleto((int)$id);
            if (!$presupuesto) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Presupuesto no encontrado'], 404);
            }

            $config = $this->model('Empresa')->obtenerConfiguracion();
            $doc_name = 'PRES-' . str_pad($presupuesto->id, 4, '0', STR_PAD_LEFT);

            $pdfService = new PdfService();
            $pdfBinario = $pdfService->generarBinario('presupuesto', [
                'presupuesto' => $presupuesto,
                'items' => $presupuesto->items,
                'empresa' => $config
            ]);

            $plantilla = $this->emailModel->obtenerPlantillas('PRESUPUESTO');
            $plantilla = $plantilla[0] ?? null;

            $variables = [
                'numero_presupuesto' => $presupuesto->numero,
                'cliente_nombre' => $presupuesto->cliente_nombre,
                'total_formateado' => number_format($presupuesto->total, 2, ',', '.'),
                'fecha_vencimiento' => $presupuesto->fecha_vencimiento ? date('d/m/Y', strtotime($presupuesto->fecha_vencimiento)) : 'N/A',
                'empresa_nombre' => $config->name ?? 'Taller Pro',
                'empresa_nit' => $config->nit ?? '',
                'empresa_direccion' => $config->direccion ?? '',
                'empresa_telefono' => $config->telefono ?? ''
            ];

            if ($plantilla) {
                $asunto = $this->reemplazarVariables($plantilla->asunto, $variables);
                $cuerpoHtml = $this->reemplazarVariables($plantilla->cuerpo_html, $variables);
            } else {
                $asunto = "Presupuesto {$presupuesto->numero} - {$config->name}";
                $cuerpoHtml = $this->generarCuerpoPresupuesto($presupuesto, $config);
            }

            $emailData = [
                'to' => $input['destinatario_email'],
                'to_name' => $input['destinatario_nombre'] ?? $presupuesto->cliente_nombre,
                'subject' => $asunto,
                'body_html' => $cuerpoHtml,
                'attachments' => [
                    ['content' => $pdfBinario, 'name' => $doc_name . '.pdf']
                ],
                'from_name' => $config->name ?? 'Taller Pro',
                'from_email' => $config->email ?? 'noreply@tallerpro.com',
                'tipo' => 'PRESUPUESTO',
                'referencia_tipo' => 'PRESUPUESTO',
                'referencia_id' => $presupuesto->id,
            ];

            $emailService = new \App\Services\EmailService();
            $result = $emailService->enviarEmailGenerico($emailData);

            if ($presupuesto->estado === 'BORRADOR' && $result['success']) {
                $this->presupuestoModel->cambiarEstado($presupuesto->id, 'ENVIADO');
            }

            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    private function reemplazarVariables($texto, $variables) {
        foreach ($variables as $key => $value) {
            $texto = str_replace('{{' . $key . '}}', $value, $texto);
        }
        return $texto;
    }

    private function generarCuerpoPresupuesto($presupuesto, $config) {
        $itemsHtml = '';
        foreach ($presupuesto->items as $item) {
            $itemsHtml .= '<tr>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($item->descripcion) . '</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . $item->cantidad . '</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">' . number_format($item->precio_unitario, 2, ',', '.') . '</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">' . number_format($item->subtotal, 2, ',', '.') . '</td>
            </tr>';
        }

        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #1e40af;">Presupuesto ' . $presupuesto->numero . '</h2>
            <p>Estimado/a ' . htmlspecialchars($presupuesto->cliente_nombre) . ',</p>
            <p>Le enviamos el presupuesto <strong>' . $presupuesto->numero . '</strong> con validez hasta el <strong>' . ($presupuesto->fecha_vencimiento ? date('d/m/Y', strtotime($presupuesto->fecha_vencimiento)) : 'N/A') . '</strong>.</p>
            
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <thead>
                    <tr style="background: #1e40af; color: white;">
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Descripción</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Cant.</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">P. Unit.</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>' . $itemsHtml . '</tbody>
            </table>
            
            <p style="text-align: right; font-size: 18px; font-weight: bold; color: #1e40af;">
                Total: ' . number_format($presupuesto->total, 2, ',', '.') . '
            </p>
            
            <p>Para aceptar este presupuesto, por favor contáctenos o responda a este correo.</p>
            <hr>
            <p style="font-size: 12px; color: #666;">' . $config->name . ' - ' . $config->nit . ' - ' . $config->direccion . ' - ' . $config->telefono . '</p>
        </div>';
    }

    public function buscarProductos() {
        try {
            $search = $_GET['q'] ?? '';
            $productos = $this->presupuestoModel->obtenerProductosInventario($search);
            return $this->jsonResponse(['success' => true, 'data' => $productos]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function buscarClientes() {
        try {
            $search = $_GET['q'] ?? '';
            $clientes = $this->presupuestoModel->obtenerClientes($search);
            return $this->jsonResponse(['success' => true, 'data' => $clientes]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getStats() {
        try {
            $desde = $_GET['desde'] ?? null;
            $hasta = $_GET['hasta'] ?? null;
            $stats = $this->presupuestoModel->obtenerEstadisticas($desde, $hasta);
            return $this->jsonResponse(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function activar($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $this->presupuestoModel->activar((int)$id, $_SESSION['user_id']);

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Presupuesto listo para anexar. Ahora aparece en OS y Facturación.',
                'redirect' => URLROOT . '/presupuesto/ver/' . $id
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function buscarActivos() {
        try {
            $search = $_GET['q'] ?? '';
            $presupuestos = $this->presupuestoModel->buscarActivos($search);
            return $this->jsonResponse(['success' => true, 'data' => $presupuestos]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function obtenerParaAnexar($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $presupuesto = $this->presupuestoModel->obtenerParaAnexar((int)$id);
            if (!$presupuesto) {
                return $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Presupuesto no encontrado o no está disponible para anexar'
                ], 404);
            }

            return $this->jsonResponse([
                'success' => true,
                'data' => $presupuesto,
                'items' => $presupuesto->items_normalizados
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function liberarInventario($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $motivo = $input['motivo'] ?? 'CANCELACION_MANUAL';

            $this->presupuestoModel->liberarInventario((int)$id, $_SESSION['user_id'], $motivo);

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Inventario liberado correctamente. Presupuesto vuelto a BORRADOR.',
                'redirect' => URLROOT . '/presupuesto/ver/' . $id
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function iniciarProceso($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $modulo = $input['modulo'] ?? 'OTRO';
            $referenciaId = $input['referencia_id'] ?? null;
            $crearReservas = array_key_exists('crear_reservas', $input) ? (bool)$input['crear_reservas'] : true;

            if (!$this->presupuestoModel->puedeAnexar((int)$id)) {
                return $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'El presupuesto no puede anexarse en su estado actual'
                ], 400);
            }

            $this->presupuestoModel->iniciarProceso((int)$id, $crearReservas);

            $modulosNombres = [
                'OS' => 'Orden de Servicio',
                'FACTURACION' => 'Facturación',
                'VENTA' => 'Venta Repuestos',
                'OTRO' => 'Otro'
            ];
            $moduloNombre = $modulosNombres[$modulo] ?? $modulo;

            $mensajeAuditoria = "Presupuesto #{$id} anexado a {$moduloNombre}";
            if ($referenciaId) {
                $mensajeAuditoria .= " (ref: #{$referenciaId})";
            }
            if (!$crearReservas) {
                $mensajeAuditoria .= " [sin reserva de stock]";
            }
            logAction('PRESUPUESTO', 'ANEXAR', $mensajeAuditoria);

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => $crearReservas
                    ? 'Presupuesto anexado correctamente. Stock reservado.'
                    : 'Presupuesto anexado correctamente.',
                'redirect' => URLROOT . '/presupuesto/ver/' . $id
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function aceptar($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $this->presupuestoModel->aceptar((int)$id, $_SESSION['user_id']);

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Presupuesto marcado como ACEPTADO. Stock reservado.',
                'redirect' => URLROOT . '/presupuesto/ver/' . $id
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function convertirAVenta($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $datosPago = $input['datos_pago'] ?? [];

            $result = $this->presupuestoModel->convertirAVenta((int)$id, $_SESSION['user_id'], $datosPago);

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Presupuesto convertido a venta correctamente.',
                'venta_id' => $result['venta_id'],
                'status' => $result['status'],
                'redirect' => URLROOT . '/venta/imprimirFactura/' . $result['venta_id']
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function obtener($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $presupuesto = $this->presupuestoModel->obtenerCompleto((int)$id);
            if (!$presupuesto) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Presupuesto no encontrado'], 404);
            }

            return $this->jsonResponse(['success' => true, 'data' => $presupuesto]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function obtenerReservas($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $reservas = $this->presupuestoModel->obtenerReservas((int)$id);
            return $this->jsonResponse(['success' => true, 'data' => $reservas]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function obtenerActivoCompleto($id = null) {
        return $this->obtenerParaAnexar($id);
    }
}