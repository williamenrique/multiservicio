<?php if (!defined('URLROOT')) exit('No direct script access allowed'); ?>
<div class="container mx-auto p-6 space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight">Plantillas de Email</h1>
            <p class="text-gray-400 mt-1">Gestiona las plantillas predefinidas para envío rápido de emails</p>
        </div>
        <button onclick="abrirModalPlantilla()" class="bg-neon-green text-navy-blue px-5 py-3 rounded-xl font-black flex items-center gap-2 transition-all hover:brightness-110 shadow-lg shadow-neon-green/20">
            <i data-lucide="plus-circle" class="w-5 h-5"></i> Nueva Plantilla
        </button>
    </div>

    <!-- Tabla de Plantillas -->
    <div class="glass-card rounded-2xl overflow-hidden shadow-xl border border-slate-100">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[11px] font-black uppercase tracking-widest border-b border-slate-100">
                        <th class="px-6 py-4">Nombre</th>
                        <th class="px-6 py-4">Tipo</th>
                        <th class="px-6 py-4">Asunto</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4">Creada</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="plantillas-body" class="divide-y divide-slate-50">
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-slate-400">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-8 h-8 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin"></div>
                                <p class="text-xs font-bold uppercase tracking-widest">Cargando plantillas...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Plantilla -->
