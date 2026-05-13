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
                if (password_verify($password, $userFound->password)) {
                    
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
    
    // ... método logout
}
