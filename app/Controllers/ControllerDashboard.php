<?php
class ControllerDashboard extends Controller {

    public function __construct() {
        AuthGuard::handle(); // Asegurar que solo usuarios logueados accedan
    }

    public function index() {
        $this->view('dashboard/index', ['titulo' => 'Panel de Control']);
    }

    /**
     * Obtiene estadísticas reales desde la base de datos para el Dashboard
     */
    public function getStats() {
        $db = new Database();
        
        // 1. Contar productos por estado de stock
        $db->query("SELECT 
            SUM(CASE WHEN stock > 5 THEN 1 ELSE 0 END) as ok,
            SUM(CASE WHEN stock <= 5 AND stock > 0 THEN 1 ELSE 0 END) as critico,
            SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as agotado
            FROM table_inventario");
        $inventory = $db->single();

        // 2. Ingresos hoy
        $db->query("SELECT SUM(total) as total FROM table_ventas WHERE DATE(fecha) = CURDATE() AND status = 'COMPLETADO'");
        $ingresosHoy = $db->single()->total ?? 0;

        // 3. Gastos mes actual
        $db->query("SELECT SUM(monto) as total FROM table_gastos WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE())");
        $gastosMes = $db->single()->total ?? 0;

        $this->jsonResponse([
            'inventory' => $inventory,
            'ingresosHoy' => $ingresosHoy,
            'gastosMes' => $gastosMes
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