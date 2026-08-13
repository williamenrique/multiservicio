<?php if (!defined('URLROOT')) exit('No direct script access allowed'); ?>
<div class="container mx-auto p-6 space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-navy-blue uppercase tracking-tight flex items-center gap-3">
                <i data-lucide="undo-2" class="w-8 h-8 text-neon-green"></i>
                Devoluciones
            </h1>
            <p class="text-sm text-slate-500 font-medium mt-1">Gestión de devoluciones de repuestos con validación de garantía</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-slate-200">
        <button id="tab-nueva" onclick="cambiarTabDevolucion('nueva')"
            class="px-6 py-3 font-black text-sm uppercase tracking-wider border-b-2 border-neon-green text-navy-blue transition-all">
            <i data-lucide="plus-circle" class="w-4 h-4 inline mr-2"></i>Nueva Devolución
        </button>
        <button id="tab-historial" onclick="cambiarTabDevolucion('historial')"
            class="px-6 py-3 font-black text-sm uppercase tracking-wider border-b-2 border-transparent text-slate-400 hover:text-navy-blue transition-all">
            <i data-lucide="history" class="w-4 h-4 inline mr-2"></i>Historial
        </button>
    </div>

    <!-- ==================== SECCIÓN: NUEVA DEVOLUCIÓN ==================== -->
    <section id="sec-nueva" class="space-y-6">

        <!-- Buscador de facturas -->
        <div class="glass-card rounded-2xl p-6">
            <h2 class="text-lg font-black text-navy-blue uppercase mb-4 flex items-center gap-2">
                <i data-lucide="search" class="w-5 h-5 text-neon-green"></i>Buscar Factura
            </h2>
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" id="search-factura" placeholder="Buscar por # factura o cliente..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                </div>
                <select id="limit-facturas" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold focus:outline-none focus:ring-2 focus:ring-neon-green">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <button onclick="cargarFacturasDevolucion(1)"
                    class="bg-navy-blue text-white px-6 py-2.5 rounded-xl font-bold text-sm uppercase hover:bg-neon-green hover:text-black transition-all">
                    <i data-lucide="search" class="w-4 h-4 inline mr-1"></i>Buscar
                </button>
            </div>
        </div>

        <!-- Tabla de facturas -->
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-wider">
                            <th class="px-4 py-3 text-left"># Factura</th>
                            <th class="px-4 py-3 text-left">Cliente</th>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-facturas" class="divide-y divide-slate-100">
                        <tr><td colspan="5" class="text-center py-10 text-slate-400 font-medium">Ingrese una búsqueda para ver facturas con repuestos</td></tr>
                    </tbody>
                </table>
            </div>
            <!-- Paginación facturas -->
            <div id="pag-facturas" class="px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-3 border-t border-slate-200">
                <p id="info-facturas" class="text-xs text-slate-500 font-medium"></p>
                <div id="ctrl-facturas" class="flex gap-1"></div>
            </div>
        </div>

        <!-- Panel de items de la factura seleccionada -->
        <div id="panel-items" class="glass-card rounded-2xl p-6 hidden">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-black text-navy-blue uppercase flex items-center gap-2">
                    <i data-lucide="package" class="w-5 h-5 text-neon-green"></i>
                    Repuestos de la Factura <span id="num-factura" class="text-neon-green"></span>
                </h2>
                <button onclick="cerrarPanelItems()" class="text-slate-400 hover:text-rose-500 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-wider">
                            <th class="px-3 py-3 text-left">Repuesto</th>
                            <th class="px-3 py-3 text-center">Cant.</th>
                            <th class="px-3 py-3 text-right">P. Unitario</th>
                            <th class="px-3 py-3 text-center">Garantía</th>
                            <th class="px-3 py-3 text-center">Estado</th>
                            <th class="px-3 py-3 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-items" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
            <p class="text-[10px] text-slate-400 font-bold mt-3 uppercase">
                <i data-lucide="info" class="w-3 h-3 inline"></i>
                La garantía se calcula según la configuración global o la asignada al repuesto. Solo se permiten devoluciones dentro del plazo.
            </p>
        </div>
    </section>

    <!-- ==================== SECCIÓN: HISTORIAL ==================== -->
    <section id="sec-historial" class="space-y-6 hidden">

        <!-- Filtros historial -->
        <div class="glass-card rounded-2xl p-6">
            <h2 class="text-lg font-black text-navy-blue uppercase mb-4 flex items-center gap-2">
                <i data-lucide="filter" class="w-5 h-5 text-neon-green"></i>Filtros
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" id="hist-search" placeholder="Buscar por cliente o repuesto..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                </div>
                <input type="date" id="hist-desde"
                    class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                <input type="date" id="hist-hasta"
                    class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-neon-green">
                <button onclick="cargarHistorialDevoluciones(1)"
                    class="bg-navy-blue text-white px-6 py-2.5 rounded-xl font-bold text-sm uppercase hover:bg-neon-green hover:text-black transition-all">
                    <i data-lucide="search" class="w-4 h-4 inline mr-1"></i>Filtrar
                </button>
            </div>
        </div>

        <!-- Tabla historial -->
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-wider">
                            <th class="px-3 py-3 text-left">Fecha</th>
                            <th class="px-3 py-3 text-left">Factura</th>
                            <th class="px-3 py-3 text-left">Cliente</th>
                            <th class="px-3 py-3 text-left">Repuesto</th>
                            <th class="px-3 py-3 text-center">Cant.</th>
                            <th class="px-3 py-3 text-right">Monto</th>
                            <th class="px-3 py-3 text-center">Destino</th>
                            <th class="px-3 py-3 text-center">Garantía</th>
                            <th class="px-3 py-3 text-left">Motivo</th>
                            <th class="px-3 py-3 text-left">Usuario</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-historial" class="divide-y divide-slate-100">
                        <tr><td colspan="10" class="text-center py-10 text-slate-400 font-medium">Cargue el historial con el botón Filtrar</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="pag-historial" class="px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-3 border-t border-slate-200">
                <p id="info-historial" class="text-xs text-slate-500 font-medium"></p>
                <div id="ctrl-historial" class="flex gap-1"></div>
            </div>
        </div>
    </section>
