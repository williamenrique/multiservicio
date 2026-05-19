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

            $usuarioInput = isset($input['usuario']) ? trim($input['usuario']) : ''; 
            $password = isset($input['password']) ? trim($input['password']) : '';
            // El flag 'force' indica si el usuario decidió cerrar la sesión activa previa
            $force = isset($input['force']) ? (bool)$input['force'] : false;

            // Busca al usuario por Email o por Nick (username)
            $userFound = $this->userModel->buscarPorIdentificador($usuarioInput);

            if ($userFound) {
                // En el futuro cambiar a password_verify
                if (($userFound->password)) {

                    // Control de sesión única: Verificar si ya hay un registro en la BD
                    $sesionActiva = $this->userModel->obtenerSesionActiva($userFound->id);

                    if ($sesionActiva && !$force) {
                        // Si hay sesión y no se forzó, enviamos el flag session_exists
                        echo json_encode([
                            'success' => false, 
                            'session_exists' => true, 
                            'error' => 'Ya tienes una sesión abierta en otro dispositivo.'
                        ]);
                        exit();
                    }

                    // Credenciales válidas: Definir variables de sesión de PHP
                    $_SESSION['user_id'] = $userFound->id;
                    $_SESSION['user_nick'] = $userFound->username;
                    $_SESSION['user_email'] = $userFound->email;
                    $_SESSION['user_nombre'] = $userFound->nombre;
                    $_SESSION['user_role'] = $userFound->nombre_rol;
                    $_SESSION['user_staff_id'] = $userFound->staff_id ?? null;
                    $_SESSION['user_foto'] = $userFound->foto;

                    // Actualizar o crear el registro de sesión única en la base de datos
                    $this->userModel->registrarSesion([
                        'session_id' => session_id(),
                        'usuario_id' => $userFound->id,
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                        'usuario_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido'
                    ]);

                    echo json_encode(['success' => true, 'redirect' => URLROOT . '/dashboard']);
                    exit();
                } else {
                    echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta.']);
                    exit();
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'El correo electrónico no está registrado o el usuario está inactivo.']);
                exit();
            }
        } else {
            redirect('auth');
        }
    }

    /**
     * Retorna los datos de la sesión actual para el frontend
     */
    public function getLoggedInUser() {
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => true,
                'user' => [
                    'staffId' => $_SESSION['user_staff_id'] ?? null,
                    'username' => $_SESSION['user_nick'],
                    'staffName' => $_SESSION['user_nombre'],
                    'role' => $_SESSION['user_role'],
                    'foto' => $_SESSION['user_foto'] ?? 'img/default.png'
                ]
            ]);
        } else {
            echo json_encode(['success' => false]);
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
}
