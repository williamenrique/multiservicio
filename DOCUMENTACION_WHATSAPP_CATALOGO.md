# DOCUMENTACIÓN DE IMPLEMENTACIÓN: NOTIFICACIONES WHATSAPP AUTOMÁTICAS PARA PEDIDOS DE CATÁLOGO

**Fecha:** 2026-09-11  
**Versión:** 1.0  
**Sistema:** Multiservicio 2.0

---

## 1. RESUMEN EJECUTIVO

Se implementó el envío automático de notificaciones por WhatsApp cuando un cliente realiza un pedido a través del catálogo público. El sistema ahora notifica **tanto al administrador como al cliente** automáticamente al confirmar el pedido.

### Lo que se logró:
- ✅ Notificación al **ADMINISTRADOR** con detalles completos del pedido
- ✅ Notificación al **CLIENTE** confirmando su pedido
- ✅ Manejo de errores robusto (no interrumpe el flujo si WhatsApp falla)
- ✅ Configuración centralizada vía variables de entorno (.env)
- ✅ Documentación completa para despliegue y mantenimiento

---

## 2. ARQUITECTURA DEL SISTEMA

```
┌─────────────────┐     HTTP POST      ┌──────────────────────┐
│  PHP (Laravel-  │ ─────────────────▶ │  Servidor Node.js    │
│   like MVC)     │   JSON: {telefono, │  (baileys /          │
│                 │    mensaje}        │   whatsapp-web.js)   │
└─────────────────┘                    └──────────┬───────────┘
                                                   │
                                                   ▼
                                            ┌──────────────────┐
                                            │  WhatsApp Web    │
                                            │  (Meta/Facebook) │
                                            └──────────────────┘
```

### Componentes:
1. **WhatsAppService.php** - Servicio PHP que hace la petición HTTP al servidor Node.js
2. **ControllerCatalogo.php** - Controlador que dispara la notificación al procesar pedido
3. **config.php** - Constantes de configuración (leídas desde .env)
4. **Servidor Node.js** - Maneja la conexión real con WhatsApp Web API

---

## 3. ARCHIVOS MODIFICADOS / CREADOS

### 3.1 Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `App/Controllers/ControllerCatalogo.php` | Línea ~525: Cambiado de `notificarPedidoCatalogoAdmin()` a `notificarPedidoCatalogo()` para notificar a admin Y cliente |

### 3.2 Archivos Creados

| Archivo | Descripción |
|---------|-------------|
| `.env.example` | Plantilla de configuración con todas las variables de WhatsApp documentadas |
| `DOCUMENTACION_WHATSAPP_CATALOGO.md` | Este documento |

### 3.3 Archivos Existentes (Ya funcionaban)

| Archivo | Función |
|---------|---------|
| `App/Services/WhatsAppService.php` | Servicio centralizado de WhatsApp con métodos para admin y cliente |
| `App/Config/config.php` | Define constantes `WHATSAPP_API_URL`, `WHATSAPP_TIMEOUT`, `WHATSAPP_ADMIN_PHONE` |

---

## 4. CONFIGURACIÓN PASO A PASO

### PASO 1: Preparar el Servidor WhatsApp (Node.js)

**Opción A: Usar Baileys (Recomendado - más ligero)**
```bash
# En tu servidor Node.js
mkdir whatsapp-server && cd whatsapp-server
npm init -y
npm install @whiskeysockets/baileys express qrcode-terminal
```

Crear `server.js`:
```javascript
const express = require('express');
const { default: makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const qrcode = require('qrcode-terminal');

const app = express();
app.use(express.json());

let sock = null;

async function connectToWhatsApp() {
    const { state, saveCreds } = await useMultiFileAuthState('./auth');
    sock = makeWASocket({ auth: state, printQRInTerminal: true });
    
    sock.ev.on('creds.update', saveCreds);
    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect } = update;
        if (connection === 'close') {
            const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;
            if (shouldReconnect) connectToWhatsApp();
        }
    });
}

connectToWhatsApp();

app.post('/enviar-pedido', async (req, res) => {
    const { telefono, mensaje } = req.body;
    
    if (!telefono || !mensaje) {
        return res.status(400).json({ success: false, error: 'Faltan parámetros' });
    }
    
    if (!sock) {
        return res.status(503).json({ success: false, error: 'WhatsApp no conectado' });
    }
    
    try {
        // Formato: 584125181629@s.whatsapp.net
        const jid = `${telefono}@s.whatsapp.net`;
        await sock.sendMessage(jid, { text: mensaje });
        res.json({ success: true });
    } catch (error) {
        console.error('Error enviando WhatsApp:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`WhatsApp API corriendo en puerto ${PORT}`));
```

**Opción B: Usar whatsapp-web.js (Más completo, usa Puppeteer)**
```bash
npm install whatsapp-web.js express qrcode-terminal
```
(Similar estructura, pero requiere Chromium/Puppeteer)

