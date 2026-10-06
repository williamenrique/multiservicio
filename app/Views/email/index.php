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
                    <option value="FACTURA">🧾 Factura</option>
                    <option value="PRESUPUESTO">📄 Presupuesto</option>
                    <option value="ORDEN_SERVICIO">🔧 Orden de Servicio</option>
                    <option value="PEDIDO_CATALOGO">📦 Pedido Catálogo</option>
                    <option value="NOTIFICACION">🔔 Notificación</option>
                    <option value="RECUPERACION">🔑 Recuperación</option>
                    <option value="ALERTA_PROVEEDOR">⚠️ Alerta Proveedor</option>
                    <option value="RESUMEN_MENSUAL">📊 Resumen Mensual</option>
                    <option value="GARANTIA">🛡️ Garantía</option>
                    <option value="OTRO">✉️ Otro</option>
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
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Buscar</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                    <input type="text" name="search" id="filter-search" placeholder="Buscar por email, nombre, asunto..."
                        class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                </div>
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
                        <th class="px-6 py-4">Referencia</th>
                        <th class="px-6 py-4">Destinatario</th>
                        <th class="px-6 py-4">Asunto</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4">Enviado por</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="emails-body" class="divide-y divide-slate-50">
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center text-slate-400">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-8 h-8 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin"></div>
                                <p class="text-xs font-bold uppercase tracking-widest">Cargando emails...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
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
                <textarea name="cuerpo_html" id="compose-cuerpo" rows="10" required placeholder="Contenido del email en HTML..." class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all font-mono text-sm resize-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Adjuntos</label>
                <input type="file" name="adjuntos[]" id="compose-adjuntos" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 outline-none focus:border-neon-green focus:ring-1 focus:ring-neon-green transition-all">
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

<!-- ============================================================= -->
<!-- PANEL LATERAL DE DETALLE                                       -->
<!-- ============================================================= -->
<div id="detalleEmailOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 hidden transition-opacity" onclick="cerrarPanelDetalle()"></div>

