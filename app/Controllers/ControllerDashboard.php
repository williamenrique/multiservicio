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
        // Determinamos el filtro según el rol: Rol 1 (ADMIN) ve todo (null), otros ven solo lo suyo.
        // Usamos el helper is_admin_check() que definimos en RoleGuard.
        $usuarioFiltro = RoleGuard::is_admin_check() ? null : $_SESSION['user_id'];

        // Centralizamos la llamada a través del modelo
        $this->jsonResponse([
            'inventory' => $this->dashboardModel->getInventoryStats(),
            'ingresosHoy' => $this->dashboardModel->getIncomeToday($usuarioFiltro),
            'gastosMes' => $this->dashboardModel->getExpensesMonth(),
            'recentSales' => $this->dashboardModel->getRecentSales($usuarioFiltro),
            'drafts' => $this->dashboardModel->getPendingDrafts($usuarioFiltro),
            'supplierDebts' => $this->dashboardModel->getSupplierDebtsSummary(),
            'history' => $this->dashboardModel->getFinancialHistory(7, $usuarioFiltro),
            'recentExpenses' => $this->dashboardModel->getRecentExpenses()
        ]);
    }
}