<?php
/**
 * CLASE CONTROLADOR BASE
 * Todos los controladores del sistema extienden de esta clase.
 * Proporciona métodos para cargar modelos y renderizar vistas con layout incluido.
 */
class Controller {

    /**
     * Carga un modelo desde la carpeta app/Models
     * @param string $model Nombre del archivo del modelo (ej: 'Usuario')
     * @return object Instancia del modelo solicitado
     */
    public function model($model) {
        $path = APPROOT . '/Models/' . $model . '.php';

        if (file_exists($path)) {
            require_once $path;
            return new $model();
        } else {
            // Error crítico si el modelo no existe
            error_log("Error: El modelo {$model} no se encontró en {$path}");
            die("Error interno: El modelo de datos no pudo ser cargado.");
        }
    }

    /**
     * Renderiza una vista inyectando automáticamente el header y footer.
     * Utiliza la lógica centralizada en el helper para mantener las vistas limpias.
     * 
     * @param string $view Nombre de la vista (ej: 'taller/ordenes')
     * @param array $data Arreglo de datos dinámicos para la vista
     */
    public function view($view, $data = []) {
        // Usamos la función renderView definida en app/Helpers/helpers.php
        // Esto asegura que todas las páginas tengan la misma estructura (Layout)
        if (function_exists('renderView')) {
            renderView($view, $data);
        } else {
            // Fallback en caso de que el helper no esté cargado
            $viewPath = APPROOT . '/Views/' . $view . '.php';
            if (file_exists($viewPath)) {
                // Extraemos el array de datos para que las llaves sean variables ($titulo, etc.)
                extract($data);

                // Intentamos cargar el header
                if (file_exists(APPROOT . '/Views/inc/header.php')) {
                    require_once APPROOT . '/Views/inc/header.php';
                }

                // Cargamos la vista principal
                require_once $viewPath;

                // Intentamos cargar el footer
                if (file_exists(APPROOT . '/Views/inc/footer.php')) {
                    require_once APPROOT . '/Views/inc/footer.php';
                }
            } else {
                die("La vista solicitada no existe.");
            }
        }
    }
}
