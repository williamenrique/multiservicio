<?php
class ControllerGastos extends Controller {
    private $gastoModel;
    private $cajaModel;

    public function __construct() {
        AuthGuard::handle();
        $this->gastoModel = $this->model('Gasto');
        $this->cajaModel = $this->model('Caja');
    }

    public function index() {
        $this->view('gastos/index', ['titulo' => 'Gastos del Taller']);
    }

    public function listar() {
        $this->jsonResponse($this->gastoModel->listar());
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            try {
                // Si el gasto es en EFECTIVO, debe haber una caja abierta
                $metodo = $input['metodo_pago'] ?? 'EFECTIVO';
                $sesionActiva = null;

                if ($metodo === 'EFECTIVO') {
                    $sesionActiva = $this->cajaModel->obtenerSesionActiva();
                    if (!$sesionActiva) {
                        throw new Exception('Debe abrir caja para registrar un gasto en efectivo.');
                    }
                }

                $idGasto = $this->gastoModel->crear($input);
                
                if ($idGasto && $metodo === 'EFECTIVO') {
                    $this->cajaModel->registrarMovimiento([
                        'sesion_id' => $sesionActiva->id,
                        'tipo' => 'EGRESO',
                        'monto' => $input['monto'],
                        'metodo_pago' => 'EFECTIVO',
                        'referencia_id' => $idGasto,
                        'concepto' => "GASTO: " . $input['descripcion']
                    ]);
                }

                $this->jsonResponse(['success' => true]);
            } catch (Exception $e) {
                $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
            }
        }
    }

    public function eliminar($id) {
        RoleGuard::isAdmin();
        if ($this->gastoModel->eliminar($id)) {
            $this->jsonResponse(['success' => true]);
        } else {
            $this->jsonResponse(['success' => false], 400);
        }
    }
}