</div>

<script>
/* ==================== ESTADO ==================== */
let paginaFacturas = 1, totalPaginasFacturas = 1, limitFacturas = 10;
let paginaHistorial = 1, totalPaginasHistorial = 1;
let facturaSeleccionada = null;

/* ==================== TABS ==================== */
function cambiarTabDevolucion(tab) {
    const secNueva = document.getElementById('sec-nueva');
    const secHist = document.getElementById('sec-historial');
    const tabNueva = document.getElementById('tab-nueva');
    const tabHist = document.getElementById('tab-historial');

    if (tab === 'nueva') {
        secNueva.classList.remove('hidden');
        secHist.classList.add('hidden');
        tabNueva.classList.add('border-neon-green', 'text-navy-blue');
        tabNueva.classList.remove('border-transparent', 'text-slate-400');
        tabHist.classList.add('border-transparent', 'text-slate-400');
        tabHist.classList.remove('border-neon-green', 'text-navy-blue');
    } else {
        secNueva.classList.add('hidden');
        secHist.classList.remove('hidden');
        tabHist.classList.add('border-neon-green', 'text-navy-blue');
        tabHist.classList.remove('border-transparent', 'text-slate-400');
        tabNueva.classList.add('border-transparent', 'text-slate-400');
        tabNueva.classList.remove('border-neon-green', 'text-navy-blue');
        if (document.getElementById('tbody-historial').querySelector('td')) {
            cargarHistorialDevoluciones(1);
        }
    }
    lucide.createIcons();
}

