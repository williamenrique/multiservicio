<?php
/**
 * Controlador de Emails
 * Gestiona el historial de emails enviados y el envío de nuevos emails.
 * 
 * NOTA: El logging en `table_emails` lo hace AHORA `EmailService` de forma
 * automática. Este controlador ya NO registra manualmente para evitar
 * duplicados. Ver `App\Services\EmailService::logEnvio()`.
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
     * NOTA: El registro en `table_emails` lo hace EmailService::logEnvio().
     */
    public function enviar() {
        try {
            // Detectar si es FormData (multipart/form-data) o JSON
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            $isFormData = strpos($contentType, 'multipart/form-data') !== false;
            
            if ($isFormData) {
                $input = $_POST;
                $adjuntos = $_FILES['adjuntos'] ?? [];
            } else {
                $input = json_decode(file_get_contents('php://input'), true) ?: [];
                $adjuntos = $input['adjuntos'] ?? [];
            }
            
            $v = new Validator($input);
            $v->required(['destinatario_email', 'asunto', 'cuerpo_html']);
            $v->email('destinatario_email');
            
            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())], 400);
            }

            $config = $this->model('Empresa')->obtenerConfiguracion();
            $attachments = $this->procesarAdjuntos($isFormData, $adjuntos);
            
            $emailData = [
                'to'              => $input['destinatario_email'],
                'to_name'         => $input['destinatario_nombre'] ?? '',
                'subject'         => $input['asunto'],
                'body_html'       => $input['cuerpo_html'],
                'body_text'       => $input['cuerpo_texto'] ?? null,
                'attachments'     => $attachments,
                'from_name'       => $config->name ?? 'Taller Pro',
                'from_email'      => $config->email ?? 'noreply@tallerpro.com',
                // Metadatos para el log automático de EmailService
                'tipo'            => $input['tipo'] ?? 'OTRO',
                'referencia_tipo' => $input['referencia_tipo'] ?? 'NINGUNO',
                'referencia_id'   => !empty($input['referencia_id']) ? (int)$input['referencia_id'] : null,
            ];

            $emailService = new \App\Services\EmailService();
            $result = $emailService->enviarEmailGenerico($emailData);

            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Envía email con plantilla (factura, presupuesto, etc.)
     * NOTA: El registro en `table_emails` lo hace EmailService::logEnvio().
     */
    public function enviarConPlantilla() {
        try {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            $isFormData = strpos($contentType, 'multipart/form-data') !== false;
            
            if ($isFormData) {
                $input = $_POST;
                $adjuntos = $_FILES['adjuntos'] ?? [];
            } else {
                $input = json_decode(file_get_contents('php://input'), true) ?: [];
                $adjuntos = $input['adjuntos'] ?? [];
            }
            
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
            if (is_string($variables)) {
                $variables = json_decode($variables, true) ?? [];
            }
            $config = $this->model('Empresa')->obtenerConfiguracion();
            
            // Agregar variables de empresa
            $variables = array_merge([
                'empresa_nombre'    => $config->name ?? 'Taller Pro',
                'empresa_nit'       => $config->nit ?? '',
                'empresa_direccion' => $config->direccion ?? '',
                'empresa_telefono'  => $config->telefono ?? '',
                'empresa_email'     => $config->email ?? ''
            ], $variables);

            $asunto     = $this->reemplazarVariables($plantilla->asunto, $variables);
            $cuerpoHtml = $this->reemplazarVariables($plantilla->cuerpo_html, $variables);

            $attachments = $this->procesarAdjuntos($isFormData, $adjuntos);

            $emailData = [
                'to'              => $input['destinatario_email'],
                'to_name'         => $input['destinatario_nombre'] ?? '',
                'subject'         => $asunto,
                'body_html'       => $cuerpoHtml,
                'attachments'     => $attachments,
                'from_name'       => $config->name ?? 'Taller Pro',
                'from_email'      => $config->email ?? 'noreply@tallerpro.com',
                // Metadatos para el log automático
                'tipo'            => $plantilla->tipo,
                'referencia_tipo' => $input['referencia_tipo'] ?? 'NINGUNO',
                'referencia_id'   => !empty($input['referencia_id']) ? (int)$input['referencia_id'] : null,
            ];

            $emailService = new \App\Services\EmailService();
            $result = $emailService->enviarEmailGenerico($emailData);

            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper: procesa los adjuntos recibidos (desde $_FILES o desde array de paths).
     * Devuelve un array de ['path' => ..., 'name' => ...].
     */
    private function procesarAdjuntos(bool $isFormData, $adjuntos): array {
        $attachments = [];
        
        if ($isFormData && !empty($adjuntos['name'][0])) {
            $uploadDir = APPROOT . '/../public_html/uploads/emails/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileCount = count($adjuntos['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($adjuntos['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName       = $adjuntos['tmp_name'][$i];
                    $originalName  = basename($adjuntos['name'][$i]);
                    $extension     = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $allowedExt    = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
                    
                    if (in_array($extension, $allowedExt)) {
                        $newName  = uniqid('email_') . '_' . $originalName;
                        $destPath = $uploadDir . $newName;
                        
                        if (move_uploaded_file($tmpName, $destPath)) {
                            $attachments[] = [
                                'path' => $destPath,
                                'name' => $originalName
                            ];
                        }
                    }
                }
            }
        } elseif (!empty($adjuntos) && is_array($adjuntos)) {
            // Adjuntos ya procesados (rutas de archivos)
            $attachments = $adjuntos;
        }
        
        return $attachments;
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
     * Vista HTML: gestión de plantillas
     */
    public function plantillas() {
        $data = [
            'titulo' => 'Plantillas de Email',
            'plantillas' => $this->emailModel->obtenerPlantillas()
        ];
        $this->view('email/plantillas', $data);
    }

    /**
     * AJAX: Lista plantillas en formato JSON (para el DataTable de la vista).
     * Reemplaza al roto `Email@plantillas` que devolvía HTML.
     */
    public function listarPlantillas() {
        try {
            $plantillas = $this->emailModel->obtenerPlantillas(null, false);
            return $this->jsonResponse([
                'success' => true,
                'data'    => $plantillas,
                'total'   => count($plantillas),
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
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

    /**
     * AJAX: Obtiene una plantilla por ID
     */
    public function obtenerPlantilla($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $plantilla = $this->emailModel->obtenerPlantilla((int)$id);
            if (!$plantilla) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Plantilla no encontrada'], 404);
            }

            return $this->jsonResponse(['success' => true, 'plantilla' => $plantilla]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Obtiene un email por ID para ver detalle
     */
    public function obtener($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $email = $this->emailModel->obtenerPorId((int)$id);
            if (!$email) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Email no encontrado'], 404);
            }

            return $this->jsonResponse(['success' => true, 'email' => $email]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Reenvía un email fallido
     */
    public function reenviar($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $email = $this->emailModel->obtenerPorId((int)$id);
            if (!$email) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Email no encontrado'], 404);
            }

            if ($email->estado !== 'FALLIDO') {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Solo se pueden reenviar emails fallidos'], 400);
            }

            $config = $this->model('Empresa')->obtenerConfiguracion();
            $emailData = [
                'to'              => $email->destinatario_email,
                'to_name'         => $email->destinatario_nombre,
                'subject'         => $email->asunto,
                'body_html'       => $email->cuerpo_html,
                'body_text'       => $email->cuerpo_texto,
                'attachments'     => $email->adjuntos ? json_decode($email->adjuntos, true) : [],
                'from_name'       => $config->name ?? 'Taller Pro',
                'from_email'      => $config->email ?? 'noreply@tallerpro.com',
                // Preservar tipo y referencia originales
                'tipo'            => $email->tipo,
                'referencia_tipo' => $email->referencia_tipo,
                'referencia_id'   => $email->referencia_id,
            ];

            $emailService = new \App\Services\EmailService();
            $result = $emailService->enviarEmailGenerico($emailData);

            // Actualizar estado del email original (el nuevo intento queda como registro nuevo)
            $nuevoEstado = $result['success'] ? 'ENVIADO' : 'FALLIDO';
            $this->emailModel->actualizarEstado(
                $email->id,
                $nuevoEstado,
                $result['success'] ? null : ($result['mensaje'] ?? 'Error al reenviar')
            );

            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }
}