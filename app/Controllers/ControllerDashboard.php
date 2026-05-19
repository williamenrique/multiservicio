<?php
class ControllerDashboard extends Controller {
    private $dashboardModel;

    public function __construct() {
        AuthGuard::handle(); // Asegurar que solo usuarios logueados accedan
        // Cargamos el modelo centralizado
        $this->dashboardModel = $this->model('Dashboard');
    }

    public function index() {
        $data = [
            'titulo' => 'Panel de Control',
            'nombre_usuario' => $_SESSION['user_nombre'] ?? 'Usuario',
            'rol_usuario' => $_SESSION['user_role'] ?? 'Sin Rol'
        ];
        $this->view('dashboard/index', $data);
    }

    /**
     * Obtiene estadísticas reales desde la base de datos para el Dashboard
     */
    public function getStats() {
        // Centralizamos la llamada a través del modelo
        $this->jsonResponse([
            'inventory' => $this->dashboardModel->getInventoryStats(),
            'ingresosHoy' => $this->dashboardModel->getIncomeToday(),
            'gastosMes' => $this->dashboardModel->getExpensesMonth(),
            'recentSales' => $this->dashboardModel->getRecentSales(),
            'drafts' => $this->dashboardModel->getPendingDrafts(),
            'supplierDebts' => $this->dashboardModel->getSupplierDebtsSummary(),
            'history' => $this->dashboardModel->getFinancialHistory(),
            'recentExpenses' => $this->dashboardModel->getRecentExpenses()
        ]);
    }
}