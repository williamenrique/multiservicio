<?php
class ControllerEmpresa extends Controller {
    private $empresaModel;

    public function __construct() {
        AuthGuard::handle();
        RoleGuard::isAdmin();
        $this->empresaModel = $this->model('Empresa'); // Cargar el modelo de Empresa
    }

    public function index() {
        $config = $this->empresaModel->obtenerConfiguracion();
        $data = [
            'titulo' => 'Configuración de la Empresa',
            'config' => $config // Pasar los datos de configuración a la vista
        ];

        $this->view('empresa/index', $data);
    }

    /**
     * Guarda o actualiza la configuración de la empresa.
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validación básica
            if (empty($input['name']) || empty($input['iva'])) {
                echo json_encode(['success' => false, 'mensaje' => 'El nombre y el IVA son campos requeridos.']);
                return;
            }

            $res = $this->empresaModel->guardarConfiguracion($input);

            if ($res) {
                echo json_encode(['success' => true, 'mensaje' => 'Configuración guardada correctamente']);
            } else {
                echo json_encode(['success' => false, 'mensaje' => 'Error al guardar la configuración']);
            }
        } else {
            http_response_code(405); // Método no permitido
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
        }
    }
}