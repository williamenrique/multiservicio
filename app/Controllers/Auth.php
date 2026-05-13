<?php
class Auth extends Controller {

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

            // Validar existencia del usuario
            $userFound = $this->userModel->buscarPorEmail($email);

            if ($userFound) {
                // Verificar contraseña hash
                //if (password_verify($password, $userFound->password)) {
                if (($userFound->password)) {
                    
                    // Crear las variables de sesión en el servidor
                    $_SESSION['user_id'] = $userFound->id;
                    $_SESSION['user_email'] = $userFound->email;
                    $_SESSION['user_nombre'] = $userFound->nombre;
                    $_SESSION['user_role'] = $userFound->nombre_rol; // Viene del JOIN con table_roles

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
     * Cierra la sesión del usuario y lo redirige a la página de inicio de sesión.
     */
    public function logout() {
        // Asegurarse de que la sesión esté iniciada antes de destruirla
        // Aunque public/index.php ya llama a session_start(), es buena práctica
        // verificarlo si este método pudiera ser llamado de forma aislada.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
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
