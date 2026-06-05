<?php
class ControllerInventario extends Controller {
    private $inventarioModel;

    public function __construct() {
        AuthGuard::handle();
        $this->inventarioModel = $this->model('Inventario');
    }

    public function index() {
        // Permitir que Administradores, Mecánicos y Cajeros vean el stock
        RoleGuard::hasAccess(['ADMINISTRADOR', 'MECANICO', 'CAJERO']);
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
                // Normalizar nombre del producto (Siempre en mayúsculas para integridad 2.0)
                if (!empty($input['nombre'])) {
                    $input['nombre'] = mb_strtoupper(trim($input['nombre']), 'UTF-8');
                }

                // Obtener el producto actual si es edición para gestionar el archivo viejo
                $prodActual = !empty($input['id']) ? $this->inventarioModel->obtenerPorId($input['id']) : null;

                // Procesar subida de imagen si se adjuntó un archivo
                if (isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = dirname(APPROOT) . '/public/uploads/inventario/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                    $fileExtension = strtolower(pathinfo($_FILES['imagen_archivo']['name'], PATHINFO_EXTENSION));
                    // Nombre único basado en el tiempo y un hash aleatorio
                    $newFileName = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExtension;
                    $destPath = $uploadDir . $newFileName;

                    if (move_uploaded_file($_FILES['imagen_archivo']['tmp_name'], $destPath)) {
                        // Borrar imagen física anterior si existe y es un archivo local (empieza por uploads/)
                        if ($prodActual && !empty($prodActual->imagen) && strpos($prodActual->imagen, 'uploads/') === 0) {
                            $oldFilePath = dirname(APPROOT) . '/public/' . $prodActual->imagen;
                            if (file_exists($oldFilePath)) @unlink($oldFilePath);
                        }
                        // Asignamos la nueva ruta para guardar en la base de datos
                        $input['imagen'] = 'uploads/inventario/' . $newFileName;
                    }
                } else {
                    // Si no se subió un archivo nuevo por $_FILES
                    if (empty($input['imagen'])) {
                        // Si el campo de texto también está vacío, conservamos la imagen que ya tenía
                        $input['imagen'] = $prodActual->imagen;
                    } else {
                        // Si el usuario pegó una URL o Base64 en el campo de texto:
                        // Verificamos si antes había un archivo físico para borrarlo
                        if ($prodActual && !empty($prodActual->imagen)) {
                            // Si la imagen anterior era local (empezaba con uploads/) 
                            // y la nueva NO lo es (es data: o http), borramos la física.
                            if (strpos($prodActual->imagen, 'uploads/') === 0 && strpos($input['imagen'], 'uploads/') === false) {
                                $oldFilePath = dirname(APPROOT) . '/public/' . $prodActual->imagen;
                                if (file_exists($oldFilePath)) @unlink($oldFilePath);
                            }
                        }
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