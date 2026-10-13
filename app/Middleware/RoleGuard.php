<?php
/**
 * Middleware de Autorización por Roles
 * Controla qué niveles de usuario pueden ejecutar ciertas acciones
 * 
 * v2.0 (2026-10-09) — FIX P2-11:
 *   • Comparación consistente: los IDs se comparan como int[] y los nombres
 *     como string[] (UPPERCASE). Antes se usaba array_map('strtoupper', ...)
 *     sobre el array mixto, que convertía IDs numéricos a strings y luego
 *     intentaba in_array() con int contra array con strings.
 *   • Acepta llamadas con: [1, 3], ['ADMINISTRADOR', 'CAJERO'], [1, 'MECANICO'].
 *   • Uso de in_array(..., true) para comparación estricta.
 */
class RoleGuard {

    // Constantes de Roles (Basado en la base de datos)
    const ADMINISTRADOR = 1;
    const MECANICO = 2;
    const CAJERO = 3;

    /**
     * Permite el acceso solo si el usuario tiene uno de los roles permitidos.
     * 
     * @param array $allowedRoles Lista mixta: IDs (int) o nombres (string).
     *                            Ejemplos válidos:
     *                              hasAccess([1, 3])
     *                              hasAccess(['ADMINISTRADOR', 'CAJERO'])
     *                              hasAccess([1, 'MECANICO'])
     * 
     * @return void  Si tiene acceso, retorna. Si no, redirige y termina.
     */
    public static function hasAccess($allowedRoles = []) {
        // 1. Verificamos que haya una sesión iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 2. Si no hay rol en la sesión, denegar
        if (!isset($_SESSION['user_role']) && !isset($_SESSION['user_role_id'])) {
            header('location: ' . URLROOT . '/auth/login');
            exit();
        }

        // 3. Normalizar entrada: si pasaron un solo valor, convertirlo a array
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }

        // 4. Separar en dos listas tipadas
        $allowedIds = [];
        $allowedNames = [];
        foreach ($allowedRoles as $r) {
            if (is_int($r)) {
                $allowedIds[] = $r;
            } elseif (is_string($r) && ctype_digit($r)) {
                // String numérico como "1" → ID
                $allowedIds[] = (int)$r;
            } elseif (is_string($r) && $r !== '') {
                // String no numérico → nombre de rol en UPPERCASE
                $allowedNames[] = strtoupper(trim($r));
            }
        }

        // 5. Obtener el rol del usuario desde la sesión
        $userRoleId = isset($_SESSION['user_role_id']) ? (int)$_SESSION['user_role_id'] : 0;
        $userRoleName = isset($_SESSION['user_role']) ? strtoupper(trim((string)$_SESSION['user_role'])) : '';

        // 6. Verificar coincidencia estricta contra cualquiera de las dos listas
        $hasIdMatch = in_array($userRoleId, $allowedIds, true);
        $hasNameMatch = $userRoleName !== '' && in_array($userRoleName, $allowedNames, true);

        if ($hasIdMatch || $hasNameMatch) {
            return; // Acceso permitido
        }

        // 7. Sin permiso → redirigir
        header('location: ' . URLROOT . '/dashboard?error=sin_permiso');
        exit();
    }

    /**
     * Atajo rápido para verificar solo administradores.
     * Mantiene compatibilidad con llamadas legacy.
     */
    public static function isAdmin() {
        self::hasAccess([self::ADMINISTRADOR, 'ADMINISTRADOR']);
    }

    /**
     * Retorna verdadero si el usuario tiene privilegios administrativos.
     * Útil para filtrar consultas SQL en los controladores.
     * 
     * @return bool
     */
    public static function is_admin_check() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $roleId = isset($_SESSION['user_role_id']) ? (int)$_SESSION['user_role_id'] : 0;
        $roleName = isset($_SESSION['user_role']) ? strtoupper((string)$_SESSION['user_role']) : '';

        // Retorna true si es ID 1 O si el nombre es ADMINISTRADOR
        return ($roleId === self::ADMINISTRADOR || $roleName === 'ADMINISTRADOR');
    }
}