<?php
/**
 * API de persistencia JSON (Puente temporal para desarrollo)
 * Ubicación: public/api.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Definimos la carpeta de datos relativa a este archivo
define('JSON_DIR', __DIR__ . '/data/');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

// Inicialización de la "base de datos"
if ($action === 'init') {
    if (!is_dir(JSON_DIR)) {
        mkdir(JSON_DIR, 0777, true);
    }

    $files = ['inventory_db', 'sales_db', 'drafts_db', 'clients_db', 'staff_db', 'suppliers_db', 'purchases_db', 'company_db', 'expenses_db', 'users_db'];
    foreach ($files as $f) {
        $path = JSON_DIR . $f . '.json';
        if (!file_exists($path) || filesize($path) === 0) {
            file_put_contents($path, '[]');
        }
    }
    echo json_encode(['success' => true, 'initialized' => $files]);
    exit();
}

$key = $_GET['key'] ?? null;
$key = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$key);
$filePath = JSON_DIR . $key . '.json';

if (!$key) {
    echo json_encode(['error' => 'Missing key parameter']);
    http_response_code(400);
    exit();
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
        if (json_decode($data) === null && json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['error' => 'Invalid JSON data']);
            http_response_code(400);
            exit();
        }
        if (!is_dir(JSON_DIR)) {
            mkdir(JSON_DIR, 0777, true);
        }
        file_put_contents($filePath, $data);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Method not allowed']);
        http_response_code(405);
        break;
}