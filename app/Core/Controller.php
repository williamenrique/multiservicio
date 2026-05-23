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
        // Forzamos PascalCase para compatibilidad con servidores Linux
        $modelName = 'Model' . ucfirst($model);
        $path = APPROOT . '/Models/' . $modelName . '.php';

        if (file_exists($path)) {
            require_once $path;
            return new $modelName();
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
                
                // Cargar empresa si no existe (fallback)
                if (isset($_SESSION['company_settings'])) {
                    $company = $_SESSION['company_settings'];
                } else {
                    try {
                        $db = new Database();
                        $db->query("SELECT * FROM table_company_settings WHERE id = 1");
                        $company = $db->single();
                        $_SESSION['company_settings'] = $company;
                    } catch (Throwable $e) { 
                        $company = (object) [
                            'name' => 'TALLER PRO',
                            'iva' => 0,
                            'logo' => ''
                        ]; 
                    }
                }

                // Aseguramos que extra_scripts siempre sea un array
                $extra_scripts = $extra_scripts ?? [];

                // Determinamos si la vista requiere el layout del dashboard (header/footer)
                // Las vistas de login o errores deben ser independientes
                $useLayout = (strpos($view, 'auth/') === false && strpos($view, 'errores/') === false);

                // Intentamos cargar el header
                if ($useLayout && file_exists(APPROOT . '/Views/inc/header.php')) {
                    require_once APPROOT . '/Views/inc/header.php';
                }

                // Cargamos la vista principal
                require_once $viewPath;

                // Intentamos cargar el footer
                if ($useLayout && file_exists(APPROOT . '/Views/inc/footer.php')) {
                    require_once APPROOT . '/Views/inc/footer.php';
                }
            } else {
                // Mensaje de error mejorado para desarrollo
                die("
                <div style='background:#0f172a; color:#f87171; padding:30px; border:1px solid #ef4444; border-radius:15px; font-family:sans-serif; max-width:600px; margin:50px auto; shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);'>
                    <h2 style='color:#ef4444; margin-top:0;'>⚠️ Error Crítico de Renderizado</h2>
                    <p style='color:#94a3b8;'>El sistema no pudo encontrar la vista solicitada.</p>
                    <div style='background:#1e293b; padding:15px; border-radius:8px; font-family:monospace; font-size:14px; color:#fff;'>
                        <strong>Vista:</strong> {$view}<br>
                        <strong>Ruta:</strong> {$viewPath}
                    </div>
                    <p style='font-size:12px; color:#64748b; margin-top:20px;'>Verifica que el archivo exista en la carpeta app/Views y que el nombre coincida exactamente.</p>
                </div>");
            }
        }
    }

    /**
     * Envía una respuesta JSON pura y finaliza la ejecución.
     * Se eliminó el auto-merge de 'success' para evitar corromper arrays de datos (listas).
     */
    public function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}
