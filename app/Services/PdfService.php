<?php
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PdfService — Genera documentos PDF con Dompdf.
 *
 * Modos de uso:
 *   1) generarDocumento(..., $stream = true)   → envía el PDF al navegador (no guarda nada).
 *   2) generarBinario($view, $data)            → devuelve el PDF como string binario en memoria.
 *                                                Ideal para adjuntar a correos sin escribir archivos.
 *
 * IMPORTANTE: Este servicio NO escribe archivos temporales en disco.
 * Los PDFs que necesitan persistir o adjuntarse se manejan en memoria.
 */
class PdfService {
    private $dompdf;

    public function __construct() {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $this->dompdf = new Dompdf($options);
    }

    /**
     * Genera el HTML completo del documento (header + template + footer)
     * y lo carga en Dompdf. No renderiza todavía.
     */
    private function prepararDocumento($view, $data = []): void {
        // Cargar la empresa para el encabezado global
        $db = new Database();
        $db->query("SELECT * FROM table_company_settings WHERE id = 1");
        $data['empresa'] = $db->single();

        // Extraer variables para que estén disponibles directamente en las vistas
        extract($data);

        // Iniciamos el buffer de salida para capturar el HTML
        ob_start();

        // En el sistema 2.0, la factura y la garantía manejan su propio layout fijo.
        // Los demás reportes siguen el flujo secuencial tradicional (header + template + footer).
        $isFullLayout = in_array($view, ['factura', 'garantia'], true);

        require APPROOT . '/Views/pdf/templates/' . $view . '.php';

        if (!$isFullLayout) {
            require APPROOT . '/Views/pdf/inc/footer.php';
        }

        $html = ob_get_clean();

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('letter', 'portrait');
        $this->dompdf->render();
    }

    /**
     * Genera un documento PDF y lo devuelve como string binario (en memoria).
     * No escribe archivos en disco. Ideal para adjuntar a correos.
     *
     * @return string  Contenido binario del PDF
     */
    public function generarBinario($view, $data = []): string {
        $this->prepararDocumento($view, $data);
        return $this->dompdf->output();
    }

    /**
     * Genera un documento PDF.
     *
     * @param string $view      Nombre del template (sin extensión)
     * @param array  $data      Variables para el template
     * @param string $filename  Nombre del archivo (solo para Content-Disposition en stream)
     * @param bool   $stream    true  → envía al navegador (default, recomendado)
     *                          false → devuelve el binario como string.
     *                                  Se mantiene por compatibilidad, pero no
     *                                  escribe archivos en disco.
     * @return string|null  Si $stream = false, devuelve el binario. Si $stream = true, hace exit.
     */
    public function generarDocumento($view, $data = [], $filename = 'documento.pdf', $stream = true) {
        $this->prepararDocumento($view, $data);

        if ($stream) {
            // Envía el PDF al navegador directamente. No toca disco.
            $this->dompdf->stream($filename, ["Attachment" => false]);
            exit;
        }

        // Modo "no stream": devolvemos el binario como string.
        // NOTA: ya NO escribimos archivos temporales en disco.
        return $this->dompdf->output();
    }
}