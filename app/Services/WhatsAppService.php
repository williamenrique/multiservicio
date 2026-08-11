<?php

namespace App\Services;

/**
 * WhatsAppService — Servicio centralizado de envío de mensajes por WhatsApp
 * 
 * Todas las notificaciones por WhatsApp del sistema pasan por aquí.
 * Se comunica con un servidor Node.js local (o remoto) que maneja
 * la conexión con la API de WhatsApp (baileys/whatsapp-web.js).
 */
class WhatsAppService
{
    private string $apiUrl;
    private int    $timeout;

    public function __construct()
    {
        $this->apiUrl  = WHATSAPP_API_URL  ?? 'http://localhost:3000/enviar-pedido';
        $this->timeout = WHATSAPP_TIMEOUT ?? 10;
    }

    /**
     * Envía un mensaje de WhatsApp a un número específico.
     *
     * @param string $telefono Número de teléfono con código de país, sin "+" ni espacios (ej: 584125181629)
     * @param string $mensaje  Texto del mensaje (acepta formato Markdown básico de WhatsApp: *negrita*, _cursiva_, ~tachado~)
     * @return array{success: bool, mensaje: string, raw: string|null}
     */
    public function enviar(string $telefono, string $mensaje): array
    {
        // Normalizar teléfono: quitar +, espacios, guiones, paréntesis
        $telefono = $this->normalizarTelefono($telefono);

        if (empty($telefono)) {
            return [
                'success'  => false,
                'mensaje'  => 'Número de teléfono inválido.',
                'respuesta' => null
            ];
        }

        $payload = json_encode([
            'telefono' => $telefono,
            'mensaje'  => $mensaje
        ]);

        try {
            $ch = curl_init($this->apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                error_log("WhatsAppService: Error cURL — {$curlError}");
                return [
                    'success'   => false,
                    'mensaje'   => "Error de conexión: {$curlError}",
                    'respuesta' => null
                ];
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success'   => true,
                    'mensaje'   => 'Mensaje enviado correctamente.',
                    'respuesta' => $response
                ];
            }

            error_log("WhatsAppService: HTTP {$httpCode} — {$response}");
            return [
                'success'   => false,
                'mensaje'   => "Error del servidor WhatsApp (HTTP {$httpCode}).",
                'respuesta' => $response
            ];
        } catch (\Throwable $e) {
            error_log("WhatsAppService: Excepción — " . $e->getMessage());
            return [
                'success'   => false,
                'mensaje'   => 'Excepción al enviar WhatsApp: ' . $e->getMessage(),
                'respuesta' => null
            ];
        }
    }

    /**
     * Normaliza un número de teléfono: elimina espacios, signos +, paréntesis y espacios.
     * Agrega el código de país 58 (Venezuela) si el número no lo tiene.
     *
     * Ejemplos:
     *  - "04125181629"  → "584125181629"  (formato venezolano local con 0 inicial)
     *  - "4125181629"   → "584125181629"  (10 dígitos sin código de país)
     *  - "584125181629" → "584125181629"  (ya tiene código de país)
     *  - "+58 412-5181629" → "584125181629"
     */
    private function normalizarTelefono(string $telefono): string
    {
        $limpio = preg_replace('/[^0-9]/', '', $telefono);

        if (empty($limpio)) {
            return '';
        }

        // Caso patológico: "5804125181629" — alguien ya le prependeó "58" a un
        // número local que aún conserva el "0" inicial. Quitamos el "580" y
        // reconstruimos correctamente como "58" + resto (sin el 0).
        if (str_starts_with($limpio, '580') && (strlen($limpio) === 13 || strlen($limpio) === 14)) {
            return '58' . substr($limpio, 3);
        }

        // Si ya tiene código de país 58 y longitud válida (12 dígitos: 58 + 10), dejar tal cual
        if (str_starts_with($limpio, '58') && strlen($limpio) === 12) {
            return $limpio;
        }

        // Formato venezolano local: empieza con "0" (ej: "04125181629" — 11 dígitos)
        // Quitamos el "0" inicial y agregamos "58"
        if (str_starts_with($limpio, '0') && strlen($limpio) === 11) {
            return '58' . substr($limpio, 1);
        }

        // Número venezolano sin código de país (10 dígitos, ej: "4125181629")
        if (strlen($limpio) === 10) {
            return '58' . $limpio;
        }

        // Si tiene otro formato pero longitud razonable (10-15), devolver tal cual
        if (strlen($limpio) >= 10 && strlen($limpio) <= 15) {
            return $limpio;
        }

        return $limpio;
    }

    // ============================================================
    // MÉTODOS PÚBLICOS — Uno por cada tipo de notificación
    // ============================================================

    /**
     * Notifica al ADMINISTRADOR sobre un nuevo pedido del catálogo.
     * Envía un resumen con datos del cliente y productos solicitados.
     */
    public function notificarPedidoCatalogoAdmin(array $datos): array
    {
        $adminTelefono = WHATSAPP_ADMIN_PHONE ?? '';
        if (empty($adminTelefono)) {
            error_log("WhatsAppService: WHATSAPP_ADMIN_PHONE no configurado.");
            return ['success' => false, 'mensaje' => 'Teléfono del administrador no configurado.', 'respuesta' => null];
        }

        $mensaje = $this->formatearPedidoAdmin($datos);
        return $this->enviar($adminTelefono, $mensaje);
    }

    /**
     * Notifica al CLIENTE que su pedido fue recibido.
     */
    public function notificarPedidoCatalogoCliente(array $datos): array
    {
        $telefono = $datos['cliente_telefono'] ?? '';
        if (empty($telefono)) {
            return ['success' => false, 'mensaje' => 'El cliente no proporcionó teléfono.', 'respuesta' => null];
        }

        $mensaje = $this->formatearPedidoCliente($datos);
        return $this->enviar($telefono, $mensaje);
    }

    /**
     * Envía ambas notificaciones de pedido de catálogo (cliente + admin).
     * No interrumpe el flujo si alguna falla.
     */
    public function notificarPedidoCatalogo(array $datos): array
    {
        $resultados = [
            'admin'   => $this->notificarPedidoCatalogoAdmin($datos),
            'cliente' => $this->notificarPedidoCatalogoCliente($datos),
        ];
        return $resultados;
    }

    // ============================================================
    // NOTIFICACIONES DE ORDEN DE SERVICIO
    // ============================================================

    /**
     * Notifica al cliente que su orden de servicio fue creada (recepción).
     */
    public function notificarOrdenServicioCreada(array $datos): array
    {
        $telefono = $datos['cliente_telefono'] ?? '';
        if (empty($telefono)) {
            return ['success' => false, 'mensaje' => 'El cliente no tiene teléfono.', 'respuesta' => null];
        }
        $mensaje = $this->formatearOrdenCreada($datos);
        return $this->enviar($telefono, $mensaje);
    }

    /**
     * Notifica al cliente que el estado de su orden de servicio cambió.
     */
    public function notificarOrdenServicioCambioEstado(array $datos): array
    {
        $telefono = $datos['cliente_telefono'] ?? '';
        if (empty($telefono)) {
            return ['success' => false, 'mensaje' => 'El cliente no tiene teléfono.', 'respuesta' => null];
        }
        $mensaje = $this->formatearCambioEstado($datos);
        return $this->enviar($telefono, $mensaje);
    }

    /**
     * Notifica al cliente que su vehículo está listo para recoger.
     */
    public function notificarOrdenServicioLista(array $datos): array
    {
        $telefono = $datos['cliente_telefono'] ?? '';
        if (empty($telefono)) {
            return ['success' => false, 'mensaje' => 'El cliente no tiene teléfono.', 'respuesta' => null];
        }
        $mensaje = $this->formatearOrdenLista($datos);
        return $this->enviar($telefono, $mensaje);
    }

    /**
     * Notifica al cliente los detalles de una factura directa (mostrador).
     */
    public function notificarFacturaDirecta(array $datos): array
    {
        $telefono = $datos['cliente_telefono'] ?? '';
        if (empty($telefono)) {
            return ['success' => false, 'mensaje' => 'El cliente no tiene teléfono.', 'respuesta' => null];
        }
        $mensaje = $this->formatearFacturaDirecta($datos);
        return $this->enviar($telefono, $mensaje);
    }

    // ============================================================
    // FORMATEADORES DE MENSAJES (Markdown de WhatsApp)
    // ============================================================

    /**
     * Formatea el mensaje para el ADMINISTRADOR con los detalles del pedido.
     */
    private function formatearPedidoAdmin(array $d): string
    {
        $lineas = [];
        $lineas[] = "🔔 *NUEVO PEDIDO DE CATÁLOGO*";
        $lineas[] = "";
        $lineas[] = "📋 *Factura:* " . ($d['venta_formateado'] ?? 'N/A');
        $lineas[] = "📋 *Pedido:* " . ($d['id_formateado'] ?? 'N/A');
        $lineas[] = "📅 *Fecha:* " . ($d['fecha'] ?? date('d/m/Y h:i A'));
        $lineas[] = "";
        $lineas[] = "👤 *DATOS DEL CLIENTE*";
        $lineas[] = "Nombre: " . ($d['cliente_nombre'] ?? 'N/A');
        $lineas[] = "Cédula: " . ($d['cliente_cedula'] ?? 'N/A');
        $lineas[] = "Teléfono: " . ($d['cliente_telefono'] ?? 'N/A');
        $lineas[] = "Correo: " . ($d['cliente_email'] ?? 'N/A');
        if (!empty($d['cliente_direccion'])) {
            $lineas[] = "Dirección: " . $d['cliente_direccion'];
        }
        $lineas[] = "";
        $lineas[] = "🛒 *PRODUCTOS SOLICITADOS*";

        $items = $d['items'] ?? [];
        if (is_array($items)) {
            foreach ($items as $item) {
                if (!is_array($item) && !is_object($item)) continue;
                $item = (array) $item;
                $nombre   = $item['descripcion'] ?? $item['nombre'] ?? 'Producto';
                $cantidad = (int)($item['cantidad'] ?? 1);
                $precio   = number_format((float)($item['precio_unitario'] ?? $item['precio'] ?? 0), 2);
                $lineas[] = "  • {$nombre} (x{$cantidad}) — \${$precio}";
            }
        }

        $lineas[] = "";
        $lineas[] = "💰 *Subtotal:* \$" . number_format((float)($d['subtotal'] ?? $d['total'] ?? 0), 2);
        if (!empty($d['iva']) && (float)$d['iva'] > 0) {
            $lineas[] = "🧾 *IVA ({$d['iva_tasa']}%):* \$" . number_format((float)$d['iva'], 2);
        }
        $lineas[] = "💵 *TOTAL:* \$" . number_format((float)($d['total'] ?? 0), 2);
        $lineas[] = "";
        $lineas[] = "⚙️ Revisa el sistema para gestionar este pedido.";

        return implode("\n", $lineas);
    }

    /**
     * Formatea el mensaje para el CLIENTE confirmando su pedido.
     */
    private function formatearPedidoCliente(array $d): string
    {
        $lineas = [];
        $lineas[] = "✅ *¡Gracias por tu pedido, {$d['cliente_nombre']}!*";
        $lineas[] = "";
        $lineas[] = "Hemos recibido tu pedido correctamente:";
        $lineas[] = "";
        $lineas[] = "📋 *Pedido:* {$d['id_formateado']}";
        $lineas[] = "📋 *Factura:* {$d['venta_formateado']}";
        $lineas[] = "📅 *Fecha:* {$d['fecha']}";
        $lineas[] = "📌 *Estado:* PENDIENTE";
        $lineas[] = "";

        $items = $d['items'] ?? [];
        if (!empty($items)) {
            $lineas[] = "🛒 *Productos:*";
            foreach ($items as $item) {
                $nombre   = $item['descripcion'] ?? $item['nombre'] ?? 'Producto';
                $cantidad = (int)($item['cantidad'] ?? 1);
                $precio   = number_format($item['precio_unitario'] ?? $item['precio'] ?? 0, 2);
                $lineas[] = "  • {$nombre} (x{$cantidad}) — \${$precio}";
            }
            $lineas[] = "";
        }

        $lineas[] = "💰 *Total:* \$" . number_format($d['total'] ?? 0, 2);
        $lineas[] = "";
        $lineas[] = "Nos pondremos en contacto contigo pronto para coordinar la entrega.";
        $lineas[] = "Si tienes dudas, responde a este mensaje o contáctanos.";

        return implode("\n", $lineas);
    }

    // ------------------------------------------------------------
    // Formateadores de ORDEN DE SERVICIO
    // ------------------------------------------------------------

    /**
     * Formatea el mensaje de orden de servicio creada (recepción).
     */
    private function formatearOrdenCreada(array $d): string
    {
        $lineas = [];
        $lineas[] = "🔧 *ORDEN DE SERVICIO CREADA*";
        $lineas[] = "";
        $lineas[] = "Hola *{$d['cliente_nombre']}*, hemos recibido tu vehículo correctamente:";
        $lineas[] = "";
        $lineas[] = "📋 *Orden:* {$d['id_formateado']}";
        $lineas[] = "📅 *Fecha:* {$d['fecha_ingreso']}";
        $lineas[] = "🚗 *Vehículo:* {$d['vehiculo']}";
        $lineas[] = "🔖 *Placa:* {$d['placa']}";
        if (!empty($d['kilometraje'])) {
            $lineas[] = "📏 *Kilometraje:* {$d['kilometraje']} km";
        }
        if (!empty($d['nivel_combustible'])) {
            $lineas[] = "⛽ *Combustible:* {$d['nivel_combustible']}";
        }
        $lineas[] = "👨‍🔧 *Mecánico:* {$d['mecanico_nombre']}";
        if (!empty($d['fecha_entrega_estimada']) && $d['fecha_entrega_estimada'] !== 'No especificada') {
            $lineas[] = "📆 *Entrega estimada:* {$d['fecha_entrega_estimada']}";
        }
        $lineas[] = "";

        $items = $d['items'] ?? [];
        if (!empty($items)) {
            $lineas[] = "🛒 *Servicios/Repuestos:*";
            foreach ($items as $it) {
                $desc = $it['descripcion'] ?? 'Ítem';
                $cant = (int)($it['cantidad'] ?? 1);
                $sub  = number_format((float)($it['subtotal'] ?? ($it['precio'] ?? 0) * $cant), 2);
                $lineas[] = "  • {$desc} (x{$cant}) — \${$sub}";
            }
            $lineas[] = "";
            $lineas[] = "💰 *Total estimado:* \$" . number_format((float)($d['total'] ?? 0), 2);
            $lineas[] = "";
        }

        if (!empty($d['observaciones'])) {
            $lineas[] = "📝 *Observaciones:* {$d['observaciones']}";
            $lineas[] = "";
        }

        $lineas[] = "Te mantendremos informado sobre el progreso del servicio.";

        return implode("\n", $lineas);
    }

    /**
     * Formatea el mensaje de cambio de estado de la orden.
     */
    private function formatearCambioEstado(array $d): string
    {
        $estados = [
            'RECIBIDO'       => 'Recibido',
            'DIAGNOSTICANDO' => 'Diagnosticando',
            'EN_REPARACION'  => 'En reparación',
            'LISTO'          => 'Listo para entrega',
            'ENTREGADO'      => 'Entregado',
        ];
        $estadoAntTxt = $estados[$d['estado_anterior']] ?? $d['estado_anterior'];
        $estadoNuevoTxt = $estados[$d['estado_nuevo']] ?? $d['estado_nuevo'];

        $lineas = [];
        $lineas[] = "🔄 *ACTUALIZACIÓN DE TU ORDEN DE SERVICIO*";
        $lineas[] = "";
        $lineas[] = "Hola *{$d['cliente_nombre']}*, el estado de tu orden ha cambiado:";
        $lineas[] = "";
        $lineas[] = "📋 *Orden:* {$d['id_formateado']}";
        $lineas[] = "🚗 *Vehículo:* {$d['vehiculo']}";
        $lineas[] = "🔖 *Placa:* {$d['placa']}";
        $lineas[] = "";
        $lineas[] = "📌 *Estado anterior:* {$estadoAntTxt}";
        $lineas[] = "✅ *Estado actual:* {$estadoNuevoTxt}";
        $lineas[] = "📅 *Fecha:* {$d['fecha_cambio']}";
        $lineas[] = "👨‍🔧 *Mecánico:* {$d['mecanico_nombre']}";
        if (!empty($d['comentario'])) {
            $lineas[] = "";
            $lineas[] = "📝 *Comentario:* {$d['comentario']}";
        }
        $lineas[] = "";
        $lineas[] = "Te avisaremos cuando tu vehículo esté listo para recoger.";

        return implode("\n", $lineas);
    }

    /**
     * Formatea el mensaje de vehículo listo para entrega.
     */
    private function formatearOrdenLista(array $d): string
    {
        $lineas = [];
        $lineas[] = "✅ *¡TU VEHÍCULO ESTÁ LISTO!*";
        $lineas[] = "";
        $lineas[] = "Hola *{$d['cliente_nombre']}*, tu vehículo ya está listo para ser recogido:";
        $lineas[] = "";
        $lineas[] = "📋 *Orden:* {$d['id_formateado']}";
        $lineas[] = "🚗 *Vehículo:* {$d['vehiculo']}";
        $lineas[] = "🔖 *Placa:* {$d['placa']}";
        $lineas[] = "📅 *Fecha:* {$d['fecha_entrega']}";
        $lineas[] = "👨‍🔧 *Mecánico:* {$d['mecanico_nombre']}";
        $lineas[] = "";

        $items = $d['items'] ?? [];
        if (!empty($items)) {
            $lineas[] = "🛒 *Servicios/Repuestos:*";
            foreach ($items as $it) {
                $desc = $it['descripcion'] ?? 'Ítem';
                $cant = (int)($it['cantidad'] ?? 1);
                $sub  = number_format((float)($it['subtotal'] ?? ($it['precio'] ?? 0) * $cant), 2);
                $lineas[] = "  • {$desc} (x{$cant}) — \${$sub}";
            }
            $lineas[] = "";
        }

        $lineas[] = "💰 *Total:* \$" . number_format((float)($d['total'] ?? 0), 2);
        $lineas[] = "";
        $lineas[] = "Puedes acercarte a nuestras instalaciones para retirarlo.";

        return implode("\n", $lineas);
    }

    /**
     * Formatea el mensaje de factura directa (mostrador).
     */
    private function formatearFacturaDirecta(array $d): string
    {
        $statusTxt = [
            'COMPLETADO' => 'Pagado (Completado)',
            'CREDITO'    => 'Crédito',
            'PENDIENTE'  => 'Pendiente (Abono)',
        ];
        $estadoTxt = $statusTxt[$d['status'] ?? ''] ?? ($d['status'] ?? 'Pendiente');

        $lineas = [];
        $lineas[] = "🧾 *FACTURA DE SERVICIO*";
        $lineas[] = "";
        $lineas[] = "Hola *{$d['cliente_nombre']}*, aquí los detalles de tu factura:";
        $lineas[] = "";
        $lineas[] = "📋 *Factura:* " . ($d['id_formateado'] ?? 'N/A');
        if (!empty($d['placa'])) {
            $lineas[] = "🚗 *Vehículo:* " . trim(($d['marca_vehiculo'] ?? '') . ' ' . ($d['modelo_vehiculo'] ?? ''));
            $lineas[] = "🔖 *Placa:* {$d['placa']}";
        }
        $lineas[] = "";

        $items = $d['items'] ?? [];
        if (!empty($items)) {
            $lineas[] = "🛒 *Detalle:*";
            foreach ($items as $it) {
                $it = (array)$it;
                $desc = $it['descripcion'] ?? 'Ítem';
                $cant = (int)($it['cantidad'] ?? 1);
                $sub  = number_format((float)($it['subtotal'] ?? ($it['precio'] ?? 0) * $cant), 2);
                $lineas[] = "  • {$desc} (x{$cant}) — \${$sub}";
            }
            $lineas[] = "";
        }

        $lineas[] = "💰 *Subtotal:* \$" . number_format((float)($d['subtotal'] ?? 0), 2);
        if (!empty($d['iva_monto']) && (float)$d['iva_monto'] > 0) {
            $lineas[] = "🧾 *IVA:* \$" . number_format((float)$d['iva_monto'], 2);
        }
        $lineas[] = "💵 *TOTAL:* \$" . number_format((float)($d['total'] ?? 0), 2);
        $lineas[] = "";
        $lineas[] = "📌 *Estado:* {$estadoTxt}";

        $pef = (float)($d['pago_efectivo'] ?? 0);
        $ptr = (float)($d['pago_transferencia'] ?? 0);
        $saldo = (float)($d['saldo_pendiente'] ?? 0);
        if ($pef > 0 || $ptr > 0) {
            $lineas[] = "";
            $lineas[] = "💸 *Pagado:* \$" . number_format($pef + $ptr, 2);
        }
        if ($saldo > 0) {
            $lineas[] = "⏳ *Saldo pendiente:* \$" . number_format($saldo, 2);
        }

        if (!empty($d['vendedor_nombre'])) {
            $lineas[] = "";
            $lineas[] = "👤 *Atendido por:* {$d['vendedor_nombre']}";
        }
        $lineas[] = "";
        $lineas[] = "¡Gracias por tu preferencia!";

        return implode("\n", $lineas);
    }

    /**
     * Notifica al cliente cuando se cierra/finaliza la facturación de una orden de servicio.
     * Incluye cómo entró el vehículo, qué se le realizó y cómo salió.
     *
     * @param array $datos Datos de la venta/orden (ver formatearFacturacionCerrada para claves)
     * @return array {success: bool, mensaje: string, respuesta: ?string}
     */
    public function notificarFacturacionCerrada(array $datos): array
    {
        $telefono = $datos['cliente_telefono'] ?? '';
        $mensaje = $this->formatearFacturacionCerrada($datos);
        return $this->enviar($telefono, $mensaje);
    }

    /**
     * Formatea el mensaje de facturación cerrada (orden de servicio finalizada).
     * Incluye diagnóstico de entrada, servicios realizados y diagnóstico de salida.
     */
    private function formatearFacturacionCerrada(array $d): string
    {
        $statusTxt = [
            'COMPLETADO' => 'Pagado (Completado)',
            'CREDITO'    => 'Crédito',
            'PENDIENTE'  => 'Pendiente (Abono)',
        ];
        $estadoTxt = $statusTxt[$d['status'] ?? ''] ?? ($d['status'] ?? 'Pendiente');

        $lineas = [];
        $lineas[] = "🔧 *SERVICIO FINALIZADO — FACTURA*";
        $lineas[] = "";
        $lineas[] = "Hola *{$d['cliente_nombre']}*, tu orden de servicio ha sido finalizada:";
        $lineas[] = "";
        $lineas[] = "📋 *Factura:* " . ($d['id_formateado'] ?? 'N/A');
        if (!empty($d['orden_id'])) {
            $lineas[] = "📝 *Orden de Servicio:* " . ($d['orden_id_formateado'] ?? str_pad((string)$d['orden_id'], 6, '0', STR_PAD_LEFT));
        }
        $lineas[] = "🚗 *Vehículo:* " . trim(($d['marca_vehiculo'] ?? '') . ' ' . ($d['modelo_vehiculo'] ?? ''));
        if (!empty($d['placa'])) {
            $lineas[] = "🔖 *Placa:* {$d['placa']}";
        }
        if (!empty($d['mecanico_nombre'])) {
            $lineas[] = "👨‍🔧 *Mecánico:* {$d['mecanico_nombre']}";
        }
        $lineas[] = "";

        // ── Cómo entró el vehículo ──
        $entrada = trim((string)($d['diagnostico_entrada'] ?? ''));
        $obsOrden = trim((string)($d['observaciones_orden'] ?? ''));
        $lineas[] = "📥 *CÓMO ENTRÓ EL VEHÍCULO:*";
        if ($entrada !== '') {
            $lineas[] = $entrada;
        }
        if ($obsOrden !== '' && $obsOrden !== $entrada) {
            $lineas[] = $obsOrden;
        }
        if ($entrada === '' && $obsOrden === '') {
            $lineas[] = "Sin observaciones de entrada registradas.";
        }
        if (!empty($d['kilometraje'])) {
            $lineas[] = "📏 *Kilometraje:* {$d['kilometraje']} km";
        }
        if (!empty($d['nivel_combustible'])) {
            $lineas[] = "⛽ *Combustible:* {$d['nivel_combustible']}";
        }
        $lineas[] = "";

        // ── Qué se le realizó ──
        $items = $d['items'] ?? [];
        if (!empty($items)) {
            $lineas[] = "🛠️ *SERVICIOS REALIZADOS:*";
            foreach ($items as $it) {
                $it = (array)$it;
                $desc = $it['descripcion'] ?? 'Ítem';
                $cant = (int)($it['cantidad'] ?? 1);
                $sub  = number_format((float)($it['subtotal'] ?? ($it['precio'] ?? 0) * $cant), 2);
                $lineas[] = "  • {$desc} (x{$cant}) — \${$sub}";
            }
            $lineas[] = "";
        }

        // ── Checklist (si existe) ──
        $checklist = $d['checklist'] ?? [];
        if (!empty($checklist)) {
            $lineas[] = "✅ *CHECKLIST DE REVISIÓN:*";
            foreach ($checklist as $chk) {
                $chk = (array)$chk;
                $item = $chk['item'] ?? '';
                $obs  = $chk['observacion'] ?? '';
                $lineas[] = "  • {$item}" . ($obs !== '' ? ": {$obs}" : '');
            }
            $lineas[] = "";
        }

        // ── Cómo salió el vehículo ──
        $salida = trim((string)($d['diagnostico_salida'] ?? ''));
        $obsFactura = trim((string)($d['observaciones_factura'] ?? ''));
        $lineas[] = "📤 *CÓMO SALIÓ EL VEHÍCULO:*";
        if ($salida !== '') {
            $lineas[] = $salida;
        }
        if ($obsFactura !== '' && $obsFactura !== $salida) {
            $lineas[] = $obsFactura;
        }
        if ($salida === '' && $obsFactura === '') {
            $lineas[] = "Sin observaciones de salida registradas.";
        }
        $lineas[] = "";

        // ── Totales ──
        $lineas[] = "💰 *Subtotal:* \$" . number_format((float)($d['subtotal'] ?? 0), 2);
        if (!empty($d['iva_monto']) && (float)$d['iva_monto'] > 0) {
            $lineas[] = "🧾 *IVA:* \$" . number_format((float)$d['iva_monto'], 2);
        }
        $lineas[] = "💵 *TOTAL:* \$" . number_format((float)($d['total'] ?? 0), 2);
        $lineas[] = "";
        $lineas[] = "📌 *Estado:* {$estadoTxt}";

        $pef = (float)($d['pago_efectivo'] ?? 0);
        $ptr = (float)($d['pago_transferencia'] ?? 0);
        $saldo = (float)($d['saldo_pendiente'] ?? 0);
        if ($pef > 0 || $ptr > 0) {
            $lineas[] = "";
            $lineas[] = "💸 *Pagado:* \$" . number_format($pef + $ptr, 2);
        }
        if ($saldo > 0) {
            $lineas[] = "⏳ *Saldo pendiente:* \$" . number_format($saldo, 2);
        }

        if (!empty($d['vendedor_nombre'])) {
            $lineas[] = "";
            $lineas[] = "👤 *Atendido por:* {$d['vendedor_nombre']}";
        }
        $lineas[] = "";
        $lineas[] = "¡Gracias por confiar en nosotros! 🚙";

        return implode("\n", $lineas);
    }
}