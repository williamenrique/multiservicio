<?php
/**
 * accion.php - Panel administrativo
 * - Explorador de archivos navegable (estructura del proyecto)
 * - Exportar base de datos (vacía o con datos)
 * - Eliminar / renombrar archivos y carpetas
 */
require_once("system/core/Config/config.system.php");

// Construir árbol de archivos desde la raíz del proyecto (compacto, navegable)
function buildFileTree($dir, $baseDir) {
    $result = [];
    // Ignorar carpetas que no aportan al panel
    $ignored = ['.', '..', '.git', 'node_modules', 'vendor'];
    if (!is_dir($dir)) return $result;
    $items = scandir($dir);
    // Primero carpetas, luego archivos, ordenados
    $folders = [];
    $files = [];
    foreach ($items as $item) {
        if (in_array($item, $ignored)) continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        $rel = str_replace($baseDir, '', $full);
        $rel = str_replace('\\', '/', $rel);
        if (is_dir($full)) {
            $folders[$item] = $rel;
        } else {
            $files[$item] = $rel;
        }
    }
    ksort($folders);
    ksort($files);
    foreach ($folders as $name => $rel) {
        $result[] = ['type' => 'folder', 'name' => $name, 'path' => $rel, 'children' => null];
    }
    foreach ($files as $name => $rel) {
        $result[] = ['type' => 'file', 'name' => $name, 'path' => $rel];
    }
    return $result;
}

