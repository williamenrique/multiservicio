<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * EmailService — Servicio centralizado de envío de correos electrónicos
 * 
 * v3.0.0 (2026-10-13) — ADJUNTOS PERSISTENTES:
 *   • Ahora los adjuntos se copian a una ubicación PERMANENTE en
 *     `public_html/uploads/emails_attachments/{YYYY}/{MM}/` con un nombre
 *     que incluye el `email_id` de la BD para trazabilidad.
 *   • El campo `table_emails.adjuntos` guarda rutas RELATIVAS al root del
 *     proyecto, no rutas absolutas del servidor.
 *   • Se borra el archivo temporal de `uploads/emails/` después de copiarlo
 *     a su destino final.
 *   • El panel de detalle del módulo Email muestra los adjuntos como links
 *     clicables que abren en pestaña nueva.
 * 
 * NOTA: Este servicio sigue registrando AUTOMÁTICAMENTE cada envío en
 * la tabla `table_emails` (ver método `logEnvio`). Los controladores
 * NO deben llamar a ModelEmail->registrar() después de invocar
 * cualquiera de los métodos públicos de este servicio.
 */
class EmailService
{
    private PHPMailer $mailer;
    private ?object $empresa = null;

    /**
     * Carpeta relativa donde se persisten los adjuntos definitivos.
     */
    private const ADJUNTOS_DIR = 'uploads/emails_attachments';

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configurarSMTP();
    }

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

        // v3.0.1 (2026-10-26) — PERFORMANCE:
        // Timeouts agresivos para que un SMTP lento NO bloquee el login
        // (especialmente dentro de register_shutdown_function).
        $this->mailer->Timeout        = 8;   // timeout total de la conexión SMTP
        $this->mailer->SMTPKeepAlive  = false;
        $this->mailer->SMTPDebug      = 0;
    }

    private function getEmpresa(): object {
        if ($this->empresa === null) {
            $modelEmpresa = new \ModelEmpresa();
            $this->empresa = $modelEmpresa->obtenerConfiguracion();
        }
        return $this->empresa;
    }

    public function renderizar(string $vista, array $data = []): string {
        $empresa = $this->getEmpresa();
        
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

    private function enviar(string $destinatario, string $nombreDestinatario, string $asunto, string $htmlBody): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            $this->mailer->addAddress($destinatario, $nombreDestinatario);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $asunto;
            $this->mailer->Body    = $htmlBody;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("EmailService: Error al enviar correo a {$destinatario}: " . $e->getMessage());
            return false;
        }
    }
    /* ============================================================
     * LOGGING CENTRALIZADO — todos los métodos públicos llaman aquí
     * ============================================================ */

    /**
     * Registra el envío en `table_emails` y persiste los adjuntos en la
     * carpeta definitiva `uploads/emails_attachments/{YYYY}/{MM}/`.
     * 
     * v3.0: nuevo flujo:
     *   1. INSERT inicial sin adjuntos definitivos (para obtener email_id).
     *   2. Copiar/mover adjuntos a su destino final.
     *   3. UPDATE con las rutas relativas definitivas.
     */
    private function logEnvio(array $meta, bool $success, ?string $error = null): void
    {
         error_log('EMAIL LOG DEBUG - Intentando registrar: ' . json_encode([
            'tipo' => $meta['tipo'] ?? 'NO DEFINIDO',
            'to' => $meta['to'] ?? 'NO DEFINIDO',
            'subject' => $meta['subject'] ?? 'NO DEFINIDO',
            'success' => $success
        ]));
        try {
            $emailModel = new \ModelEmail();

            // 1. Registrar primero SIN adjuntos definitivos (para tener email_id)
            $emailModel->registrar([
                'tipo'                => $meta['tipo'] ?? 'OTRO',
                'destinatario_email'  => $meta['to'] ?? '',
                'destinatario_nombre' => $meta['to_name'] ?? null,
                'asunto'              => $meta['subject'] ?? '',
                'cuerpo_html'         => $meta['body_html'] ?? '',
                'cuerpo_texto'        => $meta['body_text'] ?? null,
                'adjuntos'            => null,
                'referencia_tipo'     => $meta['referencia_tipo'] ?? 'NINGUNO',
                'referencia_id'       => $meta['referencia_id'] ?? null,
                'estado'              => $success ? 'ENVIADO' : 'FALLIDO',
                'error_mensaje'       => $error,
                'usuario_id'          => $_SESSION['user_id'] ?? null,
                'fecha_envio'         => $success ? date('Y-m-d H:i:s') : null,
            ]);

            $emailId = (int)$emailModel->getLastInsertId();

            // 2. Persistir adjuntos (solo si hay y si el email se registró)
            $adjuntosOriginales = $meta['attachments'] ?? [];
            $adjuntosFinales = [];

            if ($emailId > 0 && !empty($adjuntosOriginales) && is_array($adjuntosOriginales)) {
                $adjuntosFinales = $this->moverAdjuntosADestinoFinal($adjuntosOriginales, $emailId);

                // 3. UPDATE con las rutas definitivas
                if (!empty($adjuntosFinales)) {
                    $emailModel->actualizarAdjuntos($emailId, $adjuntosFinales);
                }
            }
        } catch (\Throwable $e) {
            error_log('EmailService::logEnvio falló: ' . $e->getMessage());
        }
    }

    /**
     * Copia cada adjunto a su ubicación definitiva y devuelve el array
     * con la información que se guardará en `table_emails.adjuntos`.
     * 
     * Acepta 3 formatos de adjunto:
     *   - ['content' => string_binario, 'name' => 'x.pdf'] → escribe a disco
     *   - ['path' => '/ruta/absoluta/x.pdf', 'name' => 'x.pdf'] → copia
     *   - '/ruta/absoluta/x.pdf' (string) → copia
     * 
     * Después de copiar un archivo temporal que esté en `uploads/emails/`,
     * lo elimina (ya no se necesita).
     * 
     * @param array $adjuntosOriginales
     * @param int   $emailId
     * @return array Array de {path_relativa, name, size}
     */
    private function moverAdjuntosADestinoFinal(array $adjuntosOriginales, int $emailId): array
    {
        $resultado = [];
        $baseDir = dirname(APPROOT) . '/public_html/' . self::ADJUNTOS_DIR;

        // Asegurar que las carpetas existan (por año/mes)
        $year = date('Y');
        $month = date('m');
        $subDir = $baseDir . '/' . $year . '/' . $month;
        if (!is_dir($subDir)) {
            @mkdir($subDir, 0755, true);
        }
        if (!is_dir($subDir)) {
            error_log("EmailService: no se pudo crear el directorio de adjuntos: {$subDir}");
            return [];
        }

        foreach ($adjuntosOriginales as $idx => $adjunto) {
            try {
                $resultado[] = $this->procesarAdjuntoIndividual($adjunto, $emailId, $idx, $subDir, $year, $month);
            } catch (\Throwable $e) {
                error_log("EmailService: fallo procesando adjunto #{$idx}: " . $e->getMessage());
            }
        }

        return array_values(array_filter($resultado));
    }

    /**
     * Procesa un adjunto individual. Devuelve el array resultante o null.
     */
    private function procesarAdjuntoIndividual($adjunto, int $emailId, int $idx, string $subDir, string $year, string $month): ?array
    {
        $nombreOriginal = 'adjunto_' . ($idx + 1);
        $binario = null;
        $pathOriginal = null;

        // Detectar formato
        if (is_array($adjunto)) {
            if (isset($adjunto['content']) && $adjunto['content'] !== '') {
                $binario = $adjunto['content'];
                $nombreOriginal = $adjunto['name'] ?? ('adjunto_' . ($idx + 1) . '.bin');
            } elseif (isset($adjunto['path']) && $adjunto['path'] !== '') {
                $pathOriginal = $adjunto['path'];
                $nombreOriginal = $adjunto['name'] ?? basename($pathOriginal);
            } else {
                return null;
            }
        } elseif (is_string($adjunto) && $adjunto !== '') {
            $pathOriginal = $adjunto;
            $nombreOriginal = basename($adjunto);
        } else {
            return null;
        }

        // Sanitizar nombre para el archivo final
        $nombreSeguro = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal);
        $nombreFinal = 'email_' . $emailId . '_' . time() . '_' . $idx . '_' . $nombreSeguro;
        $destinoAbsoluto = $subDir . '/' . $nombreFinal;
        $destinoRelativo = self::ADJUNTOS_DIR . '/' . $year . '/' . $month . '/' . $nombreFinal;

        // 1. Escribir/copiar el archivo
        if ($binario !== null) {
            // Binario en memoria
            if (file_put_contents($destinoAbsoluto, $binario) === false) {
                return null;
            }
            $size = strlen($binario);
        } elseif ($pathOriginal !== null) {
            // Archivo físico
            if (!file_exists($pathOriginal)) {
                error_log("EmailService: adjunto no existe en disco: {$pathOriginal}");
                return null;
            }
            if (!@copy($pathOriginal, $destinoAbsoluto)) {
                return null;
            }
            $size = @filesize($destinoAbsoluto) ?: 0;

            // Borrar el original si era un temporal en uploads/emails/
            if (strpos($pathOriginal, 'uploads/emails/') !== false && !str_contains($pathOriginal, 'emails_attachments')) {
                @unlink($pathOriginal);
            }
        } else {
            return null;
        }

        return [
            'path' => $destinoRelativo,
            'name' => $nombreOriginal,
            'size' => (int)$size,
        ];
    }

    /* ============================================================
     * MÉTODOS PÚBLICOS — Uno por cada tipo de notificación
     * ============================================================ */

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

    public function notificarPedidoCatalogo(array $datos): array {
        return [
            'cliente' => $this->notificarPedidoCatalogoCliente($datos),
            'admin'   => $this->notificarPedidoCatalogoAdmin($datos),
        ];
    }

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

    public function notificarAbonoRegistrado(array $datos): bool
    {
        $asunto = '💰 Abono registrado a Factura #' . ($datos['id_formateado'] ?? $datos['factura_id']) . ' — ' . SITENAME;
        $html = $this->renderizar('abono_registrado', $datos);
        $ok = $this->enviar($datos['cliente_email'], $datos['cliente_nombre'], $asunto, $html);

        $this->logEnvio([
            'tipo'            => 'FACTURA',
            'to'              => $datos['cliente_email'],
            'to_name'         => $datos['cliente_nombre'] ?? null,
            'subject'         => $asunto,
            'body_html'       => $html,
            'referencia_tipo' => 'FACTURA',
            'referencia_id'   => $datos['factura_id'] ?? null,
        ], $ok, $ok ? null : 'Error al enviar email de abono registrado');

        return $ok;
    }

    public function enviarRecordatorioPago(array $datos): bool
    {
        $asunto = '📢 Recordatorio de Pago — Factura #' . ($datos['id_formateado'] ?? $datos['factura_id']) . ' — ' . SITENAME;
        $html = $this->renderizar('recordatorio_pago', $datos);
        $ok = $this->enviar($datos['cliente_email'], $datos['cliente_nombre'], $asunto, $html);

        $this->logEnvio([
            'tipo'            => 'FACTURA',
            'to'              => $datos['cliente_email'],
            'to_name'         => $datos['cliente_nombre'] ?? null,
            'subject'         => $asunto,
            'body_html'       => $html,
            'referencia_tipo' => 'FACTURA',
            'referencia_id'   => $datos['factura_id'] ?? null,
        ], $ok, $ok ? null : 'Error al enviar recordatorio de pago');

        return $ok;
    }

    public function enviarEmailGenerico(array $datos): array {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            $this->mailer->addAddress($datos['to'], $datos['to_name'] ?? '');

            // v3.0: CC y BCC (separados por ;)
            if (!empty($datos['cc'])) {
                foreach (preg_split('/[;,]+/', $datos['cc']) as $cc) {
                    $cc = trim($cc);
                    if ($cc !== '' && filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                        $this->mailer->addCC($cc);
                    }
                }
            }
            if (!empty($datos['bcc'])) {
                foreach (preg_split('/[;,]+/', $datos['bcc']) as $bcc) {
                    $bcc = trim($bcc);
                    if ($bcc !== '' && filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
                        $this->mailer->addBCC($bcc);
                    }
                }
            }

            $this->mailer->isHTML(true);
            $this->mailer->Subject = $datos['subject'];
            $this->mailer->Body    = $datos['body_html'];
            $this->mailer->AltBody = $datos['body_text'] ?? strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $datos['body_html']));
            
            if (!empty($datos['attachments'])) {
                foreach ($datos['attachments'] as $attachment) {
                    if (is_array($attachment) && isset($attachment['content'])) {
                        $this->mailer->addStringAttachment(
                            $attachment['content'],
                            $attachment['name'] ?? 'documento.pdf'
                        );
                    } elseif (is_array($attachment) && isset($attachment['path'])) {
                        $this->mailer->addAttachment($attachment['path'], $attachment['name'] ?? '');
                    } elseif (is_string($attachment)) {
                        $this->mailer->addAttachment($attachment);
                    }
                }
            }
            
            if (!empty($datos['from_email'])) {
                $this->mailer->setFrom($datos['from_email'], $datos['from_name'] ?? 'Taller Pro');
            }
            
            $this->mailer->send();

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