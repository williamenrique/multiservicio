<?php
/**
 * Controlador Principal del Panel (Dashboard)
 */
class Dashboard extends Controller {

    public function __construct() {
        // Ejecutamos el guardián de seguridad.
        // Si no hay sesión, este método redirige a /auth/login e interrumpe el script.
        AuthGuard::handle();
    }

    /**
     * Muestra la pantalla de bienvenida del taller
     */
    public function index() {
        // Preparamos los datos básicos del usuario autenticado
        $data = [
            'titulo' => 'Panel de Inicio',
            'nombre_usuario' => $_SESSION['user_nombre'],
            'rol_usuario' => $_SESSION['user_role']
        ];

        // Renderizamos la vista protegida
        $this->view('dashboard/index', $data);
    }
}
