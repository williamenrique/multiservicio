<?php
/**
 * Controlador de Errores
 * Maneja las respuestas para páginas no encontradas o errores internos.
 */
class ControllerErrores extends Controller {

    public function index() {
        // Establecer código de respuesta HTTP 404
        http_response_code(404);
        
        $data = ['titulo' => '404 - No Encontrado'];
        $this->view('errores/404', $data);
    }
}