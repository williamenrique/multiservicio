<?php
/**
 * VISUALIZADOR DE ESTRUCTURA DE ARCHIVOS
 * BusYaracuy - Muestra todos los archivos y carpetas con iconos
 * Ejecutar desde: http://multiservicio.test/estructura.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// CONFIGURACIÓN
// ============================================
define('MAX_SCAN_DEPTH', 50); // Límite de profundidad para evitar bucles infinitos

// Permitir ruta personalizada vía GET (?path=...)
$customPath = isset($_GET['path']) ? trim($_GET['path']) : '';
if ($customPath !== '' && is_dir($customPath)) {
    $rootPath = realpath($customPath);
} elseif ($customPath !== '' && is_dir(__DIR__ . DIRECTORY_SEPARATOR . $customPath)) {
    $rootPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $customPath);
} else {
    // Como este archivo está dentro de public_html/, subimos un nivel para escanear toda la raíz del proyecto
    $rootPath = dirname(__DIR__);
}

$excludeDirs = ['.git', 'vendor', 'node_modules', 'cache', 'tmp', '.idea', '.vscode'];
$excludeFiles = ['.env', '.gitignore', '.htaccess', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json'];

// ============================================
// FUNCIONES
// ============================================

/**
 * Obtener icono según tipo de archivo
 */
function getIcon($name, $isDir = false)
{
    if ($isDir) {
        return '📁';
    }
    
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    
    $icons = [
        'php' => '🐘',
        'html' => '🌐',
        'css' => '🎨',
        'js' => '📜',
        'json' => '📋',
        'xml' => '📄',
        'sql' => '🗄️',
        'txt' => '📝',
        'md' => '📖',
        'log' => '📊',
        'png' => '🖼️',
        'jpg' => '🖼️',
        'jpeg' => '🖼️',
        'gif' => '🖼️',
        'svg' => '🖼️',
        'ico' => '🖼️',
        'pdf' => '📕',
        'doc' => '📘',
        'docx' => '📘',
        'xls' => '📗',
        'xlsx' => '📗',
        'ppt' => '📙',
        'pptx' => '📙',
        'zip' => '📦',
        'rar' => '📦',
        'tar' => '📦',
        'gz' => '📦',
        'exe' => '⚙️',
        'sh' => '💻',
        'bat' => '💻',
        'yml' => '📋',
        'yaml' => '📋',
        'ini' => '⚙️',
        'conf' => '⚙️',
        'htaccess' => '🔒',
        'env' => '🔐',
        'lock' => '🔒',
    ];
    
    return $icons[$ext] ?? '📄';
}

/**
 * Obtener color según tipo de archivo (para HTML)
 */
function getColor($name, $isDir = false)
{
    if ($isDir) {
        return '#2c3e50';
    }
    
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    
    $colors = [
        'php' => '#777BB4',
        'html' => '#E34F26',
        'css' => '#1572B6',
        'js' => '#F7DF1E',
        'json' => '#000000',
        'sql' => '#4479A1',
        'md' => '#083FA1',
        'log' => '#FF6B6B',
        'pdf' => '#FF0000',
        'zip' => '#FF8C00',
        'sh' => '#4EAA25',
        'env' => '#FF6B6B',
    ];
    
    return $colors[$ext] ?? '#333333';
}

/**
 * Escanear directorio recursivamente con manejo de errores
 */
