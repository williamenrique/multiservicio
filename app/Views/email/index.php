<?php if (!defined('URLROOT')) exit('No direct script access allowed'); ?>
<div class="container mx-auto p-6 space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Historial y gestión de emails enviados desde el sistema</p>
        </div>
        <div class="flex gap-3">
            <button onclick="abrirModalCompose()" class="bg-neon-green text-navy-blue px-5 py-3 rounded-xl font-black flex items-center gap-2 transition-all hover:brightness-110 shadow-lg shadow-neon-green/20">
                <i data-lucide="mail-plus" class="w-5 h-5"></i> Nuevo Email
            </button>
            <a href="<?php echo URLROOT; ?>/email/plantillas" class="bg-white border border-slate-200 text-slate-700 px-5 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-slate-50 text-sm font-semibold shadow-sm">
                <i data-lucide="file-text" class="w-4 h-4"></i> Plantillas
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card p-4 rounded-2xl border-l-4 border-blue-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Total Enviados</p>
            <h2 id="stat-total" class="text-xl font-black text-blue-600"><?php echo $data['stats']->total ?? 0; ?></h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-emerald-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Entregados</p>
            <h2 id="stat-enviados" class="text-xl font-black text-emerald-600"><?php echo $data['stats']->enviados ?? 0; ?></h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-red-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Fallidos</p>
            <h2 id="stat-fallidos" class="text-xl font-black text-red-600"><?php echo $data['stats']->fallidos ?? 0; ?></h2>
        </div>
        <div class="glass-card p-4 rounded-2xl border-l-4 border-amber-500 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Pendientes</p>
            <h2 id="stat-pendientes" class="text-xl font-black text-amber-600"><?php echo $data['stats']->pendientes ?? 0; ?></h2>
        </div>
    </div>

    <!-- Filtros -->
    <div class="glass-card p-4 rounded-2xl shadow-sm border border-slate-100">
        <form id="email-filters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Tipo</label>
                <select name="tipo" id="filter-tipo" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                    <option value="">Todos</option>
                    <option value="FACTURA">Factura</option>
                    <option value="PRESUPUESTO">Presupuesto</option>
                    <option value="NOTIFICACION">Notificación</option>
                    <option value="RECUPERACION">Recuperación</option>
                    <option value="OTRO">Otro</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Estado</label>
                <select name="estado" id="filter-estado" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                    <option value="">Todos</option>
                    <option value="ENVIADO">Enviado</option>
                    <option value="FALLIDO">Fallido</option>
                    <option value="PENDIENTE">Pendiente</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Desde</label>
                <input type="date" name="desde" id="filter-desde" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Hasta</label>
                <input type="date" name="hasta" id="filter-hasta" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
            </div>
            <div class="lg:col-span-2 flex items-end gap-2">
                <button type="button" onclick="cargarEmails(1)" class="bg-neon-green text-black px-4 py-2 rounded-lg font-black text-sm uppercase hover:opacity-90 transition flex items-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4"></i> Filtrar
                </button>
                <button type="button" onclick="limpiarFiltrosEmail()" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg font-black text-sm uppercase hover:bg-slate-50 transition flex items-center gap-2">
                    <i data-lucide="filter-x" class="w-4 h-4"></i> Limpiar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de Emails -->
    <div class="glass-card rounded-2xl overflow-hidden shadow-xl border border-slate-100">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                        <th class="px-6 py-4">Fecha</th>
                        <th class="px-6 py-4">Tipo</th>
                        <th class="px-6 py-4">Destinatario</th>
                        <th class="px-6 py-4">Asunto</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4">Enviado por</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="emails-body" class="divide-y divide-slate-50">
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center text-slate-400">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-8 h-8 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin"></div>
                                <p class="text-xs font-bold uppercase tracking-widest">Cargando emails...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <div id="emails-pagination" class="px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-3 border-t border-slate-100 hidden">
            <p id="emails-info" class="text-xs text-slate-500 font-medium"></p>
            <div id="emails-pagination-controls" class="flex gap-1"></div>
        </div>
    </div>
