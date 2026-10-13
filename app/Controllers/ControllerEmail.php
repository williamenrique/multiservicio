<?php
/**
 * Controlador de Emails
 * Gestiona el historial de emails enviados y el envío de nuevos emails.
 * 
 * v3.2 (2026-10-13):
 *   • `index()` y `compose()` cargan el HTML por defecto desde
 *     `app/Views/email/templates/default_compose.php` y lo pasan a la vista
 *     como `$data['cuerpo_default']` para prellenar el textarea.
 *   • `enviar()` ahora reemplaza `{{empresa_*}}` en el asunto y cuerpo antes
 *     de enviar (antes se enviaban tal cual).
 *   • `enviarConPlantilla()` sin cambios (ya reemplazaba).
 *   • Nuevo helper privado `renderPlantillaDefault()`.
 * 
 * v3.1 (2026-10-13):
 *   • FIX: `getClientes()` usaba `$this->clienteModel->buscar()` — método que
 *     NO existe. Ahora usa `searchClients($term)`.
 * 
 * v3.0 (2026-10-13):
 *   • `guardarPlantilla()` acepta JSON Y FormData (auto-detect).
 *   • `reenviar($id)` acepta `nuevo_email` opcional en el body.
 *   • `preview()` nuevo endpoint.
 *   • `enviar()` y `enviarConPlantilla()` soportan CC y BCC, y devuelven
 *     `warnings` si algún adjunto fue rechazado por tipo/tamaño.
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

    public function index() {
        $empresa = $this->model('Empresa')->obtenerConfiguracion();
        $data = [
            'titulo' => 'Historial de Emails',
            'plantillas' => $this->emailModel->obtenerPlantillas(),
            'stats' => $this->emailModel->obtenerEstadisticas(),
            'cuerpo_default' => $this->renderPlantillaDefault($empresa),
        ];
        $this->view('email/index', $data);
    }

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

    public function compose() {
        $empresa = $this->model('Empresa')->obtenerConfiguracion();
        $data = [
            'titulo' => 'Nuevo Email',
            'clientes' => $this->clienteModel->listar(1000, 0, null)['data'] ?? [],
            'plantillas' => $this->emailModel->obtenerPlantillas(),
            'empresa' => $empresa,
            'cuerpo_default' => $this->renderPlantillaDefault($empresa),
        ];
        $this->view('email/compose', $data);
    }

    /**
     * Renderiza el HTML por defecto desde el archivo de plantilla.
     * Devuelve el HTML como string.
     */
    private function renderPlantillaDefault($empresa): string {
        $path = APPROOT . '/Views/email/templates/default_compose.php';
        if (!file_exists($path)) {
            // Fallback mínimo si el archivo no existe
            $nombre = htmlspecialchars($empresa->name ?? 'TALLER PRO');
            return "<h2>Estimado/a cliente,</h2>\n<p>Reciba un cordial saludo de parte de <strong>{$nombre}</strong>.</p>\n<p>Quedamos a su disposición para cualquier consulta.</p>\n<p>Cordialmente,<br><strong>{$nombre}</strong></p>";
        }

        ob_start();
        include $path;
        return ob_get_clean();
    }

    public function preview() {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $cuerpoHtml = $input['cuerpo_html'] ?? '';
            $asunto = $input['asunto'] ?? '';

            if (trim($cuerpoHtml) === '') {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'El cuerpo del email está vacío']);
            }

            $empresa = $this->model('Empresa')->obtenerConfiguracion();
            $variables = [
                'empresa_nombre'    => $empresa->name ?? 'TALLER PRO',
                'empresa_nit'       => $empresa->nit ?? '',
                'empresa_direccion' => $empresa->direccion ?? '',
                'empresa_telefono'  => $empresa->telefono ?? '',
                'empresa_email'     => $empresa->email ?? '',
            ];
            foreach ($variables as $k => $v) {
                $cuerpoHtml = str_replace('{{' . $k . '}}', $v, $cuerpoHtml);
            }

            $titulo = $asunto ?: 'Vista previa';
            $contenido = $cuerpoHtml;
            ob_start();
            include APPROOT . '/Views/email/layout.php';
            $htmlFinal = ob_get_clean();

            return $this->jsonResponse([
                'success' => true,
                'html_preview' => $htmlFinal,
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Envía email genérico.
     * v3.2: ahora reemplaza {{empresa_*}} en asunto y cuerpo antes de enviar.
     */
    public function enviar() {
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
            $v->required(['destinatario_email', 'asunto', 'cuerpo_html']);
            $v->email('destinatario_email');
            
            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())], 400);
            }

            $config = $this->model('Empresa')->obtenerConfiguracion();

            // v3.2: reemplazar variables de empresa en asunto y cuerpo
            $empresaVars = [
                'empresa_nombre'    => $config->name ?? 'TALLER PRO',
                'empresa_nit'       => $config->nit ?? '',
                'empresa_direccion' => $config->direccion ?? '',
                'empresa_telefono'  => $config->telefono ?? '',
                'empresa_email'     => $config->email ?? '',
            ];
            $asuntoFinal = $this->reemplazarVariables($input['asunto'], $empresaVars);
            $cuerpoFinal = $this->reemplazarVariables($input['cuerpo_html'], $empresaVars);

            $proc = $this->procesarAdjuntos($isFormData, $adjuntos);
            
            $emailData = [
                'to'              => $input['destinatario_email'],
                'to_name'         => $input['destinatario_nombre'] ?? '',
                'subject'         => $asuntoFinal,
                'body_html'       => $cuerpoFinal,
                'body_text'       => $input['cuerpo_texto'] ?? null,
                'attachments'     => $proc['attachments'],
                'cc'              => $input['cc'] ?? null,
                'bcc'             => $input['bcc'] ?? null,
                'from_name'       => $config->name ?? 'Taller Pro',
                'from_email'      => $config->email ?? 'noreply@tallerpro.com',
                'tipo'            => $input['tipo'] ?? 'OTRO',
                'referencia_tipo' => $input['referencia_tipo'] ?? 'NINGUNO',
                'referencia_id'   => !empty($input['referencia_id']) ? (int)$input['referencia_id'] : null,
            ];

            $emailService = new \App\Services\EmailService();
            $result = $emailService->enviarEmailGenerico($emailData);

            if (!empty($proc['warnings'])) {
                $result['warnings'] = $proc['warnings'];
            }

            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Envía email con plantilla. v3.0: soporta CC/BCC y warnings.
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

            $variables = $input['variables'] ?? [];
            if (is_string($variables)) {
                $variables = json_decode($variables, true) ?? [];
            }
            $config = $this->model('Empresa')->obtenerConfiguracion();
            
            $variables = array_merge([
                'empresa_nombre'    => $config->name ?? 'Taller Pro',
                'empresa_nit'       => $config->nit ?? '',
                'empresa_direccion' => $config->direccion ?? '',
                'empresa_telefono'  => $config->telefono ?? '',
                'empresa_email'     => $config->email ?? ''
            ], $variables);

            $asunto     = $this->reemplazarVariables($plantilla->asunto, $variables);
            $cuerpoHtml = $this->reemplazarVariables($plantilla->cuerpo_html, $variables);

            $proc = $this->procesarAdjuntos($isFormData, $adjuntos);

            $emailData = [
                'to'              => $input['destinatario_email'],
                'to_name'         => $input['destinatario_nombre'] ?? '',
                'subject'         => $asunto,
                'body_html'       => $cuerpoHtml,
                'attachments'     => $proc['attachments'],
                'cc'              => $input['cc'] ?? null,
                'bcc'             => $input['bcc'] ?? null,
                'from_name'       => $config->name ?? 'Taller Pro',
                'from_email'      => $config->email ?? 'noreply@tallerpro.com',
                'tipo'            => $plantilla->tipo,
                'referencia_tipo' => $input['referencia_tipo'] ?? 'NINGUNO',
                'referencia_id'   => !empty($input['referencia_id']) ? (int)$input['referencia_id'] : null,
            ];

            $emailService = new \App\Services\EmailService();
            $result = $emailService->enviarEmailGenerico($emailData);

            if (!empty($proc['warnings'])) {
                $result['warnings'] = $proc['warnings'];
            }

            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    private function procesarAdjuntos(bool $isFormData, $adjuntos): array {
        $attachments = [];
        $warnings = [];
        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $maxSize = 10 * 1024 * 1024;
        
        if ($isFormData && !empty($adjuntos['name'][0])) {
            $uploadDir = APPROOT . '/../public_html/uploads/emails/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            
            $fileCount = count($adjuntos['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $originalName = basename($adjuntos['name'][$i]);
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $size = (int)($adjuntos['size'][$i] ?? 0);

                if ($adjuntos['error'][$i] !== UPLOAD_ERR_OK) {
                    $warnings[] = "No se pudo subir el adjunto '{$originalName}' (código {$adjuntos['error'][$i]}).";
                    continue;
                }
                if (!in_array($ext, $allowedExt)) {
                    $warnings[] = "El adjunto '{$originalName}' tiene una extensión no permitida (solo: " . implode(', ', $allowedExt) . ").";
                    continue;
                }
                if ($size > $maxSize) {
                    $warnings[] = "El adjunto '{$originalName}' supera el tamaño máximo de 10 MB.";
                    continue;
                }

                $newName  = uniqid('email_') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $destPath = $uploadDir . $newName;

                if (move_uploaded_file($adjuntos['tmp_name'][$i], $destPath)) {
                    $attachments[] = [
                        'path' => $destPath,
                        'name' => $originalName
                    ];
                } else {
                    $warnings[] = "No se pudo mover el adjunto '{$originalName}' al directorio de destino.";
                }
            }
        } elseif (!empty($adjuntos) && is_array($adjuntos)) {
            $attachments = $adjuntos;
        }
        
        return ['attachments' => $attachments, 'warnings' => $warnings];
    }

    private function reemplazarVariables($texto, $variables) {
        foreach ($variables as $key => $value) {
            $texto = str_replace('{{' . $key . '}}', $value, $texto);
        }
        return $texto;
    }

    public function getClientes() {
        try {
            $search = trim($_GET['q'] ?? '');
            $clientes = $this->clienteModel->searchClients($search);
            return $this->jsonResponse(['success' => true, 'data' => $clientes]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function plantillas() {
        $data = [
            'titulo' => 'Plantillas de Email',
            'plantillas' => $this->emailModel->obtenerPlantillas()
        ];
        $this->view('email/plantillas', $data);
    }

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
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            $isJson = strpos($contentType, 'application/json') !== false;

            if ($isJson) {
                $input = json_decode(file_get_contents('php://input'), true) ?: [];
            } else {
                $input = $_POST;
            }

            if (isset($input['activo'])) {
                $input['activo'] = (int)$input['activo'] ? 1 : 0;
            }

            $v = new Validator($input);
            $v->required(['nombre', 'tipo', 'asunto', 'cuerpo_html']);
            
            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'mensaje' => implode(' ', $v->getErrors())], 400);
            }

            $id = !empty($input['id']) ? (int)$input['id'] : null;
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

    public function reenviar($id = null) {
        try {
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'ID requerido'], 400);
            }

            $email = $this->emailModel->obtenerPorId((int)$id);
            if (!$email) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Email no encontrado'], 404);
            }

            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true) ?: [];
            $nuevoEmail = !empty($input['nuevo_email']) ? trim($input['nuevo_email']) : null;

            $destinatarioEmail = $nuevoEmail ?: $email->destinatario_email;
            $destinatarioNombre = $nuevoEmail ? '' : ($email->destinatario_nombre ?? '');

            if ($nuevoEmail && !filter_var($nuevoEmail, FILTER_VALIDATE_EMAIL)) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'El nuevo correo no es válido'], 400);
            }

            $config = $this->model('Empresa')->obtenerConfiguracion();
            $emailData = [
                'to'              => $destinatarioEmail,
                'to_name'         => $destinatarioNombre,
                'subject'         => $email->asunto,
                'body_html'       => $email->cuerpo_html,
                'body_text'       => $email->cuerpo_texto,
                'attachments'     => $email->adjuntos ? json_decode($email->adjuntos, true) : [],
                'from_name'       => $config->name ?? 'Taller Pro',
                'from_email'      => $config->email ?? 'noreply@tallerpro.com',
                'tipo'            => $email->tipo,
                'referencia_tipo' => $email->referencia_tipo,
                'referencia_id'   => $email->referencia_id,
            ];

            $emailService = new \App\Services\EmailService();
            $result = $emailService->enviarEmailGenerico($emailData);

            if (!$nuevoEmail) {
                $nuevoEstado = $result['success'] ? 'ENVIADO' : 'FALLIDO';
                $this->emailModel->actualizarEstado(
                    $email->id,
                    $nuevoEstado,
                    $result['success'] ? null : ($result['mensaje'] ?? 'Error al reenviar')
                );
            }

            return $this->jsonResponse($result);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }
}