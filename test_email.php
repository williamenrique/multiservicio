<?php
/**
 * Script de prueba del sistema de email centralizado
 * 
 * Ejecutar desde el navegador: http://localhost/multiservicio/test_email.php
 * O desde terminal: php c:\xampp\htdocs\multiservicio\test_email.php
 */

// Cargar el bootstrap de la aplicación
require_once __DIR__ . '/public_html/index.php';

use App\Services\EmailService;

echo "<h1>🧪 Prueba del Sistema de Email</h1>";
echo "<pre>";

// ============================================================
// PASO 1: Verificar constantes SMTP
// ============================================================
echo "<h2>📋 Paso 1: Verificando constantes SMTP</h2>";
echo "MAIL_HOST: " . MAIL_HOST . "\n";
echo "MAIL_PORT: " . MAIL_PORT . "\n";
echo "MAIL_USERNAME: " . MAIL_USERNAME . "\n";
echo "MAIL_PASSWORD: " . (defined('MAIL_PASSWORD') ? '******** (configurado)' : 'NO CONFIGURADO') . "\n";
echo "MAIL_ENCRYPTION: " . MAIL_ENCRYPTION . "\n";
echo "MAIL_FROM_ADDRESS: " . MAIL_FROM_ADDRESS . "\n";
echo "MAIL_FROM_NAME: " . MAIL_FROM_NAME . "\n";
echo "MAIL_ADMIN: " . MAIL_ADMIN . "\n";

// ============================================================
// PASO 2: Verificar que PHPMailer existe
// ============================================================
echo "\n<h2>📋 Paso 2: Verificando PHPMailer</h2>";
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✅ PHPMailer está disponible\n";
} else {
    echo "❌ PHPMailer NO encontrado. Ejecuta: composer install\n";
    exit;
}

// ============================================================
// PASO 3: Verificar que la empresa existe en BD
// ============================================================
echo "\n<h2>📋 Paso 3: Verificando datos de empresa</h2>";
$modelEmpresa = new \App\Models\ModelEmpresa();
$empresa = $modelEmpresa->obtenerConfiguracion();
if ($empresa) {
    echo "✅ Empresa encontrada: " . $empresa->name . "\n";
    echo "   Logo: " . ($empresa->logo ?: 'No configurado') . "\n";
    echo "   Dirección: " . ($empresa->direccion ?: 'No configurada') . "\n";
    echo "   Teléfono: " . ($empresa->telefono ?: 'No configurado') . "\n";
} else {
    echo "⚠️ No hay datos de empresa en table_company_settings. Se usará SITENAME.\n";
}

// ============================================================
// PASO 4: Verificar que las plantillas existen
// ============================================================
echo "\n<h2>📋 Paso 4: Verificando plantillas de email</h2>";
$plantillas = [
    'layout.php',
    'pedido_catalogo_cliente.php',
    'pedido_catalogo_admin.php',
];
foreach ($plantillas as $p) {
    $path = APPROOT . '/Views/email/' . $p;
    if (file_exists($path)) {
        echo "✅ $p (" . filesize($path) . " bytes)\n";
    } else {
        echo "❌ $p NO ENCONTRADO en $path\n";
    }
}

// ============================================================
// PASO 5: Probar renderizado de plantillas (sin enviar)
// ============================================================
echo "\n<h2>📋 Paso 5: Probando renderizado de plantillas</h2>";

$datosPrueba = [
    'cliente_nombre'    => 'Juan Pérez (PRUEBA)',
    'cliente_email'     => MAIL_ADMIN, // Enviamos al admin para no molestar a nadie
    'cliente_telefono'  => '0412-1234567',
    'cliente_cedula'    => 'V-12345678',
    'cliente_direccion' => 'Calle Principal #123, Caracas',
    'venta_formateado'  => 'FAC-999',
    'venta_id'          => 999,
    'id_formateado'     => 'PED-999',
    'fecha'             => date('d/m/Y h:i A'),
    'items'             => [
        ['descripcion' => 'Filtro de Aceite Toyota', 'cantidad' => 2, 'precio_unitario' => 15.50],
        ['descripcion' => 'Pastillas de Freno Delanteras', 'cantidad' => 1, 'precio_unitario' => 45.00],
        ['descripcion' => 'Bujía NGK Iridium', 'cantidad' => 4, 'precio_unitario' => 8.75],
    ],
    'subtotal' => 111.00,
    'iva'      => 21.09,
    'iva_tasa' => 19,
    'total'    => 132.09,
];

try {
    $emailService = new EmailService();
    
    // Renderizar plantilla del cliente (sin enviar)
    $reflection = new ReflectionClass($emailService);
    $metodoRenderizar = $reflection->getMethod('renderizar');
    $metodoRenderizar->setAccessible(true);
    
    $htmlCliente = $metodoRenderizar->invoke($emailService, 'pedido_catalogo_cliente', $datosPrueba);
    echo "✅ Plantilla CLIENTE renderizada: " . strlen($htmlCliente) . " caracteres\n";
    
    $htmlAdmin = $metodoRenderizar->invoke($emailService, 'pedido_catalogo_admin', $datosPrueba);
    echo "✅ Plantilla ADMIN renderizada: " . strlen($htmlAdmin) . " caracteres\n";
    
    // Guardar previews para inspección visual
    $previewDir = APPROOT . '/Views/email/previews/';
    if (!is_dir($previewDir)) mkdir($previewDir, 0777, true);
    file_put_contents($previewDir . 'cliente_preview.html', $htmlCliente);
    file_put_contents($previewDir . 'admin_preview.html', $htmlAdmin);
    echo "✅ Previews guardados en: Views/email/previews/\n";
    
} catch (Exception $e) {
    echo "❌ Error al renderizar: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// ============================================================
// PASO 6: Enviar correo de prueba
// ============================================================
echo "\n<h2>📋 Paso 6: Enviando correo de prueba</h2>";
echo "Destinatario: " . MAIL_ADMIN . "\n";

try {
    $emailService = new EmailService();
    $resultado = $emailService->notificarPedidoCatalogo($datosPrueba);
    
    if ($resultado['cliente']) {
        echo "✅ Correo de CLIENTE enviado correctamente\n";
    } else {
        echo "❌ Falló el envío al CLIENTE (revisa error_log)\n";
    }
    
    if ($resultado['admin']) {
        echo "✅ Correo de ADMIN enviado correctamente\n";
    } else {
        echo "❌ Falló el envío al ADMIN (revisa error_log)\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n</pre>";
echo "<p><strong>✅ Prueba completada.</strong> Revisa también los previews HTML en <code>Views/email/previews/</code></p>";
echo "<p><a href='" . URLROOT . "/Views/email/previews/cliente_preview.html' target='_blank'>Ver preview cliente</a> | ";
echo "<a href='" . URLROOT . "/Views/email/previews/admin_preview.html' target='_blank'>Ver preview admin</a></p>";