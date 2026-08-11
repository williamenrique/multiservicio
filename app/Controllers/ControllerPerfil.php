<?php
/**
 * Controlador para el perfil del usuario
 */
class ControllerPerfil extends Controller {
    private $perfilModel;

    public function __construct() {
        AuthGuard::handle();
        $this->perfilModel = $this->model('Perfil');
    }
    
    /**
     * Muestra la vista de perfil
     */
    public function index() {
        $usuario = $this->perfilModel->obtenerDatos($_SESSION['user_id']);
        
        $data = [
            'titulo' => 'Mi Perfil',
            'usuario' => $usuario
        ];

        $this->view('perfil/index', $data);
    }

    /**
     * Procesa la actualización del perfil vía AJAX
     */
    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $staffId = $_SESSION['user_staff_id'];
            $userId = $_SESSION['user_id'];
            
            // Obtenemos datos actuales para conocer la cédula y las rutas de archivos viejos
            $perfilActual = $this->perfilModel->obtenerDatos($userId);
            $cedula = $perfilActual->cedula;
            
            $datos = $_POST;

            // Procesar imágenes (Perfil y Frente)
            $imagenes = ['foto' => 'avatar', 'foto_frente' => 'identificacion'];
            foreach ($imagenes as $campo => $tipo) {
                if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = dirname(APPROOT) . '/public_html/uploads/perfiles/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                    $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    
                    if (in_array($ext, $allowed)) {
                        // 1. Borrar imagen anterior si existe y no es la por defecto
                        $oldFile = $perfilActual->$campo;
                        if (!empty($oldFile) && $oldFile !== 'img/default.png') {
                            $fullOldPath = APPROOT . '/../public_html/' . $oldFile;
                            if (file_exists($fullOldPath)) {
                                unlink($fullOldPath);
                            }
                        }

                        // 2. Generar nuevo nombre: cedula_tipo_timestamp.ext
                        $fileName = $cedula . '_' . $tipo . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES[$campo]['tmp_name'], $uploadDir . $fileName)) {
                            $datos[$campo] = 'uploads/perfiles/' . $fileName;
                        }
                    }
                }
            }

            // Actualizar datos básicos
            $success = $this->perfilModel->actualizarStaff($staffId, $datos);

            // Actualizar contraseña si se envió
            $pass = !empty($datos['new_password']) ? trim($datos['new_password']) : null;
            $confirm = !empty($datos['confirm_password']) ? trim($datos['confirm_password']) : null;

            if ($pass) {
                if ($pass === $confirm) {
                    if (strlen($pass) < 6) {
                        $this->jsonResponse(['success' => false, 'mensaje' => 'La contraseña debe tener al menos 6 caracteres'], 400);
                    }
                    $this->jsonResponse(['success' => false, 'mensaje' => 'Las contraseñas no coinciden'], 400);
                    return;
                }
            }

            if ($success) {
                // Actualizar nombre en sesión por si cambió
                $_SESSION['user_nombre'] = mb_strtoupper($datos['nombre'], 'UTF-8');
                // Actualizar foto en sesión si se subió una nueva
                if (!empty($datos['foto'])) $_SESSION['user_foto'] = $datos['foto'];
                
                $this->jsonResponse([
                    'success' => true, 
                    'mensaje' => 'Perfil actualizado correctamente',
                    'foto' => $datos['foto'] ?? $perfilActual->foto,
                    'foto_frente' => $datos['foto_frente'] ?? $perfilActual->foto_frente
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'mensaje' => 'Error al actualizar el perfil'], 500);
            }
        }
    }
}