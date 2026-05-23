<?php
/**
 * PUNTO DE ENTRADA ÚNICO (Front Controller)
 */

// 1. Iniciar la sesión (Fundamental para AuthGuard y RoleGuard)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar Token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1.1 Cabeceras de Seguridad HTTP
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// 2. Cargar el archivo de configuración y constantes
// Subimos un nivel para llegar a la carpeta de lógica interna
require_once __DIR__ . '/../app/Config/config.php';

// 3. Cargar los Helpers (Funciones globales)
require_once __DIR__ . '/../app/Helpers/helpers.php';

// 4. Autoload de Composer (Si usas librerías externas como Dotenv o Dompdf)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';

    // Inicializar variables de entorno
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();
    }
}

// 5. Autoload de clases del sistema (Core, Controllers, Models, etc.)
// Esto evita tener que hacer cientos de 'require' manuales
spl_autoload_register(function($nombreClase) {
    // Definimos las carpetas donde buscaremos las clases
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

/**
 * 6. Inicializar la Aplicación (El Enrutador)
 * Esta línea lee la URL y carga el controlador correspondiente.
 */
try {
    $init = new App();
} catch (Throwable $e) {
    // Capturamos Throwable para atrapar tanto Errors como Exceptions (PHP 7+)
    error_log("Error crítico: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    
    // Detectar si la petición espera JSON (AJAX / Fetch)
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
              (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => (defined('ENVIRONMENT') && ENVIRONMENT === 'development') ? $e->getMessage() : 'Ocurrió un error interno en el servidor.'
        ]);
        exit;
    }

    // Si estamos en desarrollo, mostramos el error detallado
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        echo "<div style='background:#fee2e2; border:1px solid #ef4444; padding:20px; border-radius:10px; font-family:sans-serif; margin:20px;'>";
        echo "<h1 style='color:#b91c1c; margin-top:0;'>⚠️ Error Crítico de Aplicación</h1>";
        echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
        echo "<hr style='border:0; border-top:1px solid #fca5a5; margin:15px 0;'>";
        echo "<p><strong>Trace:</strong></p>";
        echo "<pre style='background:#fff; padding:10px; border-radius:5px; border:1px solid #fca5a5; overflow:auto; font-size:12px;'>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        echo "Lo sentimos, ha ocurrido un error interno.";
    }
}
