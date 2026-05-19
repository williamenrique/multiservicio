<?php
/**
 * HELPERS DEL SISTEMA DE TALLER
 * Funciones globales para formateo, seguridad, renderizado y utilidad.
 */

/**
 * Renderiza una vista aislando la interfaz de Login de los layouts internos del taller
 * @param string $view Nombre de la vista (ej: 'dashboard/index')
 * @param array $data Datos dinámicos para pasar a la plantilla
 */
function renderView($view, $data = []) {
    // Cargar información de la empresa de forma global (Patrón Singleton en el helper)
    static $companyInfo = null;
    if ($companyInfo === null) {
        try {
            $db = new Database();
            $db->query("SELECT * FROM table_company_settings WHERE id = 1");
            $companyInfo = $db->single();
        } catch (Throwable $e) {
            // Valores por defecto en caso de error de conexión inicial
            $companyInfo = (object) ['name' => 'TALLER PRO', 'iva' => 0, 'nit' => '0000000000'];
        }
    }

    // Inyectamos el objeto $company para que esté disponible en el header, footer y vistas
    $data['company'] = $companyInfo;

    // Convertimos las llaves del array en variables independientes (ej: $data['titulo'] -> $titulo)
    extract($data);

    $header = APPROOT . '/Views/inc/header.php';
    $footer = APPROOT . '/Views/inc/footer.php';
    $viewFile = APPROOT . '/Views/' . $view . '.php';

    // Excepción de seguridad: Si la vista es la de login, se renderiza de forma aislada
    if ($view === 'auth/login') {
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("Error Crítico: La vista de Login no existe.");
        }
        return;
    }

    // Layout estructurado para las secciones privadas y protegidas de la aplicación
    if (file_exists($header)) {
        require_once $header;
    }

    if (file_exists($viewFile)) {
        require_once $viewFile;
    } else {
        die("Error Crítico: La vista '{$view}' no existe.");
    }

    if (file_exists($footer)) {
        require_once $footer;
    }
}

/**
 * Redirección rápida de páginas utilizando la URL base del sistema
 */
function redirect($page) {
    header('location: ' . URLROOT . '/' . $page);
    exit();
}

/**
 * Limpieza de datos contra ataques de Inyección XSS (Cross-Site Scripting)
 */
function s($data) {
    if (is_null($data)) return '';
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Formatear valores numéricos a la estructura contable de dinero
 */
function formatMoney($number) {
    return '$' . number_format((float)$number, 2, '.', ',');
}

/**
 * Formatear fechas para el historial y listas del taller
 */
function formatDate($date) {
    if (empty($date)) return 'N/A';
    return date('d M, Y', strtotime($date));
}

/**
 * Generar etiquetas HTML (Badges) según el estado actual de las Órdenes de Servicio
 */
function statusBadge($status) {
    $status = strtolower($status);
    $badges = [
        'pendiente' => '<span class="badge bg-warning text-dark">Pendiente</span>',
        'proceso'   => '<span class="badge bg-info text-white">En Proceso</span>',
        'terminado' => '<span class="badge bg-success text-white">Terminado</span>',
        'entregado' => '<span class="badge bg-primary text-white">Entregado</span>',
        'cancelado' => '<span class="badge bg-danger text-white">Cancelado</span>'
    ];

    return $badges[$status] ?? '<span class="badge bg-secondary">' . $s($status) . '</span>';
}

/**
 * Generar tokens CSRF para formularios (Seguridad contra falsificación de peticiones)
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar rápidamente si el usuario en sesión cuenta con un rol determinado
 */
function isRole($role) {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role);
}

/**
 * Debug rápido para desarrollo (Inspecciona variables y detiene el flujo)
 */
function dd($data) {
    echo '<pre style="background: #111; color: #0f0; padding: 20px; border-radius: 5px; font-family: monospace;">';
    print_r($data);
    echo '</pre>';
    die();
}
