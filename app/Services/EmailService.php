<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * EmailService — Servicio centralizado de envío de correos electrónicos
 * 
 * Todas las notificaciones del sistema pasan por aquí.
 * Usa PHPMailer con SMTP y plantillas HTML desde Views/email/
 * 
 * IMPORTANTE: Este servicio registra AUTOMÁTICAMENTE cada envío en
 * la tabla `table_emails` (ver método `logEnvio`). Los controladores
 * NO deben llamar a ModelEmail->registrar() después de invocar
 * cualquiera de los métodos públicos de este servicio.
 */
class EmailService
{
    private PHPMailer $mailer;
    private ?object $empresa = null;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configurarSMTP();
    }

    /**
     * Configura los parámetros SMTP desde las constantes globales
     */
    private function configurarSMTP(): void {
        $this->mailer->isSMTP();
        $this->mailer->Host       = MAIL_HOST;
        $this->mailer->Port       = MAIL_PORT;
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = MAIL_USERNAME;
        $this->mailer->Password   = MAIL_PASSWORD;
        $this->mailer->SMTPSecure = MAIL_ENCRYPTION;
        $this->mailer->CharSet    = 'UTF-8';
        $this->mailer->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    }

    /**
     * Carga los datos de la empresa (cache en memoria)
     */
    private function getEmpresa(): object {
        if ($this->empresa === null) {
            $modelEmpresa = new \ModelEmpresa();
            $this->empresa = $modelEmpresa->obtenerConfiguracion();
        }
        return $this->empresa;
    }

    /**
     * Renderiza una vista de email con los datos proporcionados
     */
    public function renderizar(string $vista, array $data = []): string {
        $empresa = $this->getEmpresa();
        
        // Normalizar items: convertir objetos stdClass a arrays
        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = array_map(function($item) {
                return (array) $item;
            }, $data['items']);
        }
        
        extract($data);
        ob_start();
        $vistaPath = APPROOT . '/Views/email/' . $vista . '.php';
        if (!file_exists($vistaPath)) {
            throw new \RuntimeException("Plantilla de email no encontrada: $vista");
        }
        include $vistaPath;
        return ob_get_clean();
    }

    /**
     * Envía un correo electrónico (método de bajo nivel)
     */
    private function enviar(string $destinatario, string $nombreDestinatario, string $asunto, string $htmlBody): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            $this->mailer->addAddress($destinatario, $nombreDestinatario);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $asunto;
            $this->mailer->Body    = $htmlBody;
            // Versión texto plano (strip_tags básico)
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("EmailService: Error al enviar correo a {$destinatario}: " . $e->getMessage());
            return false;
        }
    }

    // ============================================================
    // LOGGING CENTRALIZADO — todos los métodos públicos llaman aquí
    // ============================================================

    /**
     * Registra el envío en la tabla `table_emails`.
     * 
     * @param array  $meta   Metadatos: tipo, to, to_name, subject, body_html,
     *                       body_text, attachments, referencia_tipo, referencia_id
     * @param bool   $success
     * @param string|null $error
     */
    private function logEnvio(array $meta, bool $success, ?string $error = null): void
    {
        try {
            $emailModel = new \ModelEmail();
            $emailModel->registrar([
                'tipo'                => $meta['tipo'] ?? 'OTRO',
                'destinatario_email'  => $meta['to'] ?? '',
                'destinatario_nombre' => $meta['to_name'] ?? null,
                'asunto'              => $meta['subject'] ?? '',
                'cuerpo_html'         => $meta['body_html'] ?? '',
                'cuerpo_texto'        => $meta['body_text'] ?? null,
                'adjuntos'            => $meta['attachments'] ?? [],
                'referencia_tipo'     => $meta['referencia_tipo'] ?? 'NINGUNO',
                'referencia_id'       => $meta['referencia_id'] ?? null,
                'estado'              => $success ? 'ENVIADO' : 'FALLIDO',
                'error_mensaje'       => $error,
                'usuario_id'          => $_SESSION['user_id'] ?? null,
                'fecha_envio'         => $success ? date('Y-m-d H:i:s') : null,
            ]);
        } catch (\Throwable $e) {
            // Nunca interrumpir el flujo principal por un fallo en el log
            error_log('EmailService::logEnvio falló: ' . $e->getMessage());
        }
    }

    // ============================================================
    // MÉTODOS PÚBLICOS — Uno por cada tipo de notificación
    // ============================================================

    /**
     * Envía notificación de pedido de catálogo al CLIENTE
     */
    public function notificarPedidoCatalogoCliente(array $datos): bool {
        $asunto = 'Tu pedido #' . $datos['id_formateado'] . ' ha sido recibido — ' . SITENAME;
        $html = $this->renderizar('pedido_catalogo_cliente', $datos);
        $ok = $this->enviar($datos['cliente_email'], $datos['cliente_nombre'], $asunto, $html);

        $this->logEnvio([
            'tipo'            => 'PEDIDO_CATALOGO',
            'to'              => $datos['cliente_email'],
            'to_name'         => $datos['cliente_nombre'] ?? null,
            'subject'         => $asunto,
            'body_html'       => $html,
            'referencia_tipo' => 'PEDIDO_CATALOGO',
            'referencia_id'   => $datos['pedido_id'] ?? ($datos['venta_id'] ?? null),
        ], $ok, $ok ? null : 'Error al enviar email al cliente');

        return $ok;
    }

    /**
     * Envía notificación de pedido de catálogo al administrador
     */
    public function notificarPedidoCatalogoAdmin(array $datos): bool {
        $asunto = 'Nuevo pedido de catálogo #' . $datos['venta_formateado'] . ' — ' . $datos['cliente_nombre'];
        $html = $this->renderizar('pedido_catalogo_admin', $datos);
        $ok = $this->enviar(MAIL_ADMIN, 'Administrador', $asunto, $html);

        $this->logEnvio([
            'tipo'            => 'PEDIDO_CATALOGO',
            'to'              => MAIL_ADMIN,
            'to_name'         => 'Administrador',
            'subject'         => $asunto,
            'body_html'       => $html,
            'referencia_tipo' => 'PEDIDO_CATALOGO',
            'referencia_id'   => $datos['pedido_id'] ?? ($datos['venta_id'] ?? null),
        ], $ok, $ok ? null : 'Error al enviar email al administrador');

        return $ok;
    }

    /**
     * Envía ambas notificaciones de pedido de catálogo (cliente + admin)
     */
    public function notificarPedidoCatalogo(array $datos): array {
        return [
            'cliente' => $this->notificarPedidoCatalogoCliente($datos),
            'admin'   => $this->notificarPedidoCatalogoAdmin($datos),
        ];
    }

    /**
     * Envía notificación al cliente de que su pedido fue PROCESADO por el staff
     */
    public function notificarPedidoProcesadoCliente(array $datos): bool {
        $asunto = 'Tu pedido #' . $datos['id_formateado'] . ' ha sido procesado — ' . SITENAME;
        $html = $this->renderizar('pedido_procesado_cliente', $datos);
        $ok = $this->enviar($datos['cliente_email'], $datos['cliente_nombre'], $asunto, $html);

        $this->logEnvio([
            'tipo'            => 'PEDIDO_CATALOGO',
            'to'              => $datos['cliente_email'],
            'to_name'         => $datos['cliente_nombre'] ?? null,
            'subject'         => $asunto,
            'body_html'       => $html,
            'referencia_tipo' => 'PEDIDO_CATALOGO',
            'referencia_id'   => $datos['pedido_id'] ?? null,
        ], $ok, $ok ? null : 'Error al enviar email de pedido procesado');

        return $ok;
    }

    // ============================================================
    // NOTIFICACIONES DE ÓRDENES DE SERVICIO
    // ============================================================

    /**
     * Notifica al cliente que su orden de servicio fue creada.
     */
    public function notificarOrdenServicioCreada(array $datos): bool
    {
        $asunto = 'Orden de Servicio #' . $datos['id_formateado'] . ' creada — ' . SITENAME;
        $html = $this->renderizar('orden_servicio_creada', $datos);
        $ok = $this->enviar($datos['cliente_email'], $datos['cliente_nombre'], $asunto, $html);

        $this->logEnvio([
            'tipo'            => 'ORDEN_SERVICIO',
            'to'              => $datos['cliente_email'],
            'to_name'         => $datos['cliente_nombre'] ?? null,
            'subject'         => $asunto,
            'body_html'       => $html,
            'referencia_tipo' => 'ORDEN',
            'referencia_id'   => $datos['orden_id'] ?? null,
        ], $ok, $ok ? null : 'Error al enviar email de orden creada');

        return $ok;
    }

    /**
     * Notifica al cliente que el estado de su orden de servicio cambió.
     */
    public function notificarOrdenServicioCambioEstado(array $datos): bool
    {
        $asunto = 'Orden de Servicio #' . $datos['id_formateado'] . ' — ' . $datos['estado_nuevo'] . ' — ' . SITENAME;
        $html = $this->renderizar('orden_servicio_cambio_estado', $datos);
        $ok = $this->enviar($datos['cliente_email'], $datos['cliente_nombre'], $asunto, $html);

        $this->logEnvio([
            'tipo'            => 'ORDEN_SERVICIO',
            'to'              => $datos['cliente_email'],
            'to_name'         => $datos['cliente_nombre'] ?? null,
            'subject'         => $asunto,
            'body_html'       => $html,
            'referencia_tipo' => 'ORDEN',
            'referencia_id'   => $datos['orden_id'] ?? null,
        ], $ok, $ok ? null : 'Error al enviar email de cambio de estado');

        return $ok;
    }

    /**
     * Notifica al cliente que su vehículo está listo para recoger.
     */
    public function notificarOrdenServicioLista(array $datos): bool
    {
        $asunto = '¡Tu vehículo está listo! Orden de Servicio #' . $datos['id_formateado'] . ' — ' . SITENAME;
        $html = $this->renderizar('orden_servicio_lista', $datos);
        $ok = $this->enviar($datos['cliente_email'], $datos['cliente_nombre'], $asunto, $html);

        $this->logEnvio([
            'tipo'            => 'ORDEN_SERVICIO',
            'to'              => $datos['cliente_email'],
            'to_name'         => $datos['cliente_nombre'] ?? null,
            'subject'         => $asunto,
            'body_html'       => $html,
            'referencia_tipo' => 'ORDEN',
            'referencia_id'   => $datos['orden_id'] ?? null,
        ], $ok, $ok ? null : 'Error al enviar email de orden lista');

        return $ok;
    }

    // ============================================================
    // NOTIFICACIONES DE FACTURACIÓN DIRECTA (MOSTRADOR)
    // ============================================================

    /**
     * Notifica al cliente los detalles de una factura directa (mostrador).
     */
    public function notificarFacturaDirecta(array $datos): bool
    {
        $asunto = 'Factura #' . ($datos['id_formateado'] ?? 'FAC-' . str_pad((string)($datos['venta_id'] ?? ''), 3, '0', STR_PAD_LEFT)) . ' — ' . SITENAME;
        $html = $this->renderizar('factura_directa', $datos);
        $ok = $this->enviar($datos['cliente_email'], $datos['cliente_nombre'], $asunto, $html);

        $this->logEnvio([
            'tipo'            => 'FACTURA',
            'to'              => $datos['cliente_email'],
            'to_name'         => $datos['cliente_nombre'] ?? null,
            'subject'         => $asunto,
            'body_html'       => $html,
            'referencia_tipo' => 'FACTURA',
            'referencia_id'   => $datos['venta_id'] ?? null,
        ], $ok, $ok ? null : 'Error al enviar email de factura');

        return $ok;
    }

    // ============================================================
    // NOTIFICACIONES DE VENCIMIENTO DE PROVEEDORES
    // ============================================================

    /**
     * Envía alerta al administrador con los proveedores cuyas facturas
     * están próximas a vencer o ya vencidas.
     */
    public function notificarProveedoresVencimiento(array $proveedores, int $diasLimite = 7): bool
    {
        if (empty($proveedores)) {
            return false;
        }

        $asunto = '🔔 Alertas de Vencimiento — ' . count($proveedores) . ' proveedor(es) con facturas por vencer — ' . SITENAME;
        $html = $this->renderizar('proveedor_vencimiento', [
            'proveedores' => $proveedores,
            'dias_limite' => $diasLimite,
        ]);
        $ok = $this->enviar(MAIL_ADMIN, 'Administrador', $asunto, $html);

        $this->logEnvio([
            'tipo'            => 'ALERTA_PROVEEDOR',
            'to'              => MAIL_ADMIN,
            'to_name'         => 'Administrador',
            'subject'         => $asunto,
            'body_html'       => $html,
            'referencia_tipo' => 'NINGUNO',
            'referencia_id'   => null,
        ], $ok, $ok ? null : 'Error al enviar alerta de proveedores');

        return $ok;
    }

    // ============================================================
    // NOTIFICACIÓN DE RESUMEN MENSUAL
    // ============================================================

    /**
     * Envía al administrador un resumen detallado de la actividad del mes anterior.
     */
    public function notificarResumenMensual(
        object $ventas,
        object $gastos,
        object $utilidad,
        object $clientes,
        object $ordenes,
        array  $topProductos,
        object $inventario,
        string $mes,
        string $anio
    ): bool {
        $asunto = "📊 Resumen Mensual — {$mes} {$anio} — " . SITENAME;
        $html = $this->renderizar('resumen_mensual', [
            'ventas'       => $ventas,
            'gastos'       => $gastos,
            'utilidad'     => $utilidad,
            'clientes'     => $clientes,
            'ordenes'      => $ordenes,
            'topProductos' => $topProductos,
            'inventario'   => $inventario,
            'mes'          => $mes,
            'anio'         => $anio,
        ]);
        $ok = $this->enviar(MAIL_ADMIN, 'Administrador', $asunto, $html);

        $this->logEnvio([
            'tipo'            => 'RESUMEN_MENSUAL',
            'to'              => MAIL_ADMIN,
            'to_name'         => 'Administrador',
            'subject'         => $asunto,
            'body_html'       => $html,
            'referencia_tipo' => 'NINGUNO',
            'referencia_id'   => null,
        ], $ok, $ok ? null : 'Error al enviar resumen mensual');

        return $ok;
    }

    /**
     * Envía un email genérico con los datos proporcionados.
     * Usado para emails compuestos manualmente desde la interfaz de Email.
     * 
     * Acepta metadatos opcionales:
     *   - tipo             (string)  Ej: 'FACTURA', 'PRESUPUESTO', 'OTRO'
     *   - referencia_tipo  (string)  Ej: 'FACTURA', 'ORDEN', 'NINGUNO'
     *   - referencia_id    (int)     ID del documento referenciado
     */
    public function enviarEmailGenerico(array $datos): array {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            $this->mailer->addAddress($datos['to'], $datos['to_name'] ?? '');
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $datos['subject'];
            $this->mailer->Body    = $datos['body_html'];
            
            // Versión texto plano
            $this->mailer->AltBody = $datos['body_text'] ?? strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $datos['body_html']));
            
            // Adjuntos
            if (!empty($datos['attachments'])) {
                foreach ($datos['attachments'] as $attachment) {
                    if (is_array($attachment)) {
                        $this->mailer->addAttachment($attachment['path'], $attachment['name'] ?? '');
                    } else {
                        $this->mailer->addAttachment($attachment);
                    }
                }
            }
            
            // Configurar remitente si se proporciona
            if (!empty($datos['from_email'])) {
                $this->mailer->setFrom($datos['from_email'], $datos['from_name'] ?? 'Taller Pro');
            }
            
            $this->mailer->send();

            // Log del envío exitoso
            $this->logEnvio([
                'tipo'            => $datos['tipo'] ?? 'OTRO',
                'to'              => $datos['to'],
                'to_name'         => $datos['to_name'] ?? null,
                'subject'         => $datos['subject'],
                'body_html'       => $datos['body_html'],
                'body_text'       => $datos['body_text'] ?? null,
                'attachments'     => $datos['attachments'] ?? [],
                'referencia_tipo' => $datos['referencia_tipo'] ?? 'NINGUNO',
                'referencia_id'   => $datos['referencia_id'] ?? null,
            ], true);

            return ['success' => true, 'mensaje' => 'Email enviado correctamente'];
        } catch (Exception $e) {
            error_log("EmailService: Error al enviar email genérico: " . $e->getMessage());

            // Log del fallo
            $this->logEnvio([
                'tipo'            => $datos['tipo'] ?? 'OTRO',
                'to'              => $datos['to'],
                'to_name'         => $datos['to_name'] ?? null,
                'subject'         => $datos['subject'],
                'body_html'       => $datos['body_html'],
                'body_text'       => $datos['body_text'] ?? null,
                'attachments'     => $datos['attachments'] ?? [],
                'referencia_tipo' => $datos['referencia_tipo'] ?? 'NINGUNO',
                'referencia_id'   => $datos['referencia_id'] ?? null,
            ], false, $e->getMessage());

            return ['success' => false, 'mensaje' => $e->getMessage()];
        }
    }
}