### PASO 2: Configurar Variables de Entorno

Copiar `.env.example` a `.env` en la raíz del proyecto:
```bash
cp .env.example .env
```

Editar `.env` con tus valores reales:
```env
# WhatsApp - CAMBIA ESTOS VALORES
WHATSAPP_API_URL=http://TU_IP_SERVIDOR_NODE:3000/enviar-pedido
WHATSAPP_TIMEOUT=10
WHATSAPP_ADMIN_PHONE=584125181629  # Tu número de admin (código país + número, sin +)
```

### PASO 3: Verificar Configuración en config.php

Las constantes ya están definidas en `App/Config/config.php` (líneas ~90-93):
```php
define('WHATSAPP_API_URL', $_ENV['WHATSAPP_API_URL'] ?? 'http://localhost:3000/enviar-pedido');
define('WHATSAPP_TIMEOUT', (int)($_ENV['WHATSAPP_TIMEOUT'] ?? 10));
define('WHATSAPP_ADMIN_PHONE', $_ENV['WHATSAPP_ADMIN_PHONE'] ?? '584125181629');
```

### PASO 4: Probar la Integración

1. Iniciar servidor Node.js: `node server.js`
2. Escanear código QR con WhatsApp en tu teléfono
3. En el sistema web, hacer un pedido de prueba en el catálogo
4. Verificar que lleguen los mensajes a:
   - Tu número de admin (WHATSAPP_ADMIN_PHONE)
   - El número del cliente de prueba

---

## 5. FLUJO DE FUNCIONAMIENTO

### Cuando el cliente hace un pedido:

```
1. Cliente llena formulario en /catalogo/checkout
2. POST a /catalogo/procesar-pedido
3. ControllerCatalogo::procesarPedido()
   ├─ Valida datos
   ├─ Crea cliente en BD (si no existe)
   ├─ Procesa venta con BillingService (factura, stock, etc.)
   ├─ Crea registro en pedidos_clientes (legacy)
   ├─ Envía EMAIL de confirmación (EmailService)
   └─ 🆕 ENVÍA WHATSAPP (WhatsAppService::notificarPedidoCatalogo)
       ├─ notificarPedidoCatalogoAdmin() → Admin
       └─ notificarPedidoCatalogoCliente() → Cliente
4. Limpia carrito
5. Redirige a /catalogo/confirmacion/{ventaId}
```

### Formato de mensajes:

**Para ADMIN:**
```
🔔 *NUEVO PEDIDO DE CATÁLOGO*

📋 *Factura:* FAC-001
📋 *Pedido:* PED-001
📅 *Fecha:* 11/09/2026 10:30 AM

👤 *DATOS DEL CLIENTE*
Nombre: JUAN PEREZ
Cédula: 12345678
Teléfono: 584125181629
Correo: juan@email.com
Dirección: Calle 123, Caracas

🛒 *PRODUCTOS SOLICITADOS*
  • Filtro de Aceite (x2) — $15.50
  • Pastillas de Freno (x1) — $45.00

💰 *Subtotal:* $76.00
💵 *TOTAL:* $76.00

⚙️ Revisa el sistema para gestionar este pedido.
```

**Para CLIENTE:**
```
✅ *¡Gracias por tu pedido, JUAN PEREZ!*

Hemos recibido tu pedido correctamente:

📋 *Pedido:* PED-001
📋 *Factura:* FAC-001
📅 *Fecha:* 11/09/2026 10:30 AM
📌 *Estado:* PENDIENTE

🛒 *Productos:*
  • Filtro de Aceite (x2) — $15.50
  • Pastillas de Freno (x1) — $45.00

💰 *Total:* $76.00

Nos pondremos en contacto contigo pronto para coordinar la entrega.
Si tienes dudas, responde a este mensaje o contáctanos.
```

---

## 6. MANEJO DE ERRORES

El sistema está diseñado para **nunca interrumpir el flujo del pedido** si WhatsApp falla:

```php
try {
    $whatsappService = new \App\Services\WhatsAppService();
    $resultadoWhatsapp = $whatsappService->notificarPedidoCatalogo($datosEmail);
    
    $warnings = [];
    if (!$resultadoWhatsapp['admin']['success']) {
        $warnings[] = 'No se pudo notificar al administrador por WhatsApp.';
    }
    if (!$resultadoWhatsapp['cliente']['success']) {
        $warnings[] = 'No se pudo notificar al cliente por WhatsApp.';
    }
    
    if (!empty($warnings)) {
        $_SESSION['whatsapp_warning'] = 'El pedido se registró correctamente. ' . implode(' ', $warnings) . ' Te notificaremos por correo.';
    }
} catch (\Throwable $whatsappEx) {
    error_log("ERROR WHATSAPP CATÁLOGO: " . $whatsappEx->getMessage());
    $_SESSION['whatsapp_warning'] = 'El pedido se registró correctamente, pero el servidor de WhatsApp no está disponible en este momento. Te notificaremos por correo.';
}
```

