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
            'iva_defecto' => $config->iva ?? 0
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
}