<?php if (!defined('URLROOT')) exit('No direct script access allowed'); ?>
<div class="container mx-auto p-6 space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-navy-blue tracking-tight"><?php echo $data['titulo']; ?></h1>
            <p class="text-gray-400 mt-1">Componer y enviar un nuevo email</p>
        </div>
        <a href="<?php echo URLROOT; ?>/email" class="bg-white border border-slate-200 text-slate-700 px-5 py-3 rounded-xl flex items-center gap-2 transition-all hover:bg-slate-50 text-sm font-semibold shadow-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver al Historial
        </a>
    </div>

    <div class="glass-card p-6 rounded-2xl shadow-sm border border-slate-100">
        <form id="formComposeEmail" class="space-y-6" enctype="multipart/form-data">
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
                        <option value="PEDIDO_CATALOGO">Pedido Catálogo</option>
                        <option value="ORDEN_SERVICIO">Orden de Servicio</option>
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
                        <option value="PEDIDO_CATALOGO">Pedido Catálogo</option>
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
                <textarea name="cuerpo_html" id="compose-cuerpo" rows="12" required placeholder="Contenido del email en HTML..." class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all font-mono text-sm resize-none"></textarea>
                <p class="text-[10px] text-slate-400 mt-1">Variables disponibles: {{cliente_nombre}}, {{empresa_nombre}}, {{total_formateado}}, {{fecha_vencimiento}}, etc.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Adjuntos</label>
                <input type="file" name="adjuntos[]" id="compose-adjuntos" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
                <p class="text-[10px] text-slate-400 mt-1">PDF, imágenes, Word (máx. 10MB c/u)</p>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="<?php echo URLROOT; ?>/email" class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-xl font-black text-sm uppercase hover:bg-slate-50 transition">
                    Cancelar
                </a>
                <button type="submit" class="bg-neon-green text-navy-blue px-6 py-3 rounded-xl font-black text-sm uppercase hover:brightness-110 transition shadow-lg shadow-neon-green/20 flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i> Enviar Email
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/* ==================== MODAL COMPOSE ==================== */
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
    
    suggestions.innerHTML = matches.map(c => {
        const nombreEscapado = c.nombre.replace(/'/g, "'");
        const direccionEscapada = (c.direccion || '').replace(/'/g, "'");
        return `
        <div class="px-4 py-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0" 
             onclick="seleccionarClienteEmail('${c.email}', '${nombreEscapado}', '${c.id}', '${direccionEscapada}')">
            <div class="font-medium text-slate-700">${c.nombre}</div>
            <div class="text-xs text-slate-500">${c.email} · ${c.id}</div>
            ${c.direccion ? `<div class="text-[10px] text-slate-400">${c.direccion}</div>` : ''}
        </div>
    `;
    }).join('');
    suggestions.classList.remove('hidden');
}

function seleccionarClienteEmail(email, nombre, id, direccion = '') {
    document.getElementById('compose-email').value = email;
    document.getElementById('compose-email-nombre').value = nombre;
    document.getElementById('compose-ref-id').value = id;
    // Si existe un campo de dirección en el formulario, llenarlo
    const direccionField = document.getElementById('compose-direccion') || document.getElementById('cliente_direccion');
    if (direccionField && direccion) {
        direccionField.value = direccion;
    }
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
            window.location.href = `${URLROOT}/email`;
        } else {
            AppUtils.showToast(result.mensaje || 'Error al enviar', 'error');
        }
    } catch (err) {
        AppUtils.showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (window.lucide) lucide.createIcons();
    }
});

/* ==================== INIT ==================== */
document.addEventListener('DOMContentLoaded', () => {
    cargarSugerenciasEmails();
    lucide.createIcons();
});
</script>