function scanDirectory($dir, $indent = 0, $excludeDirs = [], $excludeFiles = [], $depth = 0)
{
    // Límite de profundidad para evitar bucles infinitos
    if ($depth > MAX_SCAN_DEPTH) {
        return [['name' => '⚠️ Límite de profundidad alcanzado', 'path' => $dir, 'isDir' => false, 'size' => 0, 'modified' => '', 'indent' => $indent, 'children' => []]];
    }
    
    // Verificar que el directorio existe y es legible
    if (!is_dir($dir) || !is_readable($dir)) {
        return [['name' => '⚠️ Sin acceso: ' . basename($dir), 'path' => $dir, 'isDir' => false, 'size' => 0, 'modified' => '', 'indent' => $indent, 'children' => []]];
    }
    
    // Suprimir errores con @ para evitar warnings en directorios problemáticos
    $items = @scandir($dir);
    if ($items === false) {
        return [['name' => '⚠️ Error al leer: ' . basename($dir), 'path' => $dir, 'isDir' => false, 'size' => 0, 'modified' => '', 'indent' => $indent, 'children' => []]];
    }
    
    $result = [];
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        
        // Verificar si es un enlace simbólico para evitar seguir loops
        if (is_link($fullPath)) {
            $realPath = @realpath($fullPath);
            // Si el enlace apunta a un directorio ya escaneado, lo saltamos
            if ($realPath !== false && is_dir($realPath) && strpos($realPath, realpath($dir)) === 0) {
                continue;
            }
        }
        
        $isDir = @is_dir($fullPath);
        
        // Excluir directorios y archivos
        if ($isDir && in_array($item, $excludeDirs)) {
            continue;
        }
        if (!$isDir && in_array($item, $excludeFiles)) {
            continue;
        }
        
        $size = null;
        $modified = '';
        if (!$isDir && @is_file($fullPath)) {
            $size = @filesize($fullPath);
            $mtime = @filemtime($fullPath);
            $modified = $mtime ? date('Y-m-d H:i:s', $mtime) : '';
        } elseif ($isDir) {
            $mtime = @filemtime($fullPath);
            $modified = $mtime ? date('Y-m-d H:i:s', $mtime) : '';
        }
        
        $result[] = [
            'name' => $item,
            'path' => $fullPath,
            'isDir' => $isDir,
            'size' => $size,
            'modified' => $modified,
            'indent' => $indent,
            'children' => $isDir ? scanDirectory($fullPath, $indent + 1, $excludeDirs, $excludeFiles, $depth + 1) : []
        ];
    }
    
    // Ordenar: directorios primero, luego archivos alfabéticamente
    usort($result, function($a, $b) {
        if ($a['isDir'] && !$b['isDir']) return -1;
        if (!$a['isDir'] && $b['isDir']) return 1;
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $result;
}

/**
 * Formatear tamaño de archivo
 */
function formatSize($bytes)
{
    if ($bytes === null) return '';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 1) . ' ' . $units[$i];
}

/**
 * Contar archivos por extensión
 */
function countExtensions($items, &$stats = [])
{
    foreach ($items as $item) {
        if ($item['isDir']) {
            countExtensions($item['children'], $stats);
        } else {
            $ext = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
            if (empty($ext)) $ext = 'sin_extensión';
            $stats[$ext] = ($stats[$ext] ?? 0) + 1;
        }
    }
    return $stats;
}

/**
 * Contar total de archivos y directorios
 */
function countItems($items, &$counts = ['files' => 0, 'dirs' => 0])
{
    foreach ($items as $item) {
        if ($item['isDir']) {
            $counts['dirs']++;
            countItems($item['children'], $counts);
        } else {
            $counts['files']++;
        }
    }
    return $counts;
}

/**
 * Generar estructura en texto plano (para exportar)
 */
function generateTextTree($items, $level = 0, &$output = '')
{
    foreach ($items as $item) {
        $indent = str_repeat('  ', $level);
        $prefix = $item['isDir'] ? '📁 ' : '📄 ';
        
        $output .= $indent . $prefix . $item['name'];
        
        if (!$item['isDir'] && $item['size'] !== null) {
            $output .= ' (' . formatSize($item['size']) . ')';
        }
        
        $output .= "\n";
        
        if ($item['isDir'] && !empty($item['children'])) {
            generateTextTree($item['children'], $level + 1, $output);
        }
    }
    return $output;
}

