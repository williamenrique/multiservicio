<?php
/**
 * accion.php - Panel administrativo AUTÓNOMO (no depende del framework)
 * ----------------------------------------------------------------------------
 * Interfaz de uso restringido (solo para quien sepa que existe).
 * Funciones:
 *   - Explorador de archivos navegable (estructura del proyecto, compacto)
 *     con botones para ELIMINAR y RENOMBRAR (modal + toast).
 *   - Exportar base de datos: vacía (solo estructura) o con datos.
 *   - Eliminar tablas seleccionadas (con protección de tablas críticas).
 *
 * NO toca public_html/index.php ni el flujo del framework.
 * Todas las acciones AJAX se resuelven dentro de este mismo archivo.
 * ----------------------------------------------------------------------------
 */

// ====== CONFIGURACIÓN DE BASE DE DATOS (mismos valores que app/Config/config.php) ======
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'multiservicio_2.0';

// ====== SEGURIDAD BÁSICA ======
// Clave de acceso simple para evitar acceso casual. Cámbiala por una propia.
$ACCESS_KEY = 'multiservicio2026';

session_start_if_needed();

// Verificación de clave de acceso (por GET ?key=... o por sesión)
if (!isset($_SESSION['accion_ok']) || $_SESSION['accion_ok'] !== true) {
    $key = $_GET['key'] ?? $_POST['key'] ?? '';
    if ($key === $ACCESS_KEY) {
        $_SESSION['accion_ok'] = true;
    } else {
        // Formulario minimalista de acceso
        http_response_code(200);
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso restringido</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  body{font-family:'Poppins',sans-serif;background:#1e293b;color:#fff;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0}
  .card{background:#0f172a;padding:35px 30px;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,.4);width:340px;max-width:90vw}
  h2{margin:0 0 20px;font-size:1.3rem;font-weight:600;text-align:center}
  input{width:100%;padding:12px 14px;border:1px solid #334155;background:#1e293b;color:#fff;border-radius:8px;font-family:inherit;font-size:.9rem;margin-bottom:14px;box-sizing:border-box}
  button{width:100%;padding:12px;background:#3f6ad8;color:#fff;border:none;border-radius:8px;font-family:inherit;font-weight:500;cursor:pointer;font-size:.95rem}
  button:hover{background:#2c4fa3}
  .err{color:#f87171;font-size:.85rem;text-align:center;margin-top:10px}
</style>
</head>
<body>
  <form class="card" method="POST" action="">
    <h2>🔒 Acceso restringido</h2>
    <input type="password" name="key" placeholder="Clave de acceso" autofocus>
    <button type="submit">Entrar</button>
    <?php if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['key'] ?? '') !== $ACCESS_KEY): ?>
      <div class="err">Clave incorrecta</div>
    <?php endif; ?>
  </form>
</body>
</html>
        <?php
        exit;
    }
}

// ====== RUTA RAÍZ DEL PROYECTO ======
// Si accion.php está dentro de public_html/, la raíz real del proyecto
// es un nivel arriba. Detectamos automáticamente para que el explorador
// pueda ver TODA la estructura del proyecto (app/, sql/, vendor/, etc.).
$_script_dir = dirname(__FILE__);
$_parent_dir = dirname($_script_dir);
// Si el directorio padre contiene 'app' o 'composer.json', asumimos que es la raíz real.
if (is_dir($_parent_dir . DIRECTORY_SEPARATOR . 'app') || file_exists($_parent_dir . DIRECTORY_SEPARATOR . 'composer.json')) {
    $PROJECT_ROOT = $_parent_dir;
} else {
    $PROJECT_ROOT = $_script_dir;
}

// ====== PROTECCIONES ======
// Carpetas/archivos que NUNCA se pueden eliminar ni renombrar.
// Carpetas críticas del sistema: se protegen ellas mismas Y todo su contenido.
$PROTECTED_DIRS = ['app', 'vendor', 'sql'];
// Elementos sueltos protegidos: solo se protege el elemento exacto (no su contenido).
$PROTECTED_ITEMS = [
    'accion.php',
    'composer.json',
    'composer.lock',
    '.htaccess',
];

// Tablas que NUNCA se pueden eliminar desde el panel.
$PROTECTED_TABLES = [
    'table_roles',
    'table_staff',
    'table_usuarios',
];

/**
 * Normaliza una ruta relativa y verifica que esté dentro del proyecto.
 * Devuelve la ruta absoluta segura o lanza excepción si es inválida.
 */
function safe_path($rel, $root) {
    $rel = str_replace('\\', '/', $rel);
    $rel = ltrim($rel, '/');
    // Evitar .. para prevenir path traversal
    if (preg_match('#(^|/)\.\.(/|$)#', $rel)) {
        throw new Exception('Ruta no permitida.');
    }
    $abs = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $real = realpath($abs);
    if ($real === false) {
        // Puede no existir aún (caso rename destino), validamos por prefijo
        $real = $abs;
    }
    $rootReal = realpath($root);
    if ($rootReal === false) $rootReal = $root;
    // Comprobamos que la ruta normalizada empieza por la raíz
    $normReal = str_replace('\\', '/', $real);
    $normRoot = str_replace('\\', '/', $rootReal);
    if (strpos($normReal, $normRoot) !== 0) {
        throw new Exception('Ruta fuera del proyecto.');
    }
    return $real;
}

/**
 * Devuelve true si la ruta relativa está protegida contra borrado/renombrado.
 * - Las carpetas críticas ($PROTECTED_DIRS) protegen todo su contenido.
 * - Los elementos sueltos ($PROTECTED_ITEMS) solo protegen el elemento exacto.
 */
function is_protected($rel) {
    global $PROTECTED_DIRS, $PROTECTED_ITEMS;
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    foreach ($PROTECTED_DIRS as $p) {
        if ($rel === $p) return true;
        if (strpos($rel, $p . '/') === 0) return true;
    }
    foreach ($PROTECTED_ITEMS as $p) {
        if ($rel === $p) return true;
    }
    return false;
}

/**
 * Construye el árbol de archivos (un nivel) de un directorio.
 */
function build_file_list($dir, $root) {
    $result = [];
    if (!is_dir($dir)) return $result;
    $ignored = ['.', '..', '.git', 'node_modules', '.vscode', '.idea'];
    $items = @scandir($dir);
    if ($items === false) return $result;
    $folders = [];
    $files = [];
    foreach ($items as $item) {
        if (in_array($item, $ignored)) continue;
        // Ocultar archivos que empiezan con punto
        if ($item !== '.' && substr($item, 0, 1) === '.') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        $rel = str_replace([$root, '\\'], ['', '/'], $full);
        $rel = ltrim($rel, '/');
        if (is_dir($full)) {
            $folders[$item] = $rel;
        } else {
            $files[$item] = $rel;
        }
    }
    ksort($folders);
    ksort($files);
    foreach ($folders as $name => $rel) {
        $result[] = ['type' => 'folder', 'name' => $name, 'path' => $rel, 'protected' => is_protected($rel)];
    }
    foreach ($files as $name => $rel) {
        $result[] = ['type' => 'file', 'name' => $name, 'path' => $rel, 'protected' => is_protected($rel)];
    }
    return $result;
}

/**
 * Conexión PDO a la base de datos.
 */
function db_connect($host, $user, $pass, $name) {
    $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function session_start_if_needed() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ====== MANEJO DE ACCIONES AJAX ======
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action !== '') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        switch ($action) {

            // ---- Listar directorio (raíz o subcarpeta) ----
            case 'list':
                $rel = $_GET['path'] ?? '';
                $dir = $rel === '' ? $PROJECT_ROOT : safe_path($rel, $PROJECT_ROOT);
                if (!is_dir($dir)) {
                    json_out(['success' => false, 'message' => 'No es un directorio válido.']);
                }
                $data = build_file_list($dir, $PROJECT_ROOT);
                json_out(['success' => true, 'data' => $data]);

            // ---- Eliminar archivo ----
            case 'deleteFile':
                $rel = $_POST['path'] ?? '';
                if ($rel === '' || is_protected($rel)) {
                    json_out(['success' => false, 'message' => 'Archivo protegido, no se puede eliminar.']);
                }
                $abs = safe_path($rel, $PROJECT_ROOT);
                if (!is_file($abs)) {
                    json_out(['success' => false, 'message' => 'El archivo no existe.']);
                }
                if (@unlink($abs)) {
                    json_out(['success' => true, 'message' => 'Archivo eliminado correctamente.']);
                }
                json_out(['success' => false, 'message' => 'No se pudo eliminar el archivo.']);

            // ---- Eliminar carpeta (recursivo) ----
            case 'deleteDirectory':
                $rel = $_POST['path'] ?? '';
                if ($rel === '' || is_protected($rel)) {
                    json_out(['success' => false, 'message' => 'Carpeta protegida, no se puede eliminar.']);
                }
                $abs = safe_path($rel, $PROJECT_ROOT);
                if (!is_dir($abs)) {
                    json_out(['success' => false, 'message' => 'La carpeta no existe.']);
                }
                $ok = remove_dir_recursive($abs);
                if ($ok) {
                    json_out(['success' => true, 'message' => 'Carpeta eliminada correctamente.']);
                }
                json_out(['success' => false, 'message' => 'No se pudo eliminar la carpeta.']);

            // ---- Renombrar archivo o carpeta ----
            case 'rename':
                $oldRel = $_POST['oldPath'] ?? '';
                $newName = trim($_POST['newName'] ?? '');
                if ($oldRel === '' || is_protected($oldRel)) {
                    json_out(['success' => false, 'message' => 'Elemento protegido, no se puede renombrar.']);
                }
                if ($newName === '' || preg_match('#[\\\\/]#', $newName)) {
                    json_out(['success' => false, 'message' => 'Nombre inválido.']);
                }
                $oldAbs = safe_path($oldRel, $PROJECT_ROOT);
                $dir = dirname($oldAbs);
                $newAbs = $dir . DIRECTORY_SEPARATOR . $newName;
                // Validar destino dentro del proyecto
                safe_path(str_replace($PROJECT_ROOT . DIRECTORY_SEPARATOR, '', $newAbs), $PROJECT_ROOT);
                if (file_exists($newAbs)) {
                    json_out(['success' => false, 'message' => 'Ya existe un elemento con ese nombre.']);
                }
                if (@rename($oldAbs, $newAbs)) {
                    json_out(['success' => true, 'message' => 'Renombrado correctamente.']);
                }
                json_out(['success' => false, 'message' => 'No se pudo renombrar.']);

            // ---- Listar tablas de la BD ----
            case 'getTables':
                $pdo = db_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                json_out(['success' => true, 'data' => $tables]);

            // ---- Exportar tablas (con datos o solo estructura) ----
            case 'exportTables':
                $tables = $_POST['tables'] ?? '[]';
                $tables = json_decode($tables, true);
                if (!is_array($tables) || count($tables) === 0) {
                    json_out(['success' => false, 'message' => 'Selecciona al menos una tabla.']);
                }
                $withData = isset($_POST['withData']) && $_POST['withData'] == '1';
                $pdo = db_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

                $sql = "-- Backup generado por accion.php\n-- Fecha: " . date('Y-m-d H:i:s') . "\n-- Base: {$DB_NAME}\n\n";
                $sql .= "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='';\n\n";

                foreach ($tables as $tbl) {
                    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tbl)) continue;
                    // Estructura
                    $create = $pdo->query("SHOW CREATE TABLE `$tbl`")->fetch();
                    $sql .= "-- Estructura de `$tbl`\n";
                    $sql .= "DROP TABLE IF EXISTS `$tbl`;\n";
                    $sql .= $create['Create Table'] . ";\n\n";

                    if ($withData) {
                        $rows = $pdo->query("SELECT * FROM `$tbl`")->fetchAll(PDO::FETCH_ASSOC);
                        if (count($rows) > 0) {
                            $sql .= "-- Datos de `$tbl`\n";
                            $cols = array_keys($rows[0]);
                            $colList = '`' . implode('`,`', $cols) . '`';
                            foreach ($rows as $row) {
                                $vals = [];
                                foreach ($row as $v) {
                                    if (is_null($v)) $vals[] = 'NULL';
                                    else $vals[] = "'" . str_replace(["\\", "'"], ["\\\\", "''"], $v) . "'";
                                }
                                $sql .= "INSERT INTO `$tbl` ($colList) VALUES (" . implode(',', $vals) . ");\n";
                            }
                            $sql .= "\n";
                        }
                    }
                }
                $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

                $filename = 'backup_' . $DB_NAME . '_' . ($withData ? 'datos' : 'estructura') . '_' . date('Y-m-d_H-i-s') . '.sql';
                header('Content-Type: application/sql');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo $sql;
                exit;

            // ---- Eliminar tablas ----
            case 'deleteTables':
                $tables = $_POST['tables'] ?? '[]';
                $tables = json_decode($tables, true);
                if (!is_array($tables) || count($tables) === 0) {
                    json_out(['success' => false, 'message' => 'Selecciona al menos una tabla.']);
                }
                $pdo = db_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
                $deleted = 0;
                $skipped = [];
                foreach ($tables as $tbl) {
                    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tbl)) continue;
                    if (in_array($tbl, $PROTECTED_TABLES)) {
                        $skipped[] = $tbl;
                        continue;
                    }
                    try {
                        $pdo->exec("DROP TABLE IF EXISTS `$tbl`");
                        $deleted++;
                    } catch (Exception $e) {
                        $skipped[] = $tbl;
                    }
                }
                $msg = "Tablas eliminadas: $deleted.";
                if (count($skipped) > 0) $msg .= ' Omitidas (protegidas): ' . implode(', ', $skipped);
                json_out(['success' => true, 'message' => $msg]);

            default:
                json_out(['success' => false, 'message' => 'Acción no reconocida.']);
        }
    } catch (Exception $e) {
        json_out(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Elimina recursivamente un directorio.
 */
function remove_dir_recursive($dir) {
    if (!is_dir($dir)) return false;
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path) && !is_link($path)) {
            remove_dir_recursive($path);
        } else {
            @unlink($path);
        }
    }
    return @rmdir($dir);
}

