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
            // Al usar FormData en el frontend, los datos llegan vía $_POST y $_FILES
            $input = $_POST;

            // Validación básica
            if (empty($input['name']) || empty($input['iva'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'El nombre y el IVA son campos requeridos.'], 400);
                return;
            }

            // Procesar subida de Logo si se adjuntó un archivo
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = APPROOT . '/../public/img/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileExtension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $newFileName = 'logo_empresa_' . time() . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $destPath)) {
                    // Guardamos la ruta relativa para que sea accesible desde la URL
                    $input['logo'] = 'public/img/' . $newFileName;
                }
            }

            $res = $this->empresaModel->guardarConfiguracion($input);

            if ($res) {
                $this->jsonResponse(['success' => true, 'mensaje' => 'Configuración guardada correctamente']);
            } else {
                $this->jsonResponse(['success' => false, 'mensaje' => 'Error al guardar la configuración'], 500);
            }
        } else {
            $this->jsonResponse(['success' => false, 'mensaje' => 'Método no permitido'], 405);
        }
    }
}