/* ==================== FACTURAS ==================== */
async function cargarFacturasDevolucion(pagina = 1) {
    paginaFacturas = pagina;
    limitFacturas = document.getElementById('limit-facturas').value;
    const search = document.getElementById('search-factura').value.trim();

    const tbody = document.getElementById('tbody-facturas');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-slate-400"><i data-lucide="loader-2" class="w-6 h-6 inline animate-spin"></i> Cargando...</td></tr>';
    lucide.createIcons();

    try {
        const res = await fetch(`${URLROOT}/devoluciones/listarFacturas`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ page: pagina, limit: limitFacturas, search })
        });
        const data = await res.json();

        if (!data.success) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-rose-500 font-medium">${data.mensaje || 'Error'}</td></tr>`;
            return;
        }

        const { data: facturas, total } = data;
        totalPaginasFacturas = Math.ceil(total / limitFacturas) || 1;

        if (facturas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-slate-400 font-medium">No se encontraron facturas con repuestos</td></tr>';
        } else {
            tbody.innerHTML = facturas.map(f => `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 font-black text-navy-blue">#${f.id}</td>
                    <td class="px-4 py-3 text-sm font-medium text-slate-700">${escapeHtml(f.cliente || 'Consumidor Final')}</td>
                    <td class="px-4 py-3 text-sm text-slate-500">${new Date(f.fecha).toLocaleDateString()}</td>
                    <td class="px-4 py-3 text-sm font-bold text-right text-slate-700">${AppUtils.formatCurrency(f.total)}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex gap-1 justify-center">
                            <button onclick="seleccionarFactura(${f.id}, '${f.fecha}')"
                                class="bg-navy-blue text-white px-3 py-1.5 rounded-lg text-xs font-bold uppercase hover:bg-neon-green hover:text-black transition-all"
                                title="Ver items para procesar devolución">
                                <i data-lucide="eye" class="w-3 h-3 inline"></i> Ver Items
                            </button>
                            <button onclick="verDetalleFacturaDevolucion(${f.id})"
                                class="bg-slate-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold uppercase hover:bg-neon-green hover:text-black transition-all"
                                title="Ver toda la información de la factura">
                                <i data-lucide="file-text" class="w-3 h-3 inline"></i> Ver Detalle
                            </button>
                        </div>
                    </td>
                </tr>`).join('');
        }

        document.getElementById('info-facturas').textContent = `Mostrando ${facturas.length} de ${total} facturas - Página ${paginaFacturas} de ${totalPaginasFacturas}`;
        renderPaginacion('ctrl-facturas', paginaFacturas, totalPaginasFacturas, 'cargarFacturasDevolucion');
        lucide.createIcons();
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-rose-500 font-medium">Error de conexión</td></tr>';
    }
}