</div>

<!-- Modal Compose Email -->
<div id="composeModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden overflow-y-auto">
    <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl overflow-hidden my-auto max-h-[90vh] flex flex-col">
        <div class="p-4 sm:p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50 sticky top-0 z-10">
            <h2 id="composeModalTitle" class="text-lg sm:text-xl font-bold text-navy-blue uppercase tracking-wider">Nuevo Email</h2>
            <button id="btnCloseComposeModal" class="text-gray-500 hover:text-navy-blue"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
        
        <form id="formComposeEmail" class="p-4 sm:p-6 space-y-5 overflow-y-auto flex-1" enctype="multipart/form-data">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Plantilla <span class="text-emerald-600">(opcional)</span></label>
                    <select name="plantilla_id" id="compose-plantilla" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                        <option value="">-- Seleccionar plantilla --</option>
                        <?php foreach ($plantillas as $p): ?>
                            <option value="<?php echo $p->id; ?>" data-tipo="<?php echo $p->tipo; ?>"><?php echo s($p->nombre); ?> (<?php echo s($p->tipo); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Tipo de Email</label>
                    <select name="tipo" id="compose-tipo" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                        <option value="OTRO">Otro</option>
                        <option value="FACTURA">Factura</option>
                        <option value="PRESUPUESTO">Presupuesto</option>
                        <option value="NOTIFICACION">Notificación</option>
                        <option value="RECUPERACION">Recuperación</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Referencia</label>
                    <select name="referencia_tipo" id="compose-ref-tipo" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                        <option value="NINGUNO">Ninguna</option>
                        <option value="FACTURA">Factura</option>
                        <option value="PRESUPUESTO">Presupuesto</option>
                        <option value="ORDEN">Orden de Servicio</option>
                        <option value="CLIENTE">Cliente</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Para (Email) *</label>
                <div class="relative">
                    <input type="email" name="destinatario_email" id="compose-email" required placeholder="cliente@ejemplo.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                    <div id="email-suggestions" class="absolute z-20 w-full bg-white border border-slate-200 rounded-xl shadow-xl mt-1 hidden max-h-40 overflow-y-auto"></div>
                </div>
                <input type="hidden" name="destinatario_nombre" id="compose-email-nombre">
                <input type="hidden" name="referencia_id" id="compose-ref-id">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Asunto *</label>
                <input type="text" name="asunto" id="compose-asunto" required placeholder="Asunto del email" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Cuerpo (HTML) *</label>
                <textarea name="cuerpo_html" id="compose-cuerpo" rows="10" required placeholder="Contenido del email en HTML..." class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all font-mono text-sm resize-none"></textarea>
                <p class="text-[10px] text-slate-400 mt-1">Variables disponibles: {{cliente_nombre}}, {{empresa_nombre}}, {{total_formateado}}, {{fecha_vencimiento}}, etc.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Adjuntos</label>
                <input type="file" name="adjuntos[]" id="compose-adjuntos" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                <p class="text-[10px] text-slate-400 mt-1">PDF, imágenes, Word (máx. 10MB c/u)</p>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="cerrarModalCompose()" class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-xl font-black text-sm uppercase hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="bg-neon-green text-navy-blue px-6 py-3 rounded-xl font-black text-sm uppercase hover:brightness-110 transition shadow-lg shadow-neon-green/20 flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i> Enviar Email
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let emailsPage = 1;
let emailsLimit = 20;
let emailsTotalPages = 1;

/* ==================== CARGA DE EMAILS ==================== */
async function cargarEmails(page = 1) {
    emailsPage = page;
    const tbody = document.getElementById('emails-body');
    
    const filters = {
        limit: emailsLimit,
        page: emailsPage,
        tipo: document.getElementById('filter-tipo').value || null,
        estado: document.getElementById('filter-estado').value || null,
        desde: document.getElementById('filter-desde').value || null,
        hasta: document.getElementById('filter-hasta').value || null,
        search: document.getElementById('filter-search')?.value?.trim() || null
    };

    try {
        const res = await fetch(`${URLROOT}/email/listar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(filters)
        });
        const result = await res.json();

        if (!result.success || result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-16 text-center text-slate-400 uppercase text-xs font-bold tracking-widest">No hay emails registrados</td></tr>';
            document.getElementById('emails-pagination').classList.add('hidden');
            return;
        }

        tbody.innerHTML = result.data.map(e => {
            const estadoBadge = e.estado === 'ENVIADO' 
                ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">Enviado</span>'
                : e.estado === 'FALLIDO'
                ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-red-100 text-red-700">Fallido</span>'
                : '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-700">Pendiente</span>';
            
            const tipoBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-600">${e.tipo}</span>`;
            
            return `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 text-xs font-bold text-navy-blue">${new Date(e.fecha_creacion).toLocaleString()}</td>
                    <td class="px-6 py-4">${tipoBadge}</td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-xs font-black text-slate-700">${e.destinatario_nombre || e.destinatario_email}</span>
                            <span class="text-[10px] text-slate-400">${e.destinatario_email}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-600 truncate max-w-xs">${e.asunto}</td>
                    <td class="px-6 py-4">${estadoBadge}</td>
                    <td class="px-6 py-4 text-xs text-slate-500">${e.usuario_nombre || 'Sistema'}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex gap-1 justify-end">
                            <button onclick="verEmail(${e.id})" class="p-2 bg-slate-100 hover:bg-neon-green hover:text-black text-slate-500 rounded-lg transition-all" title="Ver detalle">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            ${e.estado === 'FALLIDO' ? `<button onclick="reenviarEmail(${e.id})" class="p-2 bg-amber-100 hover:bg-amber-500 hover:text-black text-amber-600 rounded-lg transition-all" title="Reenviar">
                                <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                            </button>` : ''}
                        </div>
                    </td>
                </tr>`;
        }).join('');
        
        emailsTotalPages = result.total_paginas || 1;
        const total = result.total || 0;
        const start = (emailsPage - 1) * emailsLimit + 1;
        const end = Math.min(emailsPage * emailsLimit, total);
        
        document.getElementById('emails-info').textContent = `Mostrando ${start} a ${end} de ${total} emails - Página ${emailsPage} de ${emailsTotalPages}`;
        renderEmailsPagination(emailsPage, emailsTotalPages);
        document.getElementById('emails-pagination').classList.remove('hidden');
        
        if (window.lucide) lucide.createIcons();
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-16 text-center text-red-400 uppercase text-xs font-bold tracking-widest">Error al cargar los datos</td></tr>';
        document.getElementById('emails-pagination').classList.add('hidden');
    }
}

