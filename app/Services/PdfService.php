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
 * 
 * v2.1 (2026-10-09) — P3-12:
 *   • `isRemoteEnabled` = false. Los templates PDF del sistema NO cargan
 *     imágenes por URL, así que deshabilitar esto mejora rendimiento
 *     (evita intentos de resolución DNS) y seguridad (no permite SSRF).
 *   • `chroot` = dirname(APPROOT). Restringe el acceso de Dompdf a archivos
 *     locales al directorio raíz del proyecto. Previene path traversal si
 *     un template malicioso intentara leer /etc/passwd u otro archivo.
 *   • `defaultFont` = 'DejaVu Sans'. Evita warnings cuando un template
 *     usa caracteres acentuados (ñ, á, é) sin fuente específica.
 *   • `tempDir` = ruta interna dentro del proyecto. Evita escribir en
 *     /tmp del sistema (que puede no ser accesible en hosting compartido).
 */
class PdfService {
    private $dompdf;

    public function __construct() {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        // P3-12: sin imágenes remotas (más rápido, más seguro)
        $options->set('isRemoteEnabled', false);
        // P3-12: restringir rutas locales al proyecto
        $options->set('chroot', dirname(APPROOT));
        // P3-12: fuente por defecto (evita warnings con acentos)
        $options->set('defaultFont', 'DejaVu Sans');
        // P3-12: temp dir dentro del proyecto (evita /tmp del sistema)
        $tmpDir = dirname(APPROOT) . '/public_html/uploads/tmp_pdf/';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }
        if (is_dir($tmpDir) && is_writable($tmpDir)) {
            $options->set('tempDir', $tmpDir);
        }

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