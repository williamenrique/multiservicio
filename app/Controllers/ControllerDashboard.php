<?php
/**
 * Controlador de Dashboard
 * Orquestador de métricas y estadísticas para la vista principal.
 * 
 * v3.0 (2026-10-13) — REDISEÑO DEL DASHBOARD:
 *   • getStats() expone comparativos de mes actual vs mes anterior:
 *       - comparativo.ingresos (hoy vs ayer + mes vs mes anterior)
 *       - comparativo.gastos
 *       - comparativo.utilidad
 *       - comparativo.clientes
 *   • getStats() expone dos widgets operativos nuevos:
 *       - osSinMecanico (lista)
 *       - entregasProximas (lista)
 *   • Nuevo endpoint getFinancialHistoryCustom?days=N para la gráfica
 *     con filtro de rango variable (7 / 30 / mes).
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
                'profitability' => $isAdmin ? $this->facturacionModel->obtenerReporteUtilidad($desde, $hasta) : null,
            ];

            // ─── v3.0: Comparativos (solo admin) ───
            if ($isAdmin) {
                $data['comparativo'] = [
                    'ingresos' => $this->dashboardModel->getComparativoIngresos(),
                    'gastos' => $this->dashboardModel->getComparativoGastos(),
                    'utilidad' => $this->dashboardModel->getComparativoUtilidad(),
                    'clientes' => $this->dashboardModel->getClientesNuevosComparativo(),
                ];
                // ─── v3.0: Widgets operativos (solo admin) ───
                $data['osSinMecanico'] = $this->dashboardModel->getOrdenesSinMecanico(5);
                $data['entregasProximas'] = $this->dashboardModel->getEntregasProximas(48, 6);
            } else {
                $data['comparativo'] = null;
                $data['osSinMecanico'] = [];
                $data['entregasProximas'] = [];
            }

            return $this->jsonResponse($data);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * v3.0: Endpoint para la gráfica con rango de días variable.
     * GET /dashboard/getFinancialHistoryCustom?days=7|30|90|mes
     * 
     * Devuelve SOLO la serie temporal, sin sobrecargar getStats.
     */
    public function getFinancialHistoryCustom() {
        try {
            if (!RoleGuard::is_admin_check()) {
                return $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            }

            $range = $_GET['range'] ?? '7d';
            $usuarioId = null;

            switch ($range) {
                case '30d':
                    $days = 30;
                    $history = $this->dashboardModel->getFinancialHistory($days, $usuarioId);
                    break;
                case '90d':
                    $days = 90;
                    $history = $this->dashboardModel->getFinancialHistory($days, $usuarioId);
                    break;
                case 'mes':
                    // Desde el día 1 del mes actual hasta hoy
                    $diasMes = (int)date('j');
                    $history = $this->dashboardModel->getFinancialHistory($diasMes, $usuarioId);
                    break;
                case 'mes_anterior':
                    $days = (int)date('t', strtotime('last month'));
                    $history = $this->dashboardModel->getFinancialHistory($days, $usuarioId);
                    break;
                case '7d':
                default:
                    $days = 7;
                    $history = $this->dashboardModel->getFinancialHistory($days, $usuarioId);
                    break;
            }

            return $this->jsonResponse([
                'success' => true,
                'range' => $range,
                'history' => $history,
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint API para obtener productos con oferta activa
     */
    public function getProductsOnOffer() {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $products = $this->dashboardModel->getProductsOnOffer($limit);
            
            return $this->jsonResponse([
                'success' => true,
                'products' => $products
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}