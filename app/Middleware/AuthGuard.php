<?php
/**
 * Middleware de Autenticación
 * Se encarga de proteger las rutas privadas del taller.
 * 
 * v2.0 (2026-10-09) — FIX P2-12:
 *   • Ventana de gracia de 5 minutos para session_id regenerado por PHP.
 *   • Antes: cualquier desajuste entre session_id() y el registrado en BD
 *     destruía la sesión. Esto ocurría legítimamente cuando PHP regeneraba
 *     el session_id (session_regenerate_id, gc, etc.), expulsando al usuario
 *     aunque siguiera activo.
 *   • Ahora: si el session_id NO coincide pero:
 *       - `last_activity` fue hace ≤ 5 min, Y
 *       - la IP coincide con la registrada, Y
 *       - el User-Agent coincide con el registrado,
 *     entonces se asume que es el mismo navegador tras una regeneración
 *     del session_id y se sincroniza el registro en BD (UPDATE session_id).
 *   • Se exige IP + UA para evitar ping-pong entre dos navegadores
 *     distintos con la misma cuenta cuando alguien hace `force login`:
 *     el primer navegador se cierra correctamente en lugar de rebotar.
 *   • Se eliminó el chequeo de `tipo_cliente` duplicado: ya se resuelve
 *     antes de la query.
 */
class AuthGuard {

    /**
     * Ventana de gracia (en segundos) para aceptar un session_id regenerado.
     * Solo aplica si IP + User-Agent coinciden con lo registrado en BD.
     */
    const SESSION_GRACE_SECONDS = 300; // 5 minutos

    /**
     * Verifica si el usuario tiene una sesión activa válida.
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

        // Validación de sesión por plataforma (WEB/APP) contra Base de Datos
        $tipoCliente = $_SESSION['tipo_cliente'] ?? detectarTipoCliente();
        $userId = (int)$_SESSION['user_id'];
        $currentSessionId = session_id();
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

        try {
            $db = new Database();
            $db->query("SELECT session_id, ip_address, usuario_agent, last_activity 
                        FROM table_usuario_sessions 
                        WHERE usuario_id = :uid AND tipo = :tipo");
            $db->bind(':uid', $userId);
            $db->bind(':tipo', $tipoCliente);
            $registro = $db->single();
        } catch (Throwable $e) {
            // Si falla la DB, por seguridad cerramos sesión
            self::_destruirSesion();
            redirect('auth?error=session_replaced');
        }

        // Caso 1: No existe registro en BD → sesión huérfana. Destruir.
        if (!$registro) {
            self::_destruirSesion();
            redirect('auth?error=session_replaced');
        }

        // Caso 2: session_id coincide → OK (camino normal)
        if ($registro->session_id === $currentSessionId) {
            return;
        }

        // Caso 3: session_id NO coincide. Evaluar ventana de gracia.
        $puedeRecuperar = self::_dentroDeVentanaDeGracia($registro, $currentIp, $currentUa);

        if ($puedeRecuperar) {
            // Sincronizar el nuevo session_id en BD para no repetir el proceso
            try {
                $db->query("UPDATE table_usuario_sessions 
                            SET session_id = :sid, ip_address = :ip, usuario_agent = :ua 
                            WHERE usuario_id = :uid AND tipo = :tipo");
                $db->bind(':sid', $currentSessionId);
                $db->bind(':ip', $currentIp);
                $db->bind(':ua', $currentUa);
                $db->bind(':uid', $userId);
                $db->bind(':tipo', $tipoCliente);
                $db->execute();
            } catch (Throwable $e) {
                // Si no se puede sincronizar, igualmente dejamos pasar el request
                // actual para no expulsar al usuario; en el próximo intento se
                // re-evaluará la ventana de gracia.
                error_log('AuthGuard: no se pudo sincronizar session_id: ' . $e->getMessage());
            }
            return;
        }

        // Caso 4: No se puede recuperar → sesión reemplazada en otro dispositivo.
        self::_destruirSesion();
        redirect('auth?error=session_replaced');
    }

    /**
     * Determina si el registro de sesión cumple con la ventana de gracia.
     * Requisitos:
     *   1. `last_activity` fue hace ≤ SESSION_GRACE_SECONDS.
     *   2. IP registrada === IP actual.
     *   3. User-Agent registrado === User-Agent actual.
     * 
     * @param object $registro Fila de table_usuario_sessions
     * @param string $currentIp
     * @param string $currentUa
     * @return bool
     */
    private static function _dentroDeVentanaDeGracia($registro, $currentIp, $currentUa) {
        // 1. Tiempo desde la última actividad
        $lastActivityTs = strtotime($registro->last_activity ?? '');
        if (!$lastActivityTs) {
            return false;
        }
        $segundosTranscurridos = time() - $lastActivityTs;
        if ($segundosTranscurridos < 0 || $segundosTranscurridos > self::SESSION_GRACE_SECONDS) {
            return false;
        }

        // 2. IP coincide (comparación estricta)
        $ipRegistrada = (string)($registro->ip_address ?? '');
        if ($ipRegistrada !== '' && $ipRegistrada !== $currentIp) {
            return false;
        }

        // 3. User-Agent coincide
        $uaRegistrado = (string)($registro->usuario_agent ?? '');
        if ($uaRegistrado !== '' && $uaRegistrado !== $currentUa) {
            return false;
        }

        return true;
    }

    /**
     * Destruye la sesión actual (PHP + cookie).
     */
    private static function _destruirSesion() {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
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