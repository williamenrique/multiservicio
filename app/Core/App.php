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

        // 1. LÓGICA PARA EL CONTROLADOR
        if (isset($url[0])) {
            if (file_exists(APPROOT . '/Controllers/Controller' . ucwords($url[0]) . '.php')) {
                $this->controladorActual = 'Controller' . ucwords($url[0]);
                unset($url[0]);
            } else {
                // Si el controlador no existe, forzamos el controlador de Errores (404)
                $this->controladorActual = 'ControllerErrores';
            }
        }

        // Cargar el archivo del controlador requerido
        require_once APPROOT . '/Controllers/' . $this->controladorActual . '.php';
        $this->controladorActual = new $this->controladorActual;

        // 2. LÓGICA PARA EL MÉTODO
        if (isset($url[1])) {
            if (method_exists($this->controladorActual, $url[1])) {
                $this->metodoActual = $url[1];
                unset($url[1]);
            } else {
                // Si el método no existe en el controlador, manejamos el 404
            if (get_class($this->controladorActual) !== 'ControllerErrores') {
                $this->controladorActual = new ControllerErrores();
                    $this->metodoActual = 'index';
                }
            }
        }

        // 3. OBTENER PARÁMETROS RESTANTES
        $this->parametros = $url ? array_values($url) : [];

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