/* ==================== DETALLE FACTURA COMPLETO ==================== */
async function verDetalleFacturaDevolucion(facturaId) {
    try {
        AppUtils.showLoading('Cargando detalle de factura...');
        const res = await fetch(`${URLROOT}/garantia/getDetalleFactura/${facturaId}`);
        const data = await res.json();
        AppUtils.hideLoading();

        if (!data.success || !data.factura) {
            AppUtils.showToast(data.mensaje || 'No se pudo cargar el detalle de la factura', 'error');
            return;
        }

        const f = data.factura;
        const items = data.items || [];

        // Construir tabla de items
        const itemsHtml = items.map(it => {
            const esRepuesto = it.producto_id != null && it.producto_id !== '';
            const tipo = esRepuesto ? 'REPUESTO' : 'SERVICIO';
            const nombre = esRepuesto ? (it.producto_nombre || 'N/A') : (it.descripcion || 'Servicio');
            const monto = (parseFloat(it.cantidad) || 0) * (parseFloat(it.precio_unitario) || 0);
            return `
            <tr class="border-t border-slate-100">
                <td class="px-2 py-2 text-xs text-slate-700">${escapeHtml(nombre)}</td>
                <td class="px-2 py-2 text-center text-xs font-bold ${esRepuesto ? 'text-indigo-600' : 'text-emerald-600'}">${tipo}</td>
                <td class="px-2 py-2 text-center text-xs text-slate-600">${it.cantidad}</td>
                <td class="px-2 py-2 text-center text-xs text-slate-600">${escapeHtml(it.mecanico_nombre || '-')}</td>
                <td class="px-2 py-2 text-right text-xs font-bold text-slate-700">${AppUtils.formatCurrency(monto)}</td>
            </tr>`;
        }).join('');

        // Sección de Orden de Servicio (si existe)
        const seccionOS = f.os_id ? `
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                <p class="text-[11px] font-black text-slate-600 uppercase mb-2">Orden de Servicio #${f.os_id} - ${escapeHtml(f.os_estado || '-')}</p>
                <div class="grid grid-cols-2 gap-2">
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Mecánico Asignado</p><p class="font-bold">${escapeHtml(f.os_mecanico_nombre || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Kilometraje</p><p class="font-bold">${escapeHtml(f.os_kilometraje || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Combustible</p><p class="font-bold">${escapeHtml(f.os_combustible || '-')}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha Ingreso</p><p class="font-bold">${f.os_fecha_ingreso ? new Date(f.os_fecha_ingreso).toLocaleString() : '-'}</p></div>
                    <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha Entrega</p><p class="font-bold">${f.os_fecha_entrega ? new Date(f.os_fecha_entrega).toLocaleString() : '-'}</p></div>
                </div>
                ${f.os_diag_entrada ? `<div class="mt-2"><p class="text-[10px] font-black text-slate-400 uppercase">Diagnóstico Entrada</p><p class="text-xs text-slate-700">${escapeHtml(f.os_diag_entrada)}</p></div>` : ''}
                ${f.os_diag_salida ? `<div class="mt-2"><p class="text-[10px] font-black text-slate-400 uppercase">Diagnóstico Salida</p><p class="text-xs text-slate-700">${escapeHtml(f.os_diag_salida)}</p></div>` : ''}
                ${f.os_observaciones ? `<div class="mt-2"><p class="text-[10px] font-black text-slate-400 uppercase">Observaciones OS</p><p class="text-xs text-slate-700">${escapeHtml(f.os_observaciones)}</p></div>` : ''}
            </div>` : '';

        Swal.fire({
            title: `DETALLE FACTURA #${f.factura_id}`,
            html: `
                <div class="text-left space-y-3 text-sm max-h-[60vh] overflow-y-auto pr-1">
                    <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                        <p class="text-[11px] font-black text-blue-700 uppercase mb-2">Información de la Factura - ${escapeHtml(f.status || '')}</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Cliente</p><p class="font-bold text-navy-blue">${escapeHtml(f.cliente_nombre || 'Consumidor Final')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Cédula</p><p class="font-bold">${escapeHtml(f.cliente_cedula || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Teléfono</p><p class="font-bold">${escapeHtml(f.cliente_telefono || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Placa / Modelo</p><p class="font-bold">${escapeHtml(f.placa || '-')} / ${escapeHtml(f.modelo_vehiculo || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Fecha Factura</p><p class="font-bold">${f.fecha ? new Date(f.fecha).toLocaleString() : '-'}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Cobrado Por</p><p class="font-bold">${escapeHtml(f.usuario_cobro_nombre || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Atendido Por</p><p class="font-bold">${escapeHtml(f.os_mecanico_nombre || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Origen</p><p class="font-bold">${escapeHtml(f.origen || '-')}</p></div>
                            <div><p class="text-[10px] font-black text-slate-400 uppercase">Total Factura</p><p class="font-bold text-blue-700">${AppUtils.formatCurrency(f.total)}</p></div>
                        </div>
                        ${f.observaciones ? `<div class="mt-2"><p class="text-[10px] font-black text-slate-400 uppercase">Observaciones</p><p class="text-xs text-slate-700">${escapeHtml(f.observaciones)}</p></div>` : ''}
                    </div>
                    ${seccionOS}
                    ${items.length ? `
                    <div class="overflow-x-auto">
                        <p class="text-[11px] font-black text-slate-500 uppercase mb-1">Items de la Factura</p>
                        <table class="w-full">
                            <thead class="bg-slate-100">
                                <tr class="text-[10px] font-black text-slate-500 uppercase">
                                    <th class="px-2 py-2 text-left">Descripción</th>
                                    <th class="px-2 py-2 text-center">Tipo</th>
                                    <th class="px-2 py-2 text-center">Cant.</th>
                                    <th class="px-2 py-2 text-center">Mecánico</th>
                                    <th class="px-2 py-2 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>${itemsHtml}</tbody>
                        </table>
                    </div>` : '<p class="text-center text-slate-400 text-xs py-4">Esta factura no tiene items registrados.</p>'}
                </div>`,
            showCancelButton: true,
            confirmButtonText: '<i data-lucide="printer" class="w-4 h-4 inline mr-1"></i> IMPRIMIR',
            cancelButtonText: 'CERRAR',
            confirmButtonColor: '#1d4ed8',
            cancelButtonColor: '#0f766e',
            width: '700px'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open(`${URLROOT}/facturacion/imprimir/${f.factura_id}`, '_blank');
            }
        });
    } catch (e) {
        AppUtils.hideLoading();
        console.error(e);
        AppUtils.showToast('Error de conexión', 'error');
    }
}

