<?php
/**
 * Controlador de Clientes
 * Maneja la lógica de visualización y API para la gestión de clientes.
 */
class ControllerClientes extends Controller {
    private $clienteModel;

    public function __construct() {
        AuthGuard::handle();
        $this->clienteModel = $this->model('Cliente');
    }

    /**
     * Carga la vista principal de gestión de clientes
     */
    public function index() {
        $data = [
            'titulo' => 'Gestión de Clientes'
        ];

        $this->view('cliente/index', $data);
    }

    /**
     * Endpoint API para obtener la lista de clientes (AJAX)
     */
    public function listar() {
        header('Content-Type: application/json');
        $clientes = $this->clienteModel->listar();
        echo json_encode($clientes);
    }

    /**
     * Guarda o actualiza un cliente
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validar si es actualización (ya tiene ID) o creación
            $clienteExistente = $this->clienteModel->obtenerPorId($input['id']);

            if ($clienteExistente) {
                $resultado = $this->clienteModel->actualizar($input);
            } else {
                $resultado = $this->clienteModel->crear($input);
            }

            if ($resultado) {
                echo json_encode(['success' => true, 'mensaje' => 'Cliente guardado correctamente']);
            } else {
                echo json_encode(['success' => false, 'mensaje' => 'Error al guardar el cliente']);
            }
        }
    }

    /**
     * Elimina un cliente por ID
     */
    public function eliminar($id = null) {
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE' && $id) {
            if ($this->clienteModel->eliminar($id)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
        }
    }
}