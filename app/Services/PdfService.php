<?php
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService {
    private $dompdf;

    public function __construct() {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $this->dompdf = new Dompdf($options);
    }

    public function generarDocumento($view, $data = [], $filename = 'documento.pdf', $stream = true) {
        // Cargamos la empresa para el encabezado global
        $db = new Database();
        $db->query("SELECT * FROM table_company_settings WHERE id = 1");
        $data['empresa'] = $db->single();

        // Extraer variables para que estén disponibles directamente en las vistas (header, footer y templates)
        extract($data);

        // Iniciamos el buffer de salida para capturar el HTML
        ob_start();

        // En el sistema 2.0, la factura maneja su propio layout fijo para Dompdf.
        // Los demás reportes siguen el flujo secuencial tradicional.
        $isFactura = ($view === 'factura');

        if (!$isFactura) {
            require APPROOT . '/Views/pdf/inc/header.php';
        }

        require APPROOT . '/Views/pdf/templates/' . $view . '.php';
        
        if (!$isFactura) {
            require APPROOT . '/Views/pdf/inc/footer.php';
        }
        
        $html = ob_get_clean();

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('letter', 'portrait');
        $this->dompdf->render();
        
        if ($stream) {
            $this->dompdf->stream($filename, ["Attachment" => false]);
            exit;
        } else {
            $output = $this->dompdf->output();
            $tempDir = dirname(APPROOT) . '/public/temp_pdfs/';
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
            
            $filePath = $tempDir . $filename;
            file_put_contents($filePath, $output);
            return 'temp_pdfs/' . $filename;
        }
    }
}