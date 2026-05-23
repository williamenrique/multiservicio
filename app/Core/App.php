<?php
/*
 * Clase principal de la aplicación (Enrutador)
 * Mapea la URL (controlador/metodo/parametros)
 */
class App {
    protected $controladorActual = 'ControllerDashboard'; // Controlador por defecto al abrir la app
    protected $metodoActual = 'index';          // Método por defecto
    protected $parametros = [];                 // Parámetros de la URL

    public function __construct() {
        $url = $this->getUrl();
        $urlPath = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';

        // 1. Cargar el mapa de rutas configurables
        $manualRoutes = require_once APPROOT . '/Config/routes.php';

        // 2. BUSCAR EN RUTAS EXPLÍCITAS (Mapeo manual)
        if (array_key_exists($urlPath, $manualRoutes)) {
            $parts = explode('@', $manualRoutes[$urlPath]);
            $this->controladorActual = 'Controller' . ucwords($parts[0]);
            $this->metodoActual = $parts[1] ?? 'index';
            $this->parametros = [];
        } 
        // 3. FALLBACK: ENRUTAMIENTO AUTOMÁTICO (Convención)
        elseif (isset($url[0])) {
            if (file_exists(APPROOT . '/Controllers/Controller' . ucwords($url[0]) . '.php')) {
                $this->controladorActual = 'Controller' . ucwords($url[0]);
                unset($url[0]);
            } else {
                $this->controladorActual = 'ControllerErrores';
            }
        }

        // Cargar e instanciar el controlador final
        $archivo = APPROOT . '/Controllers/' . $this->controladorActual . '.php';
        if (!file_exists($archivo)) $this->controladorActual = 'ControllerErrores';

        require_once APPROOT . '/Controllers/' . $this->controladorActual . '.php';
        $this->controladorActual = new $this->controladorActual;

        // 4. LÓGICA PARA EL MÉTODO (Si no fue definido por ruta manual)
        if (isset($url[1]) && $this->metodoActual === 'index') {
            if (method_exists($this->controladorActual, $url[1])) {
                $this->metodoActual = $url[1];
                unset($url[1]);
            } else {
            if (get_class($this->controladorActual) !== 'ControllerErrores') {
                $this->controladorActual = new ControllerErrores();
                    $this->metodoActual = 'index';
                }
            }
        }

        // 5. OBTENER PARÁMETROS RESTANTES
        if (empty($this->parametros)) {
            $this->parametros = $url ? array_values($url) : [];
        }

        // 4. EJECUCIÓN
        // Llama al método del controlador con los parámetros correspondientes
        call_user_func_array([$this->controladorActual, $this->metodoActual], $this->parametros);
    }

    /**
     * Obtiene y sanitiza la URL enviada por el archivo .htaccess
     */
    public function getUrl() {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }
        return null;
    }
}