/* ==================== ITEMS DE FACTURA ==================== */
async function seleccionarFactura(facturaId, fecha) {
    facturaSeleccionada = facturaId;
    const panel = document.getElementById('panel-items');
    const tbody = document.getElementById('tbody-items');
    document.getElementById('num-factura').textContent = `#${facturaId}`;
    panel.classList.remove('hidden');
    panel.scrollIntoView({ behavior: 'smooth' });

    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-slate-400"><i data-lucide="loader-2" class="w-6 h-6 inline animate-spin"></i> Cargando items...</td></tr>';
    lucide.createIcons();

    try {
        const res = await fetch(`${URLROOT}/devoluciones/getItems/${facturaId}`);
        const data = await res.json();

        if (!data.success || !data.items || data.items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-slate-400 font-medium">Esta factura no tiene repuestos aptos para devolución</td></tr>';
            return;
        }

        tbody.innerHTML = data.items.map(it => {
            const vigente = it.garantia_vigente;
            const badgeGarantia = vigente
                ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">${it.dias_restantes} días restantes</span>`
                : `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700">Vencida</span>`;
            const estadoGarantia = `${it.dias_transcurridos}/${it.dias_garantia_aplicado} días`;
            const btn = vigente
                ? `<button onclick="abrirModalDevolucion(${facturaId}, ${it.detalle_id}, '${escapeAttr(it.descripcion)}', ${it.cantidad}, ${it.precio_unitario})"
                    class="bg-rose-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold uppercase hover:bg-rose-600 transition-all">
                    <i data-lucide="undo-2" class="w-3 h-3 inline"></i> Devolver
                  </button>`
                : `<span class="text-[10px] text-slate-400 font-bold uppercase">No disponible</span>`;
            return `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-3 py-3 text-sm font-medium text-slate-700">${escapeHtml(it.descripcion)}</td>
                    <td class="px-3 py-3 text-center text-sm font-bold text-slate-700">${it.cantidad}</td>
                    <td class="px-3 py-3 text-right text-sm text-slate-700">${AppUtils.formatCurrency(it.precio_unitario)}</td>
                    <td class="px-3 py-3 text-center text-xs font-bold text-slate-600">${estadoGarantia}</td>
                    <td class="px-3 py-3 text-center">${badgeGarantia}</td>
                    <td class="px-3 py-3 text-center">${btn}</td>
                </tr>`;
        }).join('');
        lucide.createIcons();
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-rose-500 font-medium">Error de conexión</td></tr>';
    }
}

