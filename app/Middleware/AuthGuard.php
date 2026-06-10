<?php
/**
 * Middleware de Autenticación
 * Se encarga de proteger las rutas privadas del taller
 */
class AuthGuard {

    /**
     * Verifica si el usuario tiene una sesión activa.
     * Si no, lo redirige al login.
     */
    public static function handle() {
        // Iniciamos sesión si no ha sido iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si no existe el ID del usuario en la sesión, está intentando entrar ilegalmente
        if (!isset($_SESSION['user_id'])) {
            redirect('auth');
        }
 
        // Validación de sesión única contra Base de Datos
        try {
            $db = new Database();
            $db->query("SELECT session_id FROM table_usuario_sessions WHERE usuario_id = :uid");
            $db->bind(':uid', $_SESSION['user_id']);
            $registro = $db->single();
        } catch (Throwable $e) {
            // Si falla la DB, por seguridad cerramos sesión
            $registro = false;
        }

        // Si el registro no existe o el session_id no coincide con el actual de PHP
        if (!$registro || $registro->session_id !== session_id()) {
            // Destruir la sesión actual del navegador
            $_SESSION = array();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
            }
            session_destroy();
            redirect('auth?error=session_replaced');
        }
    }

    /**
     * Verifica si el usuario tiene un rol específico (ej: 'admin')
     * Útil para proteger la facturación o gestión de personal.
     */
    public static function role($roleRequired) {
        self::handle(); // Primero verificamos que esté logueado

        // Delegamos al RoleGuard para mantener consistencia
        RoleGuard::hasAccess([$roleRequired]);
    }
}
