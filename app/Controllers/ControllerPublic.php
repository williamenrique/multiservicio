<?php
use chillerlan\QRCode\{QRCode, QROptions};

class ControllerPublic extends Controller {

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

        // URL a la que apuntará el QR
        // Ajustamos a la ruta real del método showVehicleHistoryByQr
        // Asumiendo que 'ControllerPublic' se mapea al segmento 'public' en el router
        $publicHistoryUrl = URLROOT . '/public/showVehicleHistoryByQr/' . $placa;

        // Usamos valores primitivos (strings/integers) para evitar errores de constantes inexistentes
        $options = new QROptions([
            'outputType' => 'gdimage_png', // Nombre técnico interno para el módulo GD
            'eccLevel'   => 1,             // 1 = EccLevel::L
            'scale'      => 8,
            'imageBase64'      => false,   // Queremos el binario puro
            'bgColor'    => [255, 255, 255], // Fondo blanco
            'fgColor'    => [0, 0, 0],     // Primer plano negro
        ]);

        try {
            $qrcode = new QRCode($options);
            $imageData = $qrcode->render($publicHistoryUrl);
            
            // Limpieza absoluta del buffer antes de enviar la imagen
            while (ob_get_level() > 0) ob_end_clean();
            
            header('Content-Type: image/png');
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