// ============================================
// PROCESAR DATOS
// ============================================
$structure = scanDirectory($rootPath, 0, $excludeDirs, $excludeFiles);
$extStats = countExtensions($structure);
$itemCounts = countItems($structure);

// Calcular tamaño total
$totalSize = 0;
foreach ($structure as $item) {
    if (!$item['isDir']) {
        $totalSize += $item['size'];
    }
}

// ============================================
// MANEJAR EXPORTACIÓN
// ============================================
$exportAction = $_GET['action'] ?? '';
if ($exportAction === 'export_txt') {
    // Generar el contenido del archivo
    $header = "============================================\n";
    $header .= "ESTRUCTURA DE ARCHIVOS - BusYaracuy\n";
    $header .= "============================================\n";
    $header .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $header .= "Raíz: " . $rootPath . "\n";
    $header .= "Total Items: " . ($itemCounts['files'] + $itemCounts['dirs']) . "\n";
    $header .= "Directorios: " . $itemCounts['dirs'] . "\n";
    $header .= "Archivos: " . $itemCounts['files'] . "\n";
    $header .= "Tamaño Total: " . formatSize($totalSize) . "\n";
    $header .= "============================================\n\n";
    
    $treeContent = generateTextTree($structure);
    $fullContent = $header . $treeContent;
    
    // Configurar headers para descarga
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="estructura_busyaracuy_' . date('Y-m-d_H-i-s') . '.txt"');
    header('Content-Length: ' . strlen($fullContent));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $fullContent;
    exit;
}

