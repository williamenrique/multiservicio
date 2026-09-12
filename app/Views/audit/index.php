<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-black text-navy-blue uppercase tracking-wider">Bitácora de Auditoría</h1>
            <p class="text-slate-500 text-sm font-medium">Historial de acciones y seguridad del sistema</p>
        </div>
        <div class="flex gap-2">
            <button onclick="cargarLogs()" class="bg-white border border-slate-200 p-2 rounded-xl hover:bg-slate-50 transition-all shadow-sm">
                <i data-lucide="refresh-cw" class="w-5 h-5 text-slate-600"></i>
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
        <form id="audit-filters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Desde</label>
                <input type="date" name="desde" id="filter-desde" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Hasta</label>
                <input type="date" name="hasta" id="filter-hasta" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Usuario</label>
                <select name="usuario_id" id="filter-usuario" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo $u->id; ?>"><?php echo s($u->username); ?> (<?php echo s($u->staff_name ?? ''); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Módulo</label>
                <select name="modulo" id="filter-modulo" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                    <option value="">Todos</option>
                    <?php foreach ($modulos as $m): ?>
                        <option value="<?php echo s($m->modulo); ?>"><?php echo s($m->modulo); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Acción</label>
                <select name="accion" id="filter-accion" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                    <option value="">Todas</option>
                    <?php foreach ($acciones as $a): ?>
                        <option value="<?php echo s($a->accion); ?>"><?php echo s($a->accion); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Buscar</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                    <input type="text" name="search" id="filter-search" placeholder="Buscar en descripción, usuario..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                </div>
            </div>
            <div class="lg:col-span-2 flex items-end gap-2">
                <button type="button" onclick="cargarLogs(1)" class="bg-neon-green text-black px-4 py-2 rounded-lg font-black text-sm uppercase hover:opacity-90 transition flex items-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4"></i> Filtrar
                </button>
                <button type="button" onclick="limpiarFiltros()" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg font-black text-sm uppercase hover:bg-slate-50 transition flex items-center gap-2">
                    <i data-lucide="filter-x" class="w-4 h-4"></i> Limpiar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de Logs -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha y Hora</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Usuario</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Módulo</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Acción</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Descripción</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">IP</th>
                    </tr>
                </thead>
                <tbody id="logs-body" class="divide-y divide-slate-50">
                    <!-- Se carga mediante JS -->
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-8 h-8 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin"></div>
                                <p class="text-xs font-bold uppercase tracking-widest">Cargando registros...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <div id="audit-pagination" class="px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-3 border-t border-slate-100 hidden">
            <p id="audit-info" class="text-xs text-slate-500 font-medium"></p>
            <div id="audit-pagination-controls" class="flex gap-1"></div>
        </div>
    </div>
</div>

<script>
let auditPage = 1;
let auditLimit = 50;
let auditTotalPages = 1;

async function cargarLogs(page = 1) {
    auditPage = page;
    const tbody = document.getElementById('logs-body');
    
    // Obtener valores de filtros
    const filters = {
        limit: auditLimit,
        page: auditPage,
        desde: document.getElementById('filter-desde').value || null,
        hasta: document.getElementById('filter-hasta').value || null,
        usuario_id: document.getElementById('filter-usuario').value || null,
        modulo: document.getElementById('filter-modulo').value || null,
        accion: document.getElementById('filter-accion').value || null,
        search: document.getElementById('filter-search').value.trim() || null
    };

    try {
        const res = await fetch(`${URLROOT}/audit/listar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(filters)
        });
        const result = await res.json();

        if (!result.success || result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-slate-400 uppercase text-xs font-bold tracking-widest">No hay registros encontrados</td></tr>';
            document.getElementById('audit-pagination').classList.add('hidden');
            return;
        }

        tbody.innerHTML = result.data.map(log => {
            const badgeColor = log.accion === 'DELETE' ? 'bg-red-100 text-red-600' : 
                               log.accion === 'CREATE' ? 'bg-green-100 text-green-600' : 
                               log.accion === 'LOGIN' ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-600';
            
            return `
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 text-xs font-bold text-navy-blue">${new Date(log.fecha).toLocaleString()}</td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-xs font-black text-slate-700 uppercase">${log.username || 'Sistema'}</span>
                            <span class="text-[10px] text-slate-400">${log.staff_name || ''}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4"><span class="text-[10px] font-black bg-slate-100 text-slate-500 px-2 py-1 rounded-md uppercase">${log.modulo}</span></td>
                    <td class="px-6 py-4"><span class="text-[10px] font-black ${badgeColor} px-2 py-1 rounded-md uppercase">${log.accion}</span></td>
                    <td class="px-6 py-4 text-xs text-slate-600 font-medium">${log.descripcion}</td>
                    <td class="px-6 py-4 text-[10px] font-mono text-slate-400">${log.ip_address}</td>
                </tr>
            `;
        }).join('');
        
        // Actualizar paginación
        auditTotalPages = result.total_paginas || 1;
        const total = result.total || 0;
        const start = (auditPage - 1) * auditLimit + 1;
        const end = Math.min(auditPage * auditLimit, total);
        
        document.getElementById('audit-info').textContent = `Mostrando ${start} a ${end} de ${total} registros - Página ${auditPage} de ${auditTotalPages}`;
        renderAuditPagination(auditPage, auditTotalPages);
        document.getElementById('audit-pagination').classList.remove('hidden');
        
        if (window.lucide) lucide.createIcons();
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-red-400 uppercase text-xs font-bold tracking-widest">Error al cargar los datos</td></tr>';
        document.getElementById('audit-pagination').classList.add('hidden');
    }
}

function renderAuditPagination(page, totalPages) {
    const ctrl = document.getElementById('audit-pagination-controls');
    if (totalPages <= 1) { ctrl.innerHTML = ''; return; }
    let html = '';
    html += `<button onclick="cargarLogs(${Math.max(1, page - 1)})" class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 hover:bg-slate-100 ${page === 1 ? 'opacity-40 cursor-not-allowed' : ''}" ${page === 1 ? 'disabled' : ''}><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
            html += `<button onclick="cargarLogs(${i})" class="px-3 py-1.5 rounded-lg text-xs font-bold border ${i === page ? 'bg-navy-blue text-white border-navy-blue' : 'border-slate-200 hover:bg-slate-100'}">${i}</button>`;
        } else if (i === page - 2 || i === page + 2) {
            html += `<span class="px-2 text-slate-400">...</span>`;
        }
    }
    html += `<button onclick="cargarLogs(${Math.min(totalPages, page + 1)})" class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 hover:bg-slate-100 ${page === totalPages ? 'opacity-40 cursor-not-allowed' : ''}" ${page === totalPages ? 'disabled' : ''}><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
    ctrl.innerHTML = html;
    if (window.lucide) lucide.createIcons();
}

function limpiarFiltros() {
    document.getElementById('filter-desde').value = '';
    document.getElementById('filter-hasta').value = '';
    document.getElementById('filter-usuario').value = '';
    document.getElementById('filter-modulo').value = '';
    document.getElementById('filter-accion').value = '';
    document.getElementById('filter-search').value = '';
    cargarLogs(1);
}

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    cargarLogs(1);
    lucide.createIcons();
});
</script>