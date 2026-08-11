<?php
/**
 * Controlador de Catálogo Público
 * Muestra repuestos, gestiona carrito público y pedidos.
 * NO requiere autenticación - acceso público.
 */

use App\Services\EmailService;

class ControllerCatalogo extends Controller {

    private $modelCatalogo;

    public function __construct() {
        // NOTA: No se llama a AuthGuard - es público
        $this->modelCatalogo = $this->model('Catalogo');
    }

    /**
     * Página principal del catálogo
     * GET /catalogo
     */
    public function index() {
        $busqueda = $_GET['busqueda'] ?? null;
        $categoria = $_GET['categoria'] ?? null;
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $limit = 12;
        $offset = ($pagina - 1) * $limit;

        $repuestos = $this->modelCatalogo->listarRepuestos($busqueda, $categoria, $limit, $offset);
        $total = $this->modelCatalogo->contarRepuestos($busqueda, $categoria);
        $categorias = $this->modelCatalogo->obtenerCategorias();
        $totalPaginas = max(1, ceil($total / $limit));

        $carritoCount = array_sum($_SESSION['carrito_publico'] ?? []);

        $data = [
            'titulo' => 'Catálogo de Repuestos',
            'repuestos' => $repuestos,
            'categorias' => $categorias,
            'busqueda' => $busqueda,
            'categoriaSeleccionada' => $categoria,
            'paginaActual' => $pagina,
            'totalPaginas' => $totalPaginas,
            'total' => $total,
            'carrito_count' => $carritoCount
        ];

        $this->view('public/catalogo/index', $data);
    }

    /**
     * Detalle de un repuesto
     * GET /catalogo/detalle/{id}
     */
    public function detalle($id = null) {
        if (!$id) {
            redirect('catalogo');
        }

        $repuesto = $this->modelCatalogo->obtenerRepuesto($id);
        if (!$repuesto) {
            $this->view('errores/404', ['titulo' => 'Producto no encontrado']);
            return;
        }

        $carritoCount = array_sum($_SESSION['carrito_publico'] ?? []);

        $data = [
            'titulo' => $repuesto->nombre,
            'repuesto' => $repuesto,
            'carrito_count' => $carritoCount
        ];

        $this->view('public/catalogo/detalle', $data);
    }

    /**
     * Ver carrito
     * GET /catalogo/carrito
     */
    public function carrito() {
        $carrito = $_SESSION['carrito_publico'] ?? [];
        $items = [];
        $total = 0;

        if (!empty($carrito)) {
            foreach ($carrito as $id => $cantidad) {
                $repuesto = $this->modelCatalogo->obtenerRepuesto($id);
                if ($repuesto) {
                    $subtotal = $repuesto->precio * $cantidad;
                    $total += $subtotal;
                    $items[] = [
                        'id' => $repuesto->id,
                        'nombre' => $repuesto->nombre,
                        'codigo' => $repuesto->codigo,
                        'precio' => $repuesto->precio,
                        'cantidad' => $cantidad,
                        'subtotal' => $subtotal,
                        'imagen' => $repuesto->imagen,
                        'stock' => $repuesto->stock
                    ];
                }
            }
        }

        $carritoCount = array_sum($_SESSION['carrito_publico'] ?? []);

        $data = [
            'titulo' => 'Carrito de Compras',
            'items' => $items,
            'total' => $total,
            'carrito_count' => $carritoCount
        ];

        $this->view('public/catalogo/carrito', $data);
    }