function cerrarPanelItems() {
    document.getElementById('panel-items').classList.add('hidden');
    facturaSeleccionada = null;
}

/* ==================== MODAL DEVOLUCIÓN ==================== */
async function abrirModalDevolucion(facturaId, detalleId, descripcion, cantidad, precio) {
    const { value: formValues } = await Swal.fire({
        title: 'Procesar Devolución',
        html: `
            <div class="text-left space-y-4 pt-2">
                <div class="bg-slate-50 rounded-xl p-3">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Factura #${facturaId}</p>
                    <p class="text-sm font-bold text-navy-blue mt-1">${escapeHtml(descripcion)}</p>
                    <p class="text-xs text-slate-500 mt-1">Cantidad: <b>${cantidad}</b> · P. Unit: <b>${AppUtils.formatCurrency(precio)}</b></p>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Acción con el Repuesto</label>
                    <select id="destino-select" class="swal2-input w-full m-0 text-sm">
                        <option value="STOCK">Reingresar al Inventario (Buen estado)</option>
                        <option value="DANADO">Dañado / No vuelve al stock</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Motivo de la Devolución</label>
                    <textarea id="motivo-input" rows="3" placeholder="Describa el motivo de la devolución..."
                        class="swal2-input w-full m-0 text-sm resize-none uppercase"
                        oninput="this.value = this.value.toUpperCase();"></textarea>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'PROCESAR DEVOLUCIÓN',
        confirmButtonColor: '#e11d48',
        preConfirm: () => {
            const destino = document.getElementById('destino-select').value;
            const motivo = document.getElementById('motivo-input').value.trim().toUpperCase();
            if (!motivo) {
                Swal.showValidationMessage('Debe ingresar el motivo de la devolución');
                return false;
            }
            return { detalle_id: detalleId, destino, motivo };
        }
    });

    if (formValues) {
        AppUtils.showLoading();
        try {
            const res = await fetch(`${URLROOT}/devoluciones/procesar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ factura_id: facturaId, ...formValues })
            });
            const result = await res.json();
            AppUtils.hideLoading();

            if (result.success) {
                AppUtils.showToast(result.mensaje || 'Devolución procesada');
                seleccionarFactura(facturaId, '');
                cargarFacturasDevolucion(paginaFacturas);
                if (typeof updateDashboard === 'function') updateDashboard();
            } else {
                AppUtils.showAlert('Error', result.mensaje || 'No se pudo procesar', 'error');
            }
        } catch (e) {
            AppUtils.hideLoading();
            console.error(e);
            AppUtils.showAlert('Error', 'Error de conexión', 'error');
        }
    }
}