$projectRoot = dirname(__FILE__);
$tree = buildFileTree($projectRoot, $projectRoot);
$treeJson = json_encode($tree, JSON_UNESCAPED_UNICODE);
$baseUrl = BASE_URL;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Busyaracuy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg: #f4f6f9;
            --card: #ffffff;
            --primary: #3f6ad8;
            --primary-dark: #2c4fa3;
            --text: #2d3748;
            --text-muted: #718096;
            --border: #e2e8f0;
            --danger: #e3342f;
            --success: #38c172;
            --warning: #f6993f;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 20px;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(63, 106, 216, 0.25);
        }

        .header h1 { font-size: 1.6rem; font-weight: 600; }
        .header p { font-size: 0.9rem; opacity: 0.9; margin-top: 5px; }

        .section {
            background: var(--card);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i { color: var(--primary); }

        /* Exportar BD */
        .db-actions { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-success:hover { filter: brightness(0.9); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { filter: brightness(0.9); }
        .btn-warning { background: var(--warning); color: #fff; }
        .btn-warning:hover { filter: brightness(0.9); }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; }

        .tables-list {
            margin-top: 15px;
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            display: none;
        }
        .tables-list.active { display: block; }
        .table-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 8px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.85rem;
        }
        .table-item:last-child { border-bottom: none; }
        .table-item input { cursor: pointer; }

        .select-bar { display: flex; gap: 10px; margin-bottom: 10px; font-size: 0.85rem; }
        .select-bar a { color: var(--primary); cursor: pointer; text-decoration: underline; }

        /* Explorador de archivos */
        .file-explorer {
            border: 1px solid var(--border);
            border-radius: 8px;
            max-height: 500px;
            overflow-y: auto;
            padding: 10px;
            font-size: 0.88rem;
        }
        .tree-node { margin-left: 18px; }
        .tree-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 8px;
            border-radius: 5px;
            cursor: default;
            transition: background 0.15s;
        }
        .tree-item:hover { background: #edf2f7; }
        .tree-item .icon { width: 18px; text-align: center; }
        .tree-item .name { flex: 1; }
        .tree-item .actions { display: flex; gap: 6px; opacity: 0; transition: opacity 0.15s; }
        .tree-item:hover .actions { opacity: 1; }
        .tree-item .actions button {
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 0.85rem;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .tree-item .actions .act-rename { color: var(--warning); }
        .tree-item .actions .act-delete { color: var(--danger); }
        .tree-item .actions button:hover { background: #e2e8f0; }
        .folder-toggle { cursor: pointer; user-select: none; }
        .folder-toggle .icon-folder { color: var(--warning); }
        .file-icon { color: var(--text-muted); }
        .ext-badge {
            font-size: 0.7rem;
            background: #e2e8f0;
            color: var(--text-muted);
            padding: 1px 6px;
            border-radius: 10px;
            margin-left: 6px;
        }
        .breadcrumb {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 10px;
            padding: 8px 12px;
            background: #f7fafc;
            border-radius: 6px;
        }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .section { padding: 15px; }
            .db-actions { flex-direction: column; align-items: stretch; }
        }
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
        <div class="breadcrumb"><i class="fas fa-house"></i> Raíz del proyecto</div>
        <div class="file-explorer" id="fileExplorer"></div>
    </div>

    <script>
        const base_url = "<?php echo $baseUrl; ?>";
        const treeData = <?php echo $treeJson; ?>;

        /* ===================== TOAST HELPER ===================== */
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: function (t) { t.addEventListener('mouseenter', Swal.stopTimer); t.addEventListener('mouseleave', Swal.resumeTimer); }
        });
        function toast(title, icon) { Toast.fire({ icon: icon, title: title }); }

        /* ===================== EXPORTAR BD ===================== */
        let loadedTables = [];

        function loadDatabaseTables() {
            $.ajax({
                url: base_url + 'Login/getDatabaseTables',
                type: 'GET',
                dataType: 'json',
                success: function (resp) {
                    if (resp.success && Array.isArray(resp.data)) {
                        loadedTables = resp.data;
                        renderTablesList();
                    } else {
                        toast(resp.message || 'No se pudieron obtener las tablas.', 'warning');
                    }
                },
                error: function () { toast('No se pudo conectar con el servidor.', 'error'); }
            });
        }

        function renderTablesList() {
            const $list = $('#tablesList');
            $list.empty();
            loadedTables.forEach(function (t) {
                $list.append(
                    `<div class="table-item">
                        <input type="checkbox" class="table-check" value="${t}">
                        <span>${t}</span>
                    </div>`
                );
            });
            $list.addClass('active');
            $('#selectBar').show();
        }

        function getSelectedTables() {
            return $('.table-check:checked').map(function () { return $(this).val(); }).get();
        }

        function exportTables(withData) {
            const selected = getSelectedTables();
            if (selected.length === 0) {
                toast('Selecciona al menos una tabla para exportar.', 'info');
                return;
            }
            // Si es vacía (solo estructura), enviamos flag para que el backend omita datos
            const form = $('<form>', {
                method: 'POST',
                action: base_url + 'Login/exportTables' + (withData ? '' : '?empty=1')
            });
            form.append($('<input>', { type: 'hidden', name: 'tables', value: JSON.stringify(selected) }));
            $('body').append(form);
            form.submit();
            form.remove();
        }

        function deleteTablesSubmit() {
            const selected = getSelectedTables();
            if (selected.length === 0) {
                toast('Selecciona al menos una tabla para eliminar.', 'info');
                return;
            }
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esta acción eliminará las tablas seleccionadas (excepto las protegidas).',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e3342f'
            }).then(function (r) {
                if (r.isConfirmed) {
                    $.ajax({
                        url: base_url + 'Login/deleteTables',
                        type: 'POST',
                        data: { tables: JSON.stringify(selected) },
                        dataType: 'json',
                        success: function (resp) {
                            toast(resp.message, resp.success ? 'success' : 'error');
                            if (resp.success) loadDatabaseTables();
                        },
                        error: function () { toast('No se pudo completar la solicitud.', 'error'); }
                    });
                }
            });
        }

        /* ===================== EXPLORADOR DE ARCHIVOS ===================== */
        function renderTree(nodes, $container) {
            nodes.forEach(function (node) {
                const $row = $('<div>', { class: 'tree-item' });
                const $icon = $('<span>', { class: 'icon' });
                const $name = $('<span>', { class: 'name' }).text(node.name);

                if (node.type === 'folder') {
                    $icon.html('<i class="fas fa-folder icon-folder"></i>');
                    $row.addClass('folder-toggle');
                    const $children = $('<div>', { class: 'tree-node' }).hide();
                    $row.append($icon, $name);
                    $row.on('click', function (e) {
                        e.stopPropagation();
                        if ($children.children().length === 0) {
                            // Carga perezosa vía AJAX
                            loadSubdir(node.path, $children);
                        }
                        $children.slideToggle(100);
                        $icon.find('i').toggleClass('fa-folder fa-folder-open');
                    });
                    $children.insertAfter($row);
                } else {
                    const ext = node.name.split('.').pop().toLowerCase();
                    $icon.html('<i class="fas fa-file file-icon"></i>');
                    $name.append(`<span class="ext-badge">${ext}</span>`);
                    $row.append($icon, $name);
                }

                // Acciones (eliminar / renombrar)
                const $actions = $('<span>', { class: 'actions' });
                $actions.append($('<button>', { class: 'act-rename', html: '<i class="fas fa-pen"></i>' })
                    .on('click', function (e) { e.stopPropagation(); handleRename(node); }));
                $actions.append($('<button>', { class: 'act-delete', html: '<i class="fas fa-trash"></i>' })
                    .on('click', function (e) { e.stopPropagation(); handleDelete(node); }));
                $row.append($actions);

                $container.append($row);
            });
        }

        function loadSubdir(relPath, $container) {
            // Usamos un endpoint ligero para listar subdirectorios
            $.ajax({
                url: base_url + 'Login/listDirectory',
                type: 'GET',
                data: { path: relPath },
                dataType: 'json',
                success: function (resp) {
                    if (resp.success && Array.isArray(resp.data)) {
                        renderTree(resp.data, $container);
                    } else {
                        toast(resp.message || 'No se pudo listar el directorio.', 'warning');
                    }
                },
                error: function () { toast('No se pudo cargar el directorio.', 'error'); }
            });
        }

        function handleDelete(node) {
            Swal.fire({
                title: '¿Eliminar ' + (node.type === 'folder' ? 'carpeta' : 'archivo') + '?',
                text: node.path,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e3342f'
            }).then(function (r) {
                if (!r.isConfirmed) return;
                const endpoint = node.type === 'folder' ? 'Login/deleteDirectory' : 'Login/deleteFile';
                $.ajax({
                    url: base_url + endpoint,
                    type: 'POST',
                    data: { path: node.path },
                    dataType: 'json',
                    success: function (resp) {
                        toast(resp.message, resp.success ? 'success' : 'error');
                        if (resp.success) location.reload();
                    },
                    error: function () { toast('No se pudo completar.', 'error'); }
                });
            });
        }

        function handleRename(node) {
            Swal.fire({
                title: 'Renombrar',
                input: 'text',
                inputValue: node.name,
                inputAttributes: { autocapitalize: 'off' },
                showCancelButton: true,
                confirmButtonText: 'Renombrar',
                cancelButtonText: 'Cancelar',
                inputValidator: function (value) {
                    if (!value || value === node.name) return 'Ingresa un nombre distinto';
                }
            }).then(function (r) {
                if (!r.isConfirmed) return;
                const dir = node.path.substring(0, node.path.lastIndexOf('/'));
                const newPath = (dir ? dir + '/' : '') + r.value;
                $.ajax({
                    url: base_url + 'Login/renameFileOrDirectory',
                    type: 'POST',
                    data: { oldPath: node.path, newPath: newPath },
                    dataType: 'json',
                    success: function (resp) {
                        toast(resp.message, resp.success ? 'success' : 'error');
                        if (resp.success) location.reload();
                    },
                    error: function () { toast('No se pudo completar.', 'error'); }
                });
            });
        }

        /* ===================== INIT ===================== */
        $(function () {
            renderTree(treeData, $('#fileExplorer'));

            $('#btnLoadTables').on('click', loadDatabaseTables);
            $('#btnExportData').on('click', function () { exportTables(true); });
            $('#btnExportEmpty').on('click', function () { exportTables(false); });
            $('#btnDeleteTables').on('click', deleteTablesSubmit);
            $('#selectAllTables').on('click', function () { $('.table-check').prop('checked', true); });
            $('#deselectAllTables').on('click', function () { $('.table-check').prop('checked', false); });
        });
    </script>
</body>

</html>