<div id="plantillaModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden overflow-y-auto">
    <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl overflow-hidden my-auto max-h-[90vh] flex flex-col">
        <div class="p-4 sm:p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50 sticky top-0 z-10">
            <h2 id="plantillaModalTitle" class="text-lg sm:text-xl font-bold text-navy-blue uppercase tracking-wider">Nueva Plantilla</h2>
            <button id="btnClosePlantillaModal" class="text-gray-500 hover:text-navy-blue"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
        
        <form id="formPlantilla" class="p-4 sm:p-6 space-y-5 overflow-y-auto flex-1">
            <input type="hidden" name="id" id="plantilla-id">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Nombre *</label>
                    <input type="text" name="nombre" id="plantilla-nombre" required placeholder="Ej: Factura de Venta" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Tipo *</label>
                    <select name="tipo" id="plantilla-tipo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                        <option value="OTRO">Otro</option>
                        <option value="FACTURA">Factura</option>
                        <option value="PRESUPUESTO">Presupuesto</option>
                        <option value="NOTIFICACION">Notificación</option>
                        <option value="RECUPERACION">Recuperación</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Asunto *</label>
                <input type="text" name="asunto" id="plantilla-asunto" required placeholder="Asunto con variables: {{cliente_nombre}}, {{numero_factura}}, etc." class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Cuerpo HTML *</label>
                <textarea name="cuerpo_html" id="plantilla-cuerpo" rows="12" required placeholder="Cuerpo del email en HTML con variables {{variable}}" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all font-mono text-sm resize-none"></textarea>
                <div class="mt-2 p-3 bg-blue-50 rounded-xl border border-blue-100">
                    <p class="text-[10px] font-black text-blue-700 uppercase tracking-wider mb-2">Variables disponibles:</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-1 text-xs text-blue-600 font-mono">
                        <span>{{empresa_nombre}}</span>
                        <span>{{empresa_nit}}</span>
                        <span>{{empresa_direccion}}</span>
                        <span>{{empresa_telefono}}</span>
                        <span>{{empresa_email}}</span>
                        <span>{{cliente_nombre}}</span>
                        <span>{{cliente_email}}</span>
                        <span>{{cliente_telefono}}</span>
                        <span>{{cliente_direccion}}</span>
                        <span>{{numero_factura}}</span>
                        <span>{{numero_presupuesto}}</span>
                        <span>{{total_formateado}}</span>
                        <span>{{fecha_vencimiento}}</span>
                        <span>{{fecha_factura}}</span>
                        <span>{{vehiculo_placa}}</span>
                        <span>{{vehiculo_marca}}</span>
                        <span>{{vehiculo_modelo}}</span>
                        <span>{{asunto_personalizado}}</span>
                        <span>{{cuerpo_mensaje}}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="activo" id="plantilla-activo" value="1" checked class="w-4 h-4 text-neon-green border-slate-300 rounded focus:ring-neon-green">
                    <span class="text-sm font-medium text-slate-700">Activa</span>
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="cerrarModalPlantilla()" class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-xl font-black text-sm uppercase hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="bg-neon-green text-navy-blue px-6 py-3 rounded-xl font-black text-sm uppercase hover:brightness-110 transition shadow-lg shadow-neon-green/20 flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Guardar Plantilla
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/* ==================== CARGA DE PLANTILLAS ==================== */
async function cargarPlantillas() {
    const tbody = document.getElementById('plantillas-body');
    
    try {
        const res = await fetch(`${URLROOT}/email/plantillas/listar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await res.json();

        if (!data.success || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-16 text-center text-slate-400 uppercase text-xs font-bold tracking-widest">No hay plantillas registradas</td></tr>';
            return;
        }

        tbody.innerHTML = data.data.map(p => {
            const estadoBadge = p.activo 
                ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">Activa</span>'
                : '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-500">Inactiva</span>';
            
            const tipoBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-600">${p.tipo}</span>`;
            
            return `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 font-bold text-slate-700">${p.nombre}</td>
                    <td class="px-6 py-4">${tipoBadge}</td>
                    <td class="px-6 py-4 text-xs text-slate-600 truncate max-w-xs">${p.asunto}</td>
                    <td class="px-6 py-4">${estadoBadge}</td>
                    <td class="px-6 py-4 text-xs text-slate-500">${new Date(p.fecha_creacion).toLocaleDateString()}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex gap-1 justify-end">
                            <button onclick="editarPlantilla(${p.id})" class="p-2 bg-slate-100 hover:bg-neon-green hover:text-black text-slate-500 rounded-lg transition-all" title="Editar">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button onclick="eliminarPlantilla(${p.id})" class="p-2 bg-slate-100 hover:bg-red-500 hover:text-black text-slate-500 rounded-lg transition-all" title="Eliminar">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        }).join('');
        
        if (window.lucide) lucide.createIcons();
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-16 text-center text-red-400 uppercase text-xs font-bold tracking-widest">Error al cargar los datos</td></tr>';
    }
}

/* ==================== MODAL PLANTILLA ==================== */
function abrirModalPlantilla(plantilla = null) {
    const modal = document.getElementById('plantillaModal');
    const form = document.getElementById('formPlantilla');
    form.reset();
    document.getElementById('plantilla-id').value = '';
    document.getElementById('plantillaModalTitle').textContent = 'Nueva Plantilla';
    modal.classList.remove('hidden');
    lucide.createIcons();
    
    if (plantilla) {
        document.getElementById('plantillaModalTitle').textContent = 'Editar Plantilla';
        document.getElementById('plantilla-id').value = plantilla.id;
        document.getElementById('plantilla-nombre').value = plantilla.nombre;
        document.getElementById('plantilla-tipo').value = plantilla.tipo;
        document.getElementById('plantilla-asunto').value = plantilla.asunto;
        document.getElementById('plantilla-cuerpo').value = plantilla.cuerpo_html;
        document.getElementById('plantilla-activo').checked = plantilla.activo == 1;
    }
}

function cerrarModalPlantilla() {
    document.getElementById('plantillaModal').classList.add('hidden');
}

/* ==================== GUARDAR PLANTILLA ==================== */
document.getElementById('formPlantilla')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Guardando...';
    if (window.lucide) lucide.createIcons();
    
    try {
        const res = await fetch(`${URLROOT}/email/guardarPlantilla`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData
        });
        const result = await res.json();
        
        if (result.success) {
            AppUtils.showToast('Plantilla guardada correctamente', 'success');
            cerrarModalPlantilla();
            cargarPlantillas();
        } else {
            AppUtils.showToast(result.mensaje || 'Error al guardar', 'error');
        }
    } catch (e) {
        AppUtils.showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (window.lucide) lucide.createIcons();
    }
});

async function editarPlantilla(id) {
    try {
        const res = await fetch(`${URLROOT}/email/plantillas/obtener/${id}`);
        const data = await res.json();
        if (data.success && data.plantilla) {
            abrirModalPlantilla(data.plantilla);
        }
    } catch (e) {
        AppUtils.showToast('Error al cargar plantilla', 'error');
    }
}

async function eliminarPlantilla(id) {
    const result = await Swal.fire({
        title: '¿Eliminar plantilla?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        try {
            const res = await fetch(`${URLROOT}/email/eliminarPlantilla/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
            });
            const data = await res.json();
            
            if (data.success) {
                AppUtils.showToast('Plantilla eliminada', 'success');
                cargarPlantillas();
            } else {
                AppUtils.showToast(data.mensaje || 'Error al eliminar', 'error');
            }
        } catch (e) {
            AppUtils.showToast('Error de conexión', 'error');
        }
    }
}

/* ==================== INIT ==================== */
document.addEventListener('DOMContentLoaded', () => {
    cargarPlantillas();
    lucide.createIcons();
});
</script>