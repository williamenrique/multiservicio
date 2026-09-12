<?php
/**
 * Controlador de Emails
 * Gestiona el historial de emails enviados y el envío de nuevos emails.
 */
class ControllerEmail extends Controller {
    private $emailModel;
    private $clienteModel;

    public function __construct() {
        AuthGuard::handle();
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        $this->emailModel = $this->model('Email');
        $this->clienteModel = $this->model('Cliente');
    }

    /**
     * Vista principal: historial de emails
     */
    public function index() {
        $data = [
            'titulo' => 'Historial de Emails',
            'plantillas' => $this->emailModel->obtenerPlantillas(),
            'stats' => $this->emailModel->obtenerEstadisticas()
        ];
        $this->view('email/index', $data);
    }

    /**
     * AJAX: Lista emails con paginación y filtros
     */
    public function listar() {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            
            $limit = isset($input['limit']) ? (int)$input['limit'] : 20;
            $page = isset($input['page']) ? (int)$input['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            $filters = [
                'tipo' => $input['tipo'] ?? null,
                'estado' => $input['estado'] ?? null,
                'desde' => $input['desde'] ?? null,
                'hasta' => $input['hasta'] ?? null,
                'search' => $input['search'] ?? null
            ];

            $resultado = $this->emailModel->listar($limit, $offset, $filters);
            
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
     * Vista para componer nuevo email
     */
    public function compose() {
        $data = [
            'titulo' => 'Nuevo Email',
            'clientes' => $this->clienteModel->listar(1000, 0, null)['data'] ?? [],
            'plantillas' => $this->emailModel->obtenerPlantillas(),
            'empresa' => $this->model('Empresa')->obtenerConfiguracion()
        ];
        $this->view('email/compose', $data);
    }

    /**
     * AJAX: Envía un email
     */
    public function enviar() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $v = new Validator($input);
            $v->required(['destinatario_email', 'asunto', 'cuerpo_html']);
            $v->email('destinatario_email');
            
            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())], 400);
            }

            // Obtener configuración de email
            $config = $this->model('Empresa')->obtenerConfiguracion();
            
            // Preparar datos para el servicio de email
            $emailData = [
                'to' => $input['destinatario_email'],
                'to_name' => $input['destinatario_nombre'] ?? '',
                'subject' => $input['asunto'],
                'body_html' => $input['cuerpo_html'],
                'body_text' => $input['cuerpo_texto'] ?? null,
                'attachments' => $input['adjuntos'] ?? [],
                'from_name' => $config->name ?? 'Taller Pro',
                'from_email' => $config->email ?? 'noreply@tallerpro.com'
            ];

            // Enviar email usando PHPMailer
            $emailService = new \App\Services\EmailService();
            $result = $emailService->enviarEmailGenerico($emailData);

            // Registrar en historial
            $this->emailModel->registrar([
                'tipo' => $input['tipo'] ?? 'OTRO',
                'destinatario_email' => $input['destinatario_email'],
                'destinatario_nombre' => $input['destinatario_nombre'] ?? '',
                'asunto' => $input['asunto'],
                'cuerpo_html' => $input['cuerpo_html'],
                'cuerpo_texto' => $input['cuerpo_texto'] ?? null,
                'adjuntos' => $input['adjuntos'] ?? [],
                'referencia_tipo' => $input['referencia_tipo'] ?? 'NINGUNO',
                'referencia_id' => $input['referencia_id'] ?? null,
                'estado' => $result['success'] ? 'ENVIADO' : 'FALLIDO',
                'error_mensaje' => $result['success'] ? null : ($result['mensaje'] ?? 'Error desconocido'),
                'usuario_id' => $_SESSION['user_id'],
                'fecha_envio' => $result['success'] ? date('Y-m-d H:i:s') : null
            ]);

            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Envía email con plantilla (factura, presupuesto, etc.)
     */
    public function enviarConPlantilla() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $v = new Validator($input);
            $v->required(['plantilla_id', 'destinatario_email']);
            $v->email('destinatario_email');
            
            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())], 400);
            }

            $plantilla = $this->emailModel->obtenerPlantilla($input['plantilla_id']);
            if (!$plantilla) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Plantilla no encontrada'], 404);
            }

            // Obtener datos de referencia si se proporciona
            $variables = $input['variables'] ?? [];
            $config = $this->model('Empresa')->obtenerConfiguracion();
            
            // Agregar variables de empresa
            $variables = array_merge([
                'empresa_nombre' => $config->name ?? 'Taller Pro',
                'empresa_nit' => $config->nit ?? '',
                'empresa_direccion' => $config->direccion ?? '',
                'empresa_telefono' => $config->telefono ?? '',
                'empresa_email' => $config->email ?? ''
            ], $variables);

            // Reemplazar variables en asunto y cuerpo
            $asunto = $this->reemplazarVariables($plantilla->asunto, $variables);
            $cuerpoHtml = $this->reemplazarVariables($plantilla->cuerpo_html, $variables);

            // Enviar
            $config = $this->model('Empresa')->obtenerConfiguracion();
            $emailData = [
                'to' => $input['destinatario_email'],
                'to_name' => $input['destinatario_nombre'] ?? '',
                'subject' => $asunto,
                'body_html' => $cuerpoHtml,
                'from_name' => $config->name ?? 'Taller Pro',
                'from_email' => $config->email ?? 'noreply@tallerpro.com'
            ];

            $emailService = new \App\Services\EmailService();
            $result = $emailService->enviarEmailGenerico($emailData);

            // Registrar
            $this->emailModel->registrar([
                'tipo' => $plantilla->tipo,
                'destinatario_email' => $input['destinatario_email'],
                'destinatario_nombre' => $input['destinatario_nombre'] ?? '',
                'asunto' => $asunto,
                'cuerpo_html' => $cuerpoHtml,
                'referencia_tipo' => $input['referencia_tipo'] ?? 'NINGUNO',
                'referencia_id' => $input['referencia_id'] ?? null,
                'estado' => $result['success'] ? 'ENVIADO' : 'FALLIDO',
                'error_mensaje' => $result['success'] ? null : ($result['mensaje'] ?? 'Error desconocido'),
                'usuario_id' => $_SESSION['user_id'],
                'fecha_envio' => $result['success'] ? date('Y-m-d H:i:s') : null
            ]);

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
     * AJAX: Obtiene clientes para el selector
     */
    public function getClientes() {
        try {
            $search = $_GET['q'] ?? '';
            $clientes = $this->clienteModel->buscar($search);
            return $this->jsonResponse(['success' => true, 'data' => $clientes]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Gestión de plantillas
     */
    public function plantillas() {
        $data = [
            'titulo' => 'Plantillas de Email',
            'plantillas' => $this->emailModel->obtenerPlantillas()
        ];
        $this->view('email/plantillas', $data);
    }

    public function guardarPlantilla() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $v = new Validator($input);
            $v->required(['nombre', 'tipo', 'asunto', 'cuerpo_html']);
            
            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())], 400);
            }

            $id = $input['id'] ?? null;
            $result = $this->emailModel->guardarPlantilla($input, $id);
            
            return $this->jsonResponse(['success' => $result, 'mensaje' => $result ? 'Plantilla guardada' : 'Error al guardar']);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function eliminarPlantilla($id) {
        try {
            $result = $this->emailModel->eliminarPlantilla((int)$id);
            return $this->jsonResponse(['success' => $result, 'mensaje' => $result ? 'Plantilla eliminada' : 'Error al eliminar']);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }
}