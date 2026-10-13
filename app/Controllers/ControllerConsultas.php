<?php
use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Output\QRGdImagePNG;

/**
 * Controlador para consultas públicas (Historial mediante QR)
 * El nombre 'Consultas' evita conflictos con carpetas del sistema.
 */
class ControllerConsultas extends Controller {

    private $vehiculoModel;
    private $ordenModel;

    public function __construct() {
        // No AuthGuard::handle() aquí, ya que este controlador es para acceso público
        $this->vehiculoModel = $this->model('Vehiculo');
        $this->ordenModel = $this->model('Orden');
    }

    /**
     * Genera y muestra el código QR para el historial público de un vehículo.
     * La URL codificada en el QR apuntará a /public/vehiculo/historial_qr/{placa}
     * @param string $placa La placa del vehículo
     */
    public function generateVehicleQr($placa) {
        // Desactivamos la visualización de errores para esta petición 
        // para evitar que un "Warning" corrompa el binario de la imagen.
        ini_set('display_errors', 0);
        
        $placa = strtoupper(trim($placa));

        // 1. Verificación de dependencias de la carpeta vendor
        if (!class_exists('chillerlan\QRCode\QRCode')) {
            return $this->jsonResponse(['success' => false, 'error' => 'Librería QRCode no encontrada. Ejecute composer install.'], 500);
        }

        // 2. Verificación de requisito técnico del servidor (XAMPP/GD)
        if (!extension_loaded('gd')) {
            return $this->jsonResponse(['success' => false, 'error' => 'La extensión PHP GD no está activa en su servidor.'], 500);
        }

        if (empty($placa)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Placa no proporcionada.']);
            exit;
        }

        // URL que se codifica dentro del QR. 
        // Al escanear, el usuario irá directamente a esta ruta pública.
        $publicHistoryUrl = URLROOT . '/consultas/showVehicleHistoryByQr/' . $placa;

        // Usamos valores primitivos (strings/integers) para evitar errores de constantes inexistentes
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel'       => 1,             // 1 = EccLevel::L
            'scale'          => 8,
            'outputBase64'   => false,         // Intentamos forzar binario
            'bgColor'        => [255, 255, 255], // Fondo blanco
            'moduleValues'   => [0 => [0, 0, 0], 1 => [255, 255, 255]], // Interior/Exterior colores para GD
        ]);

        try {
            $qrcode = new QRCode($options);
            $imageData = $qrcode->render($publicHistoryUrl);
            
            // 1. Limpieza absoluta del buffer para eliminar cualquier espacio en blanco previo
            while (ob_get_level() > 0) ob_end_clean();
            
            // 2. Si la librería devolvió un Data URI (Base64) por error, lo decodificamos
            if (strpos($imageData, 'data:image/png;base64,') === 0) {
                $imageData = base64_decode(str_replace('data:image/png;base64,', '', $imageData));
            }

            // Verificación de los bytes mágicos de PNG para asegurar que es una imagen válida
            if (substr($imageData, 0, 8) !== "\x89PNG\x0d\x0a\x1a\x0a") {
                error_log("Error: La salida del QR no es un PNG válido para placa $placa. Primeros 20 bytes: " . bin2hex(substr($imageData, 0, 20)));
                throw new Exception("La librería no generó una imagen PNG válida.");
            }
            
            
            // 3. Enviamos cabeceras correctas
            header('Content-Type: image/png');
            header('Content-Length: ' . strlen($imageData));
            header('Cache-Control: no-store, no-cache, must-revalidate');
            
            echo $imageData;
            exit;
        } catch (Throwable $e) {
            // Usamos Throwable para capturar tanto Excepciones como Errores de carga de clases
            error_log("Error generando QR para placa $placa: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al generar el código QR.']);
            exit;
        }
    }

    /**
     * Muestra una vista simplificada del historial de un vehículo, accesible públicamente.
     * @param string $placa La placa del vehículo
     */
    public function showVehicleHistoryByQr($placa) {
        $placa = strtoupper(trim($placa));
        if (empty($placa)) {
            $this->view('errores/404', ['titulo' => 'Vehículo no encontrado']);
            exit;
        }

        $vehiculo = $this->vehiculoModel->buscarPorPlaca($placa);
        if (!$vehiculo) {
            $this->view('errores/404', ['titulo' => 'Vehículo no encontrado']);
            exit;
        }

        // Obtener historial de órdenes de servicio (sin datos sensibles del cliente)
        $historial = $this->vehiculoModel->obtenerHistorial($placa);

        // Enriquecer cada registro del historial con su checklist e ítems facturados
        if (!empty($historial)) {
            $facturaModel = $this->model('Facturacion');
            $db = new Database();
            foreach ($historial as &$itemH) {
                // Cargar Checklist
                $itemH->checklist_data = $this->ordenModel->obtenerChecklist($itemH->id);
                
                // Buscar si tiene factura para traer los repuestos/servicios
                $db->query("SELECT id FROM table_facturas WHERE orden_id = :oid AND status != 'ANULADO' ORDER BY id DESC LIMIT 1");
                $db->bind(':oid', $itemH->id);
                $resFac = $db->single();
                
                $itemH->items_facturados = [];
                if ($resFac) {
                    $vDetalle = $facturaModel->obtenerVentaCompleta($resFac->id);
                    // Filtramos datos sensibles del cliente de los ítems facturados
                    $itemH->items_facturados = array_map(function($item) {
                        return (object)[
                            'descripcion' => $item->descripcion,
                            'cantidad' => $item->cantidad,
                            'precio_unitario' => $item->precio_unitario
                        ];
                    }, $vDetalle->items ?? []);
                }
            }
        }

        $data = [
            'titulo' => 'Historial Vehicular: ' . $placa,
            'vehiculo' => $vehiculo,
            'historial' => $historial
        ];

        // Renderizamos una vista pública dedicada
        $this->view('public/vehicle_history_qr', $data);
    }
}