function renderEmailsPagination(page, totalPages) {
    const ctrl = document.getElementById('emails-pagination-controls');
    if (totalPages <= 1) { ctrl.innerHTML = ''; return; }
    let html = '';
    html += `<button onclick="cargarEmails(${Math.max(1, page - 1)})" class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 hover:bg-slate-100 ${page === 1 ? 'opacity-40 cursor-not-allowed' : ''}" ${page === 1 ? 'disabled' : ''}><i data-lucide="chevron-left" class="w-3 h-3"></i></button>`;
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
            html += `<button onclick="cargarEmails(${i})" class="px-3 py-1.5 rounded-lg text-xs font-bold border ${i === page ? 'bg-navy-blue text-white border-navy-blue' : 'border-slate-200 hover:bg-slate-100'}">${i}</button>`;
        } else if (i === page - 2 || i === page + 2) {
            html += `<span class="px-2 text-slate-400">...</span>`;
        }
    }
    html += `<button onclick="cargarEmails(${Math.min(totalPages, page + 1)})" class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 hover:bg-slate-100 ${page === totalPages ? 'opacity-40 cursor-not-allowed' : ''}" ${page === totalPages ? 'disabled' : ''}><i data-lucide="chevron-right" class="w-3 h-3"></i></button>`;
    ctrl.innerHTML = html;
    if (window.lucide) lucide.createIcons();
}

