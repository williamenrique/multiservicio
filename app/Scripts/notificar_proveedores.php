<?php
/**
 * Script: notificar_proveedores.php
 * 
 * Verifica las facturas de proveedores próximas a vencer y envía
 * una alerta por correo al administrador.
 * 
 * Uso:
 *   - Desde navegador:   https://tudominio/app/Scripts/notificar_proveedores.php
 *   - Desde terminal:    php c:\xampp\htdocs\multiservicio\app\Scripts\notificar_proveedores.php
 *   - Tarea programada:  php c:\xampp\htdocs\multiservicio\app\Scripts\notificar_proveedores.php
 * 
 * Se recomienda ejecutar 1 vez al día.
 */

// ─── Arrancar el framework ────────────────────────────────────────
require_once __DIR__ . '/../../app/Config/config.php';
require_once __DIR__ . '/../../app/Helpers/helpers.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Autoload personalizado (mismo que en index.php)
spl_autoload_register(function ($className) {
    $prefixes = [
        'App\\Services\\' => __DIR__ . '/../Services/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $className, $len) === 0) {
            $relativeClass = substr($className, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) { require $file; return; }
        }
    }
    // Modelos sin namespace
    $file = __DIR__ . '/../Models/' . $className . '.php';
    if (file_exists($file)) { require $file; return; }
});

// ─── Configuración ────────────────────────────────────────────────
$diasLimite = 7; // Días hacia adelante para considerar "próximo a vencer"

// ─── Obtener proveedores con deudas ──────────────────────────────
try {
    $modelProveedor = new ModelProveedor();
    $proveedores = $modelProveedor->listarDeudas();
} catch (Exception $e) {
    error_log("notificar_proveedores.php: Error al consultar deudas: " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo "Error interno";
    }
    exit(1);
}

if (empty($proveedores)) {
    if (php_sapi_name() === 'cli') {
        echo "No hay proveedores con deudas pendientes.\n";
    } else {
        echo "No hay proveedores con deudas pendientes.";
    }
    exit(0);
}

// ─── Filtrar solo los que vencen dentro del límite o ya están vencidos ──
$hoy = new DateTime();
$proveedoresFiltrados = [];

foreach ($proveedores as $prov) {
    $prov = (object) $prov;
    $fechaVenc = $prov->proximo_vencimiento ?? null;

    if (empty($fechaVenc)) {
        continue; // Sin fecha de vencimiento, lo saltamos
    }

    try {
        $fechaObj = new DateTime($fechaVenc);
        $diff = $hoy->diff($fechaObj);
        $diasRestantes = (int)$diff->format('%r%a');

        // Incluir si: está vencido (días negativos), vence hoy (0), o vence dentro del límite
        if ($diasRestantes <= $diasLimite) {
            $proveedoresFiltrados[] = $prov;
        }
    } catch (Exception $e) {
        // Fecha inválida, ignorar este proveedor
        continue;
    }
}

if (empty($proveedoresFiltrados)) {
    if (php_sapi_name() === 'cli') {
        echo "No hay proveedores con vencimiento en los próximos {$diasLimite} días.\n";
    } else {
        echo "No hay proveedores con vencimiento en los próximos {$diasLimite} días.";
    }
    exit(0);
}

// ─── Enviar correo al administrador ──────────────────────────────
try {
    $emailService = new \App\Services\EmailService();
    $resultado = $emailService->notificarProveedoresVencimiento($proveedoresFiltrados, $diasLimite);

    if ($resultado) {
        $msg = "Alerta enviada correctamente. " . count($proveedoresFiltrados) . " proveedor(es) notificado(s).";
        if (php_sapi_name() === 'cli') {
            echo $msg . "\n";
        } else {
            echo $msg;
        }
        exit(0);
    } else {
        $msg = "Error al enviar el correo de alerta.";
        if (php_sapi_name() === 'cli') {
            echo $msg . "\n";
        } else {
            http_response_code(500);
            echo $msg;
        }
        exit(1);
    }
} catch (Exception $e) {
    error_log("notificar_proveedores.php: Error al enviar correo: " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo "Error al enviar correo";
    }
    exit(1);
}