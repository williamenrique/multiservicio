<?php
/**
 * Controlador de Dashboard
 * Orquestador de métricas y estadísticas para la vista principal.
 */
class ControllerDashboard extends Controller {
    private $dashboardModel;
    private $facturacionModel;

    public function __construct() {
        AuthGuard::handle();
        $this->dashboardModel = $this->model('Dashboard');
        $this->facturacionModel = $this->model('Facturacion');
    }

    public function index() {
        $data = [
            'titulo' => 'Panel de Control'
        ];
        $this->view('dashboard/index', $data);
    }

    /**
     * Endpoint API que centraliza todas las estadísticas del dashboard (AJAX)
     */
    public function getStats() {
        try {
            $isAdmin = RoleGuard::is_admin_check();
            $usuarioId = $isAdmin ? null : ($_SESSION['user_id'] ?? null);
            
            $desde = date('Y-m-01');
            $hasta = date('Y-m-d');

            $data = [
                'success' => true,
                'inventory' => $this->dashboardModel->getInventoryStats(),
                'ingresosHoy' => (float)$this->dashboardModel->getIncomeToday($usuarioId),
                'gastosMes' => $isAdmin ? (float)$this->dashboardModel->getExpensesMonth() : 0,
                'recentSales' => $this->dashboardModel->getRecentSales($usuarioId),
                'drafts' => $this->dashboardModel->getPendingDrafts($usuarioId),
                'history' => $isAdmin ? $this->dashboardModel->getFinancialHistory(7, $usuarioId) : [],
                'recentExpenses' => $isAdmin ? $this->dashboardModel->getRecentExpenses() : [],
                'lowStock' => $this->dashboardModel->getLowStockProducts(),
                'workshopStatus' => $this->dashboardModel->getServiceOrdersStatus(),
                'topProducts' => $this->dashboardModel->getTopSellingProducts(),
                'supplierDebts' => $isAdmin ? $this->dashboardModel->getSupplierDebtsSummary() : [],
                'profitability' => $isAdmin ? $this->facturacionModel->obtenerReporteUtilidad($desde, $hasta) : null
            ];

            return $this->jsonResponse($data);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}