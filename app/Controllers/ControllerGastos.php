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
        $search = $_GET['q'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $desde = $_GET['desde'] ?? null;
        $hasta = $_GET['hasta'] ?? null;

        $items = $this->gastoModel->listar($limit, $offset, $search, $desde, $hasta);
        $total = $this->gastoModel->contarTotal();
        $totalFiltrados = ($search || $desde || $hasta) ? $this->gastoModel->contarFiltrados($search, $desde, $hasta) : $total;

        return $this->jsonResponse([
            'success' => true,
            'data' => $items ?: [],
            'total' => $total,
            'totalFiltrados' => $totalFiltrados
        ]);
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            try {
                // Si el gasto es en EFECTIVO, debe haber una caja abierta
                $metodo = $input['metodo_pago'] ?? 'EFECTIVO';

                $idGasto = $this->gastoModel->crear($input);
                
                if ($idGasto !== false && $metodo === 'EFECTIVO') {
                    $this->cajaModel->registrarMovimiento([
                        'tipo' => 'EGRESO',
                        'monto' => $input['monto'],
                        'metodo_pago' => 'EFECTIVO',
                        'referencia_id' => $idGasto,
                        'concepto' => "GASTO: " . $input['descripcion']
                    ]);
                }

                return $this->jsonResponse(['success' => true]);
            } catch (Exception $e) {
                return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
            }
        }
    }

    public function eliminar($id) {
        RoleGuard::isAdmin();
        if ($this->gastoModel->eliminar($id)) {
            return $this->jsonResponse(['success' => true]);
        } else {
            return $this->jsonResponse(['success' => false], 400);
        }
    }
}