function limpiarFiltrosEmail() {
    document.getElementById('filter-tipo').value = '';
    document.getElementById('filter-estado').value = '';
    document.getElementById('filter-desde').value = '';
    document.getElementById('filter-hasta').value = '';
    document.getElementById('filter-search')?.value = '';
    cargarEmails(1);
}

/* ==================== MODAL COMPOSE ==================== */
function abrirModalCompose() {
    document.getElementById('composeModal').classList.remove('hidden');
    document.getElementById('formComposeEmail').reset();
    document.getElementById('composeModalTitle').textContent = 'Nuevo Email';
    lucide.createIcons();
    
    // Cargar sugerencias de emails de clientes
    cargarSugerenciasEmails();
}

function cerrarModalCompose() {
    document.getElementById('composeModal').classList.add('hidden');
}

async function cargarSugerenciasEmails() {
    try {
        const res = await fetch(`${URLROOT}/email/getClientes`);
        const data = await res.json();
        if (data.success) {
            window.clientesEmails = data.data;
        }
    } catch (e) {
        console.error(e);
    }
}

function mostrarSugerenciasEmail(input) {
    const suggestions = document.getElementById('email-suggestions');
    const value = input.value.toLowerCase();
    
    if (!value || !window.clientesEmails) {
        suggestions.classList.add('hidden');
        return;
    }
    
    const matches = window.clientesEmails.filter(c => 
        (c.email && c.email.toLowerCase().includes(value)) ||
        (c.nombre && c.nombre.toLowerCase().includes(value)) ||
        (c.id && c.id.toLowerCase().includes(value))
    ).slice(0, 10);
    
    if (matches.length === 0) {
        suggestions.classList.add('hidden');
        return;
    }
    
    suggestions.innerHTML = matches.map(c => `
        <div class="px-4 py-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0" 
             onclick="seleccionarClienteEmail('${c.email}', '${c.nombre.replace(/'/g, "\\'")}', '${c.id}')">
            <div class="font-medium text-slate-700">${c.nombre}</div>
            <div class="text-xs text-slate-500">${c.email} · ${c.id}</div>
        </div>
    `).join('');
    suggestions.classList.remove('hidden');
}

function seleccionarClienteEmail(email, nombre, id) {
    document.getElementById('compose-email').value = email;
    document.getElementById('compose-email-nombre').value = nombre;
    document.getElementById('compose-ref-id').value = id;
    document.getElementById('email-suggestions').classList.add('hidden');
}

document.getElementById('compose-email')?.addEventListener('input', function() {
    mostrarSugerenciasEmail(this);
});

document.getElementById('compose-email')?.addEventListener('focus', function() {
    mostrarSugerenciasEmail(this);
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('#compose-email') && !e.target.closest('#email-suggestions')) {
        document.getElementById('email-suggestions').classList.add('hidden');
    }
});

/* Cambio de plantilla */
document.getElementById('compose-plantilla')?.addEventListener('change', async function() {
    const plantillaId = this.value;
    if (!plantillaId) return;
    
    try {
        const res = await fetch(`${URLROOT}/email/obtenerPlantilla/${plantillaId}`);
        const data = await res.json();
        if (data.success && data.plantilla) {
            document.getElementById('compose-asunto').value = data.plantilla.asunto;
            document.getElementById('compose-cuerpo').value = data.plantilla.cuerpo_html;
            document.getElementById('compose-tipo').value = data.plantilla.tipo;
        }
    } catch (e) {
        console.error(e);
    }
});

/* Envío del formulario */
document.getElementById('formComposeEmail')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Enviando...';
    if (window.lucide) lucide.createIcons();
    
    try {
        const res = await fetch(`${URLROOT}/email/enviar`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const result = await res.json();
        
        if (result.success) {
            AppUtils.showToast(result.mensaje || 'Email enviado correctamente', 'success');
            cerrarModalCompose();
            cargarEmails(1);
        } else {
            AppUtils.showToast(result.mensaje || 'Error al enviar', 'error');
        }
    } catch (e) {
        AppUtils.showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (window.lucide) lucide.createIcons();
    }
});

