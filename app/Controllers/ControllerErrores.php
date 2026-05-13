<?php
class ControllerErrores extends Controller {
    
    public function index() {
        // Enviamos el código de estado HTTP 404 al navegador
        http_response_code(404);
        
        $data = [
            'titulo' => 'Página no encontrada',
            'mensaje' => 'Lo sentimos, el recurso que buscas no existe en el sistema del taller.'
        ];

        $this->view('errores/404', $data);
    }
}
