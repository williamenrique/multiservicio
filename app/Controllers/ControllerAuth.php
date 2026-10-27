<?php
/**
 * Controlador encargado de la autenticación de usuarios.
 * Maneja el inicio de sesión, cierre de sesión y control de sesiones activas.
 * 
 * v2.1 (2026-10-09) — P3-14:
 *   • FIX case-sensitive: `index()` ahora usa `$this->view('auth/Login', ...)`
 *     (con L mayúscula) para coincidir con el archivo real `Views/auth/Login.php`.
 *     En Windows funcionaba por case-insensitive, pero en Linux fallaba con
 *     "La vista 'auth/login' no existe".
 *   • Nota: el resto de vistas del sistema usan minúsculas. Si en el futuro
 *     se renombra `Login.php` a `login.php`, revertir este cambio.
 */
class ControllerAuth extends Controller {

    private $userModel;

    public function __construct() {
        $this->userModel = $this->model('Usuario');
    }

    /**
     * Muestra la vista de login. Si ya hay sesión, redirige al dashboard.
     */
    public function index() {
        if (isset($_SESSION['user_id'])) {
            redirect('dashboard');
        }
        $this->view('auth/Login', ['titulo' => 'Iniciar Sesión']);
    }

    /**
     * Procesa la petición AJAX de inicio de sesión.
     *
     * v2.2 (2026-10-26) — PERFORMANCE:
     *   • Se movieron NotificationChecker::verificarYNotificar() a un
     *     shutdown function registrada ANTES de responder. Así la respuesta
     *     HTTP al navegador se envía de inmediato y los envíos SMTP
     *     (proveedores + resumen mensual) corren en segundo plano dentro
     *     del mismo request, sin bloquear el login.
     *   • Si el servidor es php-fpm, además se cierra la conexión con
     *     fastcgi_finish_request() para liberar al navegador antes aún.
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $input = json_decode(file_get_contents('php://input'), true);

            // 1. Validación de Campos (CSRF ahora se maneja en index.php)
            $v = new Validator($input);
            $v->required(['usuario', 'password']);

            if (!$v->success()) {
                return $this->jsonResponse(['success' => false, 'errors' => $v->getErrors()], 400);
            }

            // Limpiamos entradas de posibles espacios accidentales
            $usuarioInput = trim($input['usuario']);
            $password = trim($input['password']);
            $force = isset($input['force']) ? (bool)$input['force'] : false;

            $userFound = $this->userModel->buscarPorIdentificador($usuarioInput);

            if ($userFound) {
                $isPlainTextMatch = ($password === $userFound->password);
                $isHashMatch = password_verify($password, $userFound->password);

                if ($isHashMatch || $isPlainTextMatch) {

                    // AUTO-MIGRACIÓN: Si entró con texto plano, lo hasheamos ahora mismo
                    if ($isPlainTextMatch && !$isHashMatch) {
                        $newHash = password_hash($password, PASSWORD_BCRYPT);
                        $this->userModel->actualizarPassword($userFound->id, $newHash);
                    }

                    $tipoCliente = detectarTipoCliente();
                    $sesionActiva = $this->userModel->obtenerSesionActiva($userFound->id, $tipoCliente);

                    if ($sesionActiva && !$force) {
                        return $this->jsonResponse([
                            'success' => false,
                            'session_exists' => true,
                            'error' => 'Ya tienes una sesión abierta en otro dispositivo (' . $tipoCliente . ').'
                        ]);
                    }

                    // Credenciales válidas: Definir variables de sesión de PHP
                    $_SESSION['user_id'] = $userFound->id;
                    $_SESSION['user_nick'] = $userFound->username;
                    $_SESSION['user_email'] = $userFound->email;
                    $_SESSION['user_nombre'] = $userFound->nombre;
                    $_SESSION['user_role'] = $userFound->nombre_rol ?? 'Sin Rol';
                    $_SESSION['user_role_id'] = (int)$userFound->role_id;
                    $_SESSION['user_staff_id'] = $userFound->staff_id ?? null;
                    $_SESSION['user_foto'] = $userFound->foto;
                    $_SESSION['tipo_cliente'] = $tipoCliente;

                    $this->userModel->registrarSesion([
                        'session_id' => session_id(),
                        'usuario_id' => $userFound->id,
                        'tipo' => $tipoCliente,
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                        'usuario_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido'
                    ]);

                    logAction('AUTH', 'LOGIN', "El usuario {$userFound->username} ha ingresado al sistema.");

                    // ─── PERFORMANCE FIX ─────────────────────────────
                    // Encolar notificaciones en segundo plano (post-response).
                    // NO se ejecutan antes de responder al navegador.
                    $this->_encolarNotificacionesPostLogin();

                    return $this->jsonResponse(['success' => true, 'redirect' => URLROOT . '/dashboard']);
                } else {
                    return $this->jsonResponse(['success' => false, 'error' => 'Contraseña incorrecta.'], 401);
                }
            } else {
                return $this->jsonResponse(['success' => false, 'error' => 'Usuario no encontrado o inactivo.'], 404);
            }
        } else {
            redirect('auth');
        }
    }

    /**
     * Ejecuta NotificationChecker después de que la respuesta HTTP ya
     * fue enviada al cliente. Usa `register_shutdown_function` y, si
     * está disponible, `fastcgi_finish_request()` para liberar la
     * conexión con el navegador lo antes posible.
     *
     * @return void
     */
    private function _encolarNotificacionesPostLogin(): void {
        register_shutdown_function(function () {
            // Liberar al navegador si estamos en php-fpm
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            // Dar un pequeño margen para que el cliente reciba el JSON
            // (en Apache/mod_php no hay fastcgi_finish_request, así que
            // este sleep evita que el browser quede esperando mientras
            // PHPMailer hace su handshake SMTP).
            if (!function_exists('fastcgi_finish_request')) {
                // Flush manual del buffer al cliente.
                while (ob_get_level() > 0) { @ob_end_flush(); }
                @flush();
            }

            try {
                NotificationChecker::verificarYNotificar();
            } catch (\Throwable $e) {
                error_log('PostLogin notificaciones fallaron: ' . $e->getMessage());
            }
        });
    }

