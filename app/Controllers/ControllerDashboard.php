<?php
class ControllerDashboard extends Controller {
    private $dashboardModel;

    public function __construct() {
        AuthGuard::handle(); // Asegurar que solo usuarios logueados accedan
        // Cargamos el modelo centralizado
        $this->dashboardModel = $this->model('Dashboard');
    }

    public function index() {
        $this->view('dashboard/index', ['titulo' => 'Panel de Control']);
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

    /**
     * Maneja las peticiones de datos JSON (reemplaza a api.php)
     */
    public function api() {
        header('Content-Type: application/json');
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? null;
        $key = $_GET['key'] ?? null;
        
        // Sanitizar el nombre del archivo
        $key = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$key);
        $filePath = JSON_DIR . $key . '.json';

        // Acción de inicialización
        if ($action === 'init') {
            if (!is_dir(JSON_DIR)) mkdir(JSON_DIR, 0777, true);
            $files = ['inventory_db', 'sales_db', 'drafts_db', 'clients_db', 'staff_db', 'suppliers_db', 'purchases_db', 'company_db', 'expenses_db', 'users_db'];
            foreach ($files as $f) {
                $path = JSON_DIR . $f . '.json';
                if (!file_exists($path) || filesize($path) === 0) {
                    file_put_contents($path, '[]');
                }
            }
            echo json_encode(['success' => true]);
            exit;
        }

        if (!$key) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing key']);
            exit;
        }

        switch ($method) {
            case 'GET':
                if (file_exists($filePath)) {
                    echo file_get_contents($filePath);
                } else {
                    echo json_encode([]);
                }
                break;

            case 'POST':
            case 'PUT':
                $data = file_get_contents('php://input');
                if (json_decode($data) === null) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON']);
                    exit;
                }
                file_put_contents($filePath, $data);
                echo json_encode(['success' => true]);
                break;

            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
        }
    }
}