<aside id="detalleEmailPanel" class="fixed top-0 right-0 h-full w-full max-w-3xl bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 flex flex-col">

    <!-- Header -->
    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center shrink-0">
        <div class="flex items-center gap-3">
            <div id="detalle-icono" class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-xl">✉️</div>
            <div>
                <h2 id="detalle-titulo" class="text-base font-black text-navy-blue uppercase tracking-wider">Detalle del Email</h2>
                <p id="detalle-subtitulo" class="text-[11px] text-slate-500"></p>
            </div>
        </div>
        <button onclick="cerrarPanelDetalle()" class="p-2 text-slate-400 hover:text-navy-blue hover:bg-slate-200 rounded-lg transition">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- Contenido scrolleable -->
    <div class="flex-1 overflow-y-auto">

        <!-- Metadatos -->
        <div class="p-5 border-b border-slate-100">
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="bg-slate-50 p-3 rounded-lg">
                    <p class="font-black text-slate-400 uppercase text-[10px] mb-0.5">Tipo</p>
                    <p id="detalle-tipo" class="font-bold text-slate-700">—</p>
                </div>
                <div class="bg-slate-50 p-3 rounded-lg">
                    <p class="font-black text-slate-400 uppercase text-[10px] mb-0.5">Estado</p>
                    <p id="detalle-estado" class="font-bold">—</p>
                </div>
                <div class="bg-slate-50 p-3 rounded-lg">
                    <p class="font-black text-slate-400 uppercase text-[10px] mb-0.5">Fecha creación</p>
                    <p id="detalle-fecha-creacion" class="font-bold text-slate-700">—</p>
                </div>
                <div class="bg-slate-50 p-3 rounded-lg">
                    <p class="font-black text-slate-400 uppercase text-[10px] mb-0.5">Fecha envío</p>
                    <p id="detalle-fecha-envio" class="font-bold text-slate-700">—</p>
                </div>
                <div class="bg-slate-50 p-3 rounded-lg">
                    <p class="font-black text-slate-400 uppercase text-[10px] mb-0.5">Enviado por</p>
                    <p id="detalle-usuario" class="font-bold text-slate-700">—</p>
                </div>
                <div class="bg-slate-50 p-3 rounded-lg">
                    <p class="font-black text-slate-400 uppercase text-[10px] mb-0.5">Referencia</p>
                    <p id="detalle-referencia" class="font-bold text-slate-700">—</p>
                </div>
                <div class="col-span-2 bg-slate-50 p-3 rounded-lg">
                    <p class="font-black text-slate-400 uppercase text-[10px] mb-0.5">Para</p>
                    <p id="detalle-destinatario" class="font-bold text-slate-700">—</p>
                </div>
                <div class="col-span-2 bg-slate-50 p-3 rounded-lg">
                    <p class="font-black text-slate-400 uppercase text-[10px] mb-0.5">Asunto</p>
                    <p id="detalle-asunto" class="font-bold text-slate-700">—</p>
                </div>
                <div id="detalle-error-box" class="col-span-2 bg-red-50 border border-red-100 p-3 rounded-lg hidden">
                    <p class="font-black text-red-500 uppercase text-[10px] mb-0.5">Error</p>
                    <p id="detalle-error" class="font-bold text-red-600 text-xs"></p>
                </div>
            </div>
        </div>

        <!-- Contenido del email (vista limpia) -->
        <div class="p-5">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-3">Contenido</p>
            <div class="border border-slate-200 rounded-xl bg-white p-5">
                <div id="detalle-contenido-limpio" class="email-content-clean"></div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="p-5 border-t border-slate-100 bg-slate-50 flex justify-between items-center gap-3 shrink-0">
        <div id="detalle-adjuntos-wrap" class="text-[11px] text-slate-500"></div>
        <div class="flex gap-2">
            <a id="detalle-btn-sistema" href="#" target="_blank"
               class="hidden bg-blue-600 text-white px-5 py-2.5 rounded-xl font-black text-xs uppercase hover:bg-blue-700 transition shadow-lg shadow-blue-600/20 flex items-center gap-2">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                <span id="detalle-btn-sistema-label">Ver en el sistema</span>
            </a>
            <button id="detalle-btn-reenviar" onclick="reenviarEmailDesdeDetalle()" class="hidden bg-amber-500 text-white px-5 py-2.5 rounded-xl font-black text-xs uppercase hover:bg-amber-600 transition shadow-lg shadow-amber-500/20 flex items-center gap-2">
                <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i> Reenviar
            </button>
            <button onclick="cerrarPanelDetalle()" class="bg-white border border-slate-200 text-slate-600 px-5 py-2.5 rounded-xl font-black text-xs uppercase hover:bg-slate-100 transition">
                Cerrar
            </button>
        </div>
    </div>
</aside>

<!-- ============================================================= -->
<!-- ESTILOS PARA EL CONTENIDO LIMPIO DEL EMAIL                    -->
<!-- ============================================================= -->
<style>
#detalle-contenido-limpio { color: #334155; font-size: 14px; line-height: 1.6; }
#detalle-contenido-limpio h2 {
    color: #1e293b;
    font-size: 15px;
    font-weight: 800;
    margin: 20px 0 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e2e8f0;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
#detalle-contenido-limpio h2:first-child { margin-top: 0; }
#detalle-contenido-limpio p { margin: 8px 0; color: #475569; }
#detalle-contenido-limpio strong { color: #1e293b; font-weight: 700; }

#detalle-contenido-limpio .info-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #1a56db;
    border-radius: 10px;
    padding: 14px 16px;
    margin: 12px 0;
}
#detalle-contenido-limpio .info-box p {
    margin: 4px 0;
    font-size: 13px;
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
#detalle-contenido-limpio .info-box strong { color: #1a56db; }

#detalle-contenido-limpio table.items {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 12px 0;
    font-size: 13px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}
#detalle-contenido-limpio table.items thead th {
    background: #f1f5f9;
    color: #64748b;
    padding: 10px 12px;
    text-align: left;
    font-weight: 800;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #e2e8f0;
}
#detalle-contenido-limpio table.items thead th:last-child,
#detalle-contenido-limpio table.items thead th:nth-last-child(2) { text-align: right; }
#detalle-contenido-limpio table.items tbody td {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}
#detalle-contenido-limpio table.items tbody td:last-child,
#detalle-contenido-limpio table.items tbody td:nth-last-child(2) { text-align: right; font-weight: 600; }
#detalle-contenido-limpio table.items tbody tr:last-child td { border-bottom: 1px solid #e2e8f0; }
#detalle-contenido-limpio table.items tfoot td {
    padding: 10px 12px;
    font-weight: 700;
    color: #1e293b;
    background: #f8fafc;
}
#detalle-contenido-limpio table.items tfoot td:last-child { text-align: right; }
#detalle-contenido-limpio table.items .total-row td {
    background: #eff6ff;
    color: #1a56db;
    font-size: 15px;
    font-weight: 800;
    border-top: 2px solid #1a56db;
}

