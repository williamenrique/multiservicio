<?php
/**
 * CONFIGURACIÓN GLOBAL DEL SISTEMA
 */

// 1. Configuración de la Base de Datos (Si no usas .env, cámbialos aquí directamente)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'multiservicio');

// 2. Ruta de la Aplicación (Directorio Interno)
// Esto define la ruta absoluta hasta la carpeta /app
// Ejemplo: /var/www/taller_pro_internos/app
define('APPROOT', dirname(dirname(__FILE__)));

// 3. Ruta URL (Para enlaces y carga de assets en el navegador)
// Cámbialo por tu dominio real cuando subas a producción
// Ejemplo local: http://localhost/taller_pro
// Ejemplo servidor: https://taller-pro.com
define('URLROOT', 'http://tallerv1.test');

// Ruta absoluta para el almacenamiento de datos JSON (Base de datos plana para módulos no migrados)
define('JSON_DIR', APPROOT . '/../public/json/');

// 4. Nombre del Sitio
define('SITENAME', 'Taller Pro');

// 5. Versión del Sistema
define('APPVERSION', '1.0.0');

// 6. Configuración de Entorno (development / production)
// En 'development' se muestran los errores, en 'production' se ocultan por seguridad
define('ENVIRONMENT', 'development');

// 7. Define paths for static assets (if needed in PHP)
define('URL_CSS', URLROOT . '/public/css/');
define('URL_JS', URLROOT . '/public/js/');
define('URL_IMG', URLROOT . '/public/img/');
if (ENVIRONMENT == 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 7. Configuración de Zona Horaria (Crucial para registros de órdenes y facturas)
date_default_timezone_set('America/Caracas'); // Ajusta según tu país
