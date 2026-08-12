<?php
/**
 * ControllerDevoluciones
 * Gestiona la sección dedicada de devoluciones de repuestos.
 * Permite: listar facturas con repuestos, ver items, procesar devolución
 * (validando garantía configurable) y consultar el historial.
 */
class ControllerDevoluciones extends Controller {
    private $devolucionesModel;

    public function __construct() {
        AuthGuard::handle();
        $this->devolucionesModel = $this->model('Devoluciones');
    }

    /**
     * Vista principal: listado de facturas con repuestos devolvibles.
     */
    public function index() {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        $data = [
            'titulo' => 'Devoluciones de Repuestos',
            'user_role' => $_SESSION['user_role']
        ];
        $this->view('devoluciones/index', $data);
    }

    /**
     * Endpoint AJAX: lista facturas que tienen repuestos (devolvibles).
     */
    public function listarFacturas() {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $search = $input['search'] ?? $_GET['search']['value'] ?? $_GET['search'] ?? $_GET['q'] ?? null;
            $search = ($search !== '' && $search !== null) ? $search : null;
            $limit = isset($input['limit']) ? (int)$input['limit'] : (isset($_GET['limit']) ? (int)$_GET['limit'] : 10);
            $page = isset($input['page']) ? (int)$input['page'] : 1;
            $offset = isset($input['offset']) ? (int)$input['offset'] : ($page - 1) * $limit;

            $resultado = $this->devolucionesModel->listarFacturasConRepuestos($limit, $offset, $search);
            return $this->jsonResponse(['success' => true, 'data' => $resultado['data'], 'total' => $resultado['total']]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint AJAX: obtiene los items (repuestos) de una factura para devolución.
     * Incluye cálculo de garantía vigente por item.
     * @param int $id ID de la factura
     */
    public function getItems($id) {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        try {
            $items = $this->devolucionesModel->obtenerItemsFactura((int)$id);
            if (empty($items)) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Esta factura no tiene repuestos devolvibles.'], 404);
            }
            return $this->jsonResponse(['success' => true, 'items' => $items]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint AJAX: procesa una devolución.
     * Recibe JSON: { factura_id, detalle_id, destino, motivo }
     */
    public function procesar() {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            $v = new Validator($input);
            $v->required(['factura_id', 'detalle_id', 'destino'])
              ->in('destino', ['STOCK', 'DANADO']);

            if (!$v->success()) {
                throw new Exception(implode(" ", $v->getErrors()));
            }

            $motivo = trim($input['motivo'] ?? '');

            $resultado = $this->devolucionesModel->procesarDevolucion(
                (int)$input['factura_id'],
                (int)$input['detalle_id'],
                $input['destino'],
                $motivo
            );

            return $this->jsonResponse([
                'success' => $resultado,
                'mensaje' => $resultado
                    ? 'Devolución procesada con éxito. ' . ($input['destino'] === 'STOCK' ? 'Stock reingresado al inventario.' : 'Ítem marcado como dañado (no reingresado).')
                    : 'No se pudo procesar la devolución.'
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Vista / Endpoint AJAX: historial de devoluciones con filtros.
     */
    public function historial() {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        // Detectar petición AJAX: POST con JSON, o GET con parámetros de paginación
        $rawBody = file_get_contents('php://input');
        $input = json_decode($rawBody, true) ?: [];
        $isAjax = !empty($input) || isset($_GET['limit']) || isset($_GET['offset']) || isset($_GET['draw']);
        if ($isAjax) {
            try {
                $search = $input['search'] ?? $_GET['search']['value'] ?? $_GET['search'] ?? $_GET['q'] ?? null;
                $search = ($search !== '' && $search !== null) ? $search : null;
                $limit = isset($input['limit']) ? (int)$input['limit'] : (isset($_GET['limit']) ? (int)$_GET['limit'] : 10);
                $page = isset($input['page']) ? (int)$input['page'] : 1;
                $offset = isset($input['offset']) ? (int)$input['offset'] : ($page - 1) * $limit;
                $desde = $input['desde'] ?? $_GET['desde'] ?? null;
                $hasta = $input['hasta'] ?? $_GET['hasta'] ?? null;

                $resultado = $this->devolucionesModel->listarDevoluciones($limit, $offset, $search, $desde, $hasta);
                return $this->jsonResponse(['success' => true, 'data' => $resultado['data'], 'total' => $resultado['total']]);
            } catch (Exception $e) {
                return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
            }
        }

        // Si no es AJAX, redirigir a la vista unificada con tabs
        redirect('devoluciones');
    }

    /**
     * Endpoint AJAX: obtiene el detalle de una devolución específica.
     * @param int $id
     */
    public function detalle($id) {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'CAJERO']);
        try {
            $devolucion = $this->devolucionesModel->obtenerDevolucion((int)$id);
            if (!$devolucion) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Devolución no encontrada.'], 404);
            }
            return $this->jsonResponse(['success' => true, 'data' => $devolucion]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