#detalle-contenido-limpio .highlight {
    color: #1a56db;
    font-weight: 700;
    background: #eff6ff;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    letter-spacing: 0.03em;
}
</style>

<script>
let emailsPage = 1;
let emailsLimit = 20;
let emailsTotalPages = 1;
let emailDetalleActual = null;

/* ==================== HELPERS ==================== */
function getIconoTipo(tipo) {
    const iconos = {
        'ORDEN_SERVICIO':  '🔧',
        'FACTURA':         '🧾',
        'PEDIDO_CATALOGO': '📦',
        'PRESUPUESTO':     '📄',
        'ALERTA_PROVEEDOR':'⚠️',
        'RESUMEN_MENSUAL': '📊',
        'NOTIFICACION':    '🔔',
        'RECUPERACION':    '🔑',
        'GARANTIA':        '🛡️',
        'OTRO':            '✉️'
    };
    return iconos[tipo] || '✉️';
}

function getColorTipo(tipo) {
    const colores = {
        'ORDEN_SERVICIO':  'bg-blue-100 text-blue-700',
        'FACTURA':         'bg-emerald-100 text-emerald-700',
        'PEDIDO_CATALOGO': 'bg-purple-100 text-purple-700',
        'PRESUPUESTO':     'bg-cyan-100 text-cyan-700',
        'ALERTA_PROVEEDOR':'bg-amber-100 text-amber-700',
        'RESUMEN_MENSUAL': 'bg-indigo-100 text-indigo-700',
        'NOTIFICACION':    'bg-yellow-100 text-yellow-700',
        'RECUPERACION':    'bg-pink-100 text-pink-700',
        'GARANTIA':        'bg-teal-100 text-teal-700',
        'OTRO':            'bg-slate-100 text-slate-600'
    };
    return colores[tipo] || 'bg-slate-100 text-slate-600';
}

function getAccionSistema(tipo, id) {
    if (!tipo || tipo === 'NINGUNO' || !id) return null;
    const acciones = {
        'ORDEN':           { url: `${URLROOT}/taller/historial/ORDEN/${id}`,  label: 'Ver Orden de Servicio', icon: 'wrench' },
        'FACTURA':         { url: `${URLROOT}/facturas/ver/${id}`,            label: 'Ver Factura',            icon: 'receipt' },
        'PRESUPUESTO':     { url: `${URLROOT}/presupuesto/ver/${id}`,         label: 'Ver Presupuesto',        icon: 'file-text' },
        'CLIENTE':         { url: `${URLROOT}/clientes`,                       label: 'Ver Cliente',            icon: 'user' },
        'PEDIDO_CATALOGO': { url: `${URLROOT}/catalogo/ver-pedido/${id}`,     label: 'Ver Pedido',             icon: 'package' },
        'PROVEEDOR':       { url: `${URLROOT}/proveedores`,                    label: 'Ver Proveedor',          icon: 'truck' },
        'GARANTIA':        { url: `${URLROOT}/garantia`,                       label: 'Ver Garantía',           icon: 'shield' }
    };
    return acciones[tipo] || null;
}

