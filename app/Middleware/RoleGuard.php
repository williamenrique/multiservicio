
<?php
/**
 * Middleware de Autorización por Roles
 * Controla qué niveles de usuario pueden ejecutar ciertas acciones
 */
class RoleGuard {

    // Constantes de Roles (Basado en la base de datos)
    const ADMINISTRADOR = 1;
    const MECANICO = 2;
    const EMPLEADO = 3;

    /**
     * Permite el acceso solo si el usuario tiene uno de los roles permitidos
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

        // 3. Verificamos por nombre (case-insensitive) o por ID
        $userRole = strtoupper($_SESSION['user_role']);
        $userRoleId = (int)$_SESSION['user_role_id'];

        if (!in_array($userRole, array_map('strtoupper', $allowedRoles)) && !in_array($userRoleId, $allowedRoles)) {
            // Si no tiene permiso, lo mandamos a una página de "Acceso Denegado" o Dashboard
            header('location: ' . URLROOT . '/dashboard?error=sin_permiso');
            exit();
        }
    }

    /**
     * Atajo rápido para verificar solo administradores
     */
    public static function isAdmin() {
        self::hasAccess([self::ADMINISTRADOR, 'ADMINISTRADOR']);
    }

    /**
     * Retorna verdadero si el usuario tiene privilegios administrativos.
     * Útil para filtrar consultas SQL en los controladores.
     */
    public static function is_admin_check() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $roleId = isset($_SESSION['user_role_id']) ? (int)$_SESSION['user_role_id'] : 0;
        $roleName = isset($_SESSION['user_role']) ? strtoupper($_SESSION['user_role']) : '';

        // Retorna true si es ID 1 o si el nombre es ADMINISTRADOR (sin importar mayúsculas en la sesión)
        return ($roleId === self::ADMINISTRADOR || $roleName === 'ADMINISTRADOR');
    }
}