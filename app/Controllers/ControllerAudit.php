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
        $data = ['titulo' => 'Bitácora de Auditoría'];
        $this->view('audit/index', $data);
    }

    public function listar() {
        $logs = $this->auditModel->listarLogs();
        $this->jsonResponse($logs);
    }
}