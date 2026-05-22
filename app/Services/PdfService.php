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

    public function generarDocumento($view, $data = [], $filename = 'documento.pdf') {
        // Cargamos la empresa para el encabezado global
        $db = new Database();
        $db->query("SELECT * FROM table_company_settings WHERE id = 1");
        $data['empresa'] = $db->single();

        // Iniciamos el buffer de salida para capturar el HTML
        ob_start();
        
        // 1. Encabezado Estándar
        require APPROOT . '/Views/pdf/inc/header.php';
        
        // 2. Cuerpo dinámico (el template solicitado)
        require APPROOT . '/Views/pdf/templates/' . $view . '.php';
        
        // 3. Pie de página Estándar
        require APPROOT . '/Views/pdf/inc/footer.php';
        
        $html = ob_get_clean();

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('letter', 'portrait');
        $this->dompdf->render();
        
        // Stream al navegador
        $this->dompdf->stream($filename, ["Attachment" => false]);
    }
}