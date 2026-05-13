<?php
/**
 * Middleware de Autenticación
 * Se encarga de proteger las rutas privadas del taller
 */
class AuthGuard {

    /**
     * Verifica si el usuario tiene una sesión activa.
     * Si no, lo redirige al login.
     */
    public static function handle() {
        // Iniciamos sesión si no ha sido iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si no existe el ID del usuario en la sesión, está intentando entrar ilegalmente
        if (!isset($_SESSION['user_id'])) {
            // Usamos el helper de redirección que definiremos luego
            header('location: ' . URLROOT . 'auth/login');
            exit();
        }
    }

    /**
     * Verifica si el usuario tiene un rol específico (ej: 'admin')
     * Útil para proteger la facturación o gestión de personal.
     */
    public static function role($roleRequired) {
        self::handle(); // Primero verificamos que esté logueado

        if ($_SESSION['user_role'] !== $roleRequired) {
            // Si el rol no coincide, lo mandamos al dashboard con un aviso
            header('location: ' . URLROOT . '/dashboard?error=unauthorized');
            exit();
        }
    }
}
/*
uso
<?php
class Facturacion extends Controller {
    public function __construct() {
        // Bloquea a cualquiera que no sea Administrador
        AuthGuard::role('admin');
        
        $this->facturaModel = $this->model('Factura');
    }

    public function index() {
        // Solo llega aquí si pasó la puerta del AuthGuard
        $this->view('finanzas/index');
    }
}
*/
