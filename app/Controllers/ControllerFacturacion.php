<?php
class ControllerFacturacion extends Controller {
    private $facturaModel;
    private $empresaModel;

    public function __construct() {
        AuthGuard::handle();
        $this->facturaModel = $this->model('Facturacion');
        $this->empresaModel = $this->model('Empresa');
    }

    public function index() {
        $config = $this->empresaModel->obtenerConfiguracion();
        $data = [
            'titulo' => 'Nueva Facturación',
            'iva_defecto' => $config->iva ?? 0,
            'usuario_actual' => $_SESSION['user_nombre']
        ];

        $this->view('facturacion/index', $data);
    }

    /**
     * Endpoint para buscar items en tiempo real
     */
    public function buscarItems() {
        $term = $_GET['term'] ?? '';
        $items = $this->facturaModel->buscarItems($term);
        header('Content-Type: application/json');
        echo json_encode($items);
    }

    /**
     * Lista todos los borradores activos en el sistema (Global)
     */
    public function listarBorradores() {
        header('Content-Type: application/json');
        echo json_encode($this->facturaModel->obtenerBorradoresCompleto());
    }

    /**
     * Procesa el guardado de la venta
     */
    public function procesar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            $datos = json_decode(file_get_contents('php://input'), true);
            
            if (empty($datos['items'])) {
                echo json_encode(['success' => false, 'mensaje' => 'El carrito está vacío']);
                return;
            }

            $resultado = $this->facturaModel->procesarVenta($datos);

            if ($resultado) {
                echo json_encode([
                    'success' => true, 
                    'mensaje' => 'Venta realizada con éxito',
                    'venta_id' => $resultado
                ]);
            } else {
                echo json_encode(['success' => false, 'mensaje' => 'No se pudo procesar la venta. Verifique el stock.']);
            }
        }
    }

    /**
     * Sincroniza un borrador con la base de datos para reservar stock en tiempo real.
     */
    public function sincronizarBorrador() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            $datos = json_decode(file_get_contents('php://input'), true);
            
            // Guardamos con status PENDIENTE
            $resultado = $this->facturaModel->guardarFactura($datos, 'PENDIENTE');

            if ($resultado) {
                echo json_encode([
                    'success' => true, 
                    'venta_id' => $resultado
                ]);
            } else {
                echo json_encode(['success' => false, 'mensaje' => 'No se pudo sincronizar el borrador']);
            }
        }
    }

    /**
     * Elimina un borrador de la base de datos verificando permisos por rol
     */
    public function eliminarBorrador($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            
            $borrador = $this->facturaModel->obtenerBorradorPorId($id);
            if (!$borrador) {
                echo json_encode(['success' => false, 'mensaje' => 'Borrador no encontrado']);
                return;
            }

            $userRole = trim($_SESSION['user_role'] ?? '');
            $currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
            $canDelete = false;

            // Lógica de seguridad inquebrantable
            if ($userRole === 'Administrador') {
                $canDelete = true;
            } elseif ($userRole === 'Mecánico') {
                $ownerId = property_exists($borrador, 'usuario_id') ? (int)$borrador->usuario_id : 0;

                // Solo permitir si el ID del dueño es válido y coincide con la sesión actual
                if ($ownerId > 0 && $currentUserId > 0 && $ownerId === $currentUserId) {
                    $canDelete = true;
                }
            }

            if (!$canDelete) {
                echo json_encode(['success' => false, 'mensaje' => 'No tienes permisos para eliminar este borrador']);
                return;
            }

            $resultado = $this->facturaModel->eliminarBorrador($id);
            echo json_encode(['success' => $resultado]);
        }
    }
}