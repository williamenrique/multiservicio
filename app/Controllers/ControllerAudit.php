<?php
/**
 * Controlador para la visualización de la Bitácora de Auditoría.
 */
class ControllerAudit extends Controller {
    private $auditModel;

    public function __construct() {
        // Solo administradores pueden ver los logs
        AuthGuard::role('ADMINISTRADOR');
        $this->auditModel = $this->model('Audit');
    }

    public function index() {
        $data = [
            'titulo' => 'Bitácora de Auditoría',
            'usuarios' => $this->auditModel->obtenerUsuarios(),
            'modulos' => $this->auditModel->obtenerModulos(),
            'acciones' => $this->auditModel->obtenerAcciones()
        ];
        $this->view('audit/index', $data);
    }

    public function listar() {
        // Leer parámetros de filtro
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        
        $limit = isset($input['limit']) ? (int)$input['limit'] : 50;
        $page = isset($input['page']) ? (int)$input['page'] : 1;
        $offset = ($page - 1) * $limit;
        
        $filters = [
            'desde' => $input['desde'] ?? null,
            'hasta' => $input['hasta'] ?? null,
            'usuario_id' => $input['usuario_id'] ?? null,
            'modulo' => $input['modulo'] ?? null,
            'accion' => $input['accion'] ?? null,
            'search' => $input['search'] ?? null
        ];

        $resultado = $this->auditModel->listarLogs($limit, $offset, $filters);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $resultado['data'],
            'total' => $resultado['total'],
            'pagina_actual' => $page,
            'total_paginas' => ceil($resultado['total'] / $limit)
        ]);
    }
}