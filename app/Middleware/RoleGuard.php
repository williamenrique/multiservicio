
<?php
/**
 * Middleware de Autorización por Roles
 * Controla qué niveles de usuario pueden ejecutar ciertas acciones
 */
class RoleGuard {

    /**
     * Permite el acceso solo si el usuario tiene uno de los roles permitidos
     * @param array $allowedRoles Ejemplo: ['admin', 'recepcion']
     */
    public static function hasAccess($allowedRoles = []) {
        // 1. Verificamos que haya una sesión iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 2. Si no hay rol en la sesión, denegar
        if (!isset($_SESSION['user_role'])) {
            header('location: ' . URLROOT . '/auth/login');
            exit();
        }

        // 3. Verificamos si el rol del usuario actual está en la lista de permitidos
        if (!in_array($_SESSION['user_role'], $allowedRoles)) {
            // Si no tiene permiso, lo mandamos a una página de "Acceso Denegado" o Dashboard
            header('location: ' . URLROOT . '/dashboard?error=sin_permiso');
            exit();
        }
    }

    /**
     * Atajo rápido para verificar solo administradores
     */
    public static function isAdmin() {
        self::hasAccess(['Administrador', 'ADMINISTRADOR']);
    }
}
/*
uso
// En app/Controllers/Personal.php
public function __construct() {
    AuthGuard::handle(); // Primero: ¿Estás logueado?
    RoleGuard::isAdmin(); // Segundo: ¿Eres el jefe?
}

// En app/Controllers/Taller.php
public function __construct() {
    AuthGuard::handle();
    // Aquí permitimos varios roles
    RoleGuard::hasAccess(['admin', 'recepcion', 'mecanico']);
}
*/