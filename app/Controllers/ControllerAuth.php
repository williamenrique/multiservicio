<?php
class ControllerAuth extends Controller {

    private $userModel; // Declarar la propiedad antes de usarla
    public function __construct() {
        $this->userModel = $this->model('Usuario');
    }

    public function index() {
        if (isset($_SESSION['user_id'])) {
            redirect('dashboard');
        }
        $this->view('auth/login', ['titulo' => 'Iniciar Sesión']);
    }

    /**
     * Procesa la petición AJAX/Fetch proveniente de JavaScript
     */
    public function login() {
        // Aseguramos que solo responda a peticiones POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Recibir y decodificar el JSON enviado por Fetch API
            $input = json_decode(file_get_contents('php://input'), true);

            $email = isset($input['email']) ? trim($input['email']) : '';
            $password = isset($input['password']) ? trim($input['password']) : '';
            $force = isset($input['force']) ? (bool)$input['force'] : false;

            // Validar existencia del usuario
            $userFound = $this->userModel->buscarPorEmail($email);

            if ($userFound) {
                // Verificar contraseña hash
                //if (password_verify($password, $userFound->password)) {
                if (($userFound->password)) {

                    // Verificar si ya existe una sesión abierta
                    $sesionActiva = $this->userModel->obtenerSesionActiva($userFound->id);

                    if ($sesionActiva && !$force) {
                        echo json_encode([
                            'success' => false, 
                            'session_exists' => true, 
                            'error' => 'Ya tienes una sesión abierta en otro dispositivo.'
                        ]);
                        exit();
                    }

                    // Crear las variables de sesión en el servidor
                    $_SESSION['user_id'] = $userFound->id;
                    $_SESSION['user_email'] = $userFound->email;
                    $_SESSION['user_nombre'] = $userFound->nombre;
                    $_SESSION['user_role'] = $userFound->nombre_rol; // Viene del JOIN con table_roles
                    $_SESSION['user_staff_id'] = $userFound->staff_id ?? null;

                    // Registrar la nueva sesión en la BD
                    $this->userModel->registrarSesion([
                        'session_id' => session_id(),
                        'usuario_id' => $userFound->id,
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                        'usuario_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido'
                    ]);

                    // Retornar éxito en JSON
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
                    'username' => $_SESSION['user_email'],
                    'staffName' => $_SESSION['user_nombre'],
                    'role' => $_SESSION['user_role']
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
