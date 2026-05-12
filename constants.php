<?php
// c:\xampp\htdocs\multiservicio\constants.php

// Define base directory for the application
define('APP_ROOT', __DIR__ . '/');

// Define paths for data storage (JSON files)
define('JSON_DIR', APP_ROOT . 'json/');

// Define paths for static assets (if needed in PHP)
define('CSS_DIR', APP_ROOT . 'css/');
define('JS_DIR', APP_ROOT . 'js/');
define('IMG_DIR', APP_ROOT . 'img/');

// Define web paths for HTML assets
define('URL_CSS', 'css/');
define('URL_JS', 'js/');

// Other system constants
define('DEFAULT_IVA_RATE', 0.19); // Default IVA rate as a decimal
define('APP_NAME', 'TallerPro');
?>