    /**
     * Retorna los datos de la sesión actual para el frontend
     */
    public function getLoggedInUser() {
        if (isset($_SESSION['user_id'])) {
            return $this->jsonResponse([
                'success' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'staffId' => $_SESSION['user_staff_id'] ?? null,
                    'username' => $_SESSION['user_nick'],
                    'staffName' => $_SESSION['user_nombre'],
                    'role' => $_SESSION['user_role'],
                    'roleId' => $_SESSION['user_role_id'] ?? null,
                    'foto' => $_SESSION['user_foto'] ?? 'img/default.png'
                ]
            ]);
        } else {
            return $this->jsonResponse(['success' => false]);
        }
    }

    /**
     * Cierra la sesión del usuario y lo redirige a la página de inicio de sesión.
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            logAction('AUTH', 'LOGOUT', "El usuario {$_SESSION['user_nick']} ha cerrado su sesión.");
            $this->userModel->eliminarSesiones($_SESSION['user_id']);
        }

        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }

        session_destroy();

        redirect('auth');
    }

    /**
     * Procesa la solicitud de recuperación enviada desde el modal del login.
     */
    public function solicitarRecuperacion() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $identificador = isset($input['identificador']) ? trim($input['identificador']) : '';

            $userFound = $this->userModel->buscarPorIdentificador($identificador);

            if ($userFound) {
                $res = $this->userModel->registrarSolicitudRecuperacion($userFound->id);
                return $this->jsonResponse($res 
                    ? ['success' => true, 'mensaje' => 'Solicitud enviada al administrador.'] 
                    : ['success' => false, 'error' => 'No se pudo procesar la solicitud.']);
            } else {
                return $this->jsonResponse(['success' => false, 'error' => 'No se encontró ningún usuario con esos datos.']);
            }
            exit();
        }
    }

    /**
     * Retorna las solicitudes para la campana del administrador.
     */
    public function getSolicitudes() {
        if (RoleGuard::is_admin_check()) {
            $solicitudes = $this->userModel->obtenerSolicitudesPendientes();
            return $this->jsonResponse(['success' => true, 'data' => $solicitudes]);
        } else {
            return $this->jsonResponse(['success' => false], 403);
        }
    }

    /**
     * Muestra la vista de gestión de solicitudes de recuperación (Solo Admin).
     */
    public function solicitudes() {
        RoleGuard::isAdmin();
        $data = [
            'titulo' => 'Solicitudes de Acceso'
        ];
        $this->view('recuperar/index', $data);
    }

    /**
     * Permite al administrador resetear la clave de un usuario desde la solicitud.
     */
    public function resetearClaveAdmin() {
        RoleGuard::isAdmin();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = (int)$input['user_id'];
            $newPass = trim($input['password']);

            if (empty($newPass)) {
                return $this->jsonResponse(['success' => false, 'error' => 'La clave no puede estar vacía.']);
            }

            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $res = $this->userModel->actualizarPassword($userId, $hash);

            return $this->jsonResponse(['success' => $res]);
        }
    }

    public function eliminarSolicitud($id) {
        RoleGuard::isAdmin();
        $res = $this->userModel->eliminarSolicitud($id);
        return $this->jsonResponse(['success' => $res]);
    }
}