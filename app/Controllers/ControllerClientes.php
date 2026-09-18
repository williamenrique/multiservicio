<?php
/**
 * Controlador de Clientes
 * Maneja la lógica de visualización y API para la gestión de clientes.
 */
class ControllerClientes extends Controller {
    private $clienteModel;

    public function __construct() {
        AuthGuard::handle();
        $this->clienteModel = $this->model('Cliente'); // Cargar modelo después de la autenticación
    }

    /**
     * Carga la vista principal de gestión de clientes
     */
    public function index() {
        // Eliminamos el acento para que coincida exactamente con el valor en la DB
        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO']); 
        $data = [
            'titulo' => 'Gestión de Clientes',
            'user_role' => $_SESSION['user_role'] // Pasar el rol del usuario a la vista para ajustes de UI
        ];

        $this->view('cliente/index', $data);
    }

    /**
     * Endpoint API para obtener la lista de clientes (AJAX)
     */
    public function listar() {
        $searchValue = $_GET['q'] ?? $_GET['search']['value'] ?? null;
        $search = ($searchValue !== '' && $searchValue !== null) ? $searchValue : null;

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $items = $this->clienteModel->listar($limit, $offset, $search);
        $total = $this->clienteModel->contarTotal();
        $totalFiltrados = $search ? $this->clienteModel->contarFiltrados($search) : $total;
        
        return $this->jsonResponse([
            'success' => true,
            'data' => $items ?: [],
            'total' => $total,
            'totalFiltrados' => $totalFiltrados
        ]);
    }

    /**
     * Endpoint API para obtener un cliente por su ID/Cédula (Cédula)
     */
    public function obtener($id) {
        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO']);
        $cliente = $this->clienteModel->obtenerPorId($id);
        
        // Incluir vehículos del cliente
        if ($cliente) {
            $vehiculos = $this->clienteModel->obtenerVehiculos($id);
            $cliente->vehiculos = $vehiculos;
            
            // Si hay vehículos, tomar el primero para pre-llenar el formulario
            if (!empty($vehiculos)) {
                $primerVehiculo = $vehiculos[0];
                $cliente->vehiculo_placa = $primerVehiculo->placa;
                $cliente->vehiculo_marca = $primerVehiculo->marca;
                $cliente->vehiculo_modelo = $primerVehiculo->modelo;
                $cliente->vehiculo_anio = $primerVehiculo->anio;
                $cliente->vehiculo_color = $primerVehiculo->color;
            }
        }
        
        return $this->jsonResponse($cliente);
    }

    /**
     * Endpoint API para listar vehículos de un cliente (AJAX)
     */
    public function vehiculos($id) {
        $data = $this->clienteModel->obtenerVehiculos($id);
        return $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    /**
     * Endpoint API para verificar si un ID de cliente ya existe
     */
    public function verificarId() {
        $value = $_GET['value'] ?? '';
        if (empty($value)) {
            return $this->jsonResponse(['exists' => false]);
        }
        $exists = $this->clienteModel->verificarIdUnico($value);
        return $this->jsonResponse(['exists' => $exists]);
    }

    /**
     * Guarda o actualiza un cliente
     */
    public function guardar() {
        // Permitir a Administradores y Mecánicos crear clientes (necesario para facturación rápida)
        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO']); 
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['id']) || empty($input['nombre'])) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Identificación y nombre son requeridos'], 400);
            }

            $existe = $this->clienteModel->obtenerPorId($input['id']);
            
            if ($existe) {
                $res = $this->clienteModel->actualizar($input);
            } else {
                $res = $this->clienteModel->crear($input);
            }

            // Procesar vehículo si se proporcionaron datos
            $vehiculoPlaca = $input['vehiculo_placa'] ?? null;
            if ($vehiculoPlaca && $res) {
                $vehiculoModel = $this->model('Vehiculo');
                $vehiculoExistente = $vehiculoModel->buscarPorPlaca($vehiculoPlaca);
                
                $vehiculoData = [
                    'placa' => strtoupper($vehiculoPlaca),
                    'marca' => mb_strtoupper($input['vehiculo_marca'] ?? '', 'UTF-8'),
                    'modelo' => mb_strtoupper($input['vehiculo_modelo'] ?? '', 'UTF-8'),
                    'anio' => $input['vehiculo_anio'] ?? null,
                    'color' => mb_strtoupper($input['vehiculo_color'] ?? '', 'UTF-8'),
                    'cliente_id' => $input['id']
                ];
                
                if (!$vehiculoExistente) {
                    $vehiculoModel->registrar($vehiculoData);
                } else {
                    // Actualizar vehículo existente si cambió de cliente o datos
                    if ($vehiculoExistente->cliente_id != $input['id'] ||
                        $vehiculoExistente->marca != $vehiculoData['marca'] ||
                        $vehiculoExistente->modelo != $vehiculoData['modelo'] ||
                        $vehiculoExistente->anio != $vehiculoData['anio'] ||
                        $vehiculoExistente->color != $vehiculoData['color']) {
                        $vehiculoModel->actualizar($vehiculoData);
                    }
                }
            }

            return $this->jsonResponse([
                'success' => $res, 
                'mensaje' => $res ? 'Cliente guardado correctamente' : 'Error al procesar la solicitud'
            ]);
        }
    }

    public function eliminar($id) {
        RoleGuard::isAdmin();
        $res = $this->clienteModel->eliminar($id);
        return $this->jsonResponse(['success' => $res, 'mensaje' => $res ? 'Cliente eliminado' : 'Error al eliminar']);
    }
}