function buildLinkReferencia(tipo, id) {
    if (!tipo || tipo === 'NINGUNO' || !id) {
        return '<span class="text-slate-300 text-xs">—</span>';
    }
    const accion = getAccionSistema(tipo, id);
    if (!accion) return `<span class="text-xs text-slate-500">${tipo} #${id}</span>`;
    return `<a href="${accion.url}" class="text-blue-600 hover:text-blue-800 hover:underline font-bold text-xs">${tipo} #${id}</a>`;
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function extraerContenidoEmail(htmlCompleto) {
    if (!htmlCompleto) return '<p class="text-slate-400 text-sm italic py-4">(Sin contenido)</p>';
    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlCompleto, 'text/html');
        const body = doc.querySelector('.email-body') || doc.body;

        body.querySelectorAll('a.btn, a.button').forEach(a => a.remove());

        body.querySelectorAll('p').forEach(p => {
            const texto = p.textContent.replace(/\s|\u00a0/g, '');
            if (texto === '' && !p.querySelector('img, table, hr')) {
                p.remove();
            }
        });

        return body.innerHTML;
    } catch (e) {
        console.error('Error parseando HTML del email:', e);
        return '<p class="text-red-400 text-sm italic py-4">Error al parsear el contenido</p>';
    }
}

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
            tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-16 text-center text-slate-400 uppercase text-xs font-bold tracking-widest">No hay emails registrados</td></tr>';
            document.getElementById('emails-pagination').classList.add('hidden');
            return;
        }

        tbody.innerHTML = result.data.map(e => {
            const estadoBadge = e.estado === 'ENVIADO' 
                ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">Enviado</span>'
                : e.estado === 'FALLIDO'
                ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-red-100 text-red-700">Fallido</span>'
                : '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-700">Pendiente</span>';
            
            const icono = getIconoTipo(e.tipo);
            const colorTipo = getColorTipo(e.tipo);
            const tipoBadge = `<span class="px-2 py-1 rounded-full text-[10px] font-black ${colorTipo} whitespace-nowrap">${icono} ${escapeHtml(e.tipo || 'OTRO')}</span>`;
            const linkRef = buildLinkReferencia(e.referencia_tipo, e.referencia_id);
            
            return `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 text-xs font-bold text-navy-blue whitespace-nowrap">${new Date(e.fecha_creacion).toLocaleString()}</td>
                    <td class="px-6 py-4">${tipoBadge}</td>
                    <td class="px-6 py-4">${linkRef}</td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-xs font-black text-slate-700">${escapeHtml(e.destinatario_nombre || e.destinatario_email)}</span>
                            <span class="text-[10px] text-slate-400">${escapeHtml(e.destinatario_email)}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-600 truncate max-w-xs" title="${escapeHtml(e.asunto)}">${escapeHtml(e.asunto)}</td>
                    <td class="px-6 py-4">${estadoBadge}</td>
                    <td class="px-6 py-4 text-xs text-slate-500">${escapeHtml(e.usuario_nombre || 'Sistema')}</td>
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
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-16 text-center text-red-400 uppercase text-xs font-bold tracking-widest">Error al cargar los datos</td></tr>';
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
    const searchField = document.getElementById('filter-search');
    if (searchField) searchField.value = '';
    cargarEmails(1);
}

/* ==================== MODAL COMPOSE ==================== */
function abrirModalCompose() {
    document.getElementById('composeModal').classList.remove('hidden');
    document.getElementById('formComposeEmail').reset();
    document.getElementById('composeModalTitle').textContent = 'Nuevo Email';
    lucide.createIcons();
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
    
    suggestions.innerHTML = matches.map(c => {
        const nombreEscapado = (c.nombre || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        const direccionEscapada = (c.direccion || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        const emailEscapado = (c.email || '').replace(/'/g, "\\'");
        return `
        <div class="px-4 py-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0" 
             onclick="seleccionarClienteEmail('${emailEscapado}', '${nombreEscapado}', '${c.id}', '${direccionEscapada}')">
            <div class="font-medium text-slate-700">${escapeHtml(c.nombre)}</div>
            <div class="text-xs text-slate-500">${escapeHtml(c.email)} · ${escapeHtml(c.id)}</div>
            ${c.direccion ? `<div class="text-[10px] text-slate-400">${escapeHtml(c.direccion)}</div>` : ''}
        </div>
    `;
    }).join('');
    suggestions.classList.remove('hidden');
}

function seleccionarClienteEmail(email, nombre, id, direccion = '') {
    document.getElementById('compose-email').value = email;
    document.getElementById('compose-email-nombre').value = nombre;
    document.getElementById('compose-ref-id').value = id;
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

/* ==================== PANEL DE DETALLE ==================== */
async function verEmail(id) {
    try {
        // AppUtils.showLoading('Cargando email...');
        const res = await fetch(`${URLROOT}/email/obtener/${id}`);
        const data = await res.json();
        // AppUtils.hideLoading();
        
        if (!data.success || !data.email) {
            AppUtils.showToast('No se pudo cargar el email', 'error');
            return;
        }
        
        const e = data.email;
        emailDetalleActual = e;

        const icono = getIconoTipo(e.tipo);
        document.getElementById('detalle-icono').textContent = icono;
        document.getElementById('detalle-titulo').textContent = `Email #${e.id}`;
        document.getElementById('detalle-subtitulo').textContent = e.asunto || '(sin asunto)';

        document.getElementById('detalle-tipo').textContent = `${icono} ${e.tipo || 'OTRO'}`;
        
        const estadoBadge = e.estado === 'ENVIADO' 
            ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">Enviado</span>'
            : e.estado === 'FALLIDO'
            ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-red-100 text-red-700">Fallido</span>'
            : '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-700">Pendiente</span>';
        document.getElementById('detalle-estado').innerHTML = estadoBadge;

        document.getElementById('detalle-fecha-creacion').textContent = new Date(e.fecha_creacion).toLocaleString();
        document.getElementById('detalle-fecha-envio').textContent = e.fecha_envio ? new Date(e.fecha_envio).toLocaleString() : '—';
        document.getElementById('detalle-usuario').textContent = e.usuario_nombre || 'Sistema';
        document.getElementById('detalle-referencia').innerHTML = buildLinkReferencia(e.referencia_tipo, e.referencia_id);
        document.getElementById('detalle-destinatario').innerHTML = 
            `${escapeHtml(e.destinatario_nombre || e.destinatario_email)} &lt;${escapeHtml(e.destinatario_email)}&gt;`;
        document.getElementById('detalle-asunto').textContent = e.asunto || '—';

        const errorBox = document.getElementById('detalle-error-box');
        if (e.error_mensaje) {
            errorBox.classList.remove('hidden');
            document.getElementById('detalle-error').textContent = e.error_mensaje;
        } else {
            errorBox.classList.add('hidden');
        }

        let adjuntos = [];
        try { adjuntos = e.adjuntos ? JSON.parse(e.adjuntos) : []; } catch(_) {}
        document.getElementById('detalle-adjuntos-wrap').innerHTML = adjuntos.length 
            ? `📎 ${adjuntos.length} adjunto(s)` 
            : '';

        document.getElementById('detalle-contenido-limpio').innerHTML = extraerContenidoEmail(e.cuerpo_html);

        const btnSistema = document.getElementById('detalle-btn-sistema');
        const accion = getAccionSistema(e.referencia_tipo, e.referencia_id);
        if (accion) {
            btnSistema.href = accion.url;
            document.getElementById('detalle-btn-sistema-label').textContent = accion.label;
            btnSistema.classList.remove('hidden');
        } else {
            btnSistema.classList.add('hidden');
        }

        const btnReenviar = document.getElementById('detalle-btn-reenviar');
        if (e.estado === 'FALLIDO') {
            btnReenviar.classList.remove('hidden');
        } else {
            btnReenviar.classList.add('hidden');
        }

        document.getElementById('detalleEmailOverlay').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('detalleEmailPanel').classList.remove('translate-x-full');
        }, 10);
        document.body.style.overflow = 'hidden';
        if (window.lucide) lucide.createIcons();

    } catch (err) {
        console.error(err);
        AppUtils.hideLoading();
        AppUtils.showToast('Error de conexión', 'error');
    }
}

function cerrarPanelDetalle() {
    document.getElementById('detalleEmailPanel').classList.add('translate-x-full');
    setTimeout(() => {
        document.getElementById('detalleEmailOverlay').classList.add('hidden');
    }, 300);
    document.body.style.overflow = '';
    emailDetalleActual = null;
}

function reenviarEmailDesdeDetalle() {
    if (emailDetalleActual) {
        reenviarEmail(emailDetalleActual.id);
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
            cerrarPanelDetalle();
            cargarEmails(emailsPage);
        } else {
            AppUtils.showToast(result.mensaje || 'Error al reenviar', 'error');
        }
    } catch (e) {
        AppUtils.hideLoading();
        AppUtils.showToast('Error de conexión', 'error');
    }
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const panel = document.getElementById('detalleEmailPanel');
        if (!panel.classList.contains('translate-x-full')) {
            cerrarPanelDetalle();
        }
    }
});

/* ==================== INIT ==================== */
document.addEventListener('DOMContentLoaded', () => {
    cargarEmails(1);
    lucide.createIcons();
});
</script>