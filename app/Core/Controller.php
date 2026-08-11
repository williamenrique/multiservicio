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
        
        // Intentar con "Models" (PascalCase)
        $path = APPROOT . '/Models/' . $modelName . '.php';
        
        // Si no existe, intentar con "models" (lowercase) por si acaso el servidor está configurado así
        if (!file_exists($path)) {
            $path = APPROOT . '/models/' . $modelName . '.php';
        }

        // Fallback adicional: Intentar con el nombre del archivo totalmente en minúsculas
        if (!file_exists($path)) {
            $modelLower = strtolower($modelName);
            $path = APPROOT . '/Models/' . $modelLower . '.php';
            if (!file_exists($path)) {
                $path = APPROOT . '/models/' . $modelLower . '.php';
            }
        }

        if (file_exists($path)) {
            require_once $path;
            return new $modelName();
        } else {
            // Error crítico si el modelo no existe
            throw new AppException("El modelo de datos '{$modelName}' no pudo ser cargado.", 500);
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
        // Inyectar automáticamente datos globales de sesión para evitar inconsistencias en las vistas.
        // Esto garantiza que $data['user_role'] esté disponible siempre sin esfuerzo extra en los controladores.
        if (session_status() !== PHP_SESSION_NONE && isset($_SESSION['user_id'])) {
            $data['user_role'] = $data['user_role'] ?? ($_SESSION['user_role'] ?? 'INVITADO');
            $data['user_role_id'] = $data['user_role_id'] ?? ($_SESSION['user_role_id'] ?? 0);
            $data['csrf_token'] = $_SESSION['csrf_token'] ?? '';
        }

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
                if (!isset($company)) {
                    try {
                        $db = new Database();
                        $db->query("SELECT * FROM table_company_settings WHERE id = 1");
                        $company = $db->single();
                    } catch (Throwable $e) { $company = (object) ['name' => 'TALLER']; }
                }

                // Determinamos si la vista requiere el layout del dashboard (header/footer)
                // Las vistas de login, errores o públicas deben ser independientes
                $useLayout = (strpos($view, 'auth/') === false && strpos($view, 'errores/') === false && strpos($view, 'public/') === false);

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
                throw new AppException("La vista '{$view}' no existe.", 404);
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