/* ==================== HISTORIAL ==================== */
async function cargarHistorialDevoluciones(pagina = 1) {
    paginaHistorial = pagina;
    const search = document.getElementById('hist-search').value.trim();
    const desde = document.getElementById('hist-desde').value;
    const hasta = document.getElementById('hist-hasta').value;
    const limit = 10;

    const tbody = document.getElementById('tbody-historial');
    tbody.innerHTML = '<tr><td colspan="10" class="text-center py-10 text-slate-400"><i data-lucide="loader-2" class="w-6 h-6 inline animate-spin"></i> Cargando...</td></tr>';
    lucide.createIcons();

    try {
        const res = await fetch(`${URLROOT}/devoluciones/historial`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ page: pagina, limit, search, desde, hasta })
        });
        const data = await res.json();

        if (!data.success) {
            tbody.innerHTML = `<tr><td colspan="10" class="text-center py-10 text-rose-500 font-medium">${data.mensaje || 'Error'}</td></tr>`;
            return;
        }

        const { data: lista, total } = data;
        totalPaginasHistorial = Math.ceil(total / limit) || 1;

        if (lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center py-10 text-slate-400 font-medium">No hay devoluciones registradas</td></tr>';
        } else {
            tbody.innerHTML = lista.map(d => {
                const destinoBadge = d.destino === 'STOCK'
                    ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700">Stock</span>`
                    : `<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700">Dañado</span>`;
                const garantiaTxt = `${d.dias_transcurridos}/${d.dias_garantia_aplicado}d`;
                return `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-3 py-3 text-xs text-slate-500">${new Date(d.fecha).toLocaleDateString()}</td>
                    <td class="px-3 py-3 text-xs font-black text-navy-blue">#${d.factura_id}</td>
                    <td class="px-3 py-3 text-xs font-medium text-slate-700">${escapeHtml(d.cliente || 'Consumidor Final')}</td>
                    <td class="px-3 py-3 text-xs text-slate-700">${escapeHtml(d.producto_nombre || d.descripcion || '-')}</td>
                    <td class="px-3 py-3 text-center text-xs font-bold text-slate-700">${d.cantidad}</td>
                    <td class="px-3 py-3 text-right text-xs font-bold text-slate-700">${AppUtils.formatCurrency(d.monto_devuelto)}</td>
                    <td class="px-3 py-3 text-center">${destinoBadge}</td>
                    <td class="px-3 py-3 text-center text-xs text-slate-500">${garantiaTxt}</td>
                    <td class="px-3 py-3 text-xs text-slate-600">${escapeHtml(d.motivo || '-')}</td>
                    <td class="px-3 py-3 text-xs text-slate-500">${escapeHtml(d.usuario_nombre || '-')}</td>
                </tr>`;
            }).join('');
        }

        document.getElementById('info-historial').textContent = `Mostrando ${lista.length} de ${total} devoluciones - Página ${paginaHistorial} de ${totalPaginasHistorial}`;
        renderPaginacion('ctrl-historial', paginaHistorial, totalPaginasHistorial, 'cargarHistorialDevoluciones');
        lucide.createIcons();
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-10 text-rose-500 font-medium">Error de conexión</td></tr>';
    }
}

/* ==================== HELPERS ==================== */
function renderPaginacion(containerId, pagina, totalPaginas, fnName) {
    const ctrl = document.getElementById(containerId);
    if (totalPaginas <= 1) { ctrl.innerHTML = ''; return; }
    let html = '';
    html += `<button onclick="${fnName}(${Math.max(1, pagina - 1)})" class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 hover:bg-slate-100 ${pagina === 1 ? 'opacity-40 cursor-not-allowed' : ''}" ${pagina === 1 ? 'disabled' : ''}>«</button>`;
    for (let i = 1; i <= totalPaginas; i++) {
        if (i === 1 || i === totalPaginas || (i >= pagina - 1 && i <= pagina + 1)) {
            html += `<button onclick="${fnName}(${i})" class="px-3 py-1.5 rounded-lg text-xs font-bold border ${i === pagina ? 'bg-navy-blue text-white border-navy-blue' : 'border-slate-200 hover:bg-slate-100'}">${i}</button>`;
        } else if (i === pagina - 2 || i === pagina + 2) {
            html += `<span class="px-2 text-slate-400">...</span>`;
        }
    }
    html += `<button onclick="${fnName}(${Math.min(totalPaginas, pagina + 1)})" class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 hover:bg-slate-100 ${pagina === totalPaginas ? 'opacity-40 cursor-not-allowed' : ''}" ${pagina === totalPaginas ? 'disabled' : ''}>»</button>`;
    ctrl.innerHTML = html;
}

function escapeHtml(str) {
    if (str == null) return '';
    return String(str).replace(/[&<>"']/g, c => ({ '&': '&', '<': '<', '>': '>', '"': '"', "'": '&#39;' }[c]));
}
function escapeAttr(str) {
    return escapeHtml(str).replace(/'/g, '&#39;');
}

/* ==================== INIT ==================== */
document.addEventListener('DOMContentLoaded', () => {
    cargarFacturasDevolucion(1);
    lucide.createIcons();
});
</script>
