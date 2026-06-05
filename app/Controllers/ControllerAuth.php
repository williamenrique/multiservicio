<?php
/**
 * Controlador encargado de la autenticación de usuarios.
 * Maneja el inicio de sesión, cierre de sesión y control de sesiones activas.
 */
class ControllerAuth extends Controller {

    private $userModel;

    /**
     * Constructor: Inicializa el modelo de Usuario.
     * Según Controller.php, esto carga app/Models/ModelUsuario.php
     */
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
        $this->view('auth/login', ['titulo' => 'Iniciar Sesión']);
    }

    /**
     * Procesa la petición AJAX de inicio de sesión.
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
            // El flag 'force' indica si el usuario decidió cerrar la sesión activa previa
            $force = isset($input['force']) ? (bool)$input['force'] : false;

            // Busca al usuario por Email o por Nick (username)
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

                    // Control de sesión única: Verificar si ya hay un registro en la BD
                    $sesionActiva = $this->userModel->obtenerSesionActiva($userFound->id);

                    if ($sesionActiva && !$force) {
                        // Si hay sesión y no se forzó, enviamos el flag session_exists
                        return $this->jsonResponse([
                            'success' => false, 
                            'session_exists' => true, 
                            'error' => 'Ya tienes una sesión abierta en otro dispositivo.'
                        ]);
                    }

                    // Credenciales válidas: Definir variables de sesión de PHP
                    $_SESSION['user_id'] = $userFound->id;
                    $_SESSION['user_nick'] = $userFound->username;
                    $_SESSION['user_email'] = $userFound->email;
                    $_SESSION['user_nombre'] = $userFound->nombre;
                    $_SESSION['user_role'] = $userFound->nombre_rol ?? 'Sin Rol'; // Mantenemos formato original para UI
                    $_SESSION['user_role_id'] = (int)$userFound->role_id;
                    $_SESSION['user_staff_id'] = $userFound->staff_id ?? null;
                    $_SESSION['user_foto'] = $userFound->foto;

                    // Actualizar o crear el registro de sesión única en la base de datos
                    $this->userModel->registrarSesion([
                        'session_id' => session_id(),
                        'usuario_id' => $userFound->id,
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                        'usuario_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido'
                    ]);

                    // Auditoría de inicio de sesión
                    logAction('AUTH', 'LOGIN', "El usuario {$userFound->username} ha ingresado al sistema.");

                    return $this->jsonResponse(['success' => true, 'redirect' => URLROOT . '/dashboard']);
                } else {
                    return $this->jsonResponse(['success' => false, 'error' => 'Contraseña incorrecta.'], 401);
                }
            } else {
                return $this->jsonResponse(['success' => false, 'error' => 'Usuario no encontrado o inactivo.'], 404);
            }
        } else {
            // Si se intenta entrar a /auth/login por GET, lo mandamos al index
            redirect('auth');
        }
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
        // Asegurarse de que la sesión esté iniciada antes de destruirla
        // Aunque public/index.php ya llama a session_start(), es buena práctica
        // verificarlo si este método pudiera ser llamado de forma aislada.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Limpiar el registro de sesión de la base de datos al salir
        if (isset($_SESSION['user_id'])) {
            // Auditoría de cierre de sesión
            logAction('AUTH', 'LOGOUT', "El usuario {$_SESSION['user_nick']} ha cerrado su sesión.");
            $this->userModel->eliminarSesiones($_SESSION['user_id']);
        }

        // Destruir todas las variables de sesión
        $_SESSION = array();

        // Si se desea destruir la cookie de sesión, también es necesario eliminar
        // la cookie de sesión. Nota: Esto destruirá la sesión, y no solo los datos de la sesión.
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }

        // Finalmente, destruir la sesión
        session_destroy();

        // Redirigir al usuario a la página de inicio de sesión
        redirect('auth');
    }

    /**
     * Procesa la solicitud de recuperación enviada desde el modal del login.
     */
    public function solicitarRecuperacion() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $identificador = isset($input['identificador']) ? trim($input['identificador']) : '';

            // Buscamos si el usuario existe por Email, Nick o Cédula
            // Reutilizamos buscarPorIdentificador pero podrías ampliarlo en el modelo si CI no está incluido
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