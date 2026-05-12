<?php
// c:\xampp\htdocs\multiservicio\api.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite peticiones desde cualquier origen (para desarrollo)
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejar pre-vuelos OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

if ($action === 'init') {
    $files = ['inventory_db', 'sales_db', 'drafts_db', 'clients_db', 'staff_db', 'suppliers_db', 'purchases_db', 'company_db'];
    foreach ($files as $f) {
        $path = __DIR__ . '/json/' . $f . '.json';
        if (!file_exists($path) || filesize($path) === 0) {
            file_put_contents($path, '[]');
        }
    }
    echo json_encode(['success' => true, 'initialized' => $files]);
    exit();
}

$key = $_GET['key'] ?? null; // Nombre del archivo JSON (ej: inventory_db)
$filePath = __DIR__ . '/json/' . $key . '.json';

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
            echo json_encode([]); // Devolver un array vacío si el archivo no existe
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
        file_put_contents($filePath, $data);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Method not allowed']);
        http_response_code(405);
        break;
}
?>