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
            'stats' => $this->presupuestoModel->obtenerEstadisticas()
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
     * Vista para editar presupuesto
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

        $data = [
            'titulo' => 'Editar Presupuesto',
            'presupuesto' => $presupuesto,
            'empresa' => $this->model('Empresa')->obtenerConfiguracion(),
            'config_iva' => $this->model('Empresa')->obtenerConfiguracion()->iva ?? 19.00
        ];
        $this->view('presupuesto/crear', $data);
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
     * AJAX: Crea un nuevo presupuesto
     */
    public function guardar() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $v = new Validator($input);
            $v->required(['cliente_nombre', 'items']);
            $v->array('items');
            
            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())], 400);
            }

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

            $input = json_decode(file_get_contents('php://input'), true);
            
            $v = new Validator($input);
            $v->required(['cliente_nombre', 'items']);
            $v->array('items');
            
            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())], 400);
            }

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
}