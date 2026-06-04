<?php
class ControllerDashboard extends Controller {
    private $dashboardModel;
    private $facturaModel;

    public function __construct() {
        AuthGuard::handle(); // Asegurar que solo usuarios logueados accedan
        // Cargamos el modelo centralizado
        $this->dashboardModel = $this->model('Dashboard');
        $this->facturaModel = $this->model('Facturacion');
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
        // El Administrador (Rol 1) no tiene filtro de usuario, ve todo.
        // Los demás roles (Mecánicos, etc.) ven solo sus registros.
        $usuarioFiltro = RoleGuard::is_admin_check() ? null : $_SESSION['user_id'];
        
        $desde = date('Y-m-01');
        $hasta = date('Y-m-d');

        // Cargamos el modelo de proveedor para obtener deudas detalladas
        $proveedorModel = $this->model('Proveedor');

        // Centralizamos la llamada a través del modelo
        $this->jsonResponse([
            'inventory' => $this->dashboardModel->getInventoryStats(),
            'ingresosHoy' => $this->dashboardModel->getIncomeToday($usuarioFiltro),
            'gastosMes' => $this->dashboardModel->getExpensesMonth(),
            'recentSales' => $this->dashboardModel->getRecentSales($usuarioFiltro),
            'drafts' => $this->dashboardModel->getPendingDrafts($usuarioFiltro),
            'supplierDebts' => $proveedorModel->listarDeudas(),
            'history' => $this->dashboardModel->getFinancialHistory(7, $usuarioFiltro),
            'recentExpenses' => $this->dashboardModel->getRecentExpenses(),
            'lowStock' => $this->dashboardModel->getLowStockProducts(),
            'profitability' => $this->facturaModel->obtenerReporteUtilidad($desde, $hasta),
            'workshopStatus' => $this->dashboardModel->getServiceOrdersStatus(),
            'topProducts' => $this->dashboardModel->getTopSellingProducts()
        ]);
    }
}