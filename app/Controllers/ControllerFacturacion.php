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
            try {
                header('Content-Type: application/json');
                $datos = json_decode(file_get_contents('php://input'), true);
                
                if (empty($datos['items'])) {
                    throw new AppException('El carrito está vacío');
                }

                $resultado = $this->facturaModel->procesarVenta($datos);

                echo json_encode([
                    'success' => true, 
                    'mensaje' => 'Venta realizada con éxito',
                    'venta_id' => $resultado
                ]);
            } catch (StockException $e) {
                echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'mensaje' => 'Error Crítico: ' . $e->getMessage()]);
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

            try {
                // Usamos $this->facturaModel que es como está definido en el __construct
                $resultado = $this->facturaModel->registrarAbono($ventaId, $monto, $metodo);

                // Registrar la acción en la bitácora de auditoría del sistema
                logAction('VENTA', 'ABONO', "Se registró un abono de " . $monto . " a la factura #" . $ventaId . " vía " . $metodo);
                
                return $this->jsonResponse([
                    'success' => true,
                    'mensaje' => '¡Abono registrado con éxito!'
                ]);
            } catch (Exception $e) {
                return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
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

    /**
     * Endpoint para obtener el resumen de deudores para el dashboard (AJAX)
     */
    public function getDeudoresSummary() {
        RoleGuard::isAdmin();
        header('Content-Type: application/json');
        $data = $this->facturaModel->obtenerAuditoriaTrabajos();
        echo json_encode(['success' => true, 'data' => $data]);
    }

    /**
     * Obtiene los items de una factura que son aptos para devolución (solo productos)
     */
    public function getItemsDevolucion($id) {
        $venta = $this->facturaModel->obtenerVentaCompleta($id);
        // Filtrar solo los que tienen producto_id (no servicios)
        $items = array_filter($venta->items, function($it) {
            return !empty($it->producto_id);
        });
        echo json_encode(['success' => true, 'items' => array_values($items)]);
    }

    public function procesarDevolucion() {
        RoleGuard::isAdmin();
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $this->facturaModel->procesarDevolucion(
                $input['venta_id'], 
                $input['detalle_id'], 
                $input['destino']
            );

            logAction('VENTA', 'DEVOLUCION', "Devolución de item en factura #{$input['venta_id']}. Destino: {$input['destino']}");
            echo json_encode(['success' => true, 'mensaje' => 'Devolución procesada correctamente']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint para el historial de devoluciones
     */
    public function listarDevoluciones() {
        RoleGuard::isAdmin();
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $reporteModel = $this->model('Reportes');
        echo json_encode(['success' => true, 'data' => $reporteModel->obtenerReporteDevoluciones($desde, $hasta)]);
    }

}