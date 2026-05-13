<?php
/**
 * PUNTO DE ENTRADA ÚNICO (Front Controller)
 */

// 1. Iniciar la sesión (Fundamental para AuthGuard y RoleGuard)
session_start();

// 2. Cargar el archivo de configuración y constantes
// Subimos un nivel para llegar a la carpeta de lógica interna
require_once __DIR__ . '/../app/Config/config.php';

// 3. Cargar los Helpers (Funciones globales)
require_once __DIR__ . '/../app/Helpers/helpers.php';

// 4. Autoload de Composer (Si usas librerías externas como Dotenv o Dompdf)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// 5. Autoload de clases del sistema (Core, Controllers, Models, etc.)
// Esto evita tener que hacer cientos de 'require' manuales
spl_autoload_register(function($nombreClase) {
    // Definimos las carpetas donde buscaremos las clases
    $directorios = [
        APPROOT . '/Core/',
        APPROOT . '/Middleware/',
        APPROOT . '/Services/'
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
} catch (Exception $e) {
    // Si algo falla gravemente, capturamos el error
    // En producción, podrías cargar una vista de Error 500
    error_log("Error en la aplicación: " . $e->getMessage());
    echo "Lo sentimos, ha ocurrido un error interno.";
}

