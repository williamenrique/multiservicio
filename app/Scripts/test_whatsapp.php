<?php
/**
 * Script de prueba para WhatsAppService
 * 
 * Ubicación: app/Scripts/test_whatsapp.php
 * 
 * Este script prueba el envío de WhatsApp usando el servicio centralizado.
 * NO está expuesto en public_html/ — se ejecuta desde CLI o incluyéndolo
 * desde un controlador autenticado.
 * 
 * Uso desde terminal:
 *   php app/Scripts/test_whatsapp.php
 * 
 * O desde el navegador (solo desarrollo):
 *   http://localhost/multiservicio/app/Scripts/test_whatsapp.php
 */

// Cargar el sistema
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Services/WhatsAppService.php';

use App\Services\WhatsAppService;

// --- DATOS DE PRUEBA (simula un pedido real del catálogo) ---
$telefono_cliente = "584125181629"; // Número del cliente (código país + número, sin + ni espacios)

$datosPrueba = [
    'cliente_nombre'    => 'JUAN PÉREZ',
    'cliente_email'     => 'juan@example.com',
    'cliente_telefono'  => $telefono_cliente,
    'cliente_cedula'    => 'V-12345678',
    'cliente_direccion' => 'Av. Principal, Caracas',
    'venta_formateado'  => 'FAC-001',
    'id_formateado'     => 'PED-001',
    'fecha'             => date('d/m/Y h:i A'),
    'items'             => [
        ['nombre' => 'Filtro de Aceite Toyota', 'cantidad' => 2, 'precio' => 15.50],
        ['nombre' => 'Pastillas de Freno Delanteras', 'cantidad' => 1, 'precio' => 45.00],
        ['nombre' => 'Bujía NGK Iridium', 'cantidad' => 4, 'precio' => 8.75],
    ],
    'subtotal' => 111.00,
    'iva'      => 0,
    'iva_tasa' => 19,
    'total'    => 111.00,
];

echo "<h2>🧪 Prueba de WhatsAppService</h2>";
echo "<pre>";

$whatsapp = new WhatsAppService();

// --- Prueba 1: Enviar notificación al ADMIN ---
echo "<strong>1️⃣ Enviando notificación al ADMINISTRADOR...</strong>\n";
$resultAdmin = $whatsapp->notificarPedidoCatalogoAdmin($datosPrueba);
echo "   Success: " . ($resultAdmin['success'] ? '✅ SÍ' : '❌ NO') . "\n";
echo "   Mensaje: " . $resultAdmin['mensaje'] . "\n";
if ($resultAdmin['respuesta']) {
    echo "   Respuesta: " . $resultAdmin['respuesta'] . "\n";
}

echo "\n";

// --- Prueba 2: Enviar notificación al CLIENTE ---
echo "<strong>2️⃣ Enviando al CLIENTE ({$telefono_cliente})...</strong>\n";
$resultCliente = $whatsapp->notificarPedidoCatalogoCliente($datosPrueba);
echo "   Success: " . ($resultCliente['success'] ? '✅ SÍ' : '❌ NO') . "\n";
echo "   Mensaje: " . $resultCliente['mensaje'] . "\n";
if ($resultCliente['respuesta']) {
    echo "   Respuesta: " . $resultCliente['respuesta'] . "\n";
}

echo "\n";

// --- Prueba 3: Enviar ambas notificaciones ---
echo "<strong>3️⃣ Enviando AMBAS notificaciones (admin + cliente)...</strong>\n";
$resultAmbos = $whatsapp->notificarPedidoCatalogo($datosPrueba);
echo "   Admin: " . ($resultAmbos['admin']['success'] ? '✅' : '❌') . " — " . $resultAmbos['admin']['mensaje'] . "\n";
echo "   Cliente: " . ($resultAmbos['cliente']['success'] ? '✅' : '❌') . " — " . $resultAmbos['cliente']['mensaje'] . "\n";

echo "\n<strong>✅ Prueba completada</strong>\n";
echo "</pre>";