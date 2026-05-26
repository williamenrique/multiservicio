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

            $resultado = $this->facturaModel->eliminarBorrador($id);
            echo json_encode(['success' => $resultado]);
        }
    }

    /**
     * Genera el PDF de la Factura de Venta
     */
    public function imprimir($id = null) {
        if (!$id) {
            redirect('facturacion');
        }

        $venta = $this->facturaModel->obtenerVentaCompleta($id);
        if (!$venta) {
            die("La factura #$id no existe o no ha sido completada.");
        }

        $pdf = new PdfService();
        $pdf->generarDocumento('factura', [
            'titulo_documento' => 'Factura de Venta',
            'documento_id' => $venta->id,
            'venta' => $venta
        ], 'Factura_' . $id . '.pdf');
    }
        /**
     * Procesa la petición AJAX para registrar un abono a una deuda de cliente.
     * Ruta: /facturacion/registrarAbono
     */
    public function registrarAbono() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Leer el cuerpo de la petición JSON
            $input = json_decode(file_get_contents('php://input'), true);

            // Validar que lleguen los datos mínimos
            if (!isset($input['venta_id']) || !isset($input['monto']) || !isset($input['metodo'])) {
                return $this->jsonResponse([
                    'success' => false, 
                    'mensaje' => 'Datos insuficientes para procesar el pago.'
                ], 400);
            }

            $ventaId = (int)$input['venta_id'];
            $monto = (float)$input['monto'];
            $metodo = strtoupper($input['metodo']);

            // Usamos $this->facturaModel que es como está definido en el __construct
            $resultado = $this->facturaModel->registrarAbono($ventaId, $monto, $metodo);

            if ($resultado) {
                // Registrar la acción en la bitácora de auditoría del sistema
                logAction('VENTA', 'ABONO', "Se registró un abono de " . $monto . " a la factura #" . $ventaId . " vía " . $metodo);
                
                return $this->jsonResponse([
                    'success' => true,
                    'mensaje' => '¡Abono registrado con éxito!'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Error interno al intentar guardar el abono en la base de datos.'
                ], 500);
            }
        } else {
            // Bloquear accesos que no sean POST
            return $this->jsonResponse(['success' => false, 'mensaje' => 'Método no permitido'], 405);
        }
    }

    /**
     * Endpoint para obtener las alertas de créditos vencidos (AJAX)
     */
    public function alertasCredito() {
        RoleGuard::isAdmin();
        header('Content-Type: application/json');
        $data = $this->facturaModel->obtenerCreditosVencidos(15);
        echo json_encode(['success' => true, 'data' => $data]);
    }

}