    /**
     * Agregar al carrito (AJAX)
     * POST /catalogo/agregar-carrito
     */
    public function agregarCarrito() {
        $id = (int)($_POST['producto_id'] ?? $_POST['id'] ?? 0);
        $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));

        if (!$id) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'Producto no válido.']);
            return;
        }

        $repuesto = $this->modelCatalogo->obtenerRepuesto($id);
        if (!$repuesto) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'Producto no encontrado.']);
            return;
        }

        if ($repuesto->stock < 1) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'Producto sin stock disponible.']);
            return;
        }

        if (!isset($_SESSION['carrito_publico'])) {
            $_SESSION['carrito_publico'] = [];
        }

        $carrito = &$_SESSION['carrito_publico'];
        $cantidadActual = $carrito[$id] ?? 0;
        $nuevaCantidad = $cantidadActual + $cantidad;

        // Validar que no exceda el stock disponible
        if ($nuevaCantidad > $repuesto->stock) {
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'No hay suficiente stock. Solo quedan ' . $repuesto->stock . ' unidades disponibles.'
            ]);
            return;
        }

        $carrito[$id] = $nuevaCantidad;

        $totalItems = array_sum($carrito);

        $this->jsonResponse([
            'success' => true,
            'mensaje' => 'Producto agregado al carrito.',
            'total_items' => $totalItems
        ]);
    }

    /**
     * Actualizar cantidad en carrito (AJAX)
     * POST /catalogo/actualizar-carrito
     */
    public function actualizarCarrito() {
        $id = (int)($_POST['id'] ?? 0);
        $cantidad = max(0, (int)($_POST['cantidad'] ?? 0));

        if (!isset($_SESSION['carrito_publico'])) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'Carrito vacío.']);
            return;
        }

        // Validar stock antes de actualizar
        if ($cantidad > 0) {
            $repuesto = $this->modelCatalogo->obtenerRepuesto($id);
            if (!$repuesto) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'Producto no encontrado.']);
                return;
            }
            if ($cantidad > $repuesto->stock) {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'No hay suficiente stock. Solo quedan ' . $repuesto->stock . ' unidades disponibles.'
                ]);
                return;
            }
        }

        if ($cantidad <= 0) {
            unset($_SESSION['carrito_publico'][$id]);
        } else {
            $_SESSION['carrito_publico'][$id] = $cantidad;
        }

        $totalItems = array_sum($_SESSION['carrito_publico'] ?? []);

        // Calcular subtotal del item y totales del carrito
        $subtotalItem = 0;
        $subtotalCarrito = 0;
        if ($cantidad > 0) {
            $repuesto = $this->modelCatalogo->buscarPorId($id);
            if ($repuesto) {
                $subtotalItem = $repuesto->precio * $cantidad;
            }
        }

        // Calcular totales del carrito completo
        $ids = array_keys($_SESSION['carrito_publico'] ?? []);
        if (!empty($ids)) {
            $repuestos = $this->modelCatalogo->buscarPorIds($ids);
            foreach ($repuestos as $r) {
                $qty = $_SESSION['carrito_publico'][$r->id] ?? 0;
                $subtotalCarrito += $r->precio * $qty;
            }
        }
        $iva = 0; // IVA deshabilitado temporalmente
        $total = $subtotalCarrito + $iva;

        $this->jsonResponse([
            'success' => true,
            'total_items' => $totalItems,
            'subtotal_item' => number_format($subtotalItem, 2, '.', ''),
            'subtotal' => number_format($subtotalCarrito, 2, '.', ''),
            'iva' => number_format($iva, 2, '.', ''),
            'total' => number_format($total, 2, '.', '')
        ]);
    }

    /**
     * Eliminar del carrito (AJAX)
     * POST /catalogo/eliminar-carrito
     */
    public function eliminarCarrito() {
        $id = (int)($_POST['id'] ?? 0);

        if (isset($_SESSION['carrito_publico'][$id])) {
            unset($_SESSION['carrito_publico'][$id]);
        }

        $totalItems = array_sum($_SESSION['carrito_publico'] ?? []);

        // Calcular totales del carrito completo
        $subtotalCarrito = 0;
        $ids = array_keys($_SESSION['carrito_publico'] ?? []);
        if (!empty($ids)) {
            $repuestos = $this->modelCatalogo->buscarPorIds($ids);
            foreach ($repuestos as $r) {
                $qty = $_SESSION['carrito_publico'][$r->id] ?? 0;
                $subtotalCarrito += $r->precio * $qty;
            }
        }
        $iva = 0; // IVA deshabilitado temporalmente
        $total = $subtotalCarrito + $iva;

        $this->jsonResponse([
            'success' => true,
            'total_items' => $totalItems,
            'subtotal' => number_format($subtotalCarrito, 2, '.', ''),
            'iva' => number_format($iva, 2, '.', ''),
            'total' => number_format($total, 2, '.', '')
        ]);
    }

    /**
     * Limpiar todo el carrito (AJAX)
     * POST /catalogo/limpiar-carrito
     */
    public function limpiarCarrito() {
        $_SESSION['carrito_publico'] = [];

        $this->jsonResponse([
            'success' => true,
            'total_items' => 0,
            'subtotal' => '0.00',
            'iva' => '0.00',
            'total' => '0.00'
        ]);
    }

    /**
     * Obtener conteo del carrito (AJAX)
     * GET /catalogo/contar-carrito
     */
    public function contarCarrito() {
        $totalItems = array_sum($_SESSION['carrito_publico'] ?? []);
        $this->jsonResponse(['total_items' => $totalItems]);
    }

    /**
     * Página de checkout (formulario de datos del cliente)
     * GET /catalogo/checkout
     */
    public function checkout() {
        $carrito = $_SESSION['carrito_publico'] ?? [];

        if (empty($carrito)) {
            redirect('catalogo/carrito');
        }

        $items = [];
        $total = 0;

        foreach ($carrito as $id => $cantidad) {
            $repuesto = $this->modelCatalogo->obtenerRepuesto($id);
            if ($repuesto) {
                $subtotal = $repuesto->precio * $cantidad;
                $total += $subtotal;
                $items[] = [
                    'id' => $repuesto->id,
                    'nombre' => $repuesto->nombre,
                    'codigo' => $repuesto->codigo,
                    'precio' => $repuesto->precio,
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotal
                ];
            }
        }

        $data = [
            'titulo' => 'Finalizar Pedido',
            'items' => $items,
            'total' => $total,
            'errores' => $_SESSION['checkout_errores'] ?? [],
            'formData' => $_SESSION['checkout_data'] ?? []
        ];

        // Limpiar datos de sesión después de pasarlos a la vista
        unset($_SESSION['checkout_errores'], $_SESSION['checkout_data']);

        $this->view('public/catalogo/checkout', $data);
    }

    /**
     * Procesar pedido (POST)
     * POST /catalogo/procesar-pedido
     * Integrado con BillingService para generar factura, descontar stock y registrar transacción.
     */
    public function procesarPedido() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('catalogo/carrito');
        }

        $carrito = $_SESSION['carrito_publico'] ?? [];

        if (empty($carrito)) {
            redirect('catalogo/carrito');
        }

        // Validar datos del cliente
        $nombre = trim($_POST['nombre'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $notas = trim($_POST['notas'] ?? '');

        $errores = [];

        if (empty($nombre)) $errores[] = 'El nombre es obligatorio.';
        if (empty($cedula)) $errores[] = 'La cédula/NIT es obligatoria.';
        if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'Correo electrónico no válido.';
        if (empty($telefono)) $errores[] = 'El teléfono es obligatorio.';

        if (!empty($errores)) {
            $_SESSION['checkout_errores'] = $errores;
            $_SESSION['checkout_data'] = $_POST;
            redirect('catalogo/checkout');
        }

        // Preparar items del carrito con todos los datos necesarios para BillingService
        $items = [];
        $itemsPedido = []; // Para el registro en pedidos_clientes (legacy)
        foreach ($carrito as $id => $cantidad) {
            $repuesto = $this->modelCatalogo->obtenerRepuesto($id);
            if ($repuesto && $repuesto->stock >= $cantidad) {
                $items[] = [
                    'id'          => $repuesto->id,
                    'nombre'      => $repuesto->nombre,
                    'precio'      => $repuesto->precio,
                    'cantidad'    => $cantidad,
                    'tipo'        => 'PRODUCTO',
                    'costo_promedio' => $repuesto->costo_promedio ?? $repuesto->ultimo_costo ?? 0
                ];
                $itemsPedido[] = [
                    'id'       => $repuesto->id,
                    'precio'   => $repuesto->precio,
                    'cantidad' => $cantidad
                ];
            }
        }

        if (empty($items)) {
            $_SESSION['checkout_errores'] = ['Los productos en tu carrito ya no están disponibles.'];
            redirect('catalogo/carrito');
        }

        try {
            // --- Find-or-create cliente en table_clientes ---
            $clienteModel = $this->model('Cliente');
            $clienteId = mb_strtoupper($cedula, 'UTF-8');
            $clienteExistente = $clienteModel->obtenerPorId($clienteId);

            if (!$clienteExistente) {
                $clienteModel->crear([
                    'id'        => $clienteId,
                    'nombre'    => $nombre,
                    'email'     => $correo,
                    'telefono'  => $telefono,
                    'direccion' => $direccion
                ]);
            }

            // --- 2. Construir datos para BillingService ---
            $datosFactura = [
                'cliente_id'         => $clienteId,
                'orden_id'           => null,
                'placa'              => null,
                'modelo_vehiculo'    => null,
                'items'              => $items,
                'pago_efectivo'      => 0,
                'pago_transferencia' => 0,
                'tasa_iva'           => 19,
                'aplicar_iva'        => false, // IVA deshabilitado para catálogo
                'origen'             => 'CATALOGO',
                'observaciones'      => 'VENTA CATÁLOGO PÚBLICO'
            ];

            // Calcular total y asignar como pago en efectivo (venta completada)
            $totalVenta = 0;
            foreach ($items as $it) {
                $totalVenta += $it['precio'] * $it['cantidad'];
            }
            $datosFactura['pago_efectivo'] = $totalVenta;

            // --- 3. Procesar venta completa con BillingService ---
            require_once APPROOT . '/Services/BillingService.php';
            $billingService = new BillingService();
            
            // Buscar un usuario administrador activo para asociar la venta
            $dbCheck = new Database();
            $dbCheck->query("SELECT u.id FROM table_usuarios u 
                             INNER JOIN table_roles r ON u.role_id = r.id 
                             WHERE r.nombre_rol = 'ADMINISTRADOR' AND u.estado = 'ACTIVO' 
                             LIMIT 1");
            $adminUser = $dbCheck->single();
            if (!$adminUser) {
                // Fallback: cualquier usuario activo
                $dbCheck->query("SELECT id FROM table_usuarios WHERE estado = 'ACTIVO' LIMIT 1");
                $adminUser = $dbCheck->single();
            }
            if (!$adminUser) {
                throw new Exception("No hay usuarios activos en el sistema. Contacte al administrador.");
            }
            $usuarioSistema = $adminUser->id;
            
            $ventaId = $billingService->procesarVentaCompleta($datosFactura, $usuarioSistema);

            // --- 4. Crear registro en pedidos_clientes para tracking (legacy) ---
            $datosCliente = [
                'nombre'    => $nombre,
                'cedula'    => $cedula,
                'correo'    => $correo,
                'telefono'  => $telefono,
                'direccion' => $direccion,
                'notas'     => $notas
            ];
            $pedidoId = $this->modelCatalogo->crearPedido($datosCliente, $itemsPedido);

            // --- 5. Preparar datos para notificaciones y enviar email ---
            $facturaModel = $this->model('Facturacion');
            $ventaCompleta = $facturaModel->obtenerVentaCompleta($ventaId);
            $datosEmail = [
                'cliente_nombre'    => $nombre,
                'cliente_email'     => $correo,
                'cliente_telefono'  => $telefono,
                'cliente_cedula'    => $cedula,
                'cliente_direccion' => $direccion,
                'venta_formateado'  => $ventaCompleta->id_formateado ?? 'FAC-' . str_pad($ventaId, 3, '0', STR_PAD_LEFT),
                'venta_id'          => $ventaId,
                'id_formateado'     => 'PED-' . str_pad($pedidoId, 3, '0', STR_PAD_LEFT),
                'fecha'             => date('d/m/Y h:i A'),
                'items'             => $ventaCompleta->items ?? $items,
                'subtotal'          => $ventaCompleta->subtotal ?? $totalVenta,
                'iva'               => $ventaCompleta->iva ?? 0,
                'iva_tasa'          => 19,
                'total'             => $ventaCompleta->total ?? $totalVenta,
            ];

            try {
                $emailService = new EmailService();
                $emailService->notificarPedidoCatalogo($datosEmail);
            } catch (Exception $emailEx) {
                error_log("ERROR EMAIL CATÁLOGO: " . $emailEx->getMessage());
                // No interrumpir el flujo si falla el email
            }

            // --- 6. Enviar notificación por WhatsApp al ADMIN ---
            try {
                $whatsappService = new \App\Services\WhatsAppService();
                $resultadoWhatsapp = $whatsappService->notificarPedidoCatalogoAdmin($datosEmail);
                if (!$resultadoWhatsapp['success']) {
                    $_SESSION['whatsapp_warning'] = 'El pedido se registró correctamente, pero el servidor de WhatsApp no está disponible en este momento. Te notificaremos por correo.';
                }
            } catch (\Throwable $whatsappEx) {
                error_log("ERROR WHATSAPP CATÁLOGO: " . $whatsappEx->getMessage());
                $_SESSION['whatsapp_warning'] = 'El pedido se registró correctamente, pero el servidor de WhatsApp no está disponible en este momento. Te notificaremos por correo.';
            }

            // Limpiar carrito
            unset($_SESSION['carrito_publico']);
            unset($_SESSION['checkout_data']);

            // Redirigir a confirmación con ID de factura
            redirect('catalogo/confirmacion/' . $ventaId);
        } catch (Exception $e) {
            error_log("ERROR CATÁLOGO: " . $e->getMessage());
            $_SESSION['checkout_errores'] = ['Error al procesar el pedido: ' . $e->getMessage()];
            $_SESSION['checkout_data'] = $_POST;
            redirect('catalogo/checkout');
        }
    }

    /**
     * Confirmación de pedido
     * GET /catalogo/confirmacion/{id}
     * Ahora recibe el ID de factura (table_facturas) y muestra los datos de la venta.
     */
    public function confirmacion($id = null) {
        if (!$id) {
            redirect('catalogo');
        }

        // Recoger warning de WhatsApp si existe
        $whatsappWarning = $_SESSION['whatsapp_warning'] ?? null;
        unset($_SESSION['whatsapp_warning']);

        // Intentar obtener la factura primero
        $facturaModel = $this->model('Facturacion');
        $venta = $facturaModel->obtenerVentaCompleta($id);

        if ($venta) {
            // Es una factura - mostrar datos de factura
            $data = [
                'titulo'           => 'Pedido Confirmado',
                'venta'            => $venta,
                'pedido'           => null,
                'detalles'         => $venta->items ?? [],
                'whatsapp_warning' => $whatsappWarning
            ];
            $this->view('public/catalogo/confirmacion', $data);
            return;
        }

        // Fallback: intentar como pedido legacy
        $pedido = $this->modelCatalogo->obtenerPedido($id);
        if (!$pedido) {
            $this->view('errores/404', ['titulo' => 'Pedido no encontrado']);
            return;
        }

        $detalles = $this->modelCatalogo->obtenerDetallesPedido($id);

        $data = [
            'titulo'           => 'Pedido Confirmado',
            'venta'            => null,
            'pedido'           => $pedido,
            'detalles'         => $detalles,
            'whatsapp_warning' => $whatsappWarning
        ];

        $this->view('public/catalogo/confirmacion', $data);
    }

    // ============================================================
    // MÉTODOS PARA STAFF (GESTIÓN DE PEDIDOS PÚBLICOS)
    // ============================================================

    /**
     * Lista de pedidos pendientes (solo staff autenticado)
     * GET /catalogo/pedidos-pendientes
     */
    public function pedidosPendientes() {
        // Verificar autenticación
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }

        $pedidos = $this->modelCatalogo->listarPedidosPendientes();

        $data = [
            'titulo' => 'Pedidos Pendientes',
            'pedidos' => $pedidos
        ];

        $this->view('catalogo/pedidos_pendientes', $data);
    }

    /**
     * Ver detalle de un pedido (staff)
     * GET /catalogo/ver-pedido/{id}
     */
    public function verPedido($id = null) {
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }

        if (!$id) {
            redirect('catalogo/pedidos-pendientes');
        }

        $pedido = $this->modelCatalogo->obtenerPedido($id);
        if (!$pedido) {
            $this->view('errores/404', ['titulo' => 'Pedido no encontrado']);
            return;
        }

        $detalles = $this->modelCatalogo->obtenerDetallesPedido($id);

        $empresa = $this->model('Empresa')->obtenerConfiguracion();

        $data = [
            'titulo' => 'Pedido #' . $id,
            'pedido' => $pedido,
            'detalles' => $detalles,
            'iva_defecto' => $empresa->iva ?? 0
        ];

        $this->view('catalogo/ver_pedido', $data);
    }

    /**
     * Procesar pedido (staff) - descuenta inventario
     * POST /catalogo/procesar-pedido-staff
     */
    public function procesarPedidoStaff() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'No autorizado.']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }

        $pedidoId = (int)($_POST['pedido_id'] ?? 0);

        if (!$pedidoId) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'ID de pedido no válido.']);
            return;
        }

        // Leer preferencia de IVA enviada desde el toggle de la vista
        $aplicarIva = isset($_POST['aplicar_iva']) && $_POST['aplicar_iva'] === '1';

        try {
            // Si se activa IVA, recalcular y persistir totales del pedido antes de procesar
            if ($aplicarIva) {
                $empresa = $this->model('Empresa')->obtenerConfiguracion();
                $tasaIva = (float)($empresa->iva ?? 0);
                $this->modelCatalogo->actualizarIvaPedido($pedidoId, true, $tasaIva);
            }

            $this->modelCatalogo->procesarPedido($pedidoId, $_SESSION['user_id']);

            // Enviar correo de notificación al cliente
            try {
                $pedido = $this->modelCatalogo->obtenerPedido($pedidoId);
                $detalles = $this->modelCatalogo->obtenerDetallesPedido($pedidoId);

                if ($pedido && !empty($pedido->correo)) {
                    $emailService = new EmailService();
                    $emailService->notificarPedidoProcesadoCliente([
                        'cliente_nombre'  => $pedido->nombre_cliente,
                        'cliente_email'   => $pedido->correo,
                        'id_formateado'   => 'PED-' . str_pad($pedido->id, 6, '0', STR_PAD_LEFT),
                        'fecha'           => date('d/m/Y h:i A'),
                        'items'           => $detalles,
                        'subtotal'        => $pedido->subtotal,
                        'total'           => $pedido->total,
                    ]);
                }
            } catch (\Exception $e) {
                error_log("Error al enviar email de pedido procesado: " . $e->getMessage());
            }

            $this->jsonResponse(['success' => true, 'mensaje' => 'Pedido procesado y stock actualizado.']);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()]);
        }
    }

    /**
     * Cancelar pedido (staff)
     * POST /catalogo/cancelar-pedido-staff
     */
    public function cancelarPedidoStaff() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'No autorizado.']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }

        $pedidoId = (int)($_POST['pedido_id'] ?? 0);

        if (!$pedidoId) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'ID de pedido no válido.']);
            return;
        }

        try {
            $this->modelCatalogo->cambiarEstadoPedido($pedidoId, 'CANCELADO');
            $this->jsonResponse(['success' => true, 'mensaje' => 'Pedido cancelado.']);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()]);
        }
    }

    /**
     * Lista de pedidos procesados (staff autenticado)
     * GET /catalogo/pedidos-procesados
     */
    public function pedidosProcesados() {
        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }

        $pedidos = $this->modelCatalogo->listarPedidosProcesados();

        $data = [
            'titulo' => 'Pedidos Procesados',
            'pedidos' => $pedidos
        ];

        $this->view('catalogo/pedidos_procesados', $data);
    }

    /**
     * API: Obtener conteo de pedidos pendientes para notificaciones (badge en header)
     * GET /catalogo/notificaciones-pedidos
     */
    public function notificacionesPedidos() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'No autorizado.']);
            return;
        }

        $db = new Database();
        $db->query("SELECT p.id, p.nombre_cliente, p.telefono, p.total, p.fecha_pedido,
                           COUNT(pd.id) as total_items
                    FROM pedidos_clientes p
                    LEFT JOIN pedido_detalles pd ON p.id = pd.pedido_id
                    WHERE p.estado = 'PENDIENTE'
                    GROUP BY p.id
                    ORDER BY p.fecha_pedido DESC");

        $pedidos = $db->resultSet();

        $this->jsonResponse([
            'success' => true,
            'total' => count($pedidos),
            'data' => $pedidos
        ]);
    }
}