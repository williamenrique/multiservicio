<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * EmailService — Servicio centralizado de envío de correos electrónicos
 * 
 * Todas las notificaciones del sistema pasan por aquí.
 * Usa PHPMailer con SMTP y plantillas HTML desde Views/email/
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
    private function renderizar(string $vista, array $data = []): string {
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
     * Envía un correo electrónico
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
    // MÉTODOS PÚBLICOS — Uno por cada tipo de notificación
    // ============================================================

    /**
     * Envía notificación de pedido de catálogo al CLIENTE
     */
    public function notificarPedidoCatalogoCliente(array $datos): bool {
        $asunto = 'Tu pedido #' . $datos['id_formateado'] . ' ha sido recibido — ' . SITENAME;
        $html = $this->renderizar('pedido_catalogo_cliente', $datos);
        return $this->enviar($datos['cliente_email'], $datos['cliente_nombre'], $asunto, $html);
    }

    /**
     * Envía notificación de pedido de catálogo al administrador
     */
    public function notificarPedidoCatalogoAdmin(array $datos): bool {
        $asunto = 'Nuevo pedido de catálogo #' . $datos['venta_formateado'] . ' — ' . $datos['cliente_nombre'];
        $html = $this->renderizar('pedido_catalogo_admin', $datos);
        return $this->enviar(MAIL_ADMIN, 'Administrador', $asunto, $html);
    }

    /**
     * Envía ambas notificaciones de pedido de catálogo (cliente + admin)
     */
    public function notificarPedidoCatalogo(array $datos): array {
        $resultados = [
            'cliente' => $this->notificarPedidoCatalogoCliente($datos),
            'admin'   => $this->notificarPedidoCatalogoAdmin($datos),
        ];
        return $resultados;
    }

    /**
     * Envía notificación al cliente de que su pedido fue PROCESADO por el staff
     */
    public function notificarPedidoProcesadoCliente(array $datos): bool {
        $asunto = 'Tu pedido #' . $datos['id_formateado'] . ' ha sido procesado — ' . SITENAME;
        $html = $this->renderizar('pedido_procesado_cliente', $datos);
        return $this->enviar($datos['cliente_email'], $datos['cliente_nombre'], $asunto, $html);
    }

    // ============================================================
    // FUTUROS MÉTODOS (a implementar cuando se necesiten)
    // ============================================================

    // public function notificarOrdenServicioCliente(array $datos): bool { ... }
    // public function notificarOrdenServicioAdmin(array $datos): bool { ... }
    // public function notificarVentaMostradorCliente(array $datos): bool { ... }
    // public function notificarVentaMostradorAdmin(array $datos): bool { ... }
    // public function notificarRecuperacionPassword(string $email, string $token): bool { ... }
}