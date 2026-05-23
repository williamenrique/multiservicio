<?php

// Función para minificar CSS
function minify_css($css) {
    // Eliminar comentarios
    $css = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!", "", $css);
    // Eliminar espacios en blanco alrededor de selectores, propiedades y valores
    $css = str_replace(array("\r\n", "\r", "\n", "\t", "  ", "    ", "    "), "", $css);
    // Eliminar espacios adicionales
    $css = preg_replace("/\s*([{}])\s*/", "$1", $css); // Elimina espacios antes y después de { }
    $css = preg_replace("/\s*([:;,])\s*/", "$1", $css); // Elimina espacios antes y después de : ; ,
    $css = str_replace(";}", "}", $css); // Elimina ; antes de }
    return $css;
}

// Función para minificar JavaScript (implementación básica)
// Nota: Una minificación JS completa es compleja y requiere herramientas más sofisticadas.
// Esta es una minificación muy básica para reducir el tamaño.
function minify_js($js) {
    // Eliminar comentarios de una sola línea
    $js = preg_replace("~//[^\n]*\n~", "\n", $js);
    // Eliminar comentarios multilínea
    $js = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!", "", $js);
    // Eliminar saltos de línea y tabulaciones
    $js = str_replace(array("\r\n", "\r", "\n", "\t"), "", $js);
    // Eliminar espacios duplicados
    $js = preg_replace("/\s+/", " ", $js);
    // Eliminar espacios alrededor de operadores y símbolos comunes
    $js = preg_replace("/\s*([=\+\-\*\/%&|\^~<>!?:,;{}()\[\]])\s*/", "$1", $js);
    return $js;
}

$css_input_path = 'public/css/styles.css';
$css_output_path = 'public/css/styles.min.css';

echo "Minificando CSS...\n";
if (file_exists($css_input_path)) {
    $css_content = file_get_contents($css_input_path);
    $minified_css = minify_css($css_content);
    file_put_contents($css_output_path, $minified_css);
    echo "CSS minificado guardado en: " . $css_output_path . "\n";
} else {
    echo "El archivo CSS no se encontró: " . $css_input_path . "\n";
}

$js_dir = 'public/js/';
$js_files = scandir($js_dir);
$concatenated_js_content = '';

echo "Minificando y concatenando archivos JavaScript...\n";
foreach ($js_files as $js_file) {
    if (pathinfo($js_file, PATHINFO_EXTENSION) === 'js' && $js_file !== '.' && $js_file !== '..') {
        $input_js_path = $js_dir . $js_file;

        if (file_exists($input_js_path)) {
            $js_content = file_get_contents($input_js_path);
            $minified_js = minify_js($js_content);
            $concatenated_js_content .= $minified_js . ";\n"; // Añadimos un ; para seguridad entre archivos
            echo "Procesado: " . $js_file . "\n";
        } else {
            echo "El archivo JS no se encontró: " . $input_js_path . "\n";
        }
    }
}

$js_output_path = 'public/js/app.min.js';
if (!empty($concatenated_js_content)) {
    file_put_contents($js_output_path, $concatenated_js_content);
    echo "JavaScript concatenado y minificado guardado en: " . $js_output_path . "\n";
} else {
    echo "No se encontraron archivos JavaScript para concatenar.\n";
}

echo "Proceso de minificación y concatenación completado.\n";

?>