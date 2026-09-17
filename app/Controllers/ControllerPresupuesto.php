<?php
/**
 * Controlador de Presupuestos
 * Gestiona la creación, edición, envío y seguimiento de presupuestos/cotizaciones.
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

    /**
     * Vista principal: lista de presupuestos
     */
    public function index() {
        $data = [
            'titulo' => 'Presupuestos / Cotizaciones',
            'stats' => $this->presupuestoModel->obtenerEstadisticas(),
            'config_iva' => $this->model('Empresa')->obtenerConfiguracion()->iva ?? 19.00
        ];
        $this->view('presupuesto/index', $data);
    }

    /**
     * AJAX: Lista presupuestos con paginación y filtros
     */
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

    /**
     * Vista para crear nuevo presupuesto
     */
    public function crear() {
        $data = [
            'titulo' => 'Nuevo Presupuesto',
            'empresa' => $this->model('Empresa')->obtenerConfiguracion(),
            'config_iva' => $this->model('Empresa')->obtenerConfiguracion()->iva ?? 19.00
        ];
        $this->view('presupuesto/crear', $data);
    }

    /**
     * Vista para editar presupuesto - redirige al index con parámetro de edición
     */
    public function editar($id = null) {
        if (!$id) {
            redirect('presupuesto');
        }

        $presupuesto = $this->presupuestoModel->obtenerCompleto((int)$id);
        if (!$presupuesto) {
            $this->view('errores/404', ['titulo' => 'Presupuesto no encontrado']);
            return;
        }

        // Solo permitir editar si está en BORRADOR
        if ($presupuesto->estado !== 'BORRADOR') {
            redirect('presupuesto/ver/' . $id);
        }

        // Redirigir al index con parámetro para editar
        redirect('presupuesto?edit=' . $id);
    }

    /**
     * Vista de detalle de presupuesto
     */
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

    /**
     * Procesa la entrada del request (soporta JSON y FormData)
     */
    private function procesarInput() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            return $input ?: [];
        }
        
        // Para FormData (multipart/form-data), usar $_POST y procesar items
        $input = $_POST;
        
        // Procesar items anidados (items[1][descripcion], etc.)
        $items = [];
        foreach ($input as $key => $value) {
            if (preg_match('/^items\[(\d+)\]\[(.+)\]$/', $key, $matches)) {
                $index = $matches[1];
                $field = $matches[2];
                $items[$index][$field] = $value;
            }
        }
        
        if (!empty($items)) {
            // Reordenar items por índice numérico
            ksort($items);
            $input['items'] = array_values($items);
        }
        
        // Convertir checkboxes - IVA activo por defecto (1 = sí, 0 = no)
        $input['iva_activo'] = isset($input['iva_activo']) ? (int)$input['iva_activo'] : 1;
        
        return $input;
    }

    /**
     * Procesa cliente y vehículo: crea si no existen
     */
    private function procesarClienteVehiculo($data) {
        $clienteModel = $this->model('Cliente');
        $vehiculoModel = $this->model('Vehiculo');
        
        $clienteId = $data['cliente_id'] ?? null;
        $clienteCedula = $data['cliente_cedula'] ?? null;
        $clienteNombre = $data['cliente_nombre'] ?? null;
        
        // Si no hay cliente_id pero hay cédula, buscar si existe
        if (!$clienteId && $clienteCedula) {
            $clienteExistente = $clienteModel->obtenerPorId($clienteCedula);
            if ($clienteExistente) {
                $clienteId = $clienteExistente->id;
            }
        }
        
        // Si no existe cliente, crearlo
        if (!$clienteId && $clienteNombre) {
            // Usar cédula como ID si está disponible, sino generar uno único
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
        
        // Actualizar data con cliente_id
        $data['cliente_id'] = $clienteId;
        
        // Procesar vehículo si hay datos de placa
        $vehiculoPlaca = $data['vehiculo_placa'] ?? null;
        if ($vehiculoPlaca && $clienteId) {
            // Verificar si el vehículo ya existe
            $vehiculoExistente = $vehiculoModel->buscarPorPlaca($vehiculoPlaca);
            if (!$vehiculoExistente) {
                $vehiculoData = [
                    'placa' => strtoupper($vehiculoPlaca),
                    'marca' => mb_strtoupper($data['vehiculo_marca'] ?? '', 'UTF-8'),
                    'modelo' => mb_strtoupper($data['vehiculo_modelo'] ?? '', 'UTF-8'),
                    'anio' => $data['vehiculo_anio'] ?? null,
                    'color' => mb_strtoupper($data['vehiculo_color'] ?? '', 'UTF-8'),
                    'cliente_id' => $clienteId
                ];
                $vehiculoModel->registrar($vehiculoData);
            }
        }
        
        return $data;
    }

    /**
     * Convierte campos de texto a mayúsculas
     */
    private function convertirAMayusculas($data) {
        $camposMayusculas = ['observaciones', 'condiciones', 'cliente_nombre', 'cliente_direccion', 
                            'vehiculo_marca', 'vehiculo_modelo', 'vehiculo_color'];
        
        foreach ($camposMayusculas as $campo) {
            if (isset($data[$campo]) && $data[$campo] !== null) {
                $data[$campo] = mb_strtoupper($data[$campo], 'UTF-8');
            }
        }
        
        // Email a minúsculas
        if (isset($data['cliente_email'])) {
            $data['cliente_email'] = mb_strtolower($data['cliente_email'], 'UTF-8');
        }
        
        return $data;
    }

    /**
     * AJAX: Crea un nuevo presupuesto
     */
    public function guardar() {
        try {
            $input = $this->procesarInput();
            
            $v = new Validator($input);
            $v->required(['cliente_nombre', 'items']);
            $v->array('items');
            
            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())], 400);
            }

            // Convertir campos a mayúsculas
            $input = $this->convertirAMayusculas($input);
            
            // Procesar cliente y vehículo
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

    /**
     * AJAX: Actualiza un presupuesto existente
     */
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

            // Convertir campos a mayúsculas
            $input = $this->convertirAMayusculas($input);
            
            // Procesar cliente y vehículo
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
     * AJAX: Cambia el estado del presupuesto
     */
    public function cambiarEstado($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $estado = $input['estado'] ?? '';

            $this->presupuestoModel->cambiarEstado((int)$id, $estado);

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Estado actualizado correctamente'
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Elimina un presupuesto (solo BORRADOR)
     */
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

    /**
     * AJAX: Genera y devuelve el PDF del presupuesto
     */
    public function pdf($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $presupuesto = $this->presupuestoModel->obtenerCompleto((int)$id);
            if (!$presupuesto) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Presupuesto no encontrado'], 404);
            }

            $pdfService = new \App\Services\PdfService();
            $doc_name = 'PRES-' . str_pad($presupuesto->id, 4, '0', STR_PAD_LEFT);
            $filename = $doc_name . '_' . time() . '.pdf';
            $filePath = $pdfService->generarDocumento('presupuesto', [
                'presupuesto' => $presupuesto,
                'items' => $presupuesto->items,
                'empresa' => $this->model('Empresa')->obtenerConfiguracion()
            ], $filename, false);

            return $this->jsonResponse(['success' => true, 'pdf_url' => URLROOT . '/' . $filePath]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Sirve el PDF directamente en el navegador
     */
    public function imprimir($id = null) {
        if (!$id) {
            throw new AppException("ID de presupuesto no proporcionado.", 400);
        }

        if (strpos($id, '.pdf') !== false) {
            $filePath = APPROOT . '/../public/temp_pdfs/' . $id;
            if (file_exists($filePath)) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $id . '"');
                readfile($filePath);
                exit;
            }
        }

        $presupuestoId = (int)$id;
        $presupuesto = $this->presupuestoModel->obtenerCompleto($presupuestoId);
        if (!$presupuesto) {
            throw new AppException("El presupuesto #$presupuestoId no existe.", 404);
        }

        $pdfService = new \App\Services\PdfService();
        $doc_name = 'PRES-' . str_pad($presupuesto->id, 4, '0', STR_PAD_LEFT);
        $pdfService->generarDocumento('presupuesto', [
            'presupuesto' => $presupuesto,
            'items' => $presupuesto->items,
            'empresa' => $this->model('Empresa')->obtenerConfiguracion()
        ], $doc_name . '.pdf');
        exit;
    }

    /**
     * AJAX: Envía el presupuesto por email
     */
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

            // Generar PDF para adjuntar
            $pdfService = new \App\Services\PdfService();
            $doc_name = 'PRES-' . str_pad($presupuesto->id, 4, '0', STR_PAD_LEFT);
            $filename = $doc_name . '.pdf';
            $filePath = $pdfService->generarDocumento('presupuesto', [
                'presupuesto' => $presupuesto,
                'items' => $presupuesto->items,
                'empresa' => $this->model('Empresa')->obtenerConfiguracion()
            ], $filename, false);

            $fullPath = dirname(APPROOT) . '/public/' . $filePath;

            // Usar plantilla de email para presupuesto
            $plantilla = $this->emailModel->obtenerPlantillas('PRESUPUESTO');
            $plantilla = $plantilla[0] ?? null;

            $config = $this->model('Empresa')->obtenerConfiguracion();
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
                'attachments' => [$fullPath],
                'from_name' => $config->name ?? 'Taller Pro',
                'from_email' => $config->email ?? 'noreply@tallerpro.com'
            ];

            $emailService = new \App\Services\EmailService();
            $result = $emailService->enviarEmailGenerico($emailData);

            // Registrar email enviado
            $this->emailModel->registrar([
                'tipo' => 'PRESUPUESTO',
                'destinatario_email' => $input['destinatario_email'],
                'destinatario_nombre' => $input['destinatario_nombre'] ?? $presupuesto->cliente_nombre,
                'asunto' => $asunto,
                'cuerpo_html' => $cuerpoHtml,
                'adjuntos' => [$filePath],
                'referencia_tipo' => 'PRESUPUESTO',
                'referencia_id' => $presupuesto->id,
                'estado' => $result['success'] ? 'ENVIADO' : 'FALLIDO',
                'error_mensaje' => $result['success'] ? null : ($result['mensaje'] ?? 'Error desconocido'),
                'usuario_id' => $_SESSION['user_id'],
                'fecha_envio' => $result['success'] ? date('Y-m-d H:i:s') : null
            ]);

            // Cambiar estado a ENVIADO si estaba en BORRADOR
            if ($presupuesto->estado === 'BORRADOR' && $result['success']) {
                $this->presupuestoModel->cambiarEstado($presupuesto->id, 'ENVIADO');
            }

            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Reemplaza variables en una plantilla
     */
    private function reemplazarVariables($texto, $variables) {
        foreach ($variables as $key => $value) {
            $texto = str_replace('{{' . $key . '}}', $value, $texto);
        }
        return $texto;
    }

    /**
     * Genera cuerpo HTML para presupuesto sin plantilla
     */
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

    /**
     * AJAX: Busca productos del inventario para agregar al presupuesto
     */
    public function buscarProductos() {
        try {
            $search = $_GET['q'] ?? '';
            $productos = $this->presupuestoModel->obtenerProductosInventario($search);
            return $this->jsonResponse(['success' => true, 'data' => $productos]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Busca clientes para el selector
     */
    public function buscarClientes() {
        try {
            $search = $_GET['q'] ?? '';
            $clientes = $this->presupuestoModel->obtenerClientes($search);
            return $this->jsonResponse(['success' => true, 'data' => $clientes]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Obtiene estadísticas para el dashboard
     */
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

    /**
     * AJAX: Activa un presupuesto y reserva inventario
     */
    public function activar($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $result = $this->presupuestoModel->activar((int)$id, $_SESSION['user_id']);

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Presupuesto activado correctamente. Stock reservado en inventario.',
                'redirect' => URLROOT . '/presupuesto/ver/' . $id
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Busca presupuestos en estado ACTIVO para anexar a OS/Facturación/Venta
     */
    public function buscarActivos() {
        try {
            $search = $_GET['q'] ?? '';
            $presupuestos = $this->presupuestoModel->buscarActivos($search);
            return $this->jsonResponse(['success' => true, 'data' => $presupuestos]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Obtiene detalle completo de un presupuesto activo con reservas
     */
    public function obtenerActivoCompleto($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $presupuesto = $this->presupuestoModel->obtenerActivoCompleto((int)$id);
            if (!$presupuesto) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Presupuesto no encontrado o no está activo'], 404);
            }

            return $this->jsonResponse(['success' => true, 'data' => $presupuesto]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Libera inventario reservado (cancelar activación)
     */
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

    /**
     * AJAX: Pasa un presupuesto de ACTIVO a EN_PROCESO cuando se anexa a OS/Facturación/Venta
     */
    public function iniciarProceso($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $modulo = $input['modulo'] ?? 'OTRO'; // OS, FACTURACION, VENTA
            $referenciaId = $input['referencia_id'] ?? null;

            if (!$this->presupuestoModel->puedeAnexar((int)$id)) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'El presupuesto no está en estado ACTIVO'], 400);
            }

            $this->presupuestoModel->iniciarProceso((int)$id);

            // Registrar en auditoría
            $modulosNombres = [
                'OS' => 'Orden de Servicio',
                'FACTURACION' => 'Facturación',
                'VENTA' => 'Venta Repuestos',
                'OTRO' => 'Otro'
            ];
            $moduloNombre = $modulosNombres[$modulo] ?? $modulo;

            logAction('PRESUPUESTO', 'ANEXAR', "Presupuesto anexado a {$moduloNombre} #{$referenciaId}");

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Presupuesto anexado correctamente. Estado cambiado a EN_PROCESO.',
                'redirect' => URLROOT . '/presupuesto/ver/' . $id
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Acepta un presupuesto y reserva inventario
     * Cambia estado a ACEPTADO
     */
    public function aceptar($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $result = $this->presupuestoModel->aceptar((int)$id, $_SESSION['user_id']);

            return $this->jsonResponse([
                'success' => true,
                'mensaje' => 'Presupuesto aceptado correctamente. Stock reservado en inventario.',
                'redirect' => URLROOT . '/presupuesto/ver/' . $id
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Convierte un presupuesto en una venta (factura)
     * Descuenta inventario y crea registro de venta
     */
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
                'redirect' => URLROOT . '/facturacion/ver/' . $result['venta_id']
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Obtiene un presupuesto completo para editar
     */
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

    /**
     * AJAX: Obtiene las reservas de un presupuesto
     */
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
}