// ============================================
// HTML
// ============================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estructura de Archivos - BusYaracuy</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            padding: 30px;
            color: #2d3436;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .header {
            border-bottom: 2px solid #dfe6e9;
            padding-bottom: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header-left h1 {
            font-size: 28px;
            color: #2d3436;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-left h1 span {
            background: #0984e3;
            color: white;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .header-left p {
            color: #636e72;
            margin-top: 8px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #0984e3;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0873c7;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(9, 132, 227, 0.4);
        }
        
        .btn-success {
            background: #00b894;
            color: white;
        }
        
        .btn-success:hover {
            background: #00a381;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 184, 148, 0.4);
        }
        
        .btn-secondary {
            background: #dfe6e9;
            color: #2d3436;
        }
        
        .btn-secondary:hover {
            background: #d0d7db;
        }
        
        .btn-danger {
            background: #e17055;
            color: white;
        }
        
        .btn-danger:hover {
            background: #d63031;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0 25px 0;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-item .number {
            font-size: 28px;
            font-weight: 700;
            color: #0984e3;
        }
        
        .stat-item .label {
            font-size: 13px;
            color: #636e72;
            margin-top: 4px;
        }
        
        .stat-item .number.green { color: #00b894; }
        .stat-item .number.orange { color: #fdcb6e; }
        .stat-item .number.purple { color: #6c5ce7; }
        .stat-item .number.red { color: #e17055; }
        
        .ext-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 15px 0 25px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .ext-tag {
            background: #dfe6e9;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-family: 'Consolas', monospace;
        }
        
        .ext-tag .count {
            background: #2d3436;
            color: white;
            padding: 0 8px;
            border-radius: 10px;
            margin-left: 4px;
            font-size: 11px;
        }
        
        .tree {
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.8;
            padding: 5px 0;
        }
        
        .tree-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 2px 0;
            transition: background 0.2s;
            border-radius: 4px;
            padding-left: 5px;
        }
        
        .tree-item:hover {
            background: #f0f0f0;
        }
        
        .tree-item .icon {
            font-size: 18px;
            width: 28px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .tree-item .name {
            color: #2d3436;
            font-weight: 500;
        }
        
        .tree-item .name.dir {
            color: #0984e3;
            font-weight: 600;
        }
        
        .tree-item .name.php { color: #777BB4; }
        .tree-item .name.css { color: #1572B6; }
        .tree-item .name.js { color: #F7DF1E; text-shadow: 0 0 2px rgba(0,0,0,0.1); }
        .tree-item .name.html { color: #E34F26; }
        .tree-item .name.sql { color: #4479A1; }
        .tree-item .name.md { color: #083FA1; }
        .tree-item .name.log { color: #FF6B6B; }
        
        .tree-item .info {
            color: #b2bec3;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .tree-item .size {
            color: #636e72;
            font-size: 12px;
            margin-left: auto;
            padding-right: 10px;
        }
        
        .indent {
            display: inline-block;
            width: 24px;
            flex-shrink: 0;
        }
        
        .indent-line {
            display: inline-block;
            width: 24px;
            flex-shrink: 0;
            color: #dfe6e9;
        }
        
        .tree-children {
            padding-left: 8px;
        }
        
        .tree-item .arrow {
            color: #b2bec3;
            font-size: 12px;
            margin-right: 4px;
            cursor: pointer;
            user-select: none;
        }
        
        .tree-item .arrow:hover {
            color: #0984e3;
        }
        
        .badge {
            font-size: 10px;
            padding: 1px 10px;
            border-radius: 12px;
            background: #dfe6e9;
            color: #636e72;
            font-weight: 400;
        }
        
        .badge.php { background: #777BB4; color: white; }
        .badge.css { background: #1572B6; color: white; }
        .badge.js { background: #F7DF1E; color: #2d3436; }
        .badge.html { background: #E34F26; color: white; }
        .badge.sql { background: #4479A1; color: white; }
        .badge.md { background: #083FA1; color: white; }
        .badge.log { background: #FF6B6B; color: white; }
        .badge.dir { background: #0984e3; color: white; }
        
        .tree-item .badge {
            font-size: 10px;
            padding: 1px 8px;
            border-radius: 10px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #dfe6e9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            color: #b2bec3;
            font-size: 13px;
        }
        
        .search-box {
            margin: 15px 0 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-box input {
            flex: 1;
            min-width: 200px;
            padding: 10px 16px;
            border: 2px solid #dfe6e9;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border 0.3s;
        }
        
        .search-box input:focus {
            border-color: #0984e3;
        }
        
        .highlight {
            background: #fdcb6e !important;
        }
        
        .hidden {
            display: none !important;
        }
        
        .tree-item .file-size {
            color: #b2bec3;
            font-size: 12px;
            margin-left: 8px;
        }
        
        .tree-item .modified {
            color: #b2bec3;
            font-size: 11px;
            margin-left: 12px;
        }
        
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #00b894;
            color: white;
            padding: 16px 28px;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            font-size: 15px;
            font-weight: 500;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.5s ease;
            z-index: 9999;
        }
        
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .stats { grid-template-columns: repeat(2, 1fr); }
            .tree { font-size: 13px; }
            .tree-item .info { display: none; }
            .header { flex-direction: column; align-items: flex-start; }
            .header-actions { width: 100%; }
            .header-actions .btn { flex: 1; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-left">
                <h1>
                    📂 Estructura de Archivos
                    <span>BusYaracuy</span>
                </h1>
                <p>
                    <strong>Raíz:</strong> <?= htmlspecialchars($rootPath) ?>
                    <?php if ($customPath !== ''): ?>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary" style="padding: 2px 10px; font-size: 12px; margin-left: 10px;">↩ Volver a raíz</a>
                    <?php endif; ?>
                </p>
            </div>
            <div class="header-actions">
                <button class="btn btn-success" onclick="exportStructure()">
                    📥 Exportar a TXT
                </button>
                <button class="btn btn-secondary" onclick="expandAll()">
                    📂 Expandir Todo
                </button>
                <button class="btn btn-secondary" onclick="collapseAll()">
                    📁 Colapsar Todo
                </button>
                <button class="btn btn-secondary" onclick="scrollToTop()">
                    ⬆️ Ir Arriba
                </button>
            </div>
        </div>
        
        <!-- ESTADÍSTICAS -->
        <div class="stats">
            <div class="stat-item">
                <div class="number"><?= $itemCounts['files'] + $itemCounts['dirs'] ?></div>
                <div class="label">Total Items</div>
            </div>
            <div class="stat-item">
                <div class="number green"><?= $itemCounts['dirs'] ?></div>
                <div class="label">📁 Directorios</div>
            </div>
            <div class="stat-item">
                <div class="number orange"><?= $itemCounts['files'] ?></div>
                <div class="label">📄 Archivos</div>
            </div>
            <div class="stat-item">
                <div class="number purple"><?= formatSize($totalSize) ?></div>
                <div class="label">💾 Tamaño Total</div>
            </div>
        </div>
        
        <!-- EXTENSIONES -->
        <?php if (!empty($extStats)): ?>
        <div class="ext-stats">
            <strong style="color: #636e72; margin-right: 10px;">Extensiones:</strong>
            <?php 
            arsort($extStats);
            foreach ($extStats as $ext => $count): 
                $badgeClass = match($ext) {
                    'php' => 'php',
                    'css' => 'css',
                    'js' => 'js',
                    'html' => 'html',
                    'sql' => 'sql',
                    'md' => 'md',
                    'log' => 'log',
                    default => ''
                };
            ?>
                <span class="ext-tag <?= $badgeClass ?>">
                    <?= getIcon('.' . $ext, false) ?> .<?= $ext ?>
                    <span class="count"><?= $count ?></span>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- BUSCADOR -->
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Buscar archivos o carpetas..." onkeyup="filterTree()">
            <button class="btn btn-secondary" onclick="clearSearch()">✖️ Limpiar</button>
        </div>
        
        <!-- ÁRBOL DE ARCHIVOS -->
        <div class="tree" id="treeContainer">
            <?php renderTree($structure, 0); ?>
        </div>
        
        <div class="footer">
            <span>BusYaracuy v2.0 - Estructura de Archivos • <?= date('d/m/Y H:i:s') ?></span>
            <span>📊 <?= $itemCounts['files'] + $itemCounts['dirs'] ?> items</span>
        </div>
    </div>
    
    <!-- TOAST NOTIFICATION -->
    <div class="toast" id="toast">✅ Estructura exportada correctamente</div>
    
    <script>
        // ============================================
        // FUNCIONES DE BÚSQUEDA Y NAVEGACIÓN
        // ============================================
        
        function filterTree() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            const items = document.querySelectorAll('.tree-item');
            
            items.forEach(item => {
                const name = item.querySelector('.name')?.textContent?.toLowerCase() || '';
                const path = item.dataset.path?.toLowerCase() || '';
                
                if (query === '' || name.includes(query) || path.includes(query)) {
                    item.style.display = 'flex';
                    if (query !== '' && name.includes(query)) {
                        item.style.background = '#fdcb6e';
                        item.style.borderRadius = '4px';
                    } else {
                        item.style.background = '';
                    }
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Mostrar padre si tiene hijos visibles
            document.querySelectorAll('.tree-children').forEach(container => {
                const parentItem = container.parentElement;
                const hasVisibleChildren = container.querySelector('.tree-item[style*="display: flex"]') !== null;
                if (parentItem) {
                    parentItem.style.display = hasVisibleChildren ? 'flex' : 'none';
                }
            });
        }
        
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            filterTree();
        }
        
        function toggleChildren(element) {
            const children = element.parentElement.parentElement?.querySelector('.tree-children');
            if (children) {
                const isHidden = children.style.display === 'none';
                children.style.display = isHidden ? '' : 'none';
                const arrow = element;
                arrow.textContent = isHidden ? '▼' : '▶';
            }
        }
        
        function expandAll() {
            document.querySelectorAll('.tree-children').forEach(el => {
                el.style.display = '';
            });
            document.querySelectorAll('.arrow').forEach(el => {
                el.textContent = '▼';
            });
        }
        
        function collapseAll() {
            document.querySelectorAll('.tree-children').forEach(el => {
                el.style.display = 'none';
            });
            document.querySelectorAll('.arrow').forEach(el => {
                el.textContent = '▶';
            });
        }
        
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // ============================================
        // EXPORTAR ESTRUCTURA
        // ============================================
        
        function exportStructure() {
            // Mostrar toast
            const toast = document.getElementById('toast');
            toast.textContent = '⏳ Generando archivo de exportación...';
            toast.className = 'toast show';
            
            // Abrir la exportación en una nueva ventana o descarga directa
            window.location.href = '<?= $_SERVER['PHP_SELF'] ?>?action=export_txt';
            
            // Cambiar mensaje después de un momento
            setTimeout(() => {
                toast.textContent = '✅ Estructura exportada correctamente';
                toast.className = 'toast show';
                setTimeout(() => {
                    toast.className = 'toast';
                }, 3000);
            }, 1500);
        }
        
        // ============================================
        // TECLADO: ESC para limpiar búsqueda
        // ============================================
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearSearch();
                document.getElementById('searchInput').blur();
            }
            
            // Ctrl+F para enfocar búsqueda
            if (e.key === 'f' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                document.getElementById('searchInput').focus();
                document.getElementById('searchInput').select();
            }
        });
    </script>
</body>
</html>

<?php
// ============================================
// FUNCIÓN PARA RENDERIZAR EL ÁRBOL
// ============================================

function renderTree($items, $level = 0)
{
    foreach ($items as $item):
        $isDir = $item['isDir'];
        $name = htmlspecialchars($item['name']);
        $icon = getIcon($item['name'], $isDir);
        $color = getColor($item['name'], $isDir);
        $size = $isDir ? '' : formatSize($item['size']);
        $modified = $isDir ? '' : $item['modified'];
        $hasChildren = $isDir && !empty($item['children']);
        
        // Extension badge
        $ext = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
        $badgeClass = match($ext) {
            'php' => 'php',
            'css' => 'css',
            'js' => 'js',
            'html' => 'html',
            'sql' => 'sql',
            'md' => 'md',
            'log' => 'log',
            default => ''
        };
        $badgeText = $isDir ? 'dir' : ($ext ? $ext : 'file');
        $badgeClass = $isDir ? 'dir' : $badgeClass;
        ?>
        
        <div class="tree-item" data-path="<?= htmlspecialchars($item['path']) ?>">
            <?php for ($i = 0; $i < $level; $i++): ?>
                <span class="indent-line">│</span>
            <?php endfor; ?>
            
            <?php if ($hasChildren): ?>
                <span class="arrow" onclick="toggleChildren(this)">▼</span>
            <?php else: ?>
                <span class="indent"> </span>
            <?php endif; ?>
            
            <span class="icon"><?= $icon ?></span>
            
            <?php if ($isDir): ?>
                <span class="name dir" onclick="toggleChildren(this)"><?= $name ?></span>
            <?php else: ?>
                <span class="name <?= $badgeClass ?>" style="color: <?= $color ?>"><?= $name ?></span>
            <?php endif; ?>
            
            <?php if ($badgeClass): ?>
                <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
            <?php endif; ?>
            
            <?php if ($size): ?>
                <span class="file-size"><?= $size ?></span>
            <?php endif; ?>
            
            <?php if ($modified): ?>
                <span class="modified"><?= $modified ?></span>
            <?php endif; ?>
        </div>
        
        <?php if ($hasChildren): ?>
            <div class="tree-children">
                <?php renderTree($item['children'], $level + 1); ?>
            </div>
        <?php endif; ?>
        
    <?php endforeach;
}
?>