### Posibles causas de fallo:
| Causa | Solución |
|-------|----------|
| Servidor Node.js apagado | Verificar que `node server.js` esté corriendo |
| WhatsApp desconectado (QR expirado) | Re-escanear QR en el servidor |
| Número de teléfono inválido | Verificar formato: código país + número (sin +) |
| Timeout | Aumentar `WHATSAPP_TIMEOUT` en .env |
| Firewall/Red | Verificar conectividad entre PHP y Node.js |

---

## 7. PRUEBAS Y VERIFICACIÓN

### Test Manual Rápido (desde terminal PHP):
```bash
cd /xampp/htdocs/multiservicio
php -r "
require_once 'vendor/autoload.php';
require_once 'App/Config/config.php';
require_once 'App/Services/WhatsAppService.php';

\$service = new \App\Services\WhatsAppService();
\$result = \$service->enviar('584125181629', '🧪 *TEST* - Mensaje de prueba desde PHP');
print_r(\$result);
"
```

### Verificar Logs:
```bash
# Logs de PHP (errores de WhatsApp)
tail -f /xampp/php/logs/php_error_log

# Logs del servidor Node.js
# Ver consola donde corre node server.js
```

### Endpoints de prueba en el sistema:
- `GET /catalogo` - Ver catálogo
- `GET /catalogo/carrito` - Ver carrito
- `GET /catalogo/checkout` - Formulario de pedido
- `POST /catalogo/procesar-pedido` - Procesa y envía WhatsApp

---

## 8. MANTENIMIENTO Y MONITOREO

### Verificar estado del servidor WhatsApp:
```bash
# Health check simple
curl http://localhost:3000/enviar-pedido -X POST -H "Content-Type: application/json" -d '{"telefono":"584125181629","mensaje":"test"}'
```

### Reiniciar servidor WhatsApp:
```bash
# Si usas PM2 (recomendado para producción)
pm2 restart whatsapp-server

# O manualmente
pkill -f "node server.js"
node server.js &
```

### Rotación de logs (opcional):
```bash
# En el servidor Node.js, agregar winston o similar para logs rotativos
npm install winston winston-daily-rotate-file
```

---

## 9. SOLUCIÓN DE PROBLEMAS COMUNES

| Problema | Causa | Solución |
|----------|-------|----------|
| "Error de conexión" en logs PHP | Node.js no responde | Verificar que Node.js esté corriendo y accesible |
| "HTTP 500" del servidor WhatsApp | Error interno Node.js | Revisar logs de Node.js, reiniciar |
| Mensaje no llega al cliente | Número mal formateado | Verificar que el cliente ingrese teléfono con código país |
| Mensaje no llega al admin | WHATSAPP_ADMIN_PHONE incorrecto | Verificar en .env y config.php |
| QR code no aparece | Baileys no genera QR | Borrar carpeta `auth/` y reiniciar Node.js |

---

## 10. SEGURIDAD

⚠️ **IMPORTANTE:**
- **NUNCA** subas el archivo `.env` a git (agregar a `.gitignore`)
- El servidor Node.js de WhatsApp **NO debe ser público** (solo accesible desde el servidor PHP)
- Usa firewall para restringir acceso al puerto 3000 solo al IP del servidor PHP
- Considera agregar autenticación (API Key) entre PHP y Node.js

```nginx
# Ejemplo Nginx - restringir acceso al servidor WhatsApp
location /enviar-pedido {
    allow 192.168.1.100;  # IP del servidor PHP
    deny all;
    proxy_pass http://localhost:3000;
}
```

---

## 11. EXTENSIONES FUTURAS

Posibles mejoras a implementar:
- [ ] Notificación de cambio de estado del pedido (ENVIADO, ENTREGADO, CANCELADO)
- [ ] Botones interactivos en WhatsApp (respuesta rápida)
- [ ] Plantillas de mensaje aprobadas por Meta (para mayor deliverability)
- [ ] Cola de mensajes (Redis/RabbitMQ) para alta concurrencia
- [ ] Dashboard de estado de mensajes (entregado, leído, fallido)
- [ ] Soporte multi-idioma en mensajes

---

## 12. CONTACTO Y SOPORTE

Para dudas sobre esta implementación:
- Revisar logs en `/xampp/php/logs/php_error_log`
- Verificar consola del servidor Node.js
- Consultar documentación de Baileys: https://github.com/WhiskeySockets/Baileys

---

**Fin del documento**  
*Generado automáticamente como parte de la implementación de notificaciones WhatsApp para pedidos de catálogo.*