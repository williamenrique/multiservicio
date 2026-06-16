<?php

/**
 * Encripta una cadena de texto usando AES-256-CBC
 */
function encryption($string) {
    if (empty($string)) return '';
    
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', SECRET_IV), 0, 16);
    $output = openssl_encrypt($string, METHOD, $key, 0, $iv);
    return $output ? base64_encode($output) : '';
}

/**
 * Desencripta una cadena previamente encriptada
 */
function decryption($string) {
    if (empty($string)) return '';
    
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', SECRET_IV), 0, 16);
    $output = openssl_decrypt(base64_decode($string), METHOD, $key, 0, $iv);
    return $output ?: '';
}

/**
 * Redirecciona a una página específica dentro del sistema
 */
function redirect($page) {
    header('location: ' . URLROOT . '/' . $page);
    exit();
}

/**
 * Sanitiza cadenas de texto para evitar ataques XSS al imprimir en HTML
 * @param string $html String a sanear.
 * @return string String saneado.
 */
function s(?string $html): string {
    return htmlspecialchars($html ?? '');
}

/**
 * Genera un token CSRF único para la sesión del usuario
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Función centralizada para renderizar vistas con layout (header/footer) opcional.
 */
function renderView($view, $data = []) {
    $viewPath = APPROOT . '/Views/' . $view . '.php';
    
    if (file_exists($viewPath)) {
        extract($data);

        // Obtener configuración de empresa globalmente
        try {
            $db = new Database();
            $db->query("SELECT * FROM table_company_settings WHERE id = 1");
            $company = $db->single();
        } catch (Throwable $e) { 
            $company = (object) ['name' => 'TALLER PRO']; 
        }

        // Determinar si usar el layout del dashboard
        // Las vistas de login, errores o vistas públicas no deben cargar header/footer
        $useLayout = (strpos($view, 'auth/') === false && strpos($view, 'errores/') === false && strpos($view, 'public/') === false);

        if ($useLayout && file_exists(APPROOT . '/Views/inc/header.php')) {
            require_once APPROOT . '/Views/inc/header.php';
        }

        require_once $viewPath;

        if ($useLayout && file_exists(APPROOT . '/Views/inc/footer.php')) {
            require_once APPROOT . '/Views/inc/footer.php';
        }
    } else {
        throw new AppException("Error: La vista '{$view}' no existe.", 404);
    }
}

/**
 * Registra una acción en la bitácora de auditoría.
 * @param string $modulo Nombre del módulo (AUTH, PERSONAL, INVENTARIO, etc.)
 * @param string $accion Acción realizada (LOGIN, CREATE, UPDATE, DELETE)
 * @param string $descripcion Detalle textual de lo que se hizo
 */
function logAction($modulo, $accion, $descripcion = '') {
    $db = new Database();
    $db->query("INSERT INTO table_audit_logs (usuario_id, modulo, accion, descripcion, ip_address, fecha) 
                VALUES (:uid, :mod, :acc, :des, :ip, NOW())");
    $db->bind(':uid', $_SESSION['user_id'] ?? null);
    $db->bind(':mod', mb_strtoupper($modulo, 'UTF-8'));
    $db->bind(':acc', mb_strtoupper($accion, 'UTF-8'));
    $db->bind(':des', $descripcion);
    $db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    return $db->execute();
}