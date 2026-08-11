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
            // Obtenemos la configuración actual para manejar el borrado del logo anterior
            $configActual = $this->empresaModel->obtenerConfiguracion();
            
            // Al usar FormData en el frontend, los datos llegan vía $_POST y $_FILES
            $input = $_POST;

            // Validación básica
            if (empty($input['name']) || empty($input['iva'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'El nombre y el IVA son campos requeridos.'], 400);
                return;
            }

            // Procesar subida de Logo si se adjuntó un archivo
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(APPROOT) . '/public_html/uploads/logo/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                // 1. Borrar logo anterior si existe y no es el por defecto
                if (!empty($configActual->logo)) {
                    $oldPath = dirname(APPROOT) . '/public_html/' . ltrim($configActual->logo, '/');
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $fileExtension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $newFileName = 'logo_empresa_' . time() . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $destPath)) {
                    // Guardamos la ruta relativa correcta (sin repetir public)
                    $input['logo'] = 'uploads/logo/' . $newFileName;
                }
            }

            $res = $this->empresaModel->guardarConfiguracion($input);

            if ($res) {
                $this->jsonResponse([
                    'success' => true, 
                    'mensaje' => 'Configuración guardada correctamente',
                    'new_logo_url' => $input['logo'] ?? null
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'mensaje' => 'Error al guardar la configuración'], 500);
            }
        } else {
            $this->jsonResponse(['success' => false, 'mensaje' => 'Método no permitido'], 405);
        }
    }
}