// ====== RENDER DE LA INTERFAZ ======
$tree = build_file_list($PROJECT_ROOT, $PROJECT_ROOT);
$treeJson = json_encode($tree, JSON_UNESCAPED_UNICODE);
$self = basename(__FILE__);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel de Administración - Multiservicio</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
:root{
  --bg:#f4f6f9;--card:#fff;--primary:#3f6ad8;--primary-dark:#2c4fa3;
  --text:#2d3748;--text-muted:#718096;--border:#e2e8f0;
  --danger:#e3342f;--success:#38c172;--warning:#f6993f;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);padding:20px;min-height:100vh}
.header{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;padding:20px 30px;border-radius:12px;margin-bottom:25px;box-shadow:0 4px 15px rgba(63,106,216,.25)}
.header h1{font-size:1.6rem;font-weight:600}
.header p{font-size:.9rem;opacity:.9;margin-top:5px}
.section{background:var(--card);border-radius:12px;padding:25px;margin-bottom:25px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.section-title{font-size:1.2rem;font-weight:600;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid var(--border);display:flex;align-items:center;gap:10px}
.section-title i{color:var(--primary)}
.db-actions{display:flex;flex-wrap:wrap;gap:15px;align-items:center}
.btn{padding:10px 20px;border:none;border-radius:8px;font-family:inherit;font-size:.9rem;font-weight:500;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:8px}
.btn-primary{background:var(--primary);color:#fff}.btn-primary:hover{background:var(--primary-dark)}
.btn-success{background:var(--success);color:#fff}.btn-success:hover{filter:brightness(.9)}
.btn-danger{background:var(--danger);color:#fff}.btn-danger:hover{filter:brightness(.9)}
.btn-warning{background:var(--warning);color:#fff}.btn-warning:hover{filter:brightness(.9)}
.tables-list{margin-top:15px;max-height:280px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:12px;display:none}
.tables-list.active{display:block}
.table-item{display:flex;align-items:center;gap:10px;padding:6px 8px;border-bottom:1px solid #f0f0f0;font-size:.85rem}
.table-item:last-child{border-bottom:none}
.table-item input{cursor:pointer}
.select-bar{display:flex;gap:10px;margin-bottom:10px;font-size:.85rem}
.select-bar a{color:var(--primary);cursor:pointer;text-decoration:underline}
.file-explorer{border:1px solid var(--border);border-radius:8px;max-height:560px;overflow-y:auto;padding:10px;font-size:.88rem}
.tree-item{display:flex;align-items:center;gap:8px;padding:5px 8px;border-radius:5px;cursor:default;transition:background .15s}
.tree-item:hover{background:#edf2f7}
.tree-item .icon{width:18px;text-align:center}
.tree-item .name{flex:1;word-break:break-all}
.tree-item .actions{display:flex;gap:6px;opacity:0;transition:opacity .15s}
.tree-item:hover .actions{opacity:1}
.tree-item .actions button{border:none;background:transparent;cursor:pointer;font-size:.85rem;padding:2px 6px;border-radius:4px}
.tree-item .actions .act-rename{color:var(--warning)}
.tree-item .actions .act-delete{color:var(--danger)}
.tree-item .actions button:hover{background:#e2e8f0}
.folder-toggle{cursor:pointer;user-select:none}
.folder-toggle .icon-folder{color:var(--warning)}
.file-icon{color:var(--text-muted)}
.ext-badge{font-size:.7rem;background:#e2e8f0;color:var(--text-muted);padding:1px 6px;border-radius:10px;margin-left:6px}
.breadcrumb{font-size:.85rem;color:var(--text-muted);margin-bottom:10px;padding:8px 12px;background:#f7fafc;border-radius:6px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.breadcrumb a{color:var(--primary);cursor:pointer;text-decoration:none}
.breadcrumb a:hover{text-decoration:underline}
.breadcrumb button:disabled{opacity:.4;cursor:not-allowed}
.breadcrumb button:not(:disabled){background:var(--primary);color:#fff}
.breadcrumb button:not(:disabled):hover{background:var(--primary-dark)}
.protected-badge{font-size:.65rem;background:#fed7d7;color:#9b2c2c;padding:1px 6px;border-radius:10px;margin-left:6px}
@media(max-width:768px){body{padding:10px}.section{padding:15px}.db-actions{flex-direction:column;align-items:stretch}}
</style>
</head>
<body>
<div class="header">
  <h1><i class="fas fa-tools"></i> Panel de Administración</h1>
  <p>Explorador de archivos · Exportar base de datos · Eliminar / Renombrar</p>
</div>

<!-- Sección: Exportar Base de Datos -->
<div class="section">
  <div class="section-title"><i class="fas fa-database"></i> Exportar Base de Datos</div>
  <div class="db-actions">
    <button class="btn btn-primary" id="btnLoadTables"><i class="fas fa-list"></i> Cargar Tablas</button>
    <button class="btn btn-success" id="btnExportData"><i class="fas fa-file-export"></i> Exportar con Datos</button>
    <button class="btn btn-success" id="btnExportEmpty"><i class="fas fa-file-code"></i> Exportar Vacía (Estructura)</button>
    <button class="btn btn-danger" id="btnDeleteTables"><i class="fas fa-trash"></i> Eliminar Tablas</button>
  </div>
  <div class="select-bar" id="selectBar" style="display:none;">
    <a id="selectAllTables">Seleccionar todas</a> | <a id="deselectAllTables">Quitar selección</a>
  </div>
  <div class="tables-list" id="tablesList"></div>
</div>

<!-- Sección: Explorador de Archivos -->
<div class="section">
  <div class="section-title"><i class="fas fa-folder-tree"></i> Explorador de Archivos</div>
  <div class="breadcrumb" id="breadcrumb">
    <i class="fas fa-house"></i> <a data-path="">Raíz del proyecto</a>
  </div>
  <div class="file-explorer" id="fileExplorer"></div>
</div>

<script>
const selfUrl = "<?php echo $self; ?>";
const treeData = <?php echo $treeJson; ?>;

/* ===================== TOAST ===================== */
const Toast = Swal.mixin({
  toast:true,position:'top-end',showConfirmButton:false,timer:3000,timerProgressBar:true,
  didOpen:function(t){t.addEventListener('mouseenter',Swal.stopTimer);t.addEventListener('mouseleave',Swal.resumeTimer);}
});
function toast(title,icon){ Toast.fire({icon:icon,title:title}); }

/* ===================== EXPORTAR BD ===================== */
let loadedTables = [];

function loadDatabaseTables(){
  $.ajax({url:selfUrl+'?action=getTables',type:'GET',dataType:'json',
    success:function(resp){
      if(resp.success && Array.isArray(resp.data)){ loadedTables=resp.data; renderTablesList(); }
      else { toast(resp.message||'No se pudieron obtener las tablas.','warning'); }
    },
    error:function(){ toast('No se pudo conectar con el servidor.','error'); }
  });
}

function renderTablesList(){
  const $list=$('#tablesList'); $list.empty();
  loadedTables.forEach(function(t){
    $list.append('<div class="table-item"><input type="checkbox" class="table-check" value="'+t+'"><span>'+t+'</span></div>');
  });
  $list.addClass('active'); $('#selectBar').show();
}

function getSelectedTables(){
  return $('.table-check:checked').map(function(){return $(this).val();}).get();
}

function exportTables(withData){
  const selected=getSelectedTables();
  if(selected.length===0){ toast('Selecciona al menos una tabla para exportar.','info'); return; }
  const form=$('<form>',{method:'POST',action:selfUrl+'?action=exportTables'});
  form.append($('<input>',{type:'hidden',name:'tables',value:JSON.stringify(selected)}));
  form.append($('<input>',{type:'hidden',name:'withData',value:withData?'1':'0'}));
  $('body').append(form); form.submit(); form.remove();
}

function deleteTablesSubmit(){
  const selected=getSelectedTables();
  if(selected.length===0){ toast('Selecciona al menos una tabla para eliminar.','info'); return; }
  Swal.fire({
    title:'¿Estás seguro?',text:'Esta acción eliminará las tablas seleccionadas (excepto las protegidas).',
    icon:'warning',showCancelButton:true,confirmButtonText:'Sí, eliminar',cancelButtonText:'Cancelar',confirmButtonColor:'#e3342f'
  }).then(function(r){
    if(!r.isConfirmed) return;
    $.ajax({url:selfUrl+'?action=deleteTables',type:'POST',data:{tables:JSON.stringify(selected)},dataType:'json',
      success:function(resp){ toast(resp.message,resp.success?'success':'error'); if(resp.success) loadDatabaseTables(); },
      error:function(){ toast('No se pudo completar la solicitud.','error'); }
    });
  });
}

/* ===================== EXPLORADOR DE ARCHIVOS (navegación real) ===================== */
let currentPath = "";
let historyStack = [];   // rutas anteriores para "Atrás"
let forwardStack = [];   // rutas posteriores para "Adelante"

function updateBreadcrumb(path){
  const $bc=$('#breadcrumb'); $bc.empty();
  const $home=$('<a data-path=""><i class="fas fa-house"></i> Raíz</a>')
    .on('click',function(e){ e.preventDefault(); navigateTo(''); });
  $bc.append($home);
  if(path){
    const parts=path.split('/');
    let acc='';
    parts.forEach(function(p){
      acc = acc ? acc+'/'+p : p;
      $bc.append('<span style="color:#cbd5e0">/</span>');
      $('<a data-path="'+acc+'">'+p+'</a>')
        .on('click',function(e){ e.preventDefault(); navigateTo(acc); })
        .appendTo($bc);
    });
  }
  // Botón "Atrás" / "Adelante" / "Subir"
  const $nav=$('<span>',{style:'margin-left:auto;display:flex;gap:6px'});
  const $up=$('<button class="btn" style="padding:4px 10px;font-size:.8rem" title="Subir un nivel"><i class="fas fa-level-up-alt"></i></button>')
    .prop('disabled', path==='')
    .on('click',function(){
      if(!path) return;
      const parts=path.split('/');
      parts.pop();
      navigateTo(parts.join('/'));
    });
  const $back=$('<button class="btn" style="padding:4px 10px;font-size:.8rem" title="Atrás"><i class="fas fa-arrow-left"></i></button>')
    .prop('disabled', historyStack.length===0)
    .on('click',function(){ goBack(); });
  const $fwd=$('<button class="btn" style="padding:4px 10px;font-size:.8rem" title="Adelante"><i class="fas fa-arrow-right"></i></button>')
    .prop('disabled', forwardStack.length===0)
    .on('click',function(){ goForward(); });
  $nav.append($back,$fwd,$up);
  $bc.append($nav);
}

function renderTree(nodes,$container){
  $container.empty();
  if(!nodes || nodes.length===0){
    $container.append('<div style="padding:30px;text-align:center;color:var(--text-muted);font-size:.9rem"><i class="fas fa-folder-open" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4"></i>Esta carpeta está vacía.</div>');
    return;
  }
  nodes.forEach(function(node){
    const $row=$('<div>',{class:'tree-item'});
    const $icon=$('<span>',{class:'icon'});
    const $name=$('<span>',{class:'name'}).text(node.name);

    if(node.type==='folder'){
      $icon.html('<i class="fas fa-folder icon-folder"></i>');
      $row.addClass('folder-toggle');
      $row.append($icon,$name);
      // Doble clic para abrir, clic simple también navega
      $row.on('click',function(e){
        if($(e.target).closest('.actions').length) return; // no navegar si clic en acciones
        navigateTo(node.path);
      });
    } else {
      const ext=node.name.split('.').pop().toLowerCase();
      $icon.html('<i class="fas fa-file file-icon"></i>');
      $name.append('<span class="ext-badge">'+ext+'</span>');
      $row.append($icon,$name);
    }

    // Acciones (renombrar / eliminar) — ocultar si el elemento está protegido
    const $actions=$('<span>',{class:'actions'});
    if(node.protected){
      $name.append('<span class="protected-badge" title="Elemento protegido del sistema"><i class="fas fa-lock"></i> Protegido</span>');
    } else {
      $actions.append($('<button>',{class:'act-rename',html:'<i class="fas fa-pen"></i>',title:'Renombrar'})
        .on('click',function(e){ e.stopPropagation(); handleRename(node); }));
      $actions.append($('<button>',{class:'act-delete',html:'<i class="fas fa-trash"></i>',title:'Eliminar'})
        .on('click',function(e){ e.stopPropagation(); handleDelete(node); }));
    }
    $row.append($actions);

    $container.append($row);
  });
}

/* Navega a una carpeta (ruta relativa). Registra el historial. */
function navigateTo(relPath, pushHistory){
  if(pushHistory !== false && currentPath !== relPath){
    historyStack.push(currentPath);
    forwardStack = []; // al navegar hacia nuevo lado, se limpia el "adelante"
  }
  currentPath = relPath || '';
  updateBreadcrumb(currentPath);
  loadDir(currentPath);
}

/* Carga el contenido de una carpeta vía AJAX y lo renderiza. */
function loadDir(relPath){
  const $explorer=$('#fileExplorer');
  $explorer.append('<div class="loading" style="padding:20px;text-align:center;color:var(--text-muted)"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
  $.ajax({url:selfUrl+'?action=list',type:'GET',data:{path:relPath},dataType:'json',
    success:function(resp){
      if(resp.success && Array.isArray(resp.data)){
        renderTree(resp.data,$explorer);
      } else {
        $explorer.empty();
        toast(resp.message||'No se pudo listar el directorio.','warning');
      }
    },
    error:function(){
      $explorer.empty();
      toast('No se pudo cargar el directorio.','error');
    }
  });
}

/* Navegación Atrás / Adelante */
function goBack(){
  if(historyStack.length===0) return;
  forwardStack.push(currentPath);
  currentPath = historyStack.pop();
  updateBreadcrumb(currentPath);
  loadDir(currentPath);
}
function goForward(){
  if(forwardStack.length===0) return;
  historyStack.push(currentPath);
  currentPath = forwardStack.pop();
  updateBreadcrumb(currentPath);
  loadDir(currentPath);
}

function handleDelete(node){
  Swal.fire({
    title:'¿Eliminar '+(node.type==='folder'?'carpeta':'archivo')+'?',
    text:node.path,icon:'warning',showCancelButton:true,
    confirmButtonText:'Sí, eliminar',cancelButtonText:'Cancelar',confirmButtonColor:'#e3342f'
  }).then(function(r){
    if(!r.isConfirmed) return;
    const endpoint = node.type==='folder' ? 'deleteDirectory' : 'deleteFile';
    $.ajax({url:selfUrl+'?action='+endpoint,type:'POST',data:{path:node.path},dataType:'json',
      success:function(resp){
        toast(resp.message,resp.success?'success':'error');
        if(resp.success) loadDir(currentPath); // recarga solo la carpeta actual
      },
      error:function(){ toast('No se pudo completar.','error'); }
    });
  });
}

function handleRename(node){
  Swal.fire({
    title:'Renombrar',input:'text',inputValue:node.name,
    inputAttributes:{autocapitalize:'off'},showCancelButton:true,
    confirmButtonText:'Renombrar',cancelButtonText:'Cancelar',
    inputValidator:function(value){
      if(!value || value===node.name) return 'Ingresa un nombre distinto';
      if(/[\\/]/.test(value)) return 'El nombre no puede contener barras';
    }
  }).then(function(r){
    if(!r.isConfirmed) return;
    $.ajax({url:selfUrl+'?action=rename',type:'POST',
      data:{oldPath:node.path,newName:r.value},dataType:'json',
      success:function(resp){
        toast(resp.message,resp.success?'success':'error');
        if(resp.success) loadDir(currentPath); // recarga solo la carpeta actual
      },
      error:function(){ toast('No se pudo completar.','error'); }
    });
  });
}

/* ===================== INIT ===================== */
$(function(){
  // Render inicial desde el árbol precargado (raíz)
  renderTree(treeData,$('#fileExplorer'));
  updateBreadcrumb('');

  $('#btnLoadTables').on('click',loadDatabaseTables);
  $('#btnExportData').on('click',function(){ exportTables(true); });
  $('#btnExportEmpty').on('click',function(){ exportTables(false); });
  $('#btnDeleteTables').on('click',deleteTablesSubmit);
  $('#selectAllTables').on('click',function(){ $('.table-check').prop('checked',true); });
  $('#deselectAllTables').on('click',function(){ $('.table-check').prop('checked',false); });
});
</script>
</body>
</html>