/* ==================== VER EMAIL ==================== */
async function verEmail(id) {
    try {
        AppUtils.showLoading('Cargando email...');
        const res = await fetch(`${URLROOT}/email/obtener/${id}`);
        const data = await res.json();
        AppUtils.hideLoading();
        
        if (!data.success || !data.email) {
            AppUtils.showToast('No se pudo cargar el email', 'error');
            return;
        }
        
        const e = data.email;
        const estadoBadge = e.estado === 'ENVIADO' 
            ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">Enviado</span>'
            : e.estado === 'FALLIDO'
            ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-red-100 text-red-700">Fallido</span>'
            : '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-700">Pendiente</span>';
        
        Swal.fire({
            title: `EMAIL #${e.id} - ${e.asunto}`,
            html: `
                <div class="text-left space-y-3 text-sm max-h-[70vh] overflow-y-auto pr-1">
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div><p class="font-black text-slate-400 uppercase">Tipo</p><p class="font-bold">${e.tipo}</p></div>
                        <div><p class="font-black text-slate-400 uppercase">Estado</p><p class="font-bold">${estadoBadge}</p></div>
                        <div><p class="font-black text-slate-400 uppercase">Fecha</p><p class="font-bold">${new Date(e.fecha_creacion).toLocaleString()}</p></div>
                        <div><p class="font-black text-slate-400 uppercase">Enviado por</p><p class="font-bold">${e.usuario_nombre || 'Sistema'}</p></div>
                        <div class="sm:col-span-2"><p class="font-black text-slate-400 uppercase">Para</p><p class="font-bold">${e.destinatario_nombre || e.destinatario_email} <${e.destinatario_email}></p></div>
                        <div class="sm:col-span-2"><p class="font-black text-slate-400 uppercase">Asunto</p><p class="font-bold">${e.asunto}</p></div>
                        ${e.referencia_tipo !== 'NINGUNO' ? `<div class="sm:col-span-2"><p class="font-black text-slate-400 uppercase">Referencia</p><p class="font-bold">${e.referencia_tipo} #${e.referencia_id}</p></div>` : ''}
                        ${e.error_mensaje ? `<div class="sm:col-span-2"><p class="font-black text-slate-400 uppercase">Error</p><p class="font-bold text-red-600">${e.error_mensaje}</p></div>` : ''}
                    </div>
                    <div class="pt-4 border-t border-slate-200">
                        <p class="font-black text-slate-400 uppercase mb-2">Cuerpo (HTML)</p>
                        <div class="bg-slate-50 p-4 rounded-lg max-h-60 overflow-y-auto font-mono text-xs text-slate-600 whitespace-pre-wrap">${e.cuerpo_html}</div>
                    </div>
                </div>`,
            width: '800px',
            showCancelButton: true,
            confirmButtonText: e.estado === 'FALLIDO' ? 'Reenviar' : 'Cerrar',
            cancelButtonText: 'Cerrar',
            confirmButtonColor: e.estado === 'FALLIDO' ? '#f59e0b' : '#64748b'
        }).then((result) => {
            if (result.isConfirmed && e.estado === 'FALLIDO') {
                reenviarEmail(e.id);
            }
        });
    } catch (e) {
        AppUtils.hideLoading();
        AppUtils.showToast('Error de conexión', 'error');
    }
}

async function reenviarEmail(id) {
    try {
        AppUtils.showLoading('Reenviando...');
        const res = await fetch(`${URLROOT}/email/reenviar/${id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const result = await res.json();
        AppUtils.hideLoading();
        
        if (result.success) {
            AppUtils.showToast('Email reenviado', 'success');
            cargarEmails(emailsPage);
        } else {
            AppUtils.showToast(result.mensaje || 'Error al reenviar', 'error');
        }
    } catch (e) {
        AppUtils.hideLoading();
        AppUtils.showToast('Error de conexión', 'error');
    }
}

/* ==================== INIT ==================== */
document.addEventListener('DOMContentLoaded', () => {
    cargarEmails(1);
    lucide.createIcons();
});
</script>