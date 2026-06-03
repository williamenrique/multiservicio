<?php
class ControllerInventario extends Controller {
    private $inventarioModel;

    public function __construct() {
        AuthGuard::handle();
        $this->inventarioModel = $this->model('Inventario');
    }

    public function index() {
        RoleGuard::isAdmin();
        $total = $this->inventarioModel->contarTotal();
        $data = [
            'titulo' => 'Control de Inventario',
            'user_role' => $_SESSION['user_role'],
            'total_items' => $total
        ];

        $this->view('inventario/index', $data);
    }

    public function listar() {
        // Detectar búsqueda manual o de DataTables (por compatibilidad)
        $searchValue = $_GET['search']['value'] ?? $_GET['search'] ?? $_GET['q'] ?? null;
        $search = ($searchValue !== '' && $searchValue !== null) ? $searchValue : null;

        // Soporte para paginación manual
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $items = $this->inventarioModel->listar($limit, $offset, $search);
        $total = $this->inventarioModel->contarTotal();
        $totalFiltrados = $search ? $this->inventarioModel->contarFiltrados($search) : $total;

        return $this->jsonResponse([
            'success' => true,
            'data' => $items,
            'total' => $total,
            'totalFiltrados' => $totalFiltrados
        ]);
    }

    /**
     * Muestra el historial de movimientos (Kardex) para un producto específico.
     */
    public function kardex($producto_id = null) {
        if (!$producto_id) {
            redirect('inventario');
        }

        RoleGuard::isAdmin();
        $producto = $this->inventarioModel->obtenerPorId($producto_id);
        if (!$producto) {
            redirect('inventario?error=producto_no_encontrado');
        }

        // Verificar si existe la imagen, de lo contrario enviamos null para que la vista maneje el icono 'package'
        if (empty($producto->imagen) || !file_exists(APPROOT . '/../public/' . $producto->imagen)) {
            $producto->imagen = null;
        }

        $costHistory = $this->inventarioModel->getCostHistory($producto_id);

        $this->view('inventario/kardex', [
            'titulo' => 'Kardex de ' . $producto->nombre,
            'producto' => $producto,
            'costHistory' => $costHistory
        ]);
    }

    /**
     * Endpoint AJAX para alimentar la tabla de movimientos de Kardex
     */
    public function kardexData($producto_id) {
        RoleGuard::isAdmin();
        $limit = $_GET['limit'] ?? 10;
        $offset = $_GET['offset'] ?? 0;
        $search = $_GET['q'] ?? null;

        $res = $this->inventarioModel->obtenerKardexPaginado($producto_id, (int)$limit, (int)$offset, $search);
        return $this->jsonResponse([
            'success' => true,
            'data' => $res['data'],
            'total' => $res['total']
        ]);
    }

    /**
     * Genera un PDF detallado (tipo lista) de un único movimiento de Kardex
     */
    public function imprimirMovimiento($id) {
        RoleGuard::isAdmin();
        $db = new Database();
        $db->query("SELECT k.*, i.nombre as producto_nombre, i.categoria, u.username, s.nombre as usuario_nombre
                          FROM table_kardex k
                          JOIN table_inventario i ON k.producto_id = i.id
                          LEFT JOIN table_usuarios u ON k.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE k.id = :id");
        $db->bind(':id', $id);
        $movimiento = $db->single();

        if (!$movimiento) die("Movimiento no encontrado.");

        $pdfService = new PdfService();
        $pdfService->generarDocumento('kardex_individual', [
            'titulo_documento' => 'Detalle de Movimiento de Inventario',
            'mov' => $movimiento,
            'documento_id' => $movimiento->id
        ], 'Movimiento_Kardex_' . $id . '.pdf');
        exit;
    }

    /**
     * Genera un PDF con todo el historial de Kardex del producto en formato de lista legible
     */
    public function imprimirKardexCompleto($producto_id) {
        RoleGuard::isAdmin();
        $producto = $this->inventarioModel->obtenerPorId($producto_id);
        if (!$producto) die("Producto no encontrado.");

        $db = new Database();
        $db->query("SELECT k.*, s.nombre as usuario_nombre 
                          FROM table_kardex k
                          LEFT JOIN table_usuarios u ON k.usuario_id = u.id
                          LEFT JOIN table_staff s ON u.staff_id = s.id
                          WHERE k.producto_id = :pid ORDER BY k.fecha DESC");
        $db->bind(':pid', $producto_id);
        $movimientos = $db->resultSet();

        $pdfService = new PdfService();
        $pdfService->generarDocumento('kardex_reporte', [
            'titulo_documento' => 'Historial de Kardex',
            'producto' => $producto,
            'movimientos' => $movimientos,
            'documento_id' => $producto->id
        ], 'Kardex_' . $producto_id . '.pdf');
        exit;
    }

    /**
     * Guarda o actualiza un producto del inventario
     */
    public function guardar() {
        RoleGuard::isAdmin();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Detectar si los datos vienen por JSON o por FormData (POST tradicional)
            $json = json_decode(file_get_contents('php://input'), true);
            $input = $json ?? $_POST;

            if (empty($input['nombre']) || !isset($input['precio'])) {
                return $this->jsonResponse(['success' => false, 'mensaje' => 'Nombre y precio son requeridos'], 400);
            }

            try {
                // Procesar subida de imagen si se adjuntó un archivo
                if (isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = dirname(APPROOT) . '/public/img/productos/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                    $fileExtension = strtolower(pathinfo($_FILES['imagen_archivo']['name'], PATHINFO_EXTENSION));
                    $newFileName = 'prod_' . time() . '.' . $fileExtension;
                    $destPath = $uploadDir . $newFileName;

                    if (move_uploaded_file($_FILES['imagen_archivo']['tmp_name'], $destPath)) {
                        // Si es una actualización, intentamos borrar la imagen anterior
                        if (!empty($input['id'])) {
                            $prodActual = $this->inventarioModel->obtenerPorId($input['id']);
                            if ($prodActual && !empty($prodActual->imagen)) {
                                $oldFile = dirname(APPROOT) . '/public/' . $prodActual->imagen;
                                if (file_exists($oldFile)) @unlink($oldFile);
                            }
                        }
                        // Asignamos la nueva ruta para guardar en la base de datos
                        $input['imagen'] = 'img/productos/' . $newFileName;
                    }
                }

                if (!empty($input['id'])) {
                    $res = $this->inventarioModel->actualizar($input);
                    $mensaje = 'Producto actualizado correctamente.';
                } else {
                    $res = $this->inventarioModel->crear($input);
                    $mensaje = 'Producto creado correctamente.';
                }

                return $this->jsonResponse(['success' => $res, 'mensaje' => $mensaje]);
            } catch (Exception $e) {
                return $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
        }
    }

    /**
     * Elimina un producto del inventario
     */
    public function eliminar($id) {
        RoleGuard::isAdmin();
        $res = $this->inventarioModel->eliminar($id);
        return $this->jsonResponse(['success' => $res, 'mensaje' => $res ? 'Producto eliminado' : 'Error al eliminar']);
    }
} 