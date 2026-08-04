<?php
/**
 * Script de prueba del sistema de email centralizado
 * 
 * Ejecutar desde el navegador: http://localhost/multiservicio/test_email.php
 * 
 * NOTA: Carga manualmente solo lo necesario (config, autoload, DB)
 * sin pasar por el enrutador ni AuthGuard.
 */

// 1. Cargar configuración
require_once __DIR__ . '/../app/Config/config.php';

// 2. Cargar autoload de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// 3. Cargar helpers
require_once __DIR__ . '/../app/Helpers/helpers.php';

// 4. Autoload manual del sistema (igual que en index.php)
spl_autoload_register(function($nombreClase) {
    $directorios = [
        APPROOT . '/Core/',
        APPROOT . '/Middleware/',
        APPROOT . '/Services/',
        APPROOT . '/Models/',
        APPROOT . '/Controllers/',
        APPROOT . '/Helpers/'
    ];
    foreach ($directorios as $directorio) {
        $archivo = $directorio . $nombreClase . '.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            return;
        }
    }
});

// 5. Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Services\EmailService;

echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Test Email</title>";
echo "<style>
    body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 20px; }
    .container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); padding: 30px; }
    h1 { color: #1a56db; margin-top: 0; }
    h2 { color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-top: 30px; }
    .success { color: #16a34a; }
    .error { color: #dc2626; }
    .warning { color: #d97706; }
    pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 13px; line-height: 1.5; }
    .btn { display: inline-block; padding: 10px 20px; background: #1a56db; color: #fff; text-decoration: none; border-radius: 6px; margin: 5px; }
    .btn:hover { background: #1e40af; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .badge-ok { background: #dcfce7; color: #16a34a; }
    .badge-err { background: #fee2e2; color: #dc2626; }
    .badge-warn { background: #fef3c7; color: #d97706; }
</style></head><body><div class='container'>";

echo "<h1>🧪 Prueba del Sistema de Email Centralizado</h1>";

// ============================================================
// PASO 1: Verificar constantes SMTP
// ============================================================
echo "<h2>📋 Paso 1: Constantes SMTP</h2><pre>";
echo "MAIL_HOST:        " . MAIL_HOST . "\n";
echo "MAIL_PORT:        " . MAIL_PORT . "\n";
echo "MAIL_USERNAME:    " . MAIL_USERNAME . "\n";
echo "MAIL_PASSWORD:    " . (defined('MAIL_PASSWORD') && MAIL_PASSWORD ? '<span class="success">******** (configurado)</span>' : '<span class="error">NO CONFIGURADO</span>') . "\n";
echo "MAIL_ENCRYPTION:  " . MAIL_ENCRYPTION . "\n";
echo "MAIL_FROM_ADDRESS: " . MAIL_FROM_ADDRESS . "\n";
echo "MAIL_FROM_NAME:   " . MAIL_FROM_NAME . "\n";
echo "MAIL_ADMIN:       " . MAIL_ADMIN . "\n";
echo "</pre>";

// ============================================================
// PASO 2: Verificar PHPMailer
// ============================================================
echo "<h2>📋 Paso 2: PHPMailer</h2>";
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<p><span class='badge badge-ok'>OK</span> PHPMailer está disponible</p>";
} else {
    echo "<p><span class='badge badge-err'>ERROR</span> PHPMailer NO encontrado. Ejecuta: <code>composer install</code></p>";
    echo "</div></body></html>";
    exit;
}

// ============================================================
// PASO 3: Verificar datos de empresa
// ============================================================
echo "<h2>📋 Paso 3: Datos de empresa</h2>";
try {
    $modelEmpresa = new ModelEmpresa();
    $empresa = $modelEmpresa->obtenerConfiguracion();
    if ($empresa) {
        echo "<p><span class='badge badge-ok'>OK</span> Empresa: <strong>{$empresa->name}</strong></p>";
        echo "<pre>";
        echo "Logo:      " . ($empresa->logo ?: 'No configurado') . "\n";
        echo "Dirección: " . ($empresa->direccion ?: 'No configurada') . "\n";
        echo "Teléfono:  " . ($empresa->telefono ?: 'No configurado') . "\n";
        echo "</pre>";
    } else {
        echo "<p><span class='badge badge-warn'>WARN</span> No hay datos en table_company_settings. Se usará SITENAME.</p>";
    }
} catch (Exception $e) {
    echo "<p><span class='badge badge-err'>ERROR</span> " . $e->getMessage() . "</p>";
}

// ============================================================
// PASO 4: Verificar plantillas
// ============================================================
echo "<h2>📋 Paso 4: Plantillas de email</h2>";
$plantillas = [
    'layout.php',
    'pedido_catalogo_cliente.php',
    'pedido_catalogo_admin.php',
];
echo "<pre>";
foreach ($plantillas as $p) {
    $path = APPROOT . '/Views/email/' . $p;
    if (file_exists($path)) {
        echo "<span class='success'>✅</span> $p (" . filesize($path) . " bytes)\n";
    } else {
        echo "<span class='error'>❌</span> $p NO ENCONTRADO en $path\n";
    }
}
echo "</pre>";

// ============================================================
// PASO 5: Renderizar plantillas (sin enviar)
// ============================================================
echo "<h2>📋 Paso 5: Renderizado de plantillas</h2>";

$datosPrueba = [
    'cliente_nombre'    => 'Juan Pérez (PRUEBA)',
    'cliente_email'     => MAIL_ADMIN,
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
    
    // Usar Reflection para acceder al método privado renderizar()
    $reflection = new ReflectionClass($emailService);
    $metodoRenderizar = $reflection->getMethod('renderizar');
    $metodoRenderizar->setAccessible(true);
    
    $htmlCliente = $metodoRenderizar->invoke($emailService, 'pedido_catalogo_cliente', $datosPrueba);
    echo "<p><span class='badge badge-ok'>OK</span> Plantilla CLIENTE: " . strlen($htmlCliente) . " caracteres</p>";
    
    $htmlAdmin = $metodoRenderizar->invoke($emailService, 'pedido_catalogo_admin', $datosPrueba);
    echo "<p><span class='badge badge-ok'>OK</span> Plantilla ADMIN: " . strlen($htmlAdmin) . " caracteres</p>";
    
    // Guardar previews
    $previewDir = APPROOT . '/Views/email/previews/';
    if (!is_dir($previewDir)) mkdir($previewDir, 0777, true);
    file_put_contents($previewDir . 'cliente_preview.html', $htmlCliente);
    file_put_contents($previewDir . 'admin_preview.html', $htmlAdmin);
    echo "<p><span class='badge badge-ok'>OK</span> Previews guardados en <code>Views/email/previews/</code></p>";
    
} catch (Exception $e) {
    echo "<p><span class='badge badge-err'>ERROR</span> " . $e->getMessage() . "</p>";
    echo "<pre>Archivo: " . $e->getFile() . ":" . $e->getLine() . "\nTrace: " . $e->getTraceAsString() . "</pre>";
}

// ============================================================
// PASO 6: Enviar correo de prueba
// ============================================================
echo "<h2>📋 Paso 6: Envío de correo de prueba</h2>";
echo "<p>Destinatario: <strong>" . MAIL_ADMIN . "</strong></p>";

try {
    $emailService = new EmailService();
    $resultado = $emailService->notificarPedidoCatalogo($datosPrueba);
    
    echo "<pre>";
    if ($resultado['cliente']) {
        echo "<span class='success'>✅ Correo de CLIENTE enviado correctamente</span>\n";
    } else {
        echo "<span class='error'>❌ Falló el envío al CLIENTE</span> (revisa error_log de PHP)\n";
    }
    
    if ($resultado['admin']) {
        echo "<span class='success'>✅ Correo de ADMIN enviado correctamente</span>\n";
    } else {
        echo "<span class='error'>❌ Falló el envío al ADMIN</span> (revisa error_log de PHP)\n";
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p><span class='badge badge-err'>EXCEPCIÓN</span></p>";
    echo "<pre>Mensaje: " . $e->getMessage() . "\nArchivo: " . $e->getFile() . ":" . $e->getLine() . "\n\nTrace:\n" . $e->getTraceAsString() . "</pre>";
}

echo "<hr style='margin:30px 0; border:0; border-top:1px solid #e2e8f0;'>";
echo "<p><strong>✅ Prueba completada.</strong></p>";
echo "<p>
    <a href='" . URLROOT . "/Views/email/previews/cliente_preview.html' target='_blank' class='btn'>📧 Ver preview cliente</a>
    <a href='" . URLROOT . "/Views/email/previews/admin_preview.html' target='_blank' class='btn'>📧 Ver preview admin</a>
</p